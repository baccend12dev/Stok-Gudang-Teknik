<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Item;
use Auth;
use Excel;

class BufferAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Security Layer: Blokir User Dept
        $this->middleware(function ($request, $next) {
            $user = \Auth::user();
            if ($user && $user->role === 'USER') {
                return redirect()->route('requests.index')->with('error', 'Akses Ditolak!');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $level = $request->get('buffer_level', 'all');
        $perPage = (int) $request->get('per_page', 100); // Default 100
        if ($perPage < 1) $perPage = 100;

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        // --- 1. HITUNG STATISTIK GLOBAL (KPI CARDS) ---
        $allItemsQuery = Item::select('id', 'current_stock', 'buffer_min')
            ->where('current_status', 'ACTIVE');

        if (!is_null($allowedItemIds)) {
            $allItemsQuery->whereIn('id', $allowedItemIds);
        }

        $allItems = $allItemsQuery->get();

        $stats = [
            'total' => 0,
            'empty' => 0,      // Zone 1: Kosong (Stok <= 0)
            'critical' => 0,   // Zone 2: Kritis (0 < Stok < Buffer)
            'warning' => 0,    // Zone 3: Menipis (Buffer <= Stok <= Buffer+5)
            'safe' => 0        // Zone 4: Aman
        ];

        foreach ($allItems as $item) {
            $stats['total']++;
            // FIX: Cast ke float agar desimal terbaca
            $stok = (float) $item->current_stock;
            $buff = (float) $item->buffer_min;

            if ($stok <= 0) {
                $stats['empty']++;
            } elseif ($stok < $buff) {
                $stats['critical']++;
            } elseif ($stok <= ($buff + 5)) {
                $stats['warning']++;
            } else {
                $stats['safe']++;
            }
        }

        // --- 2. QUERY DATA TABEL ---
        $q = Item::where('current_status', 'ACTIVE');

        if (!is_null($allowedItemIds)) {
            $q->whereIn('id', $allowedItemIds);
        }

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('code', 'ilike', '%' . $search . '%')
                   ->orWhere('name', 'ilike', '%' . $search . '%');
            });
        }

        // 🔥 FIXED: Filter Level (5 CARD LOGIC) - Tambahkan "current_stock > 0" untuk warning & critical
        if ($level === 'empty') {
            $q->whereRaw('current_stock <= 0');
            
        } elseif ($level === 'critical') {
            // 🔥 FIX: Pastikan stok > 0 untuk eksklusi stok kosong
            $q->whereRaw('current_stock > 0')
              ->whereRaw('current_stock < buffer_min');
              
        } elseif ($level === 'warning') {
            // 🔥 FIX: Tambahkan kondisi "current_stock > 0" untuk eksklusi stok kosong!
            $q->whereRaw('current_stock > 0')
              ->whereRaw('current_stock >= buffer_min')
              ->whereRaw('current_stock <= (buffer_min + 5)');
              
        } elseif ($level === 'safe') {
            $q->whereRaw('current_stock > (buffer_min + 5)');
        }

        $items = $q->orderBy('current_stock', 'asc')
                   ->paginate($perPage)
                   ->appends($request->query());

        // --- 3. LOGIC STATUS & SARAN ORDER ---
        $paginatedItemIds = $items->pluck('id')->toArray();
        $startDate = date('Y-m-d', strtotime('-90 days'));
        $endDate = date('Y-m-d');

        $movements = \DB::table('item_movements')
            ->whereIn('item_id', $paginatedItemIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('quantity', '<', 0)
            ->groupBy('item_id')
            ->select('item_id', \DB::raw('SUM(ABS(quantity)) as total_out'))
            ->pluck('total_out', 'item_id')
            ->all();

        $items->getCollection()->transform(function ($item) use ($movements) {
            // FIX: Cast ke float
            $stok = (float) $item->current_stock;
            $buff = (float) $item->buffer_min;
            $saran = 0;

            if ($stok <= 0) {
                $item->status_label = 'KOSONG';
                $item->status_class = 'badge-dark-soft'; 
                $saran = $buff;
            } elseif ($stok < $buff) {
                $item->status_label = 'KRITIS';
                $item->status_class = 'badge-danger';
                $saran = max(0, $buff - $stok);
            } elseif ($stok <= ($buff + 5)) {
                $item->status_label = 'MENIPIS';
                $item->status_class = 'badge-warning';
                $saran = 0;
            } else {
                $item->status_label = 'AMAN';
                $item->status_class = 'badge-success';
                $saran = 0;
            }

            $item->suggested_qty = (float) $saran; // Pastikan float

            // Rolling 90 Days Movement classification
            $totalOut = isset($movements[$item->id]) ? (float)$movements[$item->id] : 0.0;
            $item->total_out_90 = $totalOut;

            $fast = (float)$item->batas_fast_moving;
            $slow = (float)$item->batas_slow_moving;

            if ($fast > 0 && $totalOut >= $fast) {
                $class = 'FAST';
            } elseif ($slow > 0 && $totalOut <= $slow) {
                $class = 'SLOW';
            } else {
                $class = 'NORMAL';
            }
            $item->classification = $class;

            return $item;
        });

        return view('alerts.index', compact('items', 'search', 'level', 'perPage', 'stats'));
    }

    public function exportExcel(Request $request)
    {
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();
        $level = $request->get('buffer_level', 'all'); // Ambil filter dari request

        $itemsQuery = Item::where('current_status', 'ACTIVE');

        // 🔥 FIXED: Terapkan Filter yang sama persis dengan index
        if ($level === 'empty') {
            $itemsQuery->whereRaw('current_stock <= 0');
            
        } elseif ($level === 'critical') {
            $itemsQuery->whereRaw('current_stock > 0')
                        ->whereRaw('current_stock < buffer_min');
                        
        } elseif ($level === 'warning') {
            $itemsQuery->whereRaw('current_stock > 0')
                        ->whereRaw('current_stock >= buffer_min')
                        ->whereRaw('current_stock <= (buffer_min + 5)');
                        
        } elseif ($level === 'safe') {
            $itemsQuery->whereRaw('current_stock > (buffer_min + 5)');
            
        } else {
            // level = 'all' → TIDAK ADA FILTER! Export SEMUA item aktif.
            // FIX: Sebelumnya default filter hanya export Kosong+Kritis+Menipis,
            // sehingga "Stok Aman" (64 item) tidak ikut terexport.
            // Sekarang sinkron 100% dengan KPI card "Total Item Aktif".
        }

        if (!is_null($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }

        $items = $itemsQuery->orderBy('current_stock', 'asc')->get();

        $itemIds = $items->pluck('id')->toArray();
        $startDate = date('Y-m-d', strtotime('-90 days'));
        $endDate = date('Y-m-d');

        $movements = \DB::table('item_movements')
            ->whereIn('item_id', $itemIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('quantity', '<', 0)
            ->groupBy('item_id')
            ->select('item_id', \DB::raw('SUM(ABS(quantity)) as total_out'))
            ->pluck('total_out', 'item_id')
            ->all();

        return Excel::create('Rencana_Pembelian_' . date('d-m-Y'), function ($excel) use ($items, $movements) {
            $excel->sheet('Plan', function ($sheet) use ($items, $movements) {
                $sheet->row(1, ['KODE', 'NAMA BARANG', 'LOKASI', 'SATUAN', 'STOK SAAT INI', 'BUFFER MIN', 'STATUS', 'PERGERAKAN (90 HARI)', 'SARAN ORDER (QTY)']);
                $sheet->row(1, function ($row) {
                    $row->setBackground('#FFEDB8')->setFontWeight('bold')->setAlignment('center');
                });

                $row = 2;
                foreach ($items as $item) {
                    // FIX: Cast ke float
                    $stok = (float) $item->current_stock;
                    $buff = (float) $item->buffer_min;
                    $saran = 0;
                    $status = 'AMAN';

                    if ($stok <= 0) {
                        $status = 'KOSONG';
                        $saran = $buff;
                    } elseif ($stok < $buff) {
                        $status = 'KRITIS';
                        $saran = max(0, $buff - $stok);
                    } elseif ($stok <= ($buff + 5)) {
                        $status = 'MENIPIS';
                        $saran = 0;
                    }

                    // Classification
                    $totalOut = isset($movements[$item->id]) ? (float)$movements[$item->id] : 0.0;
                    $fast = (float)$item->batas_fast_moving;
                    $slow = (float)$item->batas_slow_moving;

                    if ($fast > 0 && $totalOut >= $fast) {
                        $class = 'FAST';
                    } elseif ($slow > 0 && $totalOut <= $slow) {
                        $class = 'SLOW';
                    } else {
                        $class = 'NORMAL';
                    }

                    $sheet->row($row, [
                        $item->code,
                        $item->name,
                        $item->location,
                        $item->unit,
                        (float) $stok,
                        (float) $buff,
                        $status,
                        $class,
                        (float) $saran
                    ]);
                    $row++;
                }

                $sheet->setAutoSize(true);
            });
        })->download('xlsx');
    }

    protected function getAllowedItemIdsForCurrentUser()
    {
        $user = Auth::user();
        if (!$user) return null;

        $scope = $user->inventory_scope;
        if ($scope === 'ALL' || $scope === null || $scope === '') return null;

        $categoryCodes = [];
        if (method_exists($user, 'categoryCodesForScope')) {
            $categoryCodes = $user->categoryCodesForScope();
        } else {
            if ($scope === 'GENERAL') {
                $categoryCodes = ['UNCAT', 'ATK', 'SBN', 'AKB', 'UMM'];
            } elseif ($scope === 'APPAREL') {
                $categoryCodes = ['AK', 'PK'];
            }
        }

        if (empty($categoryCodes)) return null;

        return Item::whereHas('category', function ($q) use ($categoryCodes) {
            $q->whereIn('code', $categoryCodes);
        })->pluck('id')->toArray();
    }
}
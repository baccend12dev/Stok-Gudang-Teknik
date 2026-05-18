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
        $items->getCollection()->transform(function ($item) {
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

        return Excel::create('Rencana_Pembelian_' . date('d-m-Y'), function ($excel) use ($items) {
            $excel->sheet('Plan', function ($sheet) use ($items) {
                $sheet->row(1, ['KODE', 'NAMA BARANG', 'LOKASI', 'SATUAN', 'STOK SAAT INI', 'BUFFER MIN', 'STATUS', 'SARAN ORDER (QTY)']);
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

                    $sheet->row($row, [
                        $item->code,
                        $item->name,
                        $item->location,
                        $item->unit,
                        (float) $stok,
                        (float) $buff,
                        $status,
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
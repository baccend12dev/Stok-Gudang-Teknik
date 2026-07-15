<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Excel; // Panggil Facade Excel (Maatwebsite ~2.1)

use App\Item;
use App\Department;
use App\BonHeader;
use App\BonDetail;
use App\LpbHeader;
use App\LpbDetail;
use App\StockOpnameHeader;
use App\StockOpnameDetail;
use App\Category;

class ReportController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth');

        // === SECURITY LAYER: PERFEKSIONIS ===
        // Memblokir User Dept (USER) agar tidak bisa akses Controller Admin ini.
        // Jika nekat akses URL manual, tendang balik ke halaman Request.
        $this->middleware(function ($request, $next) {
            $user = \Auth::user();
            if ($user && $user->role === 'USER') {
                return redirect()->route('requests.index')->with('error', 'Akses Ditolak! Anda tidak memiliki izin ke halaman tersebut.');
            }
            return $next($request);
        });
    }
    
    /**
     * Landing halaman laporan: isi dropdown item & departemen.
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Alias dari tombol cetak lama -> arahkan ke landing laporan.
     */
    public function items(Request $request)
    {
        return redirect()->route('reports.index');
    }

    /**
     * Kartu stok per item & periode.
     * GET: item_id, start_date (yyyy-mm-dd), end_date (yyyy-mm-dd)
     */
    public function stockCard(Request $request)
    {
        // 1. Filter Setup
        $itemId    = $request->get('item_id');
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate   = $request->get('end_date', date('Y-m-t'));

        // FIX: Order by NAME (A-Z)
        $items = Item::select('id', 'code', 'name', 'unit', 'current_stock')
                      ->orderBy('name', 'asc')
                      ->get();
        // dd($items);
        
        $selectedItem = null;
        $transactions = [];
        $openingBalance = 0;
        $endingBalance = 0;
        
        // Variabel Summary
        $totalIn = 0;
        $totalOut = 0;

        if ($itemId) {
            $selectedItem = Item::find($itemId);

            // 2. Hitung SALDO AWAL (Sum Desimal)
            $openingBalance = DB::table('item_movements')
                ->where('item_id', $itemId)
                ->where('date', '<', $startDate)
                ->sum('quantity');

            // 3. Ambil Transaksi
            $rawTransactions = DB::table('item_movements')
                ->where('item_id', $itemId)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // 4. Loop Running Balance
            $currentBalance = (float)$openingBalance;
            
            foreach ($rawTransactions as $t) {
                // FIX: Float
                $qty = (float)$t->quantity;
                $currentBalance += $qty;
                
                if($qty > 0) $totalIn += $qty;
                else $totalOut += abs($qty);
                
                $docType = '';
                $routeDetail = '#';
                $deptName = '-'; 
                
                if ($t->type == 'LPB') {
                    $docType = 'LPB';
                    $routeDetail = route('lpbs.edit', $t->reference_id); 
                    $deptName = 'Teknik';
                } elseif ($t->type == 'BON') {
                    $docType = 'BON';
                    $routeDetail = route('bons.show', $t->reference_id);
                    
                    $bonInfo = DB::table('bon_headers')
                        ->join('departments', 'bon_headers.department_id', '=', 'departments.id')
                        ->where('bon_headers.id', $t->reference_id)
                        ->select('departments.name as dept_name')
                        ->first();
                    if ($bonInfo) $deptName = $bonInfo->dept_name;

                } elseif ($t->type == 'OPNAME') {
                    $docType = 'SO';
                    // Jika ini adalah saldo awal dari input barang baru atau edit manual, jangan beri link detail
                    if (strpos($t->reference_number, 'SALDO-AWAL-') !== false || strpos($t->reference_number, 'ADJ-EDIT-') !== false) {
                        $routeDetail = '#';
                        $deptName = 'System';
                    } else {
                        $routeDetail = route('stock-opnames.show', $t->reference_id);
                        $deptName = 'Adjustment System';
                    }
                }

                $desc = isset($t->description) ? $t->description : '-';
                if (strpos($t->reference_number, 'SALDO-AWAL-') !== false) {
                    $desc = 'Input Barang Baru (Stok Awal)';
                } elseif (strpos($t->reference_number, 'ADJ-EDIT-') !== false) {
                    $desc = 'Penyesuaian Stok (Edit Manual)';
                }

                $transactions[] = (object) [
                    'date' => $t->date,
                    'type' => $docType,
                    'number' => $t->reference_number,
                    'link' => $routeDetail,
                    'description' => $desc,
                    'department' => $deptName,
                    'in' => $qty > 0 ? $qty : 0,
                    'out' => $qty < 0 ? abs($qty) : 0,
                    'balance' => $currentBalance
                ];
            }
            
            $endingBalance = $currentBalance;
        }

        return view('reports.stock_card', compact(
            'items', 'selectedItem', 'transactions', 
            'startDate', 'endDate', 'openingBalance', 'endingBalance',
            'totalIn', 'totalOut'
        ));
    }

    // === EXPORT STOCK CARD (FIXED: EXCEL .XLSX) ===
    public function exportStockCard(Request $request)
    {
        $itemId = $request->get('item_id');
        $start  = $request->get('start_date');
        $end    = $request->get('end_date');

        if (!$itemId) return redirect()->back();
        
        $item = Item::findOrFail($itemId);

        // Recalculate for Export consistency
        $openingBalance = DB::table('item_movements')
                ->where('item_id', $itemId)
                ->where('date', '<', $start)
                ->sum('quantity');

        $movements = DB::table('item_movements')
                ->where('item_id', $itemId)
                ->whereBetween('date', [$start, $end])
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

        // Gunakan nama file yang rapi
        $fileName = 'Kartu_Stok_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $item->code);

        return Excel::create($fileName, function($excel) use ($item, $movements, $start, $end, $openingBalance) {
            $excel->sheet('Kartu Stok', function($sheet) use ($item, $movements, $start, $end, $openingBalance) {
                
                // 1. Judul & Info Barang
                $sheet->mergeCells('A1:G1');
                $sheet->row(1, ['KARTU STOK BARANG']);
                $sheet->row(1, function($r){ $r->setFontSize(14)->setFontWeight('bold')->setAlignment('center'); });

                $sheet->row(3, ['Periode', date('d M Y', strtotime($start)) . ' s/d ' . date('d M Y', strtotime($end))]);
                $sheet->row(4, ['Kode Barang', $item->code]);
                $sheet->row(5, ['Nama Barang', $item->name]);
                $sheet->row(6, ['Satuan', $item->unit]);
                $sheet->row(7, []); // Spasi

                // 2. Header Tabel
                $sheet->row(8, ['Tanggal', 'Tipe', 'No Dokumen', 'Departemen', 'Keterangan', 'Masuk', 'Keluar', 'Saldo']);
                // Style Header: Bold, Center, Background Abu
                $sheet->row(8, function($row){ 
                    $row->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setBackground('#E0E0E0')
                        ->setBorder('thin', 'thin', 'thin', 'thin');
                });

                // 3. Baris Saldo Awal
                $rowIdx = 9;
                $currentBalance = (float)$openingBalance;
                
                $sheet->row($rowIdx, [
                    date('d/m/Y', strtotime($start)), 
                    '', 
                    'SALDO AWAL', 
                    '-', 
                    '-', 
                    0, 
                    0, 
                    $currentBalance
                ]);
                // Style Saldo Awal: Bold & Background Kuning Tipis
                $sheet->row($rowIdx, function($row){ 
                    $row->setFontWeight('bold')->setBackground('#FFFFCC')->setBorder('thin', 'thin', 'thin', 'thin'); 
                });
                $rowIdx++;

                // 4. Isi Data Transaksi
                foreach ($movements as $mv) {
                    // FIX: Float
                    $qty = (float)$mv->quantity;
                    $currentBalance += $qty;
                    $in  = $qty > 0 ? $qty : 0;
                    $out = $qty < 0 ? abs($qty) : 0;
                    $desc = isset($mv->description) ? $mv->description : '-';
                    
                    // Cari Nama Dept (Sama kayak logic di view)
                    $deptName = '-';
                    if ($mv->type == 'BON') {
                        $bonInfo = DB::table('bon_headers')
                            ->join('departments', 'bon_headers.department_id', '=', 'departments.id')
                            ->where('bon_headers.id', $mv->reference_id)
                            ->select('departments.name as dept_name')->first();
                        if($bonInfo) $deptName = $bonInfo->dept_name;
                    } elseif ($mv->type == 'LPB') {
                        $deptName = 'Gudang';
                    } elseif ($mv->type == 'OPNAME') {
                        $deptName = 'Adjustment';
                    }

                    $sheet->row($rowIdx, [
                        date('d/m/Y', strtotime($mv->date)),
                        $mv->type, 
                        $mv->reference_number,
                        $deptName,
                        $desc,
                        $in,
                        $out,
                        $currentBalance
                    ]);
                    
                    // Tambah border tipis per baris
                    $sheet->row($rowIdx, function($r){ $r->setBorder('thin', 'thin', 'thin', 'thin'); });
                    
                    $rowIdx++;
                }

                // Auto Size Column Biar Rapi
                $sheet->setAutoSize(true);
            });
        })->download('xlsx'); // <-- UBAH JADI XLSX
    }

    /**
     * Pemakaian per departemen (BON) selama periode.
     * GET: department_id, start_date, end_date
     */
    public function departmentUsage(Request $request)
    {
        $deptId = $request->get('department_id');
        $start  = $request->get('start_date', date('Y-m-01'));
        $end    = $request->get('end_date', date('Y-m-t'));

        $departments = Department::orderBy('name')->get();
        $department  = null;
        
        // Data Container
        $groupedData = collect([]);
        $summary = [
            'total_items' => 0,
            'total_qty'   => 0,
            'top_item'    => '-'
        ];

        if ($deptId) {
            $department = Department::find($deptId);
            
            // Ambil Detail Transaksi BON (Hanya yang ISSUED)
            $bonDetails = \App\BonDetail::whereHas('bonHeader', function($q) use ($deptId, $start, $end) {
                $q->where('department_id', $deptId)
                  ->whereBetween('date', [$start, $end])
                  ->where('status', 'ISSUED');
            })->with(['item', 'bonHeader'])->get();

            // GROUPING LOGIC: Kelompokkan berdasarkan Item ID
            $groupedData = $bonDetails->groupBy('item_id')->map(function ($details) {
                $firstItem = $details->first()->item;
                $sumQty = $details->sum('issued_quantity');
                
                return [
                    'item_id'   => $firstItem->id,
                    'code'      => $firstItem->code,
                    'name'      => $firstItem->name,
                    'unit'      => $firstItem->unit,
                    // FIX: Float
                    'total_qty' => (float)$sumQty,
                    'history'   => $details->sortBy('bonHeader.date') // Urutkan transaksi per item berdasarkan tanggal
                ];
            })->sortByDesc('total_qty'); // Urutkan item dari yang paling banyak dipakai

            // SUMMARY CALCULATION
            $summary['total_items'] = $groupedData->count();
            $summary['total_qty']   = $groupedData->sum('total_qty');
            
            // Cari Top Item
            $top = $groupedData->first();
            if ($top) {
                $summary['top_item'] = $top['name'] . ' (' . (float)$top['total_qty'] . ' ' . $top['unit'] . ')';
            }
        }

        return view('reports.department_usage', compact(
            'departments', 'department', 'groupedData', 'start', 'end', 'summary'
        ));
    }

    /**
     * Export Department Usage (Logic Lama Dipertahankan)
     */
    public function exportDepartmentUsage(Request $request)
    {
        $deptId = $request->get('department_id');
        $start  = $request->get('start_date');
        $end    = $request->get('end_date');

        if (!$deptId) return redirect()->back();
        $dept = Department::findOrFail($deptId);

        $data = \App\BonDetail::whereHas('bonHeader', function($q) use ($deptId, $start, $end) {
            $q->where('department_id', $deptId)
              ->whereBetween('date', [$start, $end])
              ->where('status', 'ISSUED');
        })->with(['item', 'bonHeader'])->get();

        return Excel::create('Pemakaian_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $dept->name), function($excel) use ($data, $dept, $start, $end) {
            $excel->sheet('Data', function($sheet) use ($data, $dept, $start, $end) {
                // Header Report
                $sheet->mergeCells('A1:G1');
                $sheet->row(1, ['LAPORAN PEMAKAIAN DEPARTEMEN: ' . strtoupper($dept->name)]);
                $sheet->row(2, ['Periode: ' . $start . ' s/d ' . $end]);
                $sheet->row(3, []);

                $sheet->row(4, ['Tanggal', 'No BON', 'Kode Item', 'Nama Item', 'Qty', 'Satuan', 'Keterangan']);
                $sheet->row(4, function($r){ $r->setFontWeight('bold')->setBackground('#CCCCCC'); });

                $rowIdx = 5;
                foreach ($data as $d) {
                    $sheet->row($rowIdx, [
                        $d->bonHeader->date->format('d/m/Y'),
                        $d->bonHeader->bon_number,
                        $d->item->code,
                        $d->item->name,
                        // FIX: Float
                        (float)$d->issued_quantity,
                        $d->item->unit,
                        $d->bonHeader->notes
                    ]);
                    $rowIdx++;
                }
            });
        })->download('xlsx');
    }

    /**
     * SALDO AKHIR (UPGRADED: INVENTORY HEALTH MONITOR)
     */
    public function saldo(Request $request)
    {
        // Ambil semua item
        // Note: Price diasumsikan 0 karena kolom belum ada di DB user
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $itemsQuery = Item::orderBy('name', 'asc');
        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }
        $items = $itemsQuery->get();

        
        $totalItems = 0;
        $critical   = 0;
        $warning    = 0;
        $safe       = 0;
        $totalAsset = 0;

        foreach ($items as $item) {
            $totalItems++;
            // FIX: Float
            $stok = (float)$item->current_stock;
            $buff = (float)$item->buffer_min;
            
            // Logic Status
            if ($stok <= $buff) {
                $item->status_label = 'CRITICAL';
                $critical++;
            } elseif ($stok <= ($buff + 5)) { // Warning zone: Buffer + 5
                $item->status_label = 'WARNING';
                $warning++;
            } else {
                $item->status_label = 'SAFE';
                $safe++;
            }
            
            // Logic Saran Restock (Target = Buffer + 10)
            $target = $buff + 10;
            $item->restock_qty = ($stok < $target) ? ($target - $stok) : 0;

            // Valuasi (Price 0 sementara)
            $price = 0; // $item->price ?? 0;
            $item->valuation = $stok * $price;
            $totalAsset += $item->valuation;
        }

        return view('reports.saldo', compact('items', 'totalItems', 'critical', 'warning', 'safe', 'totalAsset'));
    }

    public function exportSaldo()
    {
        $items = Item::orderBy('code')->get();
        
        return Excel::create('Laporan_Saldo_Stok_' . date('Ymd_Hi'), function($excel) use ($items) {
            $excel->sheet('Saldo Akhir', function($sheet) use ($items) {
                
                // Header
                $sheet->mergeCells('A1:F1');
                $sheet->cell('A1', 'LAPORAN POSISI STOK (REALTIME)');
                $sheet->row(1, function($r){ $r->setFontSize(14)->setFontWeight('bold')->setAlignment('center'); });
                
                $sheet->mergeCells('A2:F2');
                $sheet->cell('A2', 'Dicetak per: ' . date('d M Y H:i'));
                $sheet->row(2, function($r){ $r->setAlignment('center'); });

                // Table Header
                $sheet->row(4, ['KODE', 'NAMA BARANG', 'SATUAN', 'LOKASI', 'BUFFER MIN', 'STOK SAAT INI', 'STATUS', 'SARAN RESTOCK']);
                $sheet->row(4, function($r){ $r->setBackground('#CCCCCC')->setFontWeight('bold')->setAlignment('center'); });

                $rowIdx = 5;
                foreach ($items as $it) {
                    // FIX: Float
                    $stok = (float)$it->current_stock;
                    $buff = (float)$it->buffer_min;
                    
                    // Logic Status untuk Excel
                    $status = 'AMAN';
                    if ($stok <= $buff) $status = 'KRITIS';
                    elseif ($stok <= ($buff + 5)) $status = 'MENIPIS';

                    // Saran Restock
                    $restock = 0;
                    if ($stok < ($buff + 10)) $restock = ($buff + 10) - $stok;

                    $sheet->row($rowIdx, [
                        $it->code, $it->name, $it->unit, $it->location, 
                        $buff, $stok, $status, $restock
                    ]);

                    // Warna Merah jika Kritis
                    if ($status == 'KRITIS') {
                        $sheet->row($rowIdx, function($r){ $r->setFontColor('#FF0000'); });
                    }

                    $sheet->row($rowIdx, function($r){ $r->setBorder('thin','thin','thin','thin'); });
                    $rowIdx++;
                }
                $sheet->setAutoSize(true);
            });
        })->download('xlsx');
    }

    /* ---------------------------------------------------------------------
     * LAPORAN LPB PER ITEM (VIEW HTML)
     * ------------------------------------------------------------------- */
    public function reportLpbItems(Request $request)
    {
        // --- FIX FILTER: SET DEFAULT DATE JIKA NULL ---
        // Jika request 'from' tidak ada, set ke tanggal 1 bulan ini
        $from = $request->has('from') ? $request->get('from') : date('Y-m-01');
        
        // Jika request 'to' tidak ada, set ke hari ini
        $to   = $request->has('to') ? $request->get('to') : date('Y-m-d');
        
        $itemId  = $request->get('item_id');
        $perPage = (int) $request->get('perPage', 100);

        if ($perPage <= 0) { $perPage = 100; }

        // Query Dasar
        $query = DB::table('lpb_details as d')
            ->join('lpb_headers as h', 'd.lpb_header_id', '=', 'h.id')
            ->leftJoin('items as i', 'd.item_id', '=', 'i.id')
            ->select(
                'h.date',
                'h.lpb_number',
                'i.code as item_code',
                'i.name as item_name',
                'i.unit as item_unit',
                'd.quantity'
            )
            ->orderBy('h.date', 'desc')
            ->orderBy('h.id', 'desc');

        // Filter: Sekarang $from dan $to pasti ada isinya
        if ($from) { $query->where('h.date', '>=', $from); }
        if ($to)   { $query->where('h.date', '<=', $to); }
        
        if ($itemId) { $query->where('d.item_id', (int)$itemId); }
        
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();
        if (is_array($allowedItemIds)) {
            $query->whereIn('d.item_id', $allowedItemIds);
        }

        // Hitung Total (sebelum paginate)
        $totalQty = $query->sum('d.quantity');

        // Paginate
        $rows = $query->paginate($perPage);

        // Data untuk dropdown filter
        $itemsQuery = Item::orderBy('name');
        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }
        $items = $itemsQuery->get();

        return view('lpbs.report_per_item', compact('rows', 'items', 'from', 'to', 'itemId', 'totalQty', 'perPage'));
    }

   /**
     * EXPORT EXCEL LAPORAN LPB (FORMAT ACCOUNTING)
     */
    public function exportLpbItems(Request $request)
    {
        // --- FIX FILTER: SET DEFAULT DATE AGAR EXPORT KONSISTEN ---
        $from = $request->has('from') ? $request->get('from') : date('Y-m-01');
        $to   = $request->has('to') ? $request->get('to') : date('Y-m-d');
        
        $itemId = $request->get('item_id');

        // Query Detail (Kolom Harga Dihapus)
        $query = DB::table('lpb_details as d')
            ->join('lpb_headers as h', 'd.lpb_header_id', '=', 'h.id')
            ->leftJoin('items as i', 'd.item_id', '=', 'i.id')
            ->select(
                'h.lpb_number',
                'h.date',
                'i.code',
                'h.notes as header_note',
                'i.name',
                'd.quantity',
                'i.unit'
                // 'd.price', // DIHAPUS
                // 'd.total'  // DIHAPUS
            )
            ->orderBy('h.date', 'desc')
            ->orderBy('h.id', 'desc');

        if ($from) { $query->where('h.date', '>=', $from); }
        if ($to)   { $query->where('h.date', '<=', $to); }
        if ($itemId) { $query->where('d.item_id', (int)$itemId); }
        
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();
        if (is_array($allowedItemIds)) {
            $query->whereIn('d.item_id', $allowedItemIds);
        }

        $data = $query->get();

        return Excel::create('Laporan_LPB_Items_' . date('d-m-Y'), function($excel) use ($data) {
            $excel->sheet('Sheet1', function($sheet) use ($data) {
                // Header Tanpa Supplier & Harga
                $sheet->row(1, [
                    'NO','NO. LPB', 'TGL. LPB', 
                    'KODE BRG', 'NAMA BARANG', 'SAT', 'JML', 
                    'KET'
                ]);
                
                // Style Header
                $sheet->row(1, function($row) { 
                    $row->setFontWeight('bold');
                    $row->setBackground('#CCCCCC');
                    $row->setAlignment('center');
                });

                // Isi
                $rowNum = 2;
                $no = 1;
                foreach ($data as $row) {
                    $sheet->row($rowNum, [
                        $no,
                        $row->lpb_number,
                        date('d/m/Y', strtotime($row->date)),
                        $row->code,
                        $row->name,
                        $row->unit,
                        // FIX: Float
                        (float)$row->quantity,
                        // Harga & Total dihapus dari sini
                        $row->header_note
                    ]);
                    $rowNum++;
                    $no++;
                }
                
                $sheet->setAutoSize(true);
            });
        })->download('xlsx');
    }

    /**
     * EXPORT EXCEL LAPORAN BON (FORMAT ACCOUNTING REVISI FIX)
     */
    public function exportBonItems(Request $request)
    {
        $from   = $request->get('from');
        $to     = $request->get('to');
        $itemId = $request->get('item_id');
        $depts  = $request->get('departments', []);

        // Normalisasi Dept ID jika array string
        if (!is_array($depts)) {
            $depts = $depts ? [$depts] : [];
        }

        // Query TANPA d.unit_price agar tidak error SQL
        $query = DB::table('bon_details as d')
            ->join('bon_headers as h', 'd.bon_header_id', '=', 'h.id')
            ->leftJoin('items as i', 'd.item_id', '=', 'i.id')
            ->leftJoin('departments as dept', 'h.department_id', '=', 'dept.id')
            ->where('h.status', '=', 'ISSUED') // Hanya BON Issued
            ->select(
                'h.date',
                'h.bon_number',
                'dept.name as dept_name',
                'h.division_name',
                'i.code',
                'i.name',
                'i.unit',
                'd.issued_quantity',
                'h.notes'
            )
            ->orderBy('h.date', 'desc')
            ->orderBy('h.id', 'desc');

        if ($from) { $query->where('h.date', '>=', $from); }
        if ($to)   { $query->where('h.date', '<=', $to); }
        if ($itemId) { $query->where('d.item_id', (int)$itemId); }
        if (!empty($depts)) { $query->whereIn('h.department_id', $depts); }

        $data = $query->get();

        return Excel::create('Laporan_BON_' . date('d-m-Y'), function($excel) use ($data) {
            $excel->sheet('BON', function($sheet) use ($data) {
                // Header REVISI: Hapus Harga & Total
                $sheet->row(1, [
                    'NO', 'TGL', 'NO. BON', 'DEPARTMENT - DIVISI', 
                    'KODE BRG', 'NAMA BARANG', 'SAT', 'JML', 
                    'KET'
                ]);

                // Style Header
                $sheet->row(1, function($row) {
                    $row->setBackground('#CCCCCC');
                    $row->setFontWeight('bold');
                    $row->setAlignment('center');
                });

                // Isi
                $rowNum = 2;
                $no = 1;
                foreach ($data as $row) {
                    // Gabung Dept & Divisi
                    $seksi = $row->dept_name;
                    if (!empty($row->division_name)) {
                        $seksi .= ' - ' . $row->division_name;
                    }
                    
                    // Variable $price & $total dihapus karena tidak dipakai lagi

                    $sheet->row($rowNum, [
                        $no,
                        date('d/m/Y', strtotime($row->date)),
                        $row->bon_number,
                        $seksi,
                        $row->code,
                        $row->name,
                        $row->unit,
                        // FIX: Float
                        (float)$row->issued_quantity,
                        // Harga & Total dihapus dari sini
                        $row->notes
                    ]);
                    $rowNum++;
                    $no++;
                }
                $sheet->setAutoSize(true);
            });
        })->download('xlsx');
    }

    public function itemMovement(Request $request)
    {
        $start = $request->get('start_date', date('Y-m-01'));
        $end   = $request->get('end_date', date('Y-m-t'));
        $selectedClass = $request->get('classification', 'ALL');

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = Item::with('category')->where('current_status', 'ACTIVE');
        if (is_array($allowedItemIds)) {
            $query->whereIn('id', $allowedItemIds);
        }
        $items = $query->orderBy('name', 'asc')->get();

        $movements = DB::table('item_movements')
            ->whereBetween('date', [$start, $end])
            ->where('quantity', '<', 0)
            ->groupBy('item_id')
            ->select('item_id', DB::raw('SUM(ABS(quantity)) as total_out'))
            ->pluck('total_out', 'item_id')
            ->all();

        $filteredItems = collect([]);
        foreach ($items as $item) {
            $totalOut = isset($movements[$item->id]) ? (float)$movements[$item->id] : 0.0;
            $item->total_out = $totalOut;

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

            if ($selectedClass === 'ALL' || $class === $selectedClass) {
                $filteredItems->push($item);
            }
        }

        return view('reports.movement', compact('filteredItems', 'start', 'end', 'selectedClass'));
    }

    public function exportItemMovement(Request $request)
    {
        $start = $request->get('start_date', date('Y-m-01'));
        $end   = $request->get('end_date', date('Y-m-t'));
        $selectedClass = $request->get('classification', 'ALL');

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = Item::with('category')->where('current_status', 'ACTIVE');
        if (is_array($allowedItemIds)) {
            $query->whereIn('id', $allowedItemIds);
        }
        $items = $query->orderBy('name', 'asc')->get();

        $movements = DB::table('item_movements')
            ->whereBetween('date', [$start, $end])
            ->where('quantity', '<', 0)
            ->groupBy('item_id')
            ->select('item_id', DB::raw('SUM(ABS(quantity)) as total_out'))
            ->pluck('total_out', 'item_id')
            ->all();

        $filteredItems = collect([]);
        foreach ($items as $item) {
            $totalOut = isset($movements[$item->id]) ? (float)$movements[$item->id] : 0.0;
            $item->total_out = $totalOut;

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

            if ($selectedClass === 'ALL' || $class === $selectedClass) {
                $filteredItems->push($item);
            }
        }

        return Excel::create('Laporan_Pergerakan_Barang_' . $start . '_sd_' . $end, function($excel) use ($filteredItems, $start, $end, $selectedClass) {
            $excel->sheet('Pergerakan Barang', function($sheet) use ($filteredItems, $start, $end, $selectedClass) {
                $sheet->mergeCells('A1:H1');
                $sheet->row(1, ['LAPORAN PERGERAKAN BARANG (' . $selectedClass . ')']);
                $sheet->row(2, ['Periode: ' . $start . ' s/d ' . $end]);
                $sheet->row(3, []);

                $sheet->row(4, ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Batas Fast', 'Batas Slow', 'Total Keluar', 'Status']);
                $sheet->row(4, function($r){ $r->setFontWeight('bold')->setBackground('#CCCCCC'); });

                $rowIdx = 5;
                $no = 1;
                foreach ($filteredItems as $item) {
                    $sheet->row($rowIdx, [
                        $no,
                        $item->code,
                        $item->name,
                        $item->category ? $item->category->name : '-',
                        (float)$item->batas_fast_moving,
                        (float)$item->batas_slow_moving,
                        (float)$item->total_out,
                        $item->classification
                    ]);
                    $rowIdx++;
                    $no++;
                }
                $sheet->setAutoSize(true);
            });
        })->download('xlsx');
    }

    protected function getAllowedItemIdsForCurrentUser()
    {
        $user = auth()->user();

        if (!$user || !method_exists($user, 'categoryCodesForScope')) {
            return null;
        }

        $codes = $user->categoryCodesForScope();
        if (!is_array($codes) || count($codes) === 0) {
            return null;
        }

        $ids = Item::whereHas('category', function ($q) use ($codes) {
                $q->whereIn('code', $codes);
            })
            ->pluck('id')
            ->all();

        if (count($ids) === 0) {
            return array(-1);
        }

        return $ids;
    }

    public function usageTrend(Request $request)
    {
        $year        = $request->get('year', date('Y'));
        $itemId      = $request->get('item_id');
        $categoryId  = $request->get('category_id');
        $deptId      = $request->get('department_id');

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        // Get filter dropdown datasets
        $itemsQuery = Item::orderBy('name');
        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }
        $items = $itemsQuery->get();

        $categories = Category::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        // Build main query to fetch usage trend
        $query = DB::table('item_movements')
            ->join('items', 'item_movements.item_id', '=', 'items.id')
            ->leftJoin('bon_headers', function($join) {
                $join->on('item_movements.reference_id', '=', 'bon_headers.id')
                     ->where('item_movements.type', '=', 'BON');
            })
            ->whereYear('item_movements.date', $year)
            ->where('item_movements.quantity', '<', 0); // outflow

        if (is_array($allowedItemIds)) {
            $query->whereIn('item_movements.item_id', $allowedItemIds);
        }
        if ($itemId) {
            $query->where('item_movements.item_id', $itemId);
        }
        if ($categoryId) {
            $query->where('items.category_id', $categoryId);
        }
        if ($deptId) {
            $query->where('bon_headers.department_id', $deptId);
        }

        $movements = $query->select(
            'item_movements.item_id',
            'items.code',
            'items.name',
            'items.unit',
            DB::raw('EXTRACT(month FROM item_movements.date) as month'),
            DB::raw('SUM(ABS(item_movements.quantity)) as total_qty')
        )
        ->groupBy('item_movements.item_id', 'items.code', 'items.name', 'items.unit', DB::raw('EXTRACT(month FROM item_movements.date)'))
        ->get();

        // Group into matrix: item_id => [months => [1=>0, 2=>0, ...], total => X]
        $trendData = [];
        foreach ($movements as $m) {
            if (!isset($trendData[$m->item_id])) {
                $trendData[$m->item_id] = [
                    'item_id' => $m->item_id,
                    'code'    => $m->code,
                    'name'    => $m->name,
                    'unit'    => $m->unit,
                    'months'  => array_fill(1, 12, 0.0),
                    'total'   => 0.0
                ];
            }
            $trendData[$m->item_id]['months'][(int)$m->month] = (float)$m->total_qty;
            $trendData[$m->item_id]['total'] += (float)$m->total_qty;
        }

        // Sort items by total usage descending (PHP 5.6 ke atas / semua versi)
        uasort($trendData, function ($a, $b) {
            if ($a['total'] == $b['total']) {
                return 0;
            }
            // Karena ingin descending (terbesar ke terkecil), kita cek jika $b lebih besar
            return ($b['total'] > $a['total']) ? 1 : -1;
        });

        // Overall Monthly Total
        $monthlyTotals = array_fill(1, 12, 0.0);
        foreach ($trendData as $tData) {
            foreach ($tData['months'] as $mIdx => $qty) {
                $monthlyTotals[$mIdx] += $qty;
            }
        }

        // Prepare chart datasets
        $chartDatasets = [];
        if ($itemId && count($trendData) > 0) {
            $first = reset($trendData);
            $chartDatasets[] = [
                'label' => '[' . $first['code'] . '] ' . $first['name'],
                'data' => array_values($monthlyTotals),
                'borderColor' => '#6366f1',
                'backgroundColor' => 'rgba(99, 102, 241, 0.05)',
                'borderWidth' => 3,
                'fill' => true,
                'tension' => 0.3
            ];
        } else {
            $chartDatasets[] = [
                'label' => 'Total Pemakaian (Semua)',
                'data' => array_values($monthlyTotals),
                'borderColor' => '#6366f1',
                'backgroundColor' => 'rgba(99, 102, 241, 0.05)',
                'borderWidth' => 3,
                'fill' => true,
                'tension' => 0.3
            ];

            $colors = ['#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#3b82f6', '#14b8a6', '#f43f5e', '#84cc16'];
            $colorIdx = 0;
            $topItems = array_slice($trendData, 0, 10, true);
            foreach ($topItems as $itemData) {
                $chartDatasets[] = [
                    'label' => '[' . $itemData['code'] . '] ' . $itemData['name'],
                    'data' => array_values($itemData['months']),
                    'borderColor' => $colors[$colorIdx % count($colors)],
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 1.5,
                    'tension' => 0.3
                ];
                $colorIdx++;
            }
        }

        $totalAnnualQty = array_sum($monthlyTotals);
        $topItemName = '-';
        if (count($trendData) > 0) {
            $first = reset($trendData);
            $topItemName = '[' . $first['code'] . '] ' . $first['name'] . ' (' . number_format($first['total'], 2) . ' ' . $first['unit'] . ')';
        }

        $highestMonthVal = 0;
        $highestMonthIdx = 1;
        foreach ($monthlyTotals as $mIdx => $qty) {
            if ($qty > $highestMonthVal) {
                $highestMonthVal = $qty;
                $highestMonthIdx = $mIdx;
            }
        }
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $peakMonth = $monthNames[$highestMonthIdx] . ' (' . number_format($highestMonthVal, 2) . ')';

        return view('reports.usage_trend', compact(
            'items', 'categories', 'departments', 'year', 'itemId', 'categoryId', 'deptId',
            'trendData', 'chartDatasets', 'totalAnnualQty', 'topItemName', 'peakMonth'
        ));
    }

    public function exportUsageTrend(Request $request)
    {
        $year        = $request->get('year', date('Y'));
        $itemId      = $request->get('item_id');
        $categoryId  = $request->get('category_id');
        $deptId      = $request->get('department_id');

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = DB::table('item_movements')
            ->join('items', 'item_movements.item_id', '=', 'items.id')
            ->leftJoin('bon_headers', function($join) {
                $join->on('item_movements.reference_id', '=', 'bon_headers.id')
                     ->where('item_movements.type', '=', 'BON');
            })
            ->whereYear('item_movements.date', $year)
            ->where('item_movements.quantity', '<', 0);

        if (is_array($allowedItemIds)) {
            $query->whereIn('item_movements.item_id', $allowedItemIds);
        }
        if ($itemId) {
            $query->where('item_movements.item_id', $itemId);
        }
        if ($categoryId) {
            $query->where('items.category_id', $categoryId);
        }
        if ($deptId) {
            $query->where('bon_headers.department_id', $deptId);
        }

        $movements = $query->select(
            'item_movements.item_id',
            'items.code',
            'items.name',
            'items.unit',
            DB::raw('EXTRACT(month FROM item_movements.date) as month'),
            DB::raw('SUM(ABS(item_movements.quantity)) as total_qty')
        )
        ->groupBy('item_movements.item_id', 'items.code', 'items.name', 'items.unit', DB::raw('EXTRACT(month FROM item_movements.date)'))
        ->get();

        $trendData = [];
        foreach ($movements as $m) {
            if (!isset($trendData[$m->item_id])) {
                $trendData[$m->item_id] = [
                    'code'    => $m->code,
                    'name'    => $m->name,
                    'unit'    => $m->unit,
                    'months'  => array_fill(1, 12, 0.0),
                    'total'   => 0.0
                ];
            }
            $trendData[$m->item_id]['months'][(int)$m->month] = (float)$m->total_qty;
            $trendData[$m->item_id]['total'] += (float)$m->total_qty;
        }

        // Sort items by total usage descending (Kompatibel dengan PHP 5.6)
        uasort($trendData, function ($a, $b) {
            if ($a['total'] == $b['total']) {
                return 0;
            }
            // Mengembalikan 1 jika $b lebih besar dari $a agar urutannya menurun (descending)
            return ($b['total'] > $a['total']) ? 1 : -1;
        });

        $fileName = 'Trend_Pemakaian_' . $year;

        return \Excel::create($fileName, function($excel) use ($trendData, $year) {
            $excel->sheet('Trend Pemakaian', function($sheet) use ($trendData, $year) {
                $sheet->mergeCells('A1:Q1');
                $sheet->row(1, ['LAPORAN TREN PEMAKAIAN BARANG - TAHUN ' . $year]);
                $sheet->row(1, function($r){ $r->setFontSize(14)->setFontWeight('bold')->setAlignment('center'); });

                $sheet->row(3, ['Kode Barang', 'Nama Barang', 'Satuan', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des', 'Total', 'Rata-rata/Bulan']);
                $sheet->row(3, function($r){ $r->setFontWeight('bold')->setBackground('#CCCCCC'); });

                $rowIdx = 4;
                foreach ($trendData as $row) {
                    $sheet->row($rowIdx, [
                        $row['code'],
                        $row['name'],
                        $row['unit'],
                        (float)$row['months'][1],
                        (float)$row['months'][2],
                        (float)$row['months'][3],
                        (float)$row['months'][4],
                        (float)$row['months'][5],
                        (float)$row['months'][6],
                        (float)$row['months'][7],
                        (float)$row['months'][8],
                        (float)$row['months'][9],
                        (float)$row['months'][10],
                        (float)$row['months'][11],
                        (float)$row['months'][12],
                        (float)$row['total'],
                        (float)($row['total'] / 12.0)
                    ]);
                    $rowIdx++;
                }
                $sheet->setAutoSize(true);
            });
        })->download('xlsx');
    }
}
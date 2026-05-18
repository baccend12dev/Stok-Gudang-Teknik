<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Department;
use App\Item;
use Excel;

class InventoryMonthlyReportController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth');

        // === SECURITY LAYER: PERFEKSIONIS ===
        $this->middleware(function ($request, $next) {
            $user = \Auth::user();
            if ($user && $user->role === 'USER') {
                return redirect()->route('requests.index')->with('error', 'Akses Ditolak! Anda tidak memiliki izin ke halaman tersebut.');
            }
            return $next($request);
        });
    }
    
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate   = $request->get('end_date', date('Y-m-t'));
        $departments = Department::orderBy('name', 'asc')->get();

        foreach($departments as $dept) {
            if (strtoupper($dept->name) === 'BUSDEV & REGISTRASI') {
                $dept->name = 'BUSDEV & REG';
            }
        }

        // 1. Ambil Transaksi Periode Ini
        $lpbPeriod = DB::table('lpb_details as d')
            ->join('lpb_headers as h', 'h.id', '=', 'd.lpb_header_id')
            ->whereBetween('h.date', [$startDate, $endDate])
            ->select('d.item_id', DB::raw('SUM(d.quantity) as qty'))
            ->groupBy('d.item_id')
            ->pluck('qty', 'item_id');

        $bonPeriodRaw = DB::table('bon_details as d')
            ->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')
            ->where('h.status', 'ISSUED')
            ->whereBetween('h.date', [$startDate, $endDate])
            ->select('d.item_id', 'h.department_id', DB::raw('SUM(d.issued_quantity) as qty'))
            ->groupBy('d.item_id', 'h.department_id')
            ->get();

        $bonData = [];
        $totalBonPerItem = [];
        foreach ($bonPeriodRaw as $row) {
            $bonData[$row->item_id][$row->department_id] = (float)$row->qty;
            
            if (!isset($totalBonPerItem[$row->item_id])) $totalBonPerItem[$row->item_id] = 0;
            $totalBonPerItem[$row->item_id] += (float)$row->qty;
        }

        // 2. Reverse Calculation Logic
        $today = date('Y-m-d');
        $nextDay = date('Y-m-d', strtotime($endDate . ' +1 day'));
        $futureMovements = [];

        if ($nextDay <= $today) {
             $lpbFuture = DB::table('lpb_details as d')->join('lpb_headers as h', 'h.id', '=', 'd.lpb_header_id')->where('h.date', '>=', $nextDay)->select('d.item_id', DB::raw('SUM(d.quantity) as qty'))->groupBy('d.item_id')->pluck('qty', 'item_id');
             $bonFuture = DB::table('bon_details as d')->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')->where('h.status', 'ISSUED')->where('h.date', '>=', $nextDay)->select('d.item_id', DB::raw('SUM(d.issued_quantity) as qty'))->groupBy('d.item_id')->pluck('qty', 'item_id');
             
             $allItemIds = Item::pluck('id'); 
             foreach($allItemIds as $id) {
                 $in = isset($lpbFuture[$id]) ? (float)$lpbFuture[$id] : 0;
                 $out = isset($bonFuture[$id]) ? (float)$bonFuture[$id] : 0;
                 // FIX: Rounding agar presisi
                 $futureMovements[$id] = round($out - $in, 2);
             }
        }

        // 3. Build Data
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $itemsQuery = DB::table('items as i')
            ->leftJoin('categories as c', 'c.id', '=', 'i.category_id')
            ->select(
                'i.id',
                'i.code',
                'i.name',
                'i.unit',
                'i.buffer_min',
                'i.current_stock',
                DB::raw("0 as price"),
                DB::raw("COALESCE(c.name, 'LAIN-LAIN') as category_name")
            );

        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('i.id', $allowedItemIds);
        }

        $items = $itemsQuery
            ->orderBy('category_name', 'asc')
            ->orderBy('i.code', 'asc')
            ->get();


        $reportData = [];
        
        foreach ($items as $item) {
            $adj = isset($futureMovements[$item->id]) ? (float)$futureMovements[$item->id] : 0;
            
            // FIX: Rounding (Pembulatan)
            $saldoAkhir  = round((float)$item->current_stock + $adj, 2);
            $masuk       = isset($lpbPeriod[$item->id]) ? (float)$lpbPeriod[$item->id] : 0;
            $keluarTotal = isset($totalBonPerItem[$item->id]) ? (float)$totalBonPerItem[$item->id] : 0;
            
            // FIX: Rounding Saldo Awal (INI PERBAIKAN UTAMANYA)
            // Rumus Balik: Akhir - Masuk + Keluar = Awal
            $saldoAwal   = round($saldoAkhir - $masuk + $keluarTotal, 2);
            
            $deptUsage = [];
            foreach ($departments as $dept) {
                $deptUsage[$dept->id] = isset($bonData[$item->id][$dept->id]) ? $bonData[$item->id][$dept->id] : 0;
            }

            $reportData[] = (object) [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'unit' => $item->unit,
                'buffer_min' => (float)$item->buffer_min,
                'category_name' => $item->category_name,
                'saldo_awal' => $saldoAwal,
                'masuk' => $masuk,
                'keluar_total' => $keluarTotal,
                'saldo_akhir' => $saldoAkhir,
                'dept_usage' => $deptUsage, 
                'harga' => 0 
            ];
        }

        $groupedData = collect($reportData)->groupBy('category_name');

        return view('reports.inventory_monthly', compact(
            'groupedData', 'departments', 'startDate', 'endDate'
        ));
    }

    public function getDrillDown(Request $request)
    {
        $type = $request->get('type'); 
        $itemId = $request->get('item_id');
        $deptId = $request->get('dept_id'); 
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        if ($type === 'in') {
            $data = DB::table('lpb_details as d')
                ->join('lpb_headers as h', 'h.id', '=', 'd.lpb_header_id')
                ->where('d.item_id', $itemId)->whereBetween('h.date', [$start, $end])
                ->select('h.id as doc_id', 'h.date', 'h.lpb_number as doc_number', 'd.quantity', 'h.notes', DB::raw("'LPB' as doc_type"), DB::raw("'Supplier' as source"))
                ->orderBy('h.date', 'desc')->get();
        } else {
            $q = DB::table('bon_details as d')
                ->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')
                ->leftJoin('departments as dept', 'dept.id', '=', 'h.department_id')
                ->where('d.item_id', $itemId)->where('h.status', 'ISSUED')->whereBetween('h.date', [$start, $end]);
            if ($deptId) { $q->where('h.department_id', $deptId); }
            $data = $q->select('h.id as doc_id', 'h.date', 'h.bon_number as doc_number', 'd.issued_quantity as quantity', 'dept.name as source', 'h.notes', DB::raw("'BON' as doc_type"))
                ->orderBy('h.date', 'desc')->get();
        }
        return response()->json($data);
    }

    /**
     * EXPORT EXCEL (FINAL & PERFECT)
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate   = $request->get('end_date', date('Y-m-t'));
        $departments = Department::orderBy('name', 'asc')->get();
        
        $lpbPeriod = DB::table('lpb_details as d')->join('lpb_headers as h', 'h.id', '=', 'd.lpb_header_id')->whereBetween('h.date', [$startDate, $endDate])->select('d.item_id', DB::raw('SUM(d.quantity) as qty'))->groupBy('d.item_id')->pluck('qty', 'item_id');
        $bonPeriodRaw = DB::table('bon_details as d')->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')->where('h.status', 'ISSUED')->whereBetween('h.date', [$startDate, $endDate])->select('d.item_id', DB::raw('SUM(d.issued_quantity) as qty'))->groupBy('d.item_id')->get();
        
        $totalBonPerItem = []; 
        foreach ($bonPeriodRaw as $row) { 
            if (!isset($totalBonPerItem[$row->item_id])) $totalBonPerItem[$row->item_id] = 0;
            $totalBonPerItem[$row->item_id] += (float)$row->qty; 
        }
        
        $today = date('Y-m-d'); $nextDay = date('Y-m-d', strtotime($endDate . ' +1 day')); $futureMovements = [];
        if ($nextDay <= $today) {
             $lpbF = DB::table('lpb_details as d')->join('lpb_headers as h', 'h.id', '=', 'd.lpb_header_id')->where('h.date', '>=', $nextDay)->select('d.item_id', DB::raw('SUM(d.quantity) as qty'))->groupBy('d.item_id')->pluck('qty', 'item_id');
             $bonF = DB::table('bon_details as d')->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')->where('h.status', 'ISSUED')->where('h.date', '>=', $nextDay)->select('d.item_id', DB::raw('SUM(d.issued_quantity) as qty'))->groupBy('d.item_id')->pluck('qty', 'item_id');
             $allIds = Item::pluck('id'); 
             foreach($allIds as $id) { 
                 $in=isset($lpbF[$id])?(float)$lpbF[$id]:0; 
                 $out=isset($bonF[$id])?(float)$bonF[$id]:0; 
                 // FIX: Rounding
                 $futureMovements[$id] = round($out - $in, 2); 
             }
        }
        
        $items = DB::table('items as i')->leftJoin('categories as c', 'c.id', '=', 'i.category_id')->select('i.*', DB::raw("0 as price"), DB::raw("COALESCE(c.name, 'LAIN-LAIN') as category_name"))->orderBy('category_name', 'asc')->orderBy('i.code', 'asc')->get();
        $reportData = [];
        
        foreach ($items as $item) {
            $adj = isset($futureMovements[$item->id]) ? (float)$futureMovements[$item->id] : 0;
            
            // FIX: Rounding di Export juga
            $saldoAkhir  = round((float)$item->current_stock + $adj, 2);
            $masuk       = isset($lpbPeriod[$item->id]) ? (float)$lpbPeriod[$item->id] : 0;
            $keluar      = isset($totalBonPerItem[$item->id]) ? (float)$totalBonPerItem[$item->id] : 0;
            $saldoAwal   = round($saldoAkhir - $masuk + $keluar, 2);
            
            $reportData[] = (object) ['code'=>$item->code, 'name'=>$item->name, 'unit'=>$item->unit, 'category_name'=>$item->category_name, 'saldo_awal'=>$saldoAwal, 'masuk'=>$masuk, 'keluar'=>$keluar, 'saldo_akhir'=>$saldoAkhir, 'harga'=> 0];
        }
        $groupedData = collect($reportData)->groupBy('category_name');

        return Excel::create('Laporan_Persediaan_All_' . date('M_Y', strtotime($startDate)), function($excel) use ($groupedData, $startDate, $endDate) {
            $excel->sheet('ALL', function($sheet) use ($groupedData, $startDate, $endDate) {
                
                // HEADER
                $sheet->mergeCells('A1:N1'); $sheet->cell('A1', 'LAPORAN PERSEDIAAN UMUM (SHEET ALL)');
                $sheet->mergeCells('A2:N2'); $sheet->cell('A2', 'Periode: ' . date('d M Y', strtotime($startDate)) . ' s/d ' . date('d M Y', strtotime($endDate)));
                $sheet->row(1, function($r){ $r->setFontSize(14)->setFontWeight('bold')->setAlignment('left'); });
                $sheet->row(2, function($r){ $r->setFontSize(12)->setFontWeight('bold')->setAlignment('left'); });

                // HEADER KOLOM
                $sheet->mergeCells('A4:A5'); $sheet->cell('A4', 'KODE');
                $sheet->mergeCells('B4:B5'); $sheet->cell('B4', 'NAMA BARANG');
                $sheet->mergeCells('C4:C5'); $sheet->cell('C4', 'SAT');
                $sheet->mergeCells('D4:E4'); $sheet->cell('D4', 'SALDO AWAL');
                $sheet->mergeCells('F4:G4'); $sheet->cell('F4', 'MASUK (BELI)');
                $sheet->mergeCells('H4:I4'); $sheet->cell('H4', 'TOTAL KELUAR');
                $sheet->mergeCells('J4:K4'); $sheet->cell('J4', 'SALDO AKHIR');
                $sheet->mergeCells('L4:L5'); $sheet->cell('L4', 'HARGA SATUAN');
                $sheet->mergeCells('M4:M5'); $sheet->cell('M4', 'JUMLAH (RP)');
                $sheet->mergeCells('N4:N5'); $sheet->cell('N4', 'KETERANGAN');

                $sheet->cell('D5', 'QTY'); $sheet->cell('E5', 'RP');
                $sheet->cell('F5', 'QTY'); $sheet->cell('G5', 'RP');
                $sheet->cell('H5', 'QTY'); $sheet->cell('I5', 'RP');
                $sheet->cell('J5', 'QTY'); $sheet->cell('K5', 'RP');

                $sheet->row(4, function($r){ $r->setBackground('#D9D9D9')->setFontWeight('bold')->setAlignment('center')->setValignment('center'); });
                $sheet->row(5, function($r){ $r->setBackground('#F2F2F2')->setFontWeight('bold')->setAlignment('center'); });

                // DATA LOOP
                $rowIdx = 6;
                
                // Grand Total Accumulator
                $grandTotal = [
                    'awal_qty' => 0, 'awal_rp' => 0,
                    'in_qty' => 0, 'in_rp' => 0,
                    'out_qty' => 0, 'out_rp' => 0,
                    'end_qty' => 0, 'end_rp' => 0,
                    'val_total' => 0
                ];

                foreach($groupedData as $catName => $items) {
                    // Subtotal Accumulator
                    $subTotal = [
                        'awal_qty' => 0, 'awal_rp' => 0,
                        'in_qty' => 0, 'in_rp' => 0,
                        'out_qty' => 0, 'out_rp' => 0,
                        'end_qty' => 0, 'end_rp' => 0,
                        'val_total' => 0
                    ];

                    // Header Kategori
                    $sheet->mergeCells('A'.$rowIdx.':N'.$rowIdx);
                    $sheet->cell('A'.$rowIdx, $catName);
                    $sheet->row($rowIdx, function($r){ $r->setBackground('#FFFFCC')->setFontWeight('bold'); });
                    $rowIdx++;

                    foreach($items as $d) {
                        $valAwal   = $d->saldo_awal * $d->harga;
                        $valMasuk  = $d->masuk * $d->harga;
                        $valKeluar = $d->keluar * $d->harga;
                        $valAkhir  = $d->saldo_akhir * $d->harga;
                        $valTotal  = $valAkhir; 

                        // Accumulate Subtotal (FIX: Float & Rounding)
                        $subTotal['awal_qty'] += (float)$d->saldo_awal; $subTotal['awal_rp'] += $valAwal;
                        $subTotal['in_qty']   += (float)$d->masuk;      $subTotal['in_rp']   += $valMasuk;
                        $subTotal['out_qty']  += (float)$d->keluar;     $subTotal['out_rp']  += $valKeluar;
                        $subTotal['end_qty']  += (float)$d->saldo_akhir;$subTotal['end_rp']  += $valAkhir;
                        $subTotal['val_total']+= $valTotal;

                        // Function kosongkan 0
                        $f = function($val) { return $val == 0 ? '' : $val; };
                        
                        $sheet->row($rowIdx, [
                            $d->code, $d->name, $d->unit,
                            $f((float)$d->saldo_awal), $f($valAwal),
                            $f((float)$d->masuk),      $f($valMasuk),
                            $f((float)$d->keluar),     $f($valKeluar),
                            $f((float)$d->saldo_akhir),$f($valAkhir),
                            $f($d->harga),      $f($valTotal),
                            ''
                        ]);
                        $sheet->row($rowIdx, function($r){ $r->setBorder('thin','thin','thin','thin'); });
                        $rowIdx++;
                    }

                    // BARIS SUBTOTAL KATEGORI
                    // FIX: Rounding agar subtotal juga bersih
                    $f = function($val) { return $val == 0 ? '' : round($val, 2); };

                    $sheet->row($rowIdx, [
                        '', 'TOTAL '.$catName, '',
                        $f((float)$subTotal['awal_qty']), $f($subTotal['awal_rp']),
                        $f((float)$subTotal['in_qty']),   $f($subTotal['in_rp']),
                        $f((float)$subTotal['out_qty']),  $f($subTotal['out_rp']),
                        $f((float)$subTotal['end_qty']),  $f($subTotal['end_rp']),
                        '', $f($subTotal['val_total']), ''
                    ]);
                    $sheet->row($rowIdx, function($r){ $r->setFontWeight('bold')->setBackground('#EFEFEF')->setBorder('thin','thin','thin','thin'); });
                    $rowIdx++;
                    
                    // Add to Grand Total
                    foreach($subTotal as $k => $v) $grandTotal[$k] += $v;
                }

                // BARIS GRAND TOTAL
                // FIX: Rounding
                $f = function($val) { return $val == 0 ? '' : round($val, 2); };
                
                $sheet->row($rowIdx, [
                    '', 'GRAND TOTAL', '',
                    $f((float)$grandTotal['awal_qty']), $f($grandTotal['awal_rp']),
                    $f((float)$grandTotal['in_qty']),   $f($grandTotal['in_rp']),
                    $f((float)$grandTotal['out_qty']),  $f($grandTotal['out_rp']),
                    $f((float)$grandTotal['end_qty']),  $f($grandTotal['end_rp']),
                    '', $f($grandTotal['val_total']), ''
                ]);
                $sheet->row($rowIdx, function($r){ 
                    $r->setFontWeight('bold')->setFontSize(12)->setBackground('#404040')->setFontColor('#FFFFFF')->setBorder('thin','thin','thin','thin'); 
                });

                $sheet->setAutoSize(true);
                $sheet->freezePane('D7');
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
}
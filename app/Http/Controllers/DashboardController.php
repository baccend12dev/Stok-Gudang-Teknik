<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Item;
use App\BonHeader;
use App\LpbHeader;
use App\StockOpnameHeader;
use App\RequestHeader; // Tambahkan ini
use Excel;

class DashboardController extends Controller
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
        $now        = Carbon::now();
        $startMonth = $now->copy()->startOfMonth();
        $endMonth   = $now->copy()->endOfMonth();
        $lastMonth  = $now->copy()->subMonth();
        $lastMonthStart = $lastMonth->copy()->startOfMonth();
        $lastMonthEnd   = $lastMonth->copy()->endOfMonth();

        $monthLabel = $now->format('F Y');
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        // FILTER
        $kpiStartStr = $request->get('start_date', $startMonth->format('Y-m-d'));
        $kpiEndStr   = $request->get('end_date', $now->format('Y-m-d'));

        $kpiStart = Carbon::parse($kpiStartStr)->startOfDay();
        $kpiEnd   = Carbon::parse($kpiEndStr)->endOfDay();

        $kpiPeriodLabel = $kpiStart->format('d M') . ' - ' . $kpiEnd->format('d M Y');

        // --- 1. KPI FLOW (ITEM) ---
        $bonThisMonthQuery = DB::table('bon_details as d')
            ->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')
            ->where('h.status', 'ISSUED')
            ->whereBetween('h.date', [$kpiStart, $kpiEnd]);
        if (is_array($allowedItemIds)) $bonThisMonthQuery->whereIn('d.item_id', $allowedItemIds);

        $tmpBonThis = $bonThisMonthQuery->select(DB::raw('COUNT(DISTINCT d.item_id) as total'))->first();
        $bonThisMonth = $tmpBonThis ? (int) $tmpBonThis->total : 0;
        // FIX: Float
        $bonThisMonthQty = (float) $bonThisMonthQuery->sum('d.issued_quantity');

        $bonLastMonthQuery = DB::table('bon_details as d')
            ->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')
            ->where('h.status', 'ISSUED')
            ->whereBetween('h.date', [$lastMonthStart, $lastMonthEnd]);
        if (is_array($allowedItemIds)) $bonLastMonthQuery->whereIn('d.item_id', $allowedItemIds);

        $tmpBonLast = $bonLastMonthQuery->select(DB::raw('COUNT(DISTINCT d.item_id) as total'))->first();
        $bonLastMonth = $tmpBonLast ? (int) $tmpBonLast->total : 0;

        $lpbThisMonthQuery = DB::table('lpb_details as d')
            ->join('lpb_headers as h', 'h.id', '=', 'd.lpb_header_id')
            ->whereBetween('h.date', [$kpiStart, $kpiEnd]);
        if (is_array($allowedItemIds)) $lpbThisMonthQuery->whereIn('d.item_id', $allowedItemIds);

        $tmpLpbThis = $lpbThisMonthQuery->select(DB::raw('COUNT(DISTINCT d.item_id) as total'))->first();
        $lpbThisMonth = $tmpLpbThis ? (int) $tmpLpbThis->total : 0;
        // FIX: Float
        $lpbThisMonthQty = (float) $lpbThisMonthQuery->sum('d.quantity');

        $bonTrend = 0;
        if ($bonLastMonth > 0) {
            $bonTrend = (($bonThisMonth - $bonLastMonth) / $bonLastMonth) * 100;
        } elseif ($bonThisMonth > 0) {
            $bonTrend = 100;
        }

        // --- 2. KPI STOCK ---
        $emptyStockQuery = Item::where('current_status', 'ACTIVE')->where('current_stock', '<=', 0);
        if (is_array($allowedItemIds)) $emptyStockQuery->whereIn('id', $allowedItemIds);
        $emptyStock = (int) $emptyStockQuery->count();

        $criticalQuery = Item::where('current_status', 'ACTIVE')->where('current_stock', '>', 0)->whereRaw('current_stock <= buffer_min');
        if (is_array($allowedItemIds)) $criticalQuery->whereIn('id', $allowedItemIds);
        $criticalCount = (int) $criticalQuery->count();

        $warningQuery = Item::where('current_status', 'ACTIVE')
            ->where('current_stock', '>', 0)
            ->whereRaw('current_stock > buffer_min')
            ->whereRaw('current_stock <= (buffer_min + 5)');
        if (is_array($allowedItemIds)) $warningQuery->whereIn('id', $allowedItemIds);
        $warningCount = (int) $warningQuery->count();

        $assetQuery = DB::table('items')->select(DB::raw('SUM(current_stock * 0) as total_asset'));
        if (is_array($allowedItemIds)) $assetQuery->whereIn('id', $allowedItemIds);
        // FIX: Float
        $assetValue = (float) $assetQuery->value('total_asset');

        // --- 3. TOP 5 DEPT BOROS ---
        $topDeptsQuery = DB::table('bon_details as d')
            ->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')
            ->join('departments as dept', 'dept.id', '=', 'h.department_id')
            ->where('h.status', 'ISSUED')
            ->whereBetween('h.date', [$kpiStart, $kpiEnd]);
        if (is_array($allowedItemIds)) $topDeptsQuery->whereIn('d.item_id', $allowedItemIds);

        $topDepts = $topDeptsQuery
            ->select('dept.name', DB::raw('COUNT(DISTINCT d.item_id) as total'))
            ->groupBy('dept.name')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        $pieLabels = $topDepts->pluck('name')->toArray();
        $pieData   = $topDepts->pluck('total')->toArray();

        // --- 4. CHART 6 BULAN ---
        $monthsLabels = [];
        $trendDataIn  = [];
        $trendDataOut = [];

        for ($i = 5; $i >= 0; $i--) {
            $mStart = $now->copy()->subMonths($i)->startOfMonth();
            $mEnd   = $now->copy()->subMonths($i)->endOfMonth();
            $monthsLabels[] = $mStart->format('M Y');

            $inQuery = DB::table('lpb_details as d')
                ->join('lpb_headers as h', 'h.id', '=', 'd.lpb_header_id')
                ->whereBetween('h.date', [$mStart, $mEnd]);
            if (is_array($allowedItemIds)) $inQuery->whereIn('d.item_id', $allowedItemIds);
            $tmpIn = $inQuery->select(DB::raw('COUNT(DISTINCT d.item_id) as total'))->first();
            $trendDataIn[] = $tmpIn ? (int) $tmpIn->total : 0;

            $outQuery = DB::table('bon_details as d')
                ->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')
                ->where('h.status', 'ISSUED')
                ->whereBetween('h.date', [$mStart, $mEnd]);
            if (is_array($allowedItemIds)) $outQuery->whereIn('d.item_id', $allowedItemIds);
            $tmpOut = $outQuery->select(DB::raw('COUNT(DISTINCT d.item_id) as total'))->first();
            $trendDataOut[] = $tmpOut ? (int) $tmpOut->total : 0;
        }

        // --- 5. PRIORITAS TUGAS (KANAN) ---
        $pendingBonQuery = BonHeader::where('status', 'PENDING');
        if (is_array($allowedItemIds)) {
            $pendingBonQuery->whereHas('details', function($q) use ($allowedItemIds) {
                $q->whereIn('item_id', $allowedItemIds);
            });
        }
        $pendingBon = (int) $pendingBonQuery->count();

        $draftSoQuery = StockOpnameHeader::where('status', 'DRAFT');
        if (is_array($allowedItemIds)) {
            $draftSoQuery->whereHas('details', function($q) use ($allowedItemIds) {
                $q->whereIn('item_id', $allowedItemIds);
            });
        }
        $draftSo = (int) $draftSoQuery->count();

        $restockNeedQuery = Item::where('current_status', 'ACTIVE')->whereRaw('current_stock <= buffer_min');
        if (is_array($allowedItemIds)) $restockNeedQuery->whereIn('id', $allowedItemIds);
        $totalRestockNeed = (int) $restockNeedQuery->count();

        // --- 7. WIDGET REQUEST PENDING (NEW ADDITION) ---
        $pendingReqQuery = RequestHeader::where('status', 'OPEN');
        // Filter Scope (PENTING)
        $user = Auth::user();
        $codes = null;
        if (method_exists($user, 'categoryCodesForScope')) {
            $codes = $user->categoryCodesForScope();
        }

        if (is_array($codes) && count($codes) > 0) {
            // Filter STRICT: Hanya request yang mengandung item dalam scope user
            $pendingReqQuery->whereHas('details.item.category', function ($q) use ($codes) {
                $q->whereIn('code', $codes);
            });
        }
        $pendingRequestCount = (int) $pendingReqQuery->count();

        // --- 6. AKTIVITAS TERBARU ---
        $recentBonQuery = BonHeader::with('department')
            ->where('status', 'ISSUED')
            ->whereBetween('date', [$kpiStart, $kpiEnd]);
        if (is_array($allowedItemIds)) {
            $recentBonQuery->whereHas('details', function($q) use ($allowedItemIds) {
                $q->whereIn('item_id', $allowedItemIds);
            });
        }
        $recentBon = $recentBonQuery->orderBy('date', 'desc')->take(5)->get();

        return view('dashboard.index', compact(
            'bonThisMonth', 'bonTrend', 'lpbThisMonth',
            'bonThisMonthQty', 'lpbThisMonthQty',
            'kpiStartStr', 'kpiEndStr', 'kpiPeriodLabel',
            'criticalCount', 'emptyStock', 'warningCount', 'totalRestockNeed', 'assetValue',
            'pieLabels', 'pieData',
            'monthsLabels', 'trendDataIn', 'trendDataOut',
            'pendingBon', 'draftSo', 'pendingRequestCount', // Add variable here
            'recentBon', 'monthLabel'
        ));
    }
    
    // ================= DETAIL OUT (BON) =================
    public function detailOut(Request $request)
    {
        // LOGIC TANGGAL DEFAULT: 1 Bulan Ini s/d Hari Ini
        if ($request->has('start_date')) {
            $startStr = $request->get('start_date');
        } else {
            $startStr = Carbon::now()->startOfMonth()->format('Y-m-d');
        }

        if ($request->has('end_date')) {
            $endStr = $request->get('end_date');
        } else {
            $endStr = Carbon::now()->format('Y-m-d');
        }

        $itemId = $request->get('item_id');

        $start = Carbon::parse($startStr)->startOfDay();
        $end   = Carbon::parse($endStr)->endOfDay();

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = DB::table('bon_details as d')
            ->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('categories as c', 'c.id', '=', 'i.category_id')
            ->where('h.status', 'ISSUED')
            ->whereBetween('h.date', [$start, $end])
            ->select(
                'i.code', 'i.name', 'i.unit', 'c.name as category_name',
                DB::raw('SUM(d.issued_quantity) as total_qty'),
                DB::raw('COUNT(d.id) as freq')
            );

        if (is_array($allowedItemIds)) {
            $query->whereIn('d.item_id', $allowedItemIds);
        }

        if ($itemId) {
            $query->where('d.item_id', $itemId);
        }

        $items = $query->groupBy('i.id', 'i.code', 'i.name', 'i.unit', 'c.name')
                       ->orderBy('total_qty', 'desc')
                       ->get();

        // Dropdown items
        $itemsQuery = Item::where('current_status', 'ACTIVE')->orderBy('name', 'asc');
        if (is_array($allowedItemIds)) $itemsQuery->whereIn('id', $allowedItemIds);
        $allItems = $itemsQuery->get();

        // Label periode
        $periodLabel = $start->format('d M Y') . ' - ' . $end->format('d M Y');

        // FIX ROUTE NAME: sesuai routes/web.php
        $exportRoute = route('dashboard.detail.out.export', [
            'start_date' => $startStr,
            'end_date'   => $endStr,
            'item_id'    => $itemId
        ]);

        $pageTitle = 'Detail Barang Keluar';
        $pageIcon  = 'fa-upload';
        $themeClass = 'sc-orange';

        return view('dashboard.detail', compact(
            'items', 'allItems', 'startStr', 'endStr', 'itemId',
            'periodLabel', 'exportRoute', 'pageTitle', 'pageIcon', 'themeClass'
        ));
    }

    // ================= DETAIL IN (LPB) =================
    public function detailIn(Request $request)
    {
        if ($request->has('start_date')) {
            $startStr = $request->get('start_date');
        } else {
            $startStr = Carbon::now()->startOfMonth()->format('Y-m-d');
        }

        if ($request->has('end_date')) {
            $endStr = $request->get('end_date');
        } else {
            $endStr = Carbon::now()->format('Y-m-d');
        }

        $itemId = $request->get('item_id');

        $start = Carbon::parse($startStr)->startOfDay();
        $end   = Carbon::parse($endStr)->endOfDay();

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = DB::table('lpb_details as d')
            ->join('lpb_headers as h', 'h.id', '=', 'd.lpb_header_id')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('categories as c', 'c.id', '=', 'i.category_id')
            ->whereBetween('h.date', [$start, $end])
            ->select(
                'i.code', 'i.name', 'i.unit', 'c.name as category_name',
                DB::raw('SUM(d.quantity) as total_qty'),
                DB::raw('COUNT(d.id) as freq')
            );

        if (is_array($allowedItemIds)) {
            $query->whereIn('d.item_id', $allowedItemIds);
        }

        if ($itemId) {
            $query->where('d.item_id', $itemId);
        }

        $items = $query->groupBy('i.id', 'i.code', 'i.name', 'i.unit', 'c.name')
                       ->orderBy('total_qty', 'desc')
                       ->get();

        // Dropdown items
        $itemsQuery = Item::where('current_status', 'ACTIVE')->orderBy('name', 'asc');
        if (is_array($allowedItemIds)) $itemsQuery->whereIn('id', $allowedItemIds);
        $allItems = $itemsQuery->get();

        // Label periode
        $periodLabel = $start->format('d M Y') . ' - ' . $end->format('d M Y');

        // FIX ROUTE NAME: sesuai routes/web.php
        $exportRoute = route('dashboard.detail.in.export', [
            'start_date' => $startStr,
            'end_date'   => $endStr,
            'item_id'    => $itemId
        ]);

        $pageTitle = 'Detail Barang Masuk';
        $pageIcon  = 'fa-download';
        $themeClass = 'sc-blue';

        return view('dashboard.detail', compact(
            'items', 'allItems', 'startStr', 'endStr', 'itemId',
            'periodLabel', 'exportRoute', 'pageTitle', 'pageIcon', 'themeClass'
        ));
    }

    // ================= EXPORT ROUTE HANDLER (SESUI ROUTES/web.php) =================
    // routes/web.php:
    // - dashboard.detail.out.export => DashboardController@exportDetailOut
    // - dashboard.detail.in.export  => DashboardController@exportDetailIn
    public function exportDetailOut(Request $request)
    {
        return $this->exportOut($request);
    }

    public function exportDetailIn(Request $request)
    {
        return $this->exportIn($request);
    }

    // ================= EXPORT OUT =================
    public function exportOut(Request $request)
    {
        if ($request->has('start_date')) {
            $startStr = $request->get('start_date');
        } else {
            $startStr = Carbon::now()->startOfMonth()->format('Y-m-d');
        }

        if ($request->has('end_date')) {
            $endStr = $request->get('end_date');
        } else {
            $endStr = Carbon::now()->format('Y-m-d');
        }

        $itemId = $request->get('item_id');

        $start = Carbon::parse($startStr)->startOfDay();
        $end   = Carbon::parse($endStr)->endOfDay();
        $label = $start->format('d-M') . '_sd_' . $end->format('d-M-Y');

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = DB::table('bon_details as d')
            ->join('bon_headers as h', 'h.id', '=', 'd.bon_header_id')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('categories as c', 'c.id', '=', 'i.category_id')
            ->where('h.status', 'ISSUED')
            ->whereBetween('h.date', [$start, $end])
            ->select(
                'i.code', 'i.name', 'i.unit', 'c.name as category_name',
                DB::raw('SUM(d.issued_quantity) as total_qty'),
                DB::raw('COUNT(d.id) as freq')
            );

        if (is_array($allowedItemIds)) {
            $query->whereIn('d.item_id', $allowedItemIds);
        }

        if ($itemId) {
            $query->where('d.item_id', $itemId);
        }

        $items = $query->groupBy('i.id', 'i.code', 'i.name', 'i.unit', 'c.name')
                    ->orderBy('total_qty', 'desc')
                    ->get();

        return Excel::create('Detail_Barang_Keluar_' . $label, function($excel) use ($items, $start, $end) {
            $excel->sheet('Data', function($sheet) use ($items, $start, $end) {

                // Lebar kolom biar rapi
                $sheet->setWidth(array(
                    'A' => 5,   // NO
                    'B' => 14,  // KODE BARANG
                    'C' => 45,  // NAMA BARANG
                    'D' => 22,  // KATEGORI
                    'E' => 14,  // FREKUENSI (X)
                    'F' => 12,  // TOTAL QTY
                    'G' => 10   // SATUAN
                ));

                // Judul & periode
                $sheet->row(1, ['LAPORAN DETAIL BARANG KELUAR']);
                $sheet->row(2, ['Periode: ' . $start->format('d M Y') . ' s/d ' . $end->format('d M Y')]);
                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');

                $sheet->cell('A1', function($cell){
                    $cell->setFontWeight('bold');
                    $cell->setAlignment('left');
                });
                $sheet->cell('A2', function($cell){
                    $cell->setAlignment('left');
                });

                // Header tabel
                $sheet->row(4, ['NO', 'KODE BARANG', 'NAMA BARANG', 'KATEGORI', 'FREKUENSI (X)', 'TOTAL QTY', 'SATUAN']);
                $sheet->row(4, function($row){
                    $row->setFontWeight('bold')
                        ->setBackground('#FFE7D6')
                        ->setAlignment('center');
                });

                // Data
                $no = 1;
                $rowNum = 5;
                foreach ($items as $item) {
                    $sheet->row($rowNum, [
                        $no,
                        $item->code,
                        $item->name,
                        $item->category_name,
                        (int) $item->freq,
                        // FIX: Float
                        (float) $item->total_qty,
                        $item->unit
                    ]);

                    $sheet->cell('A'.$rowNum, function($cell){ $cell->setAlignment('center'); });
                    $sheet->cell('E'.$rowNum, function($cell){ $cell->setAlignment('center'); });
                    $sheet->cell('F'.$rowNum, function($cell){ $cell->setAlignment('center'); });
                    $sheet->cell('G'.$rowNum, function($cell){ $cell->setAlignment('center'); });

                    $no++;
                    $rowNum++;
                }
            });
        })->download('xlsx');
    }

    // ================= EXPORT IN =================
    public function exportIn(Request $request)
    {
        if ($request->has('start_date')) {
            $startStr = $request->get('start_date');
        } else {
            $startStr = Carbon::now()->startOfMonth()->format('Y-m-d');
        }

        if ($request->has('end_date')) {
            $endStr = $request->get('end_date');
        } else {
            $endStr = Carbon::now()->format('Y-m-d');
        }

        $itemId = $request->get('item_id');

        $start = Carbon::parse($startStr)->startOfDay();
        $end   = Carbon::parse($endStr)->endOfDay();
        $label = $start->format('d-M') . '_sd_' . $end->format('d-M-Y');

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = DB::table('lpb_details as d')
            ->join('lpb_headers as h', 'h.id', '=', 'd.lpb_header_id')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('categories as c', 'c.id', '=', 'i.category_id')
            ->whereBetween('h.date', [$start, $end])
            ->select(
                'i.code', 'i.name', 'i.unit', 'c.name as category_name',
                DB::raw('SUM(d.quantity) as total_qty'),
                DB::raw('COUNT(d.id) as freq')
            );

        if (is_array($allowedItemIds)) {
            $query->whereIn('d.item_id', $allowedItemIds);
        }

        if ($itemId) {
            $query->where('d.item_id', $itemId);
        }

        $items = $query->groupBy('i.id', 'i.code', 'i.name', 'i.unit', 'c.name')
                    ->orderBy('total_qty', 'desc')
                    ->get();

        return Excel::create('Detail_Barang_Masuk_' . $label, function($excel) use ($items, $start, $end) {
            $excel->sheet('Data', function($sheet) use ($items, $start, $end) {

                // Lebar kolom
                $sheet->setWidth(array(
                    'A' => 5,   // NO
                    'B' => 14,  // KODE BARANG
                    'C' => 45,  // NAMA BARANG
                    'D' => 22,  // KATEGORI
                    'E' => 14,  // FREKUENSI (X)
                    'F' => 12,  // TOTAL QTY
                    'G' => 10   // SATUAN
                ));

                // Judul & periode
                $sheet->row(1, ['LAPORAN DETAIL BARANG MASUK']);
                $sheet->row(2, ['Periode: ' . $start->format('d M Y') . ' s/d ' . $end->format('d M Y')]);
                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');

                $sheet->cell('A1', function($cell){
                    $cell->setFontWeight('bold');
                    $cell->setAlignment('left');
                });
                $sheet->cell('A2', function($cell){
                    $cell->setAlignment('left');
                });

                // Header tabel
                $sheet->row(4, ['NO', 'KODE BARANG', 'NAMA BARANG', 'KATEGORI', 'FREKUENSI (X)', 'TOTAL QTY', 'SATUAN']);
                $sheet->row(4, function($row){
                    $row->setFontWeight('bold')
                        ->setBackground('#DBEAFE')
                        ->setAlignment('center');
                });

                // Data
                $no = 1;
                $rowNum = 5;
                foreach ($items as $item) {
                    $sheet->row($rowNum, [
                        $no,
                        $item->code,
                        $item->name,
                        $item->category_name,
                        (int) $item->freq,
                        // FIX: Float
                        (float) $item->total_qty,
                        $item->unit
                    ]);

                    $sheet->cell('A'.$rowNum, function($cell){ $cell->setAlignment('center'); });
                    $sheet->cell('E'.$rowNum, function($cell){ $cell->setAlignment('center'); });
                    $sheet->cell('F'.$rowNum, function($cell){ $cell->setAlignment('center'); });
                    $sheet->cell('G'.$rowNum, function($cell){ $cell->setAlignment('center'); });

                    $no++;
                    $rowNum++;
                }
            });
        })->download('xlsx');
    }

    // ================= SCOPE ITEM BY USER =================
    protected function getAllowedItemIdsForCurrentUser()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return null;
        }

        if (!method_exists($user, 'categoryCodesForScope')) {
            return null;
        }

        $codes = $user->categoryCodesForScope();

        if (!is_array($codes) || count($codes) === 0) {
            return null;
        }

        $ids = \App\Item::select('items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->whereIn('categories.code', $codes)
            ->pluck('items.id')
            ->toArray();

        if (count($ids) === 0) {
            return [-1];
        }

        return $ids;
    }
}
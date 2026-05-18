<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\StockOpnameHeader;
use App\StockOpnameDetail;
use App\Item;
use Carbon\Carbon;
use App\Helpers\InventoryHelper;
use Excel;

class StockOpnameController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth');

        // === SECURITY LAYER ===
        $this->middleware(function ($request, $next) {
            $user = \Auth::user();
            if ($user && $user->role === 'USER') {
                return redirect()->route('requests.index')->with('error', 'Akses Ditolak! Admin Only.');
            }
            return $next($request);
        });
    }
    
    // --- LIST ---
    public function index(Request $request)
    {
        $q       = trim($request->get('q', ''));
        $dfrom   = $request->has('date_from') ? $request->get('date_from') : date('Y-m-01');
        $dto     = $request->has('date_to') ? $request->get('date_to') : date('Y-m-d');
        $perPage = (int) $request->get('per_page', 10);
        if ($perPage < 1) $perPage = 10;

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = StockOpnameHeader::query();

        // Scope User
        if (!is_null($allowedItemIds)) {
            $query->whereHas('details', function ($d) use ($allowedItemIds) {
                $d->whereIn('item_id', $allowedItemIds);
            })->withCount(['details' => function ($d) use ($allowedItemIds) {
                $d->whereIn('item_id', $allowedItemIds);
            }]);
        } else {
            $query->withCount('details');
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('notes', 'ilike', '%' . $q . '%')
                    ->orWhere(DB::raw("CAST(id AS TEXT)"), 'like', '%' . $q . '%');
            });
        }
        
        if ($dfrom !== '') $query->where('opname_date', '>=', $dfrom);
        if ($dto !== '')   $query->where('opname_date', '<=', $dto);

        $query->orderBy('opname_date', 'desc')->orderBy('id', 'desc');
        $opnameHeaders = $query->paginate($perPage)->appends($request->query());

        return view('stock_opnames.index', compact('opnameHeaders', 'q', 'dfrom', 'dto', 'perPage'));
    }

    // --- CREATE FORM ---
    public function create()
    {
        $categoriesQuery = DB::table('categories')->orderBy('name', 'asc');

        $user = Auth::user();
        if ($user) {
            $scope = $user->inventory_scope;
            if ($scope !== 'ALL' && $scope !== null && $scope !== '') {
                $codes = method_exists($user, 'categoryCodesForScope') ? $user->categoryCodesForScope() : [];
                if (!empty($codes)) {
                    $categoriesQuery->whereIn('code', $codes);
                }
            }
        }

        $categories = $categoriesQuery->get();
        return view('stock_opnames.create', compact('categories'));
    }

    // ============================================================
    // API: SMART LOAD ITEMS (THE CORE LOGIC)
    // ============================================================
    public function getItemsByCategory(Request $request)
    {
        $catId = $request->get('category_id');
        $date  = $request->get('date'); // Tanggal SO untuk acuan periode
        $mode  = $request->get('mode', 'active'); // 'active' or 'all'

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        // Base Query
        $query = DB::table('items')
            ->select('id', 'code', 'name', 'unit', 'current_stock', 'location')
            ->where('current_status', 'ACTIVE');

        // 1. Filter Scope Admin
        if (!is_null($allowedItemIds)) {
            $query->whereIn('id', $allowedItemIds);
        }

        // 2. Filter Kategori
        if ($catId && $catId !== 'all') {
            $query->where('category_id', (int) $catId);
        }

        // 3. SMART FILTER LOGIC (Active vs All)
        if ($mode === 'active') {
            // Tentukan Periode (Bulan dari tanggal yg dipilih)
            // Jika user pilih tgl 15 Jan, kita cek transaksi 1 Jan - 31 Jan
            $dt = Carbon::parse($date);
            $startOfMonth = $dt->copy()->startOfMonth()->format('Y-m-d');
            $endOfMonth   = $dt->copy()->endOfMonth()->format('Y-m-d');

            // A. Cari Item yang ada Transaksi LPB (Masuk) bulan ini
            $lpbItems = DB::table('lpb_details')
                ->join('lpb_headers', 'lpb_details.lpb_header_id', '=', 'lpb_headers.id')
                ->whereBetween('lpb_headers.date', [$startOfMonth, $endOfMonth])
                ->pluck('lpb_details.item_id')->toArray();

            // B. Cari Item yang ada Transaksi BON (Keluar) bulan ini
            $bonItems = [];
            $bonItems = DB::table('bon_details')
                ->join('bon_headers', 'bon_details.bon_header_id', '=', 'bon_headers.id')
                ->whereBetween('bon_headers.date', [$startOfMonth, $endOfMonth])
                ->where('bon_headers.status', '!=', 'CANCELLED')
                ->pluck('bon_details.item_id')->toArray();

            // Gabungkan ID Transaksi
            $transactionItemIds = array_unique(array_merge($lpbItems, $bonItems));

            // Logic Filter FIXED:
            // Tampilkan jika: (Ada Transaksi ATAU Stok > 0)
            // DAN WAJIB: Stok saat ini harus > 0 (Sesuai request: Barang 0 hilang dari list)
            $query->where(function($q) use ($transactionItemIds) {
                $q->whereIn('id', $transactionItemIds)
                  ->orWhere('current_stock', '>', 0);
            })->where('current_stock', '>', 0); // <--- FILTER STOK 0 HILANG
        }

        $items = $query->orderBy('location', 'asc')->orderBy('code', 'asc')->get();

        return response()->json($items);
    }

    // --- STORE ---
    public function store(Request $request)
    {
        $this->validate($request, [
            'opname_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $header = StockOpnameHeader::create([
                'opname_date' => Carbon::parse($request->get('opname_date')),
                'notes'       => $request->get('notes'),
                'status'      => 'DRAFT'
            ]);

            $itemsData = [];
            if ($request->has('items_json')) {
                $itemsData = json_decode($request->get('items_json'), true);
            }

            $detailsToInsert = [];
            $now = Carbon::now();

            foreach ($itemsData as $r) {
                if (!isset($r['item_id'])) continue;

                // FIX: Float
                $system   = isset($r['system']) ? (float) $r['system'] : 0;
                // Jika fisik kosong/null, anggap 0 (atau anggap sama dgn sistem? Better 0 force input)
                $physical = isset($r['physical']) && $r['physical'] !== '' ? (float) $r['physical'] : 0;
                $diff     = $physical - $system;

                $detailsToInsert[] = [
                    'opname_header_id'  => $header->id,
                    'item_id'           => (int) $r['item_id'],
                    'system_quantity'   => $system,
                    'physical_quantity' => $physical,
                    'difference'        => $diff,
                    'notes'             => isset($r['notes']) ? $r['notes'] : null,
                    'created_at'        => $now,
                    'updated_at'        => $now
                ];
            }

            foreach (array_chunk($detailsToInsert, 500) as $chunk) {
                StockOpnameDetail::insert($chunk);
            }

            DB::commit();
            return redirect()->route('stock-opnames.show', $header->id)
                ->with('success', 'Draft SO berhasil disimpan. Silakan review.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // --- SHOW ---
    public function show($id)
    {
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();
        $opname = StockOpnameHeader::with(['details.item'])->findOrFail($id);

        if (!is_null($allowedItemIds)) {
            $filtered = $opname->details->filter(function ($d) use ($allowedItemIds) {
                return in_array($d->item_id, $allowedItemIds);
            })->values();
            $opname->setRelation('details', $filtered);
        }

        return view('stock_opnames.show', compact('opname'));
    }

    // --- EDIT ---
    public function edit($id)
    {
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();
        $opname = StockOpnameHeader::with(['details.item'])->findOrFail($id);

        if ($opname->status === 'PROCESSED') {
            return redirect()->route('stock-opnames.show', $id)
                ->with('error', 'SO yang sudah diproses tidak bisa diedit.');
        }

        if (!is_null($allowedItemIds)) {
            $filtered = $opname->details->filter(function ($d) use ($allowedItemIds) {
                return in_array($d->item_id, $allowedItemIds);
            })->values();
            $opname->setRelation('details', $filtered);
        }

        $categoriesQuery = DB::table('categories')->orderBy('name', 'asc');
        $user = Auth::user();
        if ($user) {
            $scope = $user->inventory_scope;
            if ($scope !== 'ALL' && $scope !== null && $scope !== '') {
                $codes = method_exists($user, 'categoryCodesForScope') ? $user->categoryCodesForScope() : [];
                if (!empty($codes)) $categoriesQuery->whereIn('code', $codes);
            }
        }
        $categories = $categoriesQuery->get();

        $detailsJson = $opname->details->map(function ($d) {
            return [
                'item_id'  => $d->item_id,
                'code'     => $d->item ? $d->item->code : 'N/A',
                'name'     => $d->item ? $d->item->name : '-',
                'location' => $d->item ? $d->item->location : '-',
                'unit'     => $d->item ? $d->item->unit : '-',
                // FIX: Float
                'system'   => (float) $d->system_quantity,
                'physical' => (float) $d->physical_quantity,
                'notes'    => $d->notes
            ];
        });

        return view('stock_opnames.edit', compact('opname', 'detailsJson', 'categories'));
    }

    // --- UPDATE ---
    public function update(Request $request, $id)
    {
        $opname = StockOpnameHeader::findOrFail($id);
        if ($opname->status === 'PROCESSED') return back()->with('error', 'Sudah diproses.');

        DB::beginTransaction();
        try {
            $opname->opname_date = Carbon::parse($request->get('opname_date'));
            $opname->notes       = $request->get('notes');
            $opname->save();

            StockOpnameDetail::where('opname_header_id', $id)->delete();

            $itemsData = [];
            if ($request->has('items_json')) {
                $itemsData = json_decode($request->get('items_json'), true);
            }

            $detailsToInsert = [];
            $now = Carbon::now();

            foreach ($itemsData as $r) {
                if (!isset($r['item_id'])) continue;
                
                // FIX: Float
                $system   = (float) $r['system'];
                $physical = (float) $r['physical'];
                $diff     = $physical - $system;

                $detailsToInsert[] = [
                    'opname_header_id'  => $opname->id,
                    'item_id'           => (int) $r['item_id'],
                    'system_quantity'   => $system,
                    'physical_quantity' => $physical,
                    'difference'        => $diff,
                    'notes'             => isset($r['notes']) ? $r['notes'] : null,
                    'created_at'        => $now,
                    'updated_at'        => $now
                ];
            }

            foreach (array_chunk($detailsToInsert, 500) as $chunk) {
                StockOpnameDetail::insert($chunk);
            }

            DB::commit();
            return redirect()->route('stock-opnames.show', $opname->id)->with('success', 'Update berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // --- PROCESS (Finalize & Adjust Stok) ---
    public function process($id)
    {
        $opname = StockOpnameHeader::with('details')->findOrFail($id);
        if ($opname->status === 'PROCESSED') return back()->with('error', 'Sudah diproses.');

        DB::beginTransaction();
        try {
            foreach ($opname->details as $d) {
                if ($d->difference != 0) {
                    InventoryHelper::recordMovement(
                        $d->item_id,
                        $opname->opname_date,
                        'OPNAME',
                        $opname->id,
                        'SO-' . $opname->id,
                        // FIX: Float Difference
                        (float)$d->difference
                    );
                }
            }
            $opname->status = 'PROCESSED';
            $opname->save();
            DB::commit();
            return redirect()->route('stock-opnames.show', $id)->with('success', 'Stok berhasil disesuaikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal proses: ' . $e->getMessage());
        }
    }

    // --- EXPORT EXCEL ---
    public function exportExcel($id)
    {
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();
        $opname = StockOpnameHeader::with(['details.item'])->findOrFail($id);
        $data   = $opname->details;

        if (!is_null($allowedItemIds)) {
            $data = $data->filter(function ($d) use ($allowedItemIds) {
                return in_array($d->item_id, $allowedItemIds);
            })->values();
        }

        $filename = 'Stock_Opname_' . $opname->opname_date->format('Ymd') . '_' . $opname->id;

        return Excel::create($filename, function ($excel) use ($data, $opname) {
            $excel->sheet('SO Result', function ($sheet) use ($data, $opname) {
                $sheet->mergeCells('A1:G1');
                $sheet->row(1, ['LAPORAN STOCK OPNAME']);
                $sheet->row(1, function ($row) {
                    $row->setFontWeight('bold')->setFontSize(14)->setAlignment('center');
                });

                $sheet->mergeCells('A2:G2');
                $sheet->row(2, ['Tanggal: ' . $opname->opname_date->format('d M Y') . ' | Ref: #' . $opname->id]);
                $sheet->row(2, function ($row) { $row->setAlignment('center'); });

                $sheet->row(4, ['NO', 'KODE', 'NAMA BARANG', 'SAT', 'STOCK CARD (SISTEM)', 'STOCK OPNAME (FISIK)', 'SELISIH UNIT']);
                $sheet->row(4, function ($row) {
                    $row->setBackground('#E0E0E0');
                    $row->setFontWeight('bold');
                    $row->setAlignment('center');
                    $row->setBorder('thin', 'thin', 'thin', 'thin');
                });

                $rowNum = 5;
                $no = 1;
                foreach ($data as $d) {
                    $item = $d->item;
                    $sheet->row($rowNum, [
                        $no,
                        $item ? $item->code : '-',
                        $item ? $item->name : 'Item Terhapus',
                        $item ? $item->unit : '-',
                        // FIX: Float Excel
                        (float) $d->system_quantity,
                        (float) $d->physical_quantity,
                        (float) $d->difference
                    ]);

                    $sheet->row($rowNum, function ($row) {
                        $row->setBorder('thin', 'thin', 'thin', 'thin');
                    });

                    if ($d->difference < 0) {
                        $sheet->cell('G' . $rowNum, function ($cell) { $cell->setFontColor('#FF0000')->setFontWeight('bold'); });
                    } elseif ($d->difference > 0) {
                        $sheet->cell('G' . $rowNum, function ($cell) { $cell->setFontColor('#16A34A')->setFontWeight('bold'); });
                    }
                    $rowNum++;
                    $no++;
                }
                $sheet->setAutoSize(true);
            });
        })->download('xlsx');
    }

    // --- ROLLBACK ---
    public function rollback($id)
    {
        $opname = StockOpnameHeader::with('details')->findOrFail($id);
        if ($opname->status !== 'PROCESSED') return redirect()->back()->with('error', 'Hanya SO Processed yg bisa rollback.');

        DB::beginTransaction();
        try {
            InventoryHelper::deleteMovement('OPNAME', $opname->id);
            $opname->status = 'DRAFT';
            $opname->save();
            DB::commit();
            return redirect()->route('stock-opnames.show', $id)->with('success', 'Rollback berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal rollback: ' . $e->getMessage());
        }
    }

    // --- DESTROY ---
    public function destroy($id)
    {
        $opname = StockOpnameHeader::with('details')->findOrFail($id);
        
        DB::beginTransaction();
        try {
            if ($opname->status === 'PROCESSED') {
                InventoryHelper::deleteMovement('OPNAME', $opname->id);
            }
            StockOpnameDetail::where('opname_header_id', $id)->delete();
            $opname->delete();
            DB::commit();
            return redirect()->route('stock-opnames.index')->with('success', 'Data Stock Opname #' . $id . ' berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

    // --- SCOPE HELPER ---
    protected function getAllowedItemIdsForCurrentUser()
    {
        $user = Auth::user();
        if (!$user) return null;
        $scope = $user->inventory_scope;
        if ($scope === 'ALL' || $scope === null || $scope === '') return null;

        $categoryCodes = method_exists($user, 'categoryCodesForScope') ? $user->categoryCodesForScope() : [];
        if (empty($categoryCodes)) return null;

        return Item::whereHas('category', function ($q) use ($categoryCodes) {
            $q->whereIn('code', $categoryCodes);
        })->pluck('id')->toArray();
    }
}
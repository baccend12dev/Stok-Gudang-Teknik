<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BonHeader;
use App\BonDetail;
use App\RequestHeader;
use App\RequestDetail;
use App\Item;
use App\Department;
use App\DepartmentItemQuota;
use Carbon\Carbon;
use DB;
use App\Helpers\InventoryHelper; // <-- Panggil Helper

class BonController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $user = \Auth::user();
            if ($user && $user->role === 'USER') {
                // Kecualikan route API divisions agar user biasa bisa memilih divisi mereka
                if ($request->is('*api/divisions*')) {
                    return $next($request);
                }
                return redirect()->route('requests.index')->with('error', 'Akses Ditolak! Anda tidak memiliki izin ke halaman tersebut.');
            }
            return $next($request);
        });
    }
    
    /**
     * Daftar departemen INDUK yang aktif.
     */
    protected function getActiveRootDepartments()
    {
        $q = Department::query();

        // filter aktif kalau kolomnya ada
        if (\Schema::hasColumn('departments', 'is_active')) {
            $q->where('is_active', 1);
        }

        // ambil yang induk kalau punya parent_id
        if (\Schema::hasColumn('departments', 'parent_id')) {
            $q->whereNull('parent_id');
        }

        return $q->orderBy('name', 'asc')->get();
    }

    /**
     * API AJAX: Mengambil daftar divisi berdasarkan ID Departemen
     * Route: /api/divisions (Pastikan sudah didaftarkan di web.php)
     */
    public function getDivisionsByDept(Request $request)
    {
        $deptId = $request->get('department_id');
        
        if (!$deptId) {
            return response()->json([]);
        }

        // Ambil dari tabel divisions yang sudah di-seed
        $divisions = DB::table('divisions')
            ->where('department_id', $deptId)
            ->orderBy('name', 'asc')
            ->select('id', 'name')
            ->get();

        return response()->json($divisions);
    }

    /**
     * Ringkasan kuota 1 dept + item
     */
    protected function getDeptQuotaInfo($departmentId, $itemId, $excludeBonId = null)
    {
        $total = DepartmentItemQuota::where('department_id', $departmentId)
            ->where('item_id', $itemId)
            ->sum('quota_quantity');

        if (!$total) {
            return [
                'total'     => 0,
                'used'      => 0,
                'remaining' => 0,
            ];
        }

        $usedQuery = BonDetail::where('item_id', $itemId)
            ->whereHas('bonHeader', function ($q) use ($departmentId, $excludeBonId) {
                $q->where('department_id', $departmentId)
                  ->whereIn('status', ['APPROVED', 'ISSUED']);

                if (!is_null($excludeBonId)) {
                    $q->where('id', '!=', $excludeBonId);
                }
            });

        // FIX: Cast ke float untuk support decimal
        $used = (float) $usedQuery->sum('approved_quantity');

        // FIX: Cast ke float
        $total  = (float) $total;
        $remain = $total - $used;
        if ($remain < 0) {
            $remain = 0;
        }

        return [
            'total'     => $total,
            'used'      => $used,
            'remaining' => $remain,
        ];
    }

    // =====================================================================
    // REPORT - BON PER ITEM
    // =====================================================================
    public function reportPerItem(Request $request)
    {
        // --- UPDATE: DEFAULT DATE LOGIC (1st Month to Today) ---
        $from    = $request->has('from') ? $request->get('from') : date('Y-m-01');
        $to      = $request->has('to') ? $request->get('to') : date('Y-m-d');
        
        $itemId  = $request->get('item_id');
        $perPage = (int) $request->get('perPage', 100);

        if ($perPage <= 0) {
            $perPage = 100;
        }

        $selectedDepartments = $request->get('departments', []);
        if (!is_array($selectedDepartments)) {
            if ($selectedDepartments === null || $selectedDepartments === '') {
                $selectedDepartments = [];
            } else {
                $selectedDepartments = array($selectedDepartments);
            }
        }

        $normalizedDepartments = array();
        foreach ($selectedDepartments as $dep) {
            if ($dep === '' || $dep === null) {
                continue;
            }
            $normalizedDepartments[] = (int) $dep;
        }
        $selectedDepartments = $normalizedDepartments;

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $baseQuery = DB::table('bon_details as d')
            ->join('bon_headers as h', 'd.bon_header_id', '=', 'h.id')
            ->leftJoin('items as i', 'd.item_id', '=', 'i.id')
            ->leftJoin('departments as dept', 'h.department_id', '=', 'dept.id')
            ->where('h.status', '=', 'ISSUED');

        if ($from) {
            $baseQuery->where('h.date', '>=', $from);
        }

        if ($to) {
            $baseQuery->where('h.date', '<=', $to);
        }

        if (count($selectedDepartments) > 0) {
            $baseQuery->whereIn('h.department_id', $selectedDepartments);
        }

        // Filter item berdasarkan scope user
        if (is_array($allowedItemIds)) {
            $baseQuery->whereIn('d.item_id', $allowedItemIds);
        }

        if ($itemId) {
            $baseQuery->where('d.item_id', (int) $itemId);
        }

        $queryForTotal = clone $baseQuery;
        // FIX: Cast ke float
        $totalQty      = (float) $queryForTotal->sum('d.issued_quantity');

        $queryForRows = clone $baseQuery;
        $rows = $queryForRows
            ->select(
                'd.id as detail_id',
                'h.date',
                'h.bon_number',
                'h.division_name',
                'i.code as item_code',
                'i.name as item_name',
                'i.unit as item_unit',
                'dept.name as department_name',
                'd.quantity',
                'd.approved_quantity',
                'd.issued_quantity'
            )
            ->orderBy('h.date', 'desc')
            ->orderBy('h.id', 'desc')
            ->orderBy('d.id', 'asc')
            ->paginate($perPage);

        $departments = $this->getActiveRootDepartments();

        // Dropdown barang juga harus ke-filter
        $itemsQuery = Item::orderBy('name', 'asc');
        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }
        $items = $itemsQuery->get();

        return view('bons.report_per_item', [
            'rows'                => $rows,
            'from'                => $from,
            'to'                  => $to,
            'selectedDepartments' => $selectedDepartments,
            'itemId'              => $itemId,
            'perPage'             => $perPage,
            'departments'         => $departments,
            'items'               => $items,
            'totalQty'            => $totalQty,
        ]);
    }


    // =====================================================================
    // INDEX BON
    // =====================================================================
    public function index(Request $request)
    {
        $perPage = (int) $request->get('perPage', 100);
        if ($perPage <= 0) {
            $perPage = 100;
        }

        $q          = trim($request->get('q', ''));
        
        // --- UPDATE: DEFAULT DATE LOGIC (1st Month to Today) ---
        $from       = $request->has('from') ? $request->get('from') : date('Y-m-01');
        $to         = $request->has('to') ? $request->get('to') : date('Y-m-d');
        
        $department = $request->get('department');
        $status     = $request->get('status');

        // Ambil scope item untuk user saat ini
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = BonHeader::with('department');

        // Filter BON berdasarkan buku (inventory_scope)
        if (is_array($allowedItemIds)) {
            $query->whereHas('details', function ($q2) use ($allowedItemIds) {
                $q2->whereIn('item_id', $allowedItemIds);
            });
        }

        // Filter pencarian teks
        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('bon_number', 'ilike', $like)
                    ->orWhere('notes', 'ilike', $like);
            });
        }

        // Filter tanggal
        if (!empty($from)) {
            $query->where('date', '>=', $from);
        }
        if (!empty($to)) {
            $query->where('date', '<=', $to);
        }

        // Filter departemen
        if (!empty($department)) {
            $query->where('department_id', (int) $department);
        }

        // Filter status
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Urutan tampilan: PENDING dulu, baru berdasarkan tanggal dan ID
        $query->orderByRaw("CASE WHEN status = 'PENDING' THEN 0 ELSE 1 END")
              ->orderBy('date', 'asc')
              ->orderBy('id', 'desc');

        $bons = $query->paginate($perPage)->appends($request->except('page'));

        $departments = $this->getActiveRootDepartments();

        return view('bons.index', [
            'bons'        => $bons,
            'departments' => $departments,
            'q'           => $q,
            'from'        => $from,
            'to'          => $to,
            'department'  => $department,
            'status'      => $status,
            'perPage'     => $perPage,
        ]);
    }

    // =====================================================================
    // CREATE
    // =====================================================================
    public function create()
    {
        $departments = $this->getActiveRootDepartments();
        
        // Division Map kita kosongkan, karena sekarang via AJAX dari DB
        $divisionMap = []; 

        return view('bons.create', [
            'departments' => $departments,
            'divisionMap' => $divisionMap,
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'date'          => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'division_name' => 'nullable|string|max:100',
        ]);

        $items = (array) $request->get('items', []);
        if (count($items) === 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Minimal 1 item harus diisi.');
        }

        DB::beginTransaction();

        try {
            $bon = new BonHeader();
            $bon->date          = $request->get('date');
            $bon->department_id = (int) $request->get('department_id');
            $bon->notes         = $request->get('notes');
            $bon->status        = 'PENDING';

            if (\Schema::hasColumn('bon_headers', 'division_name')) {
                $bon->division_name = $request->get('division_name');
            }

            $bonNumber = trim($request->get('bon_number'));
            if ($bonNumber === '') {
                $bon->bon_number = $this->generateBonNumber(Carbon::parse($bon->date));
            } else {
                $bon->bon_number = $bonNumber;
            }

            $bon->save();

            foreach ($items as $row) {
                if (!isset($row['item_id'])) {
                    continue;
                }

                $itemId = (int) $row['item_id'];
                // FIX: Cast ke float untuk support decimal
                $qty    = isset($row['quantity']) ? (float) $row['quantity'] : 0;

                if ($qty <= 0) {
                    continue;
                }

                $detail                    = new BonDetail();
                $detail->bon_header_id     = $bon->id;
                $detail->item_id           = $itemId;
                $detail->quantity          = $qty;
                $detail->approved_quantity = 0;
                $detail->issued_quantity   = 0;
                $detail->save();
            }

            DB::commit();

            return redirect()->route('bons.show', $bon->id)
                ->with('success', 'BON berhasil dibuat dan masih berstatus PENDING.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan BON: ' . $e->getMessage());
        }
    }

    // =====================================================================
    // SHOW
    // =====================================================================
    public function show($id)
    {
        // 1. Ambil Data Utama
        $bon = BonHeader::with(['details.item', 'department', 'user', 'requestReference.approver', 'requestReference.details'])->findOrFail($id);

        // 2. LOGIC BARU: ANALISA JATAH (COMPREHENSIVE FIX!)
        $quotaAnalysis = [];
        $currentMonth = \Carbon\Carbon::parse($bon->date)->month;
        $currentYear = \Carbon\Carbon::parse($bon->date)->year;

        foreach ($bon->details as $d) {
            // A. Ambil Plafon (Limit) dari Master Data
            $budget = \App\DepartmentBudget::where('department_id', $bon->department_id)
                ->where('item_id', $d->item_id)
                ->first();

            // FIX: Cast ke float
            $limit = $budget ? (float)$budget->monthly_limit : 0;

            // B. Hitung Pemakaian Bulan Ini (EXCLUDE BON INI!)
            $usedSoFar = \App\BonDetail::whereHas('bonHeader', function($q) use ($bon, $currentMonth, $currentYear) {
                $q->where('department_id', $bon->department_id)
                ->where('status', '=', 'ISSUED')
                ->where('id', '!=', $bon->id)  // 🔥 EXCLUDE BON INI!
                ->whereMonth('date', '=', $currentMonth)
                ->whereYear('date', '=', $currentYear);
            })->where('item_id', $d->item_id)->sum('issued_quantity');

            // FIX: Cast ke float
            $usedSoFar = (float)$usedSoFar;

            // C. Kalkulasi Sisa Awal (Sebelum BON ini dihitung)
            $sisaAwal = $limit - $usedSoFar;
            
            // D. FIXED LOGIC: Tentukan "Minta" berdasarkan Status BON
            $minta = 0;
            
            if ($bon->status === 'ISSUED') {
                // FIX: Cast ke float
                $minta = (float)$d->issued_quantity;
                
            } elseif ($bon->status === 'APPROVED') {
                // FIX: Cast ke float
                $minta = (float)$d->approved_quantity;
                
            } else {
                // FIX: Cast ke float
                $approvedQty = (float)$d->approved_quantity;
                
                if ($approvedQty > 0) {
                    $minta = $approvedQty;
                } else {
                    $minta = (float)$d->quantity;
                }
            }
            
            $minta = max(0, $minta);
            
            // E. Kalkulasi Sisa Akhir (Setelah BON ini dihitung)
            $sisaAkhir = $sisaAwal - $minta;

            // F. Tentukan Status
            $statusLabel = 'AMAN';
            $statusClass = 'text-success';

            if ($limit <= 0) {
                $statusLabel = 'NO BUDGET';
                $statusClass = 'text-danger';
            } elseif ($sisaAkhir < 0) {
                $statusLabel = 'OVER LIMIT';
                $statusClass = 'text-danger fw-bold';
            } elseif ($sisaAkhir < ($limit * 0.1)) {
                $statusLabel = 'KRITIS';
                $statusClass = 'text-warning';
            }

            $quotaAnalysis[$d->id] = [
                'item_name'   => $d->item->name,
                'limit'       => $limit,
                'used'        => $usedSoFar,
                'sisa_awal'   => $sisaAwal,
                'req_qty'     => $minta,
                'sisa_akhir'  => $sisaAkhir,
                'status_txt'  => $statusLabel,
                'status_cls'  => $statusClass,
                'is_over'     => ($limit > 0 && $sisaAkhir < 0)
            ];
        }

        return view('bons.show', [
            'bon' => $bon,
            'quotaAnalysis' => $quotaAnalysis
        ]);
    }



    // =====================================================================
    // EDIT
    // =====================================================================
    public function edit($id)
    {
        $bon = BonHeader::with('details.item')->findOrFail($id);

        if ($bon->status !== 'PENDING') {
            return redirect()->route('bons.show', $bon->id)
                ->with('error', 'Hanya BON PENDING yang dapat diedit.');
        }

        $departments = $this->getActiveRootDepartments();
        
        // Division Map dikosongkan, karena sekarang AJAX
        $divisionMap = [];

        return view('bons.edit', [
            'bon'         => $bon,
            'departments' => $departments,
            'divisionMap' => $divisionMap,
        ]);
    }

    public function update(Request $request, $id)
    {
        $bon = BonHeader::with('details')->findOrFail($id);

        if ($bon->status !== 'PENDING') {
            return redirect()->route('bons.show', $bon->id)
                ->with('error', 'Hanya BON PENDING yang dapat diupdate.');
        }

        $this->validate($request, [
            'date'          => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'division_name' => 'nullable|string|max:100',
        ]);

        $items = (array) $request->get('items', []);
        if (count($items) === 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Minimal 1 item harus diisi.');
        }

        DB::beginTransaction();

        try {
            $bon->date          = $request->get('date');
            $bon->department_id = (int) $request->get('department_id');
            $bon->notes         = $request->get('notes');

            if (\Schema::hasColumn('bon_headers', 'division_name')) {
                $bon->division_name = $request->get('division_name');
            }

            $bonNumber = trim($request->get('bon_number'));
            if ($bonNumber !== '') {
                $bon->bon_number = $bonNumber;
            }

            $bon->save();

            foreach ($bon->details as $d) {
                $d->delete();
            }

            foreach ($items as $row) {
                if (!isset($row['item_id'])) {
                    continue;
                }

                $itemId = (int) $row['item_id'];
                // FIX: Cast ke float untuk support decimal
                $qty    = isset($row['quantity']) ? (float) $row['quantity'] : 0;

                if ($qty <= 0) {
                    continue;
                }

                $detail                    = new BonDetail();
                $detail->bon_header_id     = $bon->id;
                $detail->item_id           = $itemId;
                $detail->quantity          = $qty;
                $detail->approved_quantity = 0;
                $detail->issued_quantity   = 0;
                $detail->save();
            }

            DB::commit();

            return redirect()->route('bons.show', $bon->id)
                ->with('success', 'BON berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengupdate BON: ' . $e->getMessage());
        }
    }

    // =====================================================================
    // DESTROY
    // =====================================================================
    public function destroy($id)
    {
        // Ambil data BON beserta detailnya
        $bon = BonHeader::with('details')->findOrFail($id);
        
        DB::beginTransaction();
        try {
            // =========================================================================
            // 1. LOGIC STOK GUDANG (THE FIX - SUDAH BENAR)
            // =========================================================================
            // Hanya kembalikan stok JIKA bon sudah pernah mengurangi stok (ISSUED)
            if ($bon->status === 'ISSUED') {
                // Panggil Helper untuk hapus movement & balikan stok
                InventoryHelper::deleteMovement('BON', $bon->id);
            }
            // JIKA PENDING/APPROVED: KITA DIAMKAN STOKNYA (Karena belum pernah terpotong)

            // =========================================================================
            // 2. EXTRACT REQUEST NUMBER DARI BON NOTES (FIX BARU!)
            // =========================================================================
            // Format notes: "From Req: REQ/202601/0001 (Scope GENERAL - Bu Ani)"
            // Kita ambil request number-nya untuk sync nanti
            $reqNo = $this->extractRequestNumberFromBonNotes($bon->notes);

            // =========================================================================
            // 3. HAPUS DATA BON (BERSIH-BERSIH)
            // =========================================================================
            // Hapus detail dulu
            BonDetail::where('bon_header_id', $bon->id)->delete();
            
            // Hapus header
            $bon->delete();

            // =========================================================================
            // 4. SYNC REQUEST PROCESSED QTY (FIX UTAMA!)
            // =========================================================================
            // Jika BON ini berasal dari Request, kita sync ulang processed_qty
            // Method ini akan:
            // - Hitung ulang processed_qty dari BON ISSUED yang tersisa
            // - Update status Request (APPROVED/PARTIAL/CLOSED)
            // - Kalau semua BON dihapus → processed_qty = 0 & status = APPROVED
            if ($reqNo) {
                $this->syncRequestProcessedQtyByRequestNumber($reqNo);
            }

            DB::commit();
            
            // =========================================================================
            // 5. SUCCESS MESSAGE (Informative)
            // =========================================================================
            $msg = 'Data BON berhasil dihapus.';
            
            if ($reqNo) {
                $msg .= ' Data Request telah diperbarui (processed qty & status).';
            }
            
            if ($bon->status === 'ISSUED') {
                $msg .= ' Stok telah dikembalikan ke gudang.';
            }
            
            return redirect()->route('bons.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    // =====================================================================
    // ROLLBACK (Batalkan Issue)
    // =====================================================================
    public function rollback(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $bon = BonHeader::with('details')->findOrFail($id);

            // 1. Validasi Status
            if ($bon->status !== 'ISSUED') {
                return redirect()->back()->with('error', 'Hanya BON status ISSUED yang bisa di-rollback.');
            }

            // 2. Kembalikan Stok Fisik Barang Balik
            foreach ($bon->details as $d) {
                // Helper sudah meng-handle pengembalian stok dan hapus movement
                InventoryHelper::deleteMovement('BON', $bon->id, $d->item_id);
            }

            // 🔥 3. RESET approved_quantity & issued_quantity agar analisa plafon kembali ke titik nol
            foreach ($bon->details as $d) {
                $d->approved_quantity = 0;
                $d->issued_quantity   = 0;
                $d->save();
            }

            // 4. Update Status BON jadi PENDING
            $bon->status = 'PENDING';
            $bon->save();

            // 🔥 5. SYNC REQUEST SELF HEALING (FIX: bon_header_id bukan bonheaderid!)
            $reqNo = $this->extractRequestNumberFromBonNotes($bon->notes);
            if ($reqNo) {
                $this->syncRequestProcessedQtyByRequestNumber($reqNo);
            } else {
                // 🔥 FIX CRITICAL: Column name salah! PostgreSQL case-sensitive!
                // BEFORE: ->where('bonheaderid', $bon->id)
                // AFTER:  ->where('bon_header_id', $bon->id)
                $reqHeader = RequestHeader::where('bon_header_id', $bon->id)->first();
                if ($reqHeader) {
                    $this->syncRequestProcessedQtyByRequestNumber($reqHeader->request_number);
                }
            }

            DB::commit();

            return redirect()
                ->route('bons.show', $bon->id)
                ->with('success', 'Rollback sukses! Stok dikembalikan ke gudang dan qty persetujuan di-reset.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Gagal Rollback. ' . $e->getMessage());
        }
    }

    // =====================================================================
    // APPROVE
    // =====================================================================
    public function approve(Request $request, $id)
    {
        // 1. VALIDASI DATA (HYBRID APPROACH - Allow 0 for empty stock cases)
        // Change: gt:0 → min:0 (compatible with Laravel 5.6+)
        // Allow 0 untuk handle kasus stok kosong (wajar)
        $this->validate($request, [
            'details' => 'required|array',
            'details.*.approved_quantity' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $bon = BonHeader::with('details.item')->findOrFail($id);

            if ($bon->status !== 'PENDING') {
                throw new \Exception('Hanya BON berstatus PENDING yang bisa disetujui.');
            }

            // =====================================================================
            // 2. DETECT SUSPICIOUS ITEMS (Server-Side Safety Net)
            // =====================================================================
            // Cek: Stok > 0 tapi Admin input 0 (kemungkinan lupa!)
            // Kecuali user sudah konfirmasi via flag confirm_zero_items
            if (!$request->has('confirm_zero_items')) {
                $suspicious = [];
                
                foreach ($request->details as $detailId => $data) {
                    $detail = BonDetail::with('item')->findOrFail($detailId);
                    
                    if ($detail->bon_header_id !== $bon->id) continue;
                    
                    // FIX: Cast ke float
                    $inputQty = (float) $data['approved_quantity'];
                    $item = $detail->item;
                    
                    if (!$item) continue;
                    
                    // FIX: Cast ke float
                    $stock = (float) $item->current_stock;
                    
                    // SUSPICIOUS: Stok tersedia (> 0), tapi admin input 0
                    if ($stock > 0 && $inputQty == 0) {
                        $suspicious[] = $item->name . ' (Stok: ' . $stock . ')';
                    }
                }
                
                // Jika ada item suspicious, tolak dan suruh konfirmasi
                if (count($suspicious) > 0) {
                    $msg = 'Ada ' . count($suspicious) . ' item dengan stok tersedia tapi diinput 0: ' . implode(', ', $suspicious) . '. Pastikan ini bukan kesalahan input!';
                    return back()->with('warning', $msg)->withInput();
                }
            }

            // =====================================================================
            // 3. PROCESS APPROVAL (FIX: SAVE SEMUA VALUE, TERMASUK 0!)
            // =====================================================================
            $processedCount = 0;
            $zeroCount = 0;
            
            foreach ($request->details as $detailId => $data) {
                $detail = BonDetail::findOrFail($detailId);
                
                if ($detail->bon_header_id !== $bon->id) continue;
                
                // FIX: Cast ke float
                $inputQty = (float) $data['approved_quantity'];
                $item = $detail->item;
                
                // ===================================================================
                // FIX: JANGAN SKIP! SAVE VALUE 0 KE DATABASE!
                // ===================================================================
                if ($inputQty == 0) {
                    // TETAP SAVE VALUE 0!
                    $detail->approved_quantity = 0;
                    $detail->save();
                    $zeroCount++;
                    continue; // Lanjut ke item berikutnya
                }
                
                // === VALIDASI STOK FISIK (untuk qty > 0) ===
                if (!$item) {
                    throw new \Exception("Item tidak ditemukan.");
                }
                
                if ($item->current_stock < $inputQty) {
                    throw new \Exception("Stok tidak cukup untuk item: {$item->name}. (Stok: {$item->current_stock}, Input: {$inputQty})");
                }
                
                // === SAVE APPROVED QUANTITY ===
                $detail->approved_quantity = $inputQty;
                $detail->save();
                $processedCount++;
            }

            // === VALIDASI FINAL: Minimal ada 1 item yang qty > 0 ===
            if ($processedCount === 0) {
                throw new \Exception("Tidak ada item yang disetujui dengan qty > 0. BON tidak dapat di-approve.");
            }

            // === UPDATE STATUS BON ===
            $bon->status = 'APPROVED';
            $bon->save();

            // Sync request status
            $reqNo = $this->extractRequestNumberFromBonNotes($bon->notes);
            if ($reqNo) {
                $this->syncRequestProcessedQtyByRequestNumber($reqNo);
            }

            DB::commit();
            
            // === SUCCESS MESSAGE (Clean & Informative) ===
            $msg = 'BON berhasil disetujui.';
            if ($zeroCount > 0) {
                $msg .= " ({$processedCount} item diproses, {$zeroCount} item qty = 0)";
            } else {
                $msg .= " Semua item valid dan stok mencukupi.";
            }
            
            return redirect()->route('bons.show', $bon->id)->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal Approve: ' . $e->getMessage())->withInput();
        }
    }


    public function issue($id)
    {
        DB::beginTransaction();
        try {
            // Eager load details + item untuk performa
            $bon = BonHeader::with('details.item')->findOrFail($id);

            if ($bon->status !== 'APPROVED') {
                throw new \Exception('Hanya BON berstatus APPROVED yang bisa di-issue.');
            }

            foreach ($bon->details as $detail) {
                // --- CRITICAL FIX: GUNAKAN APPROVED_QUANTITY ---
                // FIX: Cast ke float
                $qtyToDeduct = (float) $detail->approved_quantity;
                $item = $detail->item;

                // Skip item jika qty = 0 (Tidak ada pergerakan stok)
                if ($qtyToDeduct == 0) {
                    $detail->issued_quantity = 0;
                    $detail->save();
                    continue;
                }

                // Validasi Terakhir: Double-check sebelum potong saldo
                if ($item->current_stock < $qtyToDeduct) {
                    throw new \Exception("Gagal Issue! Stok fisik berubah dan kini tidak cukup untuk item: {$item->name}.");
                }

                // ===================================================================
                // FIX CRITICAL: JANGAN POTONG MANUAL! BIAR HELPER YANG URUS SEMUA!
                // ===================================================================
                
                // 1. Catat di Helper Inventory (Kartu Stok)
                // Helper akan otomatis update current_stock + catat movement
                InventoryHelper::recordMovement(
                    $item->id,
                    Carbon::now()->format('Y-m-d'),
                    'BON',
                    $bon->id,
                    $bon->bon_number,
                    -$qtyToDeduct  // Negatif = keluar, Helper akan potong stok otomatis
                );

                // 2. Update Detail Issued
                $detail->issued_quantity = $qtyToDeduct;
                $detail->save();
            }

            // 3. UPDATE STATUS BON
            $bon->status = 'ISSUED';
            $bon->save();

            // ===================================================================
            // 4. SYNC REQUEST PROCESSED QTY
            // ===================================================================
            $reqNo = $this->extractRequestNumberFromBonNotes($bon->notes);
            
            if ($reqNo) {
                $this->syncRequestProcessedQtyByRequestNumber($reqNo);
            }

            DB::commit();

            return redirect()->route('bons.show', $bon->id)
                ->with('success', 'Barang berhasil dikeluarkan (Issued). Stok telah terpotong sesuai jumlah disetujui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal Issue: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $bon = BonHeader::findOrFail($id);

        if ($bon->status !== 'PENDING') {
            return redirect()->route('bons.show', $bon->id)
                ->with('error', 'Hanya BON PENDING yang dapat di-reject.');
        }

        $bon->status = 'REJECTED';
        $bon->save();

        // Sync request status
        $reqNo = $this->extractRequestNumberFromBonNotes($bon->notes);
        if ($reqNo) {
            $this->syncRequestProcessedQtyByRequestNumber($reqNo);
        }

        return redirect()->route('bons.show', $bon->id)
            ->with('success', 'BON telah direject.');
    }

    public function cancel($id)
    {
        DB::beginTransaction();
        try {
            $bon = BonHeader::findOrFail($id);

            // Validasi: Jika sudah CANCELLED, stop.
            if ($bon->status === 'CANCELLED') {
                return redirect()->back()->with('error', 'BON ini sudah dibatalkan sebelumnya.');
            }

            // 1. Jika BON sudah ISSUED, wajib Rollback Stok Fisik
            if ($bon->status === 'ISSUED') {
                // Hapus movement stok (kembalikan ke gudang)
                InventoryHelper::deleteMovement('BON', $bon->id);
            }

            // 2. Ubah Status BON jadi CANCELLED
            $bon->status = 'CANCELLED';
            $bon->save();

            // 3. SYNC REQUEST (Hanya update Qty, Status JANGAN diubah otomatis)
            $reqNo = $this->extractRequestNumberFromBonNotes($bon->notes);
            
            if ($reqNo) {
                // A. Sync Ulang (Hitung processed_qty berdasarkan BON sisa yang aktif)
                // Ini PENTING tetap dijalankan agar 'processed_qty' di Request menjadi 0 (atau berkurang).
                // Dengan begitu, validasi saat lu mau Un-approve manual nanti akan lolos.
                $this->syncRequestProcessedQtyByRequestNumber($reqNo);

                // [REMOVED] Logic otomatis ubah status ke OPEN dihapus sesuai request.
                // Biarkan status tetap APPROVED/PARTIAL agar user melakukan Un-approve manual.
            }

            DB::commit();
            return redirect()->route('bons.index')
                ->with('success', 'BON #' . $bon->bon_number . ' berhasil dibatalkan. Data Qty Request diperbarui (Status tetap).');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membatalkan BON: ' . $e->getMessage());
        }
    }

    // =====================================================================
    // LOOKUP ITEM
    // =====================================================================
    public function lookupItems(Request $request)
    {
        $q = trim($request->get('q', ''));

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $items = Item::query();

        if ($q !== '') {
            $like = '%' . $q . '%';
            $items->where(function ($qq) use ($like) {
                $qq->where('code', 'ILIKE', $like)
                   ->orWhere('name', 'ILIKE', $like);
            });
        }

        if (is_array($allowedItemIds)) {
            $items->whereIn('id', $allowedItemIds);
        }

        $items = $items
            ->orderBy('code', 'asc')
            ->limit(20)
            ->get();

        $out = [];
        foreach ($items as $it) {
            $out[] = [
                'id'         => $it->id,
                'code'       => $it->code,
                'name'       => $it->name,
                'unit'       => $it->unit,
                'buffer_min' => (float) $it->buffer_min, // FIX: Cast ke float
                'text'       => $it->code . ' - ' . $it->name,
            ];
        }

        return response()->json($out);
    }

    public function resolveItems(Request $request)
    {
        $ids = (array) $request->get('ids', []);

        if (count($ids) === 0) {
            return response()->json([]);
        }

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $items = Item::whereIn('id', $ids);
        if (is_array($allowedItemIds)) {
            $items->whereIn('id', $allowedItemIds);
        }

        $items = $items->get();

        $out = [];
        foreach ($items as $it) {
            $out[] = [
                'id'   => $it->id,
                'code' => $it->code,
                'name' => $it->name,
                'unit' => $it->unit,
                'text' => $it->code . ' - ' . $it->name,
            ];
        }

        return response()->json($out);
    }

    /**
     * Ambil request_number dari notes BON.
     * Format notes yang dipakai di RequestController: "From Req: REQ/202512/0005"
     */
    protected function extractRequestNumberFromBonNotes($notes)
    {
        if (!$notes) return null;

        $matches = array();
        if (preg_match('/From\s+Req:\s*([A-Za-z0-9\/\-_]+)/i', $notes, $matches)) {
            return isset($matches[1]) ? trim($matches[1]) : null;
        }
        return null;
    }

    /**
     * SINKRONISASI processed_qty REQUEST berdasarkan SUM approved_quantity dari BON yang APPROVED/ISSUED
     * Ini bikin Request selalu akurat walaupun:
     * - approve parsial (10 diminta, 8 di-approve)
     * - BON di-cancel / dihapus setelah approve (rollback otomatis via re-sync)
     */
    protected function syncRequestProcessedQtyByRequestNumber($requestNumber)
    {
        if (!$requestNumber) return;

        $hdr = DB::table('request_headers')->where('request_number', $requestNumber)->first();
        if (!$hdr) return;

        $reqDetails = DB::table('request_details')
            ->where('request_header_id', (int) $hdr->id)
            ->get();

        // FIX: HANYA Status ISSUED yang dihitung sebagai processed_qty
        $rows = DB::table('bon_details as bd')
            ->join('bon_headers as bh', 'bd.bon_header_id', '=', 'bh.id')
            ->where('bh.status', '=', 'ISSUED') // <-- PERUBAHAN UTAMA DISINI
            ->where('bh.notes', 'ILIKE', '%From Req: ' . $requestNumber . '%')
            ->groupBy('bd.item_id')
            ->select('bd.item_id', DB::raw('COALESCE(SUM(bd.issued_quantity),0) as total_issued'))
            ->get();

        $mapIssued = array();
        foreach ($rows as $r) {
            // FIX: Cast ke float
            $mapIssued[(int) $r->item_id] = (float) $r->total_issued;
        }

        $allDone = true;
        $anyProcessed = false;

        foreach ($reqDetails as $d) {
            $itemId = (int) $d->item_id;
            // FIX: Cast ke float
            $need   = (float) $d->quantity;

            // FIX: Cast ke float
            $proc = isset($mapIssued[$itemId]) ? (float) $mapIssued[$itemId] : 0;

            if ($proc > $need) $proc = $need;
            if ($proc < 0) $proc = 0;

            DB::table('request_details')
                ->where('id', (int) $d->id)
                ->update(array(
                    'processed_qty' => $proc,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ));

            if ($proc > 0) $anyProcessed = true;
            if ($proc < $need) $allDone = false;
        }

        // Update status header request berdasarkan status BON terakhir
        $bon = DB::table('bon_headers')
            ->where('request_id', (int) $hdr->id)
            ->orderBy('id', 'desc')
            ->first();

        $newStatus = 'APPROVED'; // Default fallback
        if ($bon) {
            if ($bon->status === 'PENDING') {
                $newStatus = 'PENDING';
            } elseif ($bon->status === 'APPROVED') {
                $newStatus = 'APPROVED';
            } elseif ($bon->status === 'ISSUED') {
                $newStatus = 'CLOSED'; // Selesai
            } elseif ($bon->status === 'REJECTED') {
                $newStatus = 'REJECTED';
            } elseif ($bon->status === 'CANCELLED') {
                $newStatus = 'CANCELLED';
            }
        }

        DB::table('request_headers')
            ->where('id', (int) $hdr->id)
            ->update(array(
                'status'     => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
    }

    protected function generateBonNumber(Carbon $date)
    {
        $prefix = 'BON/' . $date->format('Ym') . '/';

        $last = BonHeader::where('bon_number', 'LIKE', $prefix . '%')
            ->orderBy('bon_number', 'desc')
            ->first();

        $nextNumber = 1;
        if ($last) {
            $lastNumber = (int) substr($last->bon_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    protected function getAllowedItemIdsForCurrentUser()
    {
        $user = \Auth::user();
        if (!$user) {
            return null;
        }

        if (!method_exists($user, 'categoryCodesForScope')) {
            return null;
        }

        $codes = $user->categoryCodesForScope();

        // ALL → helper mengembalikan null
        if (!is_array($codes) || count($codes) === 0) {
            return null;
        }

        $ids = Item::whereHas('category', function ($q) use ($codes) {
                $q->whereIn('code', $codes);
            })
            ->pluck('id')
            ->all();

        if (count($ids) === 0) {
            // supaya whereIn tidak error, tapi hasil tetap kosong
            return array(-1);
        }

        return $ids;
    }

    protected function isSuperAdmin()
    {
        $u = \Auth::user();
        return ($u && $u->role === 'SUPER_ADMIN');
    }

    /**
     * Filter BON details berdasarkan scope user.
     * - SUPER_ADMIN: semua detail
     * - ADMIN scope: hanya detail item dalam allowedItemIds
     */
    protected function filterBonDetailsByScope(BonHeader $bon)
    {
        if ($this->isSuperAdmin()) {
            return $bon;
        }

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();
        if (is_array($allowedItemIds)) {
            $filtered = $bon->details->filter(function ($detail) use ($allowedItemIds) {
                return in_array((int) $detail->item_id, $allowedItemIds, true);
            })->values();

            $bon->setRelation('details', $filtered);
        }

        return $bon;
    }

    // =====================================================================
    // PRINT TANDA TERIMA (PDF STYLE)
    // =====================================================================
    public function printBon($id)
    {
        // Ambil data BON lengkap dengan relasi user, details.item, dan department
        // FIX: Sekarang aman memanggil 'user' karena model sudah diperbaiki
        $bon = BonHeader::with(['details.item', 'department', 'user'])->findOrFail($id);

        return view('bons.print', compact('bon'));
    }
}
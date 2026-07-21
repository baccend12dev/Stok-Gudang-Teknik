<?php

namespace App\Http\Controllers;

use App\RequestHeader;
use App\RequestDetail;
use App\Department;
use App\Item;
use App\BonHeader;
use App\BonDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Excel;
class RequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ============================================================
    // 1) INDEX (FIX: Logic Scope agar Sinkron dengan Dashboard)
    // ============================================================
    public function index(Request $request)
    {
        $user = Auth::user();

        // Eager Loading 'details.item' PENTING untuk fitur Expand Row
        $query = RequestHeader::with(['department', 'user', 'details.item'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        // --- 1. SCOPE FILTER (User vs Approver vs Admin vs Super Admin) ---
        if (strtoupper($user->role) === 'APPROVAL') {
            // Role APPROVAL: HANYA lihat request yang mana dia adalah approver-nya (approver_id = user->id)
            $query->where('approver_id', $user->id);
        } elseif ($user->role === 'USER') {
            // User biasa: lihat request sendiri + request yang dia jadi approver (PENDING_APPROVAL)
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function($q2) use ($user) {
                      $q2->where('approver_id', $user->id)
                         ->where('status', 'PENDING_APPROVAL');
                  });
            });
        } elseif ($user->role === 'SUPER_ADMIN') {
            // Super Admin: lihat semua tanpa filter
        } else {
            // Admin biasa: TIDAK lihat PENDING_APPROVAL (kecuali yang ditujukan padanya sebagai approver)
            $query->where(function($q) use ($user) {
                $q->where('status', '!=', 'PENDING_APPROVAL')
                  ->orWhere(function($q2) use ($user) {
                      $q2->where('approver_id', $user->id)
                         ->where('status', 'PENDING_APPROVAL');
                  });
            });

            // Filter berdasarkan Scope Kategori (General/Apparel)
            $codes = null;
            if (method_exists($user, 'categoryCodesForScope')) {
                $codes = $user->categoryCodesForScope();
            }

            if ($codes !== null) {
                $query->whereHas('details.item.category', function($q) use ($codes) {
                    $q->whereIn('code', $codes);
                });
            }
        }

        // --- 2. FILTER STATUS (FIX BUG 'ALL') ---
        // Hanya filter jika status ada DAN BUKAN 'ALL'
        if ($request->has('status') && $request->status != '' && $request->status != 'ALL') {
            $query->where('status', $request->status);
        }

        // --- 3. FILTER TANGGAL ---
        if ($request->has('from') && $request->from != '') {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->has('to') && $request->to != '') {
            $query->whereDate('date', '<=', $request->to);
        }

        // --- 4. GLOBAL SEARCH ---
        if ($request->has('search') && $request->search != '') {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('request_number', 'like', '%'.$s.'%')
                  ->orWhereHas('department', function($d) use ($s){
                      $d->where('name', 'like', '%'.$s.'%');
                  })
                  ->orWhereHas('user', function($u) use ($s){
                      $u->where('name', 'like', '%'.$s.'%');
                  });
            });
        }

        $requests = $query->paginate(20);

        return view('requests.index', compact('requests'));
    }

    // ============================================================
    // 2) CREATE
    // ============================================================
    public function create()
    {
        $user = Auth::user();
        $isSuperAdmin = ($user->role === 'SUPER_ADMIN');
        
        // FIX: Realtime Date menggunakan Carbon Now
        $now = Carbon::now();
        $currentDay = $now->day;

        /*// --- 1. STRICT DATE VALIDATION (1-7) ---
        // Kecuali Super Admin (Emergency Mode)
        if (!$isSuperAdmin && ($currentDay < 1 || $currentDay > 7)) {
            return redirect()->route('requests.index')
                ->with('error', 'MAAF, PERIODE REQUEST DITUTUP. Request hanya dapat dibuat pada tanggal 1 s.d 7 setiap bulannya.');
        }*/

        $deptId = $user->department_id;
        
        // Data untuk Super Admin (Dropdown Pilihan)
        $allUsers = [];
        if ($isSuperAdmin) {
            $allUsers = \App\User::with('department')
                ->where('role', '!=', 'SUPER_ADMIN')
                ->orderBy('name')
                ->get();
        } else {
            $department = Department::find($deptId);
        }

        // Ambil Item Aktif dengan Kategori
        $items = Item::with('category')
            ->where('current_status', 'ACTIVE')
            ->orderBy('code', 'asc')
            ->get();

        $approvals = \App\User::whereIn('role', ['APPROVAL', 'Approval', 'approval'])->orderBy('name')->get();

        // View Data (SIMPLE & CLEAN)
        $data = [
            'items'         => $items,
            'isSuperAdmin'  => $isSuperAdmin,
            'allUsers'      => $allUsers,
            'approvals'     => $approvals
        ];

        if (!$isSuperAdmin) {
            $data['department'] = isset($department) ? $department : null;
        }

        return view('requests.create', $data);
    }

    // ============================================================
    // 3) STORE
    // ============================================================
    public function store(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = ($user->role === 'SUPER_ADMIN');
        $currentDay = (int)date('d');

        /* // NOTE: Validasi Strict Mode 1-7 (Masih dikomentari sesuai file terakhir lu buat testing)
        // Jika nanti mau diaktifkan, uncomment aja.
        if (!$isSuperAdmin && ($currentDay < 1 || $currentDay > 7)) {
            return redirect()->route('requests.index')
                ->with('error', 'GAGAL: Periode Request Ditutup (Hanya tgl 1-7).');
        }
        */

        // 2. VALIDASI INPUT
        $rules = [
            'date' => 'required|date',
            'division_name' => 'required|string|max:100',
            'approval' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.remarks' => 'required|string|max:255'
        ];
        
        // Super Admin wajib pilih User & Dept
        if ($isSuperAdmin) {
            $rules['department_id'] = 'required|exists:departments,id';
            $rules['user_id'] = 'required|exists:users,id';
        }

        $this->validate($request, $rules);

        DB::beginTransaction();
        try {
            
            // Tentukan Target User & Dept
            $targetUserId = $isSuperAdmin ? $request->user_id : $user->id;
            $targetDeptId = $isSuperAdmin ? $request->department_id : $user->department_id;

            // Simpan Header
            $hdr = new RequestHeader();
            $hdr->request_number = $this->generateRequestNumber();
            $hdr->date = $request->date;
            $hdr->department_id = $targetDeptId;
            $hdr->division_name = $request->division_name;
            $hdr->approver_id = $request->approval;
            $hdr->user_id = $targetUserId;
            $hdr->notes = $request->notes; 
            $hdr->status = 'PENDING_APPROVAL';
            $hdr->save();

            // Simpan Details
            foreach($request->items as $row) {
                if(isset($row['item_id']) && isset($row['quantity']) && $row['quantity'] > 0) {
                    $dtl = new RequestDetail();
                    $dtl->request_header_id = $hdr->id;
                    $dtl->item_id = $row['item_id'];
                    // FIX: Cast ke Float (decimal)
                    $dtl->quantity = (float) $row['quantity'];
                    $dtl->remarks = isset($row['remarks']) ? $row['remarks'] : null;
                    $dtl->processed_qty = 0;
                    $dtl->save();
                }
            }

            DB::commit();
            return redirect()->route('requests.index')->with('success', 'Request berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat request: ' . $e->getMessage())->withInput();
        }
    }

    // ============================================================
    // EDIT (Hanya status OPEN)
    // ============================================================
    public function edit($id)
    {
        $hdr = RequestHeader::with('details')->findOrFail($id);
        $user = Auth::user();

        // Validasi Akses
        if ($user->role === 'USER' && $hdr->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke request ini.');
        }

        // Validasi Status
        if ($hdr->status !== 'OPEN' && $hdr->status !== 'PENDING_APPROVAL') {
            return redirect()->route('requests.index')
                ->with('error', 'Request hanya bisa diedit jika status masih OPEN atau PENDING APPROVAL.');
        }

        $department = Department::find($hdr->department_id);
        
        $items = Item::with('category')
            ->where('current_status', 'ACTIVE')
            ->orderBy('code', 'asc')
            ->get();

        $approvals = \App\User::whereIn('role', ['APPROVAL', 'Approval', 'approval'])->orderBy('name')->get();

        return view('requests.edit', [
            'hdr'           => $hdr,
            'items'         => $items,
            'department'    => $department,
            'approvals'     => $approvals
        ]);
    }

    // ============================================================
    // UPDATE
    // ============================================================
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'date' => 'required|date',
            'division_name' => 'required|string|max:100',
            'approval' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.remarks' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $hdr = RequestHeader::findOrFail($id);
            
            if($hdr->status !== 'OPEN' && $hdr->status !== 'PENDING_APPROVAL') {
                return back()->with('error', 'Gagal update. Status bukan OPEN atau PENDING APPROVAL.');
            }

            $hdr->date = $request->date;
            $hdr->division_name = $request->division_name;
            $hdr->approver_id = $request->approval;
            $hdr->notes = $request->notes; // Update catatan dari input
            $hdr->save();

            // Reset Details
            RequestDetail::where('request_header_id', $id)->delete();

            foreach($request->items as $row) {
                if(isset($row['item_id']) && isset($row['quantity']) && $row['quantity'] > 0) {
                    $dtl = new RequestDetail();
                    $dtl->request_header_id = $hdr->id;
                    $dtl->item_id = $row['item_id'];
                    // FIX: Cast ke Float (decimal)
                    $dtl->quantity = (float) $row['quantity'];
                    $dtl->remarks = isset($row['remarks']) ? $row['remarks'] : null;
                    $dtl->processed_qty = 0;
                    $dtl->save();
                }
            }

            DB::commit();
            return redirect()->route('requests.index')->with('success', 'Request berhasil diupdate. ' . $estimasiString);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    // ============================================================
    // EDIT PARSIAL (Super Admin Only - Approved/Partial)
    // Item yang sudah diproses (processed_qty > 0) bisa edit qty
    // tapi min qty = processed_qty. Item baru bisa ditambahkan.
    // ============================================================
    public function editPartial($id)
    {
        $user = Auth::user();
        
        // Hanya Super Admin
        if ($user->role !== 'SUPER_ADMIN') {
            abort(403, 'Hanya Super Admin yang bisa mengedit request parsial.');
        }

        $hdr = RequestHeader::with('details.item.category')->findOrFail($id);

        // Validasi status: hanya APPROVED atau PARTIAL
        if (!in_array($hdr->status, array('APPROVED', 'PARTIAL'))) {
            return redirect()->route('requests.show', $id)
                ->with('error', 'Fitur ini hanya tersedia untuk request berstatus APPROVED atau PARTIAL.');
        }

        // Pisahkan: Locked (processed) vs Editable (unprocessed)
        $lockedDetails   = array();
        $editableDetails = array();
        $hasEditableItems = false;

        foreach ($hdr->details as $d) {
            $processedQty = (float) $d->processed_qty;
            if ($processedQty > 0) {
                // Item ini sudah diproses → bisa edit qty TAPI min = processed_qty
                $lockedDetails[] = $d;
            } else {
                // Item belum diproses sama sekali → fully editable
                $editableDetails[] = $d;
                $hasEditableItems = true;
            }
        }

        // Load semua item aktif untuk picker
        $items = Item::with('category')
            ->where('current_status', 'ACTIVE')
            ->orderBy('code', 'asc')
            ->get();

        $department = Department::find($hdr->department_id);

        // Ambil reserved map untuk validasi di view
        $reservedMap = $this->getReservedPendingQtyMap($hdr->request_number);

        return view('requests.edit-partial', array(
            'hdr'              => $hdr,
            'lockedDetails'    => $lockedDetails,
            'editableDetails'  => $editableDetails,
            'items'            => $items,
            'department'       => $department,
            'reservedMap'      => $reservedMap,
        ));
    }

    // ============================================================
    // UPDATE PARSIAL (Super Admin Only)
    // Logic:
    //   - Item processed_qty > 0: HANYA update qty (min = processed_qty)
    //   - Item processed_qty == 0: delete + re-insert dari form
    //   - Item baru: insert
    // ============================================================
    public function updatePartial(Request $request, $id)
    {
        $user = Auth::user();
        
        if ($user->role !== 'SUPER_ADMIN') {
            abort(403, 'Hanya Super Admin yang bisa mengedit request parsial.');
        }

        $this->validate($request, array(
            'date' => 'required|date',
        ));

        DB::beginTransaction();
        try {
            $hdr = RequestHeader::with('details')->findOrFail($id);

            if (!in_array($hdr->status, array('APPROVED', 'PARTIAL'))) {
                return back()->with('error', 'Status request tidak valid untuk edit parsial.');
            }

            // --- 1. Update Tanggal ---
            $hdr->date = $request->date;
            // $hdr->notes = $systemNote; // Remove system note to keep it clean
            $hdr->save();

            // --- 2. Proses item yang sudah diproses (processed_qty > 0) ---
            // Hanya update qty jika qty berubah, dan TIDAK BOLEH kurang dari processed_qty
            $lockedItems = $request->input('locked_items', array());
            foreach ($lockedItems as $detailId => $data) {
                $detail = RequestDetail::where('id', $detailId)
                    ->where('request_header_id', $hdr->id)
                    ->first();
                
                if (!$detail) continue;
                
                $processedQty = (float) $detail->processed_qty;
                $newQty = (float) $data['quantity'];
                
                // Validasi: qty baru TIDAK boleh kurang dari processed_qty
                if ($newQty < $processedQty) {
                    $newQty = $processedQty; // Force ke minimum
                }
                
                // Validasi: qty baru MINIMAL 0.01
                if ($newQty < 0.01) {
                    $newQty = $processedQty > 0 ? $processedQty : 0.01;
                }
                
                $detail->quantity = $newQty;
                // Update remarks jika ada
                if (isset($data['remarks'])) {
                    $detail->remarks = $data['remarks'];
                }
                $detail->save();
            }

            // --- 3. Hapus SEMUA detail yang processed_qty == 0 (akan di-replace) ---
            // Tapi cek dulu apakah ada yang reserved di BON PENDING
            $reservedMap = $this->getReservedPendingQtyMap($hdr->request_number);
            
            $editableDetailIds = RequestDetail::where('request_header_id', $hdr->id)
                ->where('processed_qty', 0)
                ->pluck('item_id', 'id')
                ->toArray(); // [detail_id => item_id]

            // Cek BON PENDING reservation sebelum delete
            foreach ($editableDetailIds as $detailId => $itemId) {
                $reserved = isset($reservedMap[(int)$itemId]) ? (float)$reservedMap[(int)$itemId] : 0;
                if ($reserved > 0) {
                    DB::rollBack();
                    $itemName = Item::find($itemId);
                    $name = $itemName ? $itemName->name : 'ID #' . $itemId;
                    return back()->with('error', 
                        'Gagal update! Item "' . $name . '" sudah di-reserve di BON PENDING. ' .
                        'Batalkan BON terlebih dahulu sebelum mengedit item ini.'
                    )->withInput();
                }
            }
            
            // Aman: hapus semua detail yang belum diproses
            RequestDetail::where('request_header_id', $hdr->id)
                ->where('processed_qty', 0)
                ->delete();

            // --- 4. Insert ulang item editable dari form ---
            $editableItems = $request->input('items', array());
            foreach ($editableItems as $row) {
                if (isset($row['item_id']) && isset($row['quantity']) && $row['quantity'] > 0) {
                    $dtl = new RequestDetail();
                    $dtl->request_header_id = $hdr->id;
                    $dtl->item_id = $row['item_id'];
                    $dtl->quantity = (float) $row['quantity'];
                    $dtl->remarks = isset($row['remarks']) ? $row['remarks'] : null;
                    $dtl->processed_qty = 0;
                    $dtl->save();
                }
            }

            DB::commit();
            return redirect()->route('requests.show', $hdr->id)
                ->with('success', 'Request berhasil diupdate (Mode Parsial).');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update parsial: ' . $e->getMessage());
        }
    }

    // ============================================================
    // DESTROY (Hapus Permanen - Hanya status OPEN)
    // ============================================================
    public function destroy($id)
    {
        $hdr = RequestHeader::findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'USER' && $hdr->user_id !== $user->id) {
            abort(403);
        }

        if ($hdr->status !== 'OPEN' && $hdr->status !== 'PENDING_APPROVAL') {
            return back()->with('error', 'Hanya request berstatus OPEN atau PENDING APPROVAL yang boleh dihapus.');
        }

        DB::beginTransaction();
        try {
            // Hapus detail dulu (walaupun di DB biasanya cascade, tapi manual lebih aman di app level)
            RequestDetail::where('request_header_id', $hdr->id)->delete();
            
            // Hapus header
            $hdr->delete();

            DB::commit();
            return redirect()->route('requests.index')
                ->with('success', 'Request berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

    // ============================================================
    // 4) SHOW
    // ============================================================
    public function show($id)
    {
        $hdr = RequestHeader::with(['details.item.category', 'department', 'user'])->findOrFail($id);

        $user = Auth::user();
        if ($user->role === 'USER' && $hdr->user_id !== $user->id) {
            abort(403, 'Anda tidak punya akses ke request ini.');
        }

        // Tampilan khusus Role APPROVAL (Hanya fokus pada keperluan & persetujuan, tanpa stok gudang)
        if (strtoupper($user->role) === 'APPROVAL') {
            return view('approvals.show', [
                'hdr'  => $hdr,
                'user' => $user
            ]);
        }

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser(); 
        $reservedMap    = $this->getReservedPendingQtyMap($hdr->request_number);

        // Init Variable
        $mineTotalQty  = 0; $mineDoneQty  = 0; $mineReservedQty = 0;
        $otherTotalQty = 0; $otherDoneQty = 0; $otherReservedQty = 0;
        $canProcessAny = false;

        // --- CORE LOGIC: DETEKSI ITEM MILIK SIAPA ---
        if ($user->role !== 'USER') {
            foreach ($hdr->details as $d) {
                // FIX: Cast Float
                $qty      = (float) $d->quantity;
                $done     = (float) $d->processed_qty; 
                $reserved = isset($reservedMap[(int)$d->item_id]) ? (float)$reservedMap[(int)$d->item_id] : 0;
                
                // Sisa yang belum diproses (untuk validasi tombol buat bon)
                $remForDraft = $qty - $done - $reserved;

                // Cek kepemilikan item
                $isMine = true;
                if (is_array($allowedItemIds)) {
                    $isMine = in_array((int) $d->item_id, $allowedItemIds, true);
                }

                if ($isMine) {
                    $mineTotalQty     += $qty;
                    $mineDoneQty      += $done;
                    $mineReservedQty  += $reserved;
                    if ($remForDraft > 0) $canProcessAny = true;
                } else {
                    $otherTotalQty     += $qty;
                    $otherDoneQty      += $done;
                    $otherReservedQty  += $reserved;
                }
            }
        }

        // Variabel untuk View
        // Apakah ini request campuran? (Ada item saya DAN item orang lain yang belum selesai)
        // Kita cek total qty, bukan sisa, supaya admin tau konteks "ini request rame-rame"
        $isMixedRequest = ($mineTotalQty > 0 && $otherTotalQty > 0);

        // Hitung sisa real (untuk display)
        $mineRemain  = max(0, $mineTotalQty - $mineDoneQty - $mineReservedQty);
        $otherRemain = max(0, $otherTotalQty - $otherDoneQty - $otherReservedQty);

        $relatedBons = BonHeader::where('notes', 'ILIKE', '%From Req: ' . $hdr->request_number . '%')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('requests.show', [
            'hdr'             => $hdr,
            'user'            => $user,
            'allowedItemIds'  => $allowedItemIds,
            // Logic Data
            'isMixedRequest'  => $isMixedRequest,
            'canProcessAny'   => $canProcessAny,
            'mineTotalQty'    => $mineTotalQty,
            'otherTotalQty'   => $otherTotalQty,
            // Standard Data
            'reservedMap'     => $reservedMap,
            'relatedBons'     => $relatedBons,
            // Pass sisa juga buat optional display
            'mineRemain'      => $mineRemain,
        ]);
    }

    // ============================================================
    // 5a) APPROVER (ATASAN) APPROVE / REJECT
    //     PENDING_APPROVAL → OPEN (approve) atau REJECTED (reject)
    // ============================================================
    public function approverApprove(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $hdr = RequestHeader::with('details')->findOrFail($id);
            $user = Auth::user();

            // Validasi: hanya approver yang ditunjuk atau Super Admin
            if ($user->id != $hdr->approver_id && $user->role !== 'SUPER_ADMIN') {
                return back()->with('error', 'Anda bukan atasan yang ditunjuk untuk menyetujui request ini.');
            }

            // Validasi status
            if ($hdr->status !== 'PENDING_APPROVAL') {
                return back()->with('error', 'Request ini tidak dalam status Menunggu Persetujuan.');
            }

            // 1. Update status request
            $hdr->status = 'PENDING';
            $hdr->approved_by_approver_at = Carbon::now();
            $hdr->save();

            // 2. Buat BonHeader otomatis (Single BON untuk seluruh item)
            $bon = new BonHeader();
            $bon->bon_number    = $this->generateBonNumber(Carbon::now());
            $bon->date          = Carbon::now()->format('Y-m-d');
            $bon->department_id = $hdr->department_id;
            $bon->division_name = $hdr->division_name;
            $bon->request_id    = $hdr->id;
            $bon->notes         = 'From Req: ' . $hdr->request_number;
            $bon->status        = 'PENDING';
            $bon->save();

            // 3. Salin semua detail ke BonDetail (processed_qty di-sync saat Admin Gudang memproses/mengeluarkan BON)
            foreach ($hdr->details as $d) {
                $bd = new BonDetail();
                $bd->bon_header_id     = $bon->id;
                $bd->item_id           = (int) $d->item_id;
                $bd->quantity          = (float) $d->quantity;
                $bd->approved_quantity = 0; // Baru diisi oleh Admin Gudang
                $bd->issued_quantity   = 0;
                $bd->save();
            }

            DB::commit();
            return redirect()->route('requests.show', $hdr->id)
                ->with('success', 'Request berhasil disetujui! BON ' . $bon->bon_number . ' telah dibuat otomatis untuk diproses Admin Gudang.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyetujui request: ' . $e->getMessage());
        }
    }

    public function approverReject(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $hdr = RequestHeader::findOrFail($id);
            $user = Auth::user();

            // Validasi: hanya approver yang ditunjuk atau Super Admin
            if ($user->id != $hdr->approver_id && $user->role !== 'SUPER_ADMIN') {
                return back()->with('error', 'Anda bukan atasan yang ditunjuk untuk menolak request ini.');
            }

            // Validasi status
            if ($hdr->status !== 'PENDING_APPROVAL') {
                return back()->with('error', 'Request ini tidak dalam status Menunggu Persetujuan.');
            }

            $hdr->status = 'REJECTED';
            $hdr->notes = ($hdr->notes ? $hdr->notes . ' | ' : '') . 'Ditolak oleh atasan: ' . $user->name;
            $hdr->save();

            DB::commit();
            return redirect()->route('requests.show', $hdr->id)
                ->with('success', 'Request telah ditolak.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menolak request: ' . $e->getMessage());
        }
    }

    // ============================================================
    // 5b) ADMIN APPROVE / REJECT / CANCEL
    // ============================================================
    public function approve(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // ===================================================================
            // FIX: Ambil REQUEST HEADER, bukan BonHeader!
            // ===================================================================
            $hdr = RequestHeader::with('details.item')->findOrFail($id);
            
            $user = Auth::user();
            
            // 1. VALIDASI ROLE (Hanya Admin dan jika ada approver_id harus sesuai atau Super Admin)
            if (!$this->isAdmin($user)) {
                return back()->with('error', 'Hanya admin yang boleh approve request.');
            }
            if ($hdr->approver_id && $user->id != $hdr->approver_id && $user->role !== 'SUPER_ADMIN') {
                return back()->with('error', 'Anda bukan atasan yang ditunjuk untuk menyetujui request ini.');
            }
            
            // 2. VALIDASI STATUS (Hanya OPEN yang bisa diapprove)
            if ($hdr->status !== 'OPEN') {
                return back()->with('error', 'Hanya request berstatus OPEN yang bisa diapprove.');
            }
            
            // ===================================================================
            // 3. VALIDASI STOK & AUTO-CALCULATE APPROVED QTY
            // ===================================================================
            $allAvailable = true;
            $warnings = [];
            
            foreach ($hdr->details as $detail) {
                $item = $detail->item;
                
                if (!$item) {
                    $warnings[] = "Item ID {$detail->item_id} tidak ditemukan.";
                    $allAvailable = false;
                    continue;
                }
                
                // FIX: Float
                $requestedQty = (float) $detail->quantity;
                $currentStock = (float) $item->current_stock;
                
                // Cek apakah stok cukup
                if ($currentStock < $requestedQty) {
                    $warnings[] = "{$item->name}: Stok kurang (Diminta: {$requestedQty}, Tersedia: {$currentStock})";
                    $allAvailable = false;
                }
            }
            
            // ===================================================================
            // 4. UPDATE STATUS REQUEST
            // ===================================================================
            // Logic: Request hanya berubah status saja.
            // BON nanti dibuat manual via tombol "Buat BON" di halaman show.
            $hdr->status = 'APPROVED';
            $hdr->save();
            
            DB::commit();
            
            // ===================================================================
            // 5. SUCCESS MESSAGE (Informative)
            // ===================================================================
            if ($allAvailable) {
                return redirect()->route('requests.show', $hdr->id)
                    ->with('success', 'Request berhasil diapprove! Semua item stoknya cukup. Silakan buat BON untuk proses lebih lanjut.');
            } else {
                $warningMsg = implode(' | ', $warnings);
                return redirect()->route('requests.show', $hdr->id)
                    ->with('warning', 'Request berhasil diapprove, tapi ada peringatan stok: ' . $warningMsg);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal Approve Request: ' . $e->getMessage());
        }
    }



    public function reject($id)
    {
        $hdr = RequestHeader::findOrFail($id);

        if (!$this->isAdmin(Auth::user())) {
            abort(403, 'Hanya admin yang boleh reject.');
        }

        if ($hdr->status !== 'OPEN') {
            return back()->with('error', 'Request tidak bisa di-reject karena status bukan OPEN.');
        }

        $hdr->status = 'REJECTED';
        $hdr->save();

        return back()->with('success', 'Request berhasil di-reject.');
    }

    public function cancel($id)
    {
        $hdr = RequestHeader::findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'USER' && $hdr->user_id !== $user->id) {
            abort(403, 'Anda tidak punya akses.');
        }

        if (!in_array($hdr->status, ['OPEN'], true)) {
            return back()->with('error', 'Request hanya bisa dibatalkan saat status OPEN.');
        }

        $hdr->status = 'CANCELLED';
        $hdr->save();

        return back()->with('success', 'Request berhasil dibatalkan.');
    }

    // ============================================================
    // UN-APPROVE (Kembalikan ke OPEN jika belum diproses)
    // ============================================================
    public function unapprove($id)
    {
        $hdr = RequestHeader::with('details')->findOrFail($id);
        $user = Auth::user();

        // 1. Validasi Role (Hanya Admin)
        if (!$this->isAdmin($user)) {
            abort(403, 'Hanya admin yang boleh membatalkan approval.');
        }

        // 2. Validasi Status Awal
        if ($hdr->status !== 'APPROVED') {
            return back()->with('error', 'Hanya request status APPROVED yang bisa dibatalkan.');
        }

        // 3. Validasi Processed Qty (Harus 0 bersih)
        // FIX: Sum float
        $totalProcessed = (float)$hdr->details->sum('processed_qty');
        if ($totalProcessed > 0) {
            return back()->with('error', 'Gagal! Sebagian barang sudah diproses/issued. Tidak bisa un-approve.');
        }

        // 4. Validasi Relasi BON (Harus Kosong dari BON Aktif)
        // FIX: Cek apakah ada BON aktif.
        // BON dianggap "Mati/Aman" jika statusnya CANCELLED atau REJECTED.
        // Jadi kita hitung BON yang statusnya BUKAN Cancelled DAN BUKAN Rejected.
        $linkedBons = BonHeader::where('notes', 'ILIKE', '%From Req: ' . $hdr->request_number . '%')
            ->whereNotIn('status', ['CANCELLED', 'REJECTED']) // <-- UPDATE LOGIC DISINI
            ->count();

        if ($linkedBons > 0) {
            return back()->with('error', 'Gagal! Masih ada ' . $linkedBons . ' BON aktif (Pending/Approved) yang terhubung. Harap hapus, cancel, atau reject BON terlebih dahulu.');
        }

        // EKSEKUSI
        DB::beginTransaction();
        try {
            $hdr->status = 'OPEN';
            $hdr->save();

            DB::commit();
            return back()->with('success', 'Approval dibatalkan. Request kembali berstatus OPEN.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ============================================================
    // 6) CREATE BON FROM REQUEST (FIXED: SMART LOGIC SUPER ADMIN)
    // ============================================================
    public function createBonFromRequest(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $hdr  = RequestHeader::with(['details.item.category'])->findOrFail($id);
            $user = Auth::user();

            if (!$this->isAdmin($user)) {
                abort(403, 'Hanya admin yang boleh membuat BON dari Request.');
            }

            if (!in_array($hdr->status, ['OPEN', 'APPROVED', 'PARTIAL'])) {
                return back()->with('error', 'Status Request tidak valid untuk diproses.');
            }

            $allowedItemIds = $this->getAllowedItemIdsForCurrentUser(); 
            $reservedMap    = $this->getReservedPendingQtyMap($hdr->request_number);

            // Disabled scope validation check because admin is single and can process all requests.
            // if ($user->role !== 'SUPER_ADMIN' && !is_array($allowedItemIds)) {
            //     return back()->with('error', 'Scope kategori Anda belum diset.');
            // }

            // ========================================================
            // CASE A: SUPER_ADMIN => AUTO SPLIT (SMART STOCK CHECK)
            // ========================================================
            if ($user->role === 'SUPER_ADMIN') {

                $GENERAL_CODES = ['ATK','SBN','AKB','UMM'];
                $APPAREL_CODES = ['AK','PK'];

                $groups = [
                    'GENERAL' => [],
                    'APPAREL' => [],
                    'OTHER'   => [],
                ];
                
                $skippedItems = 0; // Counter item yang stoknya 0

                foreach ($hdr->details as $d) {
                    // FIX: Float
                    $qty      = (float) $d->quantity;
                    $done     = (float) $d->processed_qty;
                    $reserved = isset($reservedMap[(int)$d->item_id]) ? (float)$reservedMap[(int)$d->item_id] : 0;

                    $remainingForDraft = $qty - $done - $reserved;
                    if ($remainingForDraft <= 0) continue;

                    // --- NEW: SMART CHECK STOCK (Sama dengan Admin Biasa) ---
                    $currentItem = Item::find($d->item_id);
                    // FIX: Float
                    $realStock = $currentItem ? (float)$currentItem->current_stock : 0;

                    // 1. Jika Stok Kosong -> SKIP
                    if ($realStock <= 0) {
                        $skippedItems++;
                        continue; 
                    }

                    // 2. Ambil Stok yang Ada (Partial jika kurang)
                    $qtyToProcess = min($remainingForDraft, $realStock);
                    // --------------------------------------------------------

                    $catCode = '';
                    if ($d->item && $d->item->category && $d->item->category->code) {
                        $catCode = strtoupper(trim($d->item->category->code));
                    }

                    if ($catCode !== '' && in_array($catCode, $GENERAL_CODES, true)) {
                        $groups['GENERAL'][] = ['detail_obj' => $d, 'qty_to_process' => $qtyToProcess];
                    } elseif ($catCode !== '' && in_array($catCode, $APPAREL_CODES, true)) {
                        $groups['APPAREL'][] = ['detail_obj' => $d, 'qty_to_process' => $qtyToProcess];
                    } else {
                        $groups['OTHER'][] = ['detail_obj' => $d, 'qty_to_process' => $qtyToProcess];
                    }
                }

                $created = [];
                foreach ($groups as $key => $rows) {
                    if (count($rows) < 1) continue;

                    $label = ($key === 'GENERAL')
                        ? 'From Req: ' . $hdr->request_number . ' | Scope GENERAL (Bu Ani)'
                        : (($key === 'APPAREL')
                            ? 'From Req: ' . $hdr->request_number . ' | Scope APPAREL (Bu Shinta)'
                            : 'From Req: ' . $hdr->request_number . ' | Scope OTHER (Unmapped)');

                    $bon = $this->createBonHeaderAndDetails($hdr, $rows, $label);

                    $created[] = [
                        'id'     => $bon->id,
                        'number' => $bon->bon_number,
                        'label'  => $label,
                    ];
                }

                // Handling Hasil
                if (count($created) < 1) {
                    // Jika tidak ada BON tercipta, cek apakah karena stok kosong semua
                    if ($skippedItems > 0) {
                        return back()->with('error', 'Gagal memproses! ' . $skippedItems . ' item stoknya KOSONG (0).');
                    }
                    return back()->with('error', 'Tidak ada item tersisa yang bisa diproses.');
                }
                
                // Jika berhasil, cek apakah status perlu jadi PARTIAL
                if ($hdr->status === 'OPEN' || $hdr->status === 'APPROVED') {
                    $hdr->status = 'PARTIAL';
                    $hdr->save();
                }

                DB::commit();
                
                // Pesan Sukses dengan Warning jika ada skip
                $msg = 'BON berhasil dibuat (Auto Split).';
                if ($skippedItems > 0) {
                    $msg .= ' Peringatan: ' . $skippedItems . ' item dilewati karena STOK KOSONG.';
                }

                return redirect()
                    ->route('requests.show', $hdr->id)
                    ->with('success', $msg)
                    ->with('created_bons', $created);
            }

            // ========================================================
            // CASE B: ADMIN scope (LOGIC TETAP / SAMA)
            // ========================================================
            $detailsToProcess = [];
            $skippedItems = 0;
            $skippedNames = [];

            foreach ($hdr->details as $d) {
                // FIX: Float
                $qty      = (float) $d->quantity;
                $done     = (float) $d->processed_qty;
                $reserved = isset($reservedMap[(int)$d->item_id]) ? (float)$reservedMap[(int)$d->item_id] : 0;
                
                $remainingForDraft = $qty - $done - $reserved;

                if ($remainingForDraft <= 0) continue;

                // 1. Cek Scope
                $isMyScope = true;
                if (is_array($allowedItemIds)) {
                    $isMyScope = in_array((int) $d->item_id, $allowedItemIds, true);
                }

                if ($isMyScope) {
                    // 2. VALIDASI STOK GUDANG (Smart Skip)
                    $currentItem = Item::find($d->item_id);
                    // FIX: Float
                    $realStock = $currentItem ? (float)$currentItem->current_stock : 0;

                    if ($realStock <= 0) {
                        $skippedItems++;
                        $skippedNames[] = $currentItem ? $currentItem->name : 'Item #'.$d->item_id;
                        continue; 
                    }

                    // 3. Ambil Stok yang Ada
                    $qtyToProcess = min($remainingForDraft, $realStock);

                    $detailsToProcess[] = [
                        'detail_obj'     => $d,
                        'qty_to_process' => $qtyToProcess
                    ];
                }
            }

            if (count($detailsToProcess) === 0) {
                if ($skippedItems > 0) {
                    return back()->with('error', 'Gagal memproses! ' . $skippedItems . ' item dalam scope Anda stoknya KOSONG (0).');
                }
                return back()->with('error', 'Tidak ada item dalam scope Anda yang bisa diproses.');
            }

            $bon = $this->createBonHeaderAndDetails($hdr, $detailsToProcess, 'From Req: ' . $hdr->request_number);

            if ($hdr->status === 'OPEN' || $hdr->status === 'APPROVED') {
                $hdr->status = 'PARTIAL';
                $hdr->save();
            }

            DB::commit();

            $msg = 'BON berhasil dibuat.';
            if ($skippedItems > 0) {
                $msg .= ' Peringatan: ' . $skippedItems . ' item dilewati karena STOK KOSONG.';
            }

            return redirect()->route('bons.show', $bon->id)->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // ============================================================
    // REKAP KEBUTUHAN (DASHBOARD PENGADAAN - TIME BOXED)
    // ============================================================
    public function recap(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'USER') {
            return redirect()->route('dashboard')->with('error', 'Akses Ditolak.');
        }

        // --- 1. FILTER TANGGAL (Time-Boxed Logic) ---
        $startDate = $request->input('from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('to', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Validasi format tanggal (Safety)
        try {
            $cStart = Carbon::parse($startDate)->startOfDay();
            $cEnd   = Carbon::parse($endDate)->endOfDay();
        } catch (\Exception $e) {
            $cStart = Carbon::now()->startOfMonth()->startOfDay();
            $cEnd   = Carbon::now()->endOfMonth()->endOfDay();
            $startDate = $cStart->format('Y-m-d');
            $endDate   = $cEnd->format('Y-m-d');
        }

        $allowedCodes = null;
        if (method_exists($user, 'categoryCodesForScope')) {
            $allowedCodes = $user->categoryCodesForScope();
        }

        // --- 2. QUERY ITEM RECAP (RAW CALCULATION MODE) ---
        // Sesuai Request: Sistem "Bodo Amat" status Closed/Open.
        // Hitungan murni: (Total Minta Bulan Itu + Buffer) - Stok Sekarang.
        
        $query = RequestDetail::select(
                'request_details.item_id',
                
                // TOTAL REQUESTED (History Murni / Raw Sum)
                // Tidak peduli status processed_qty atau status header (kecuali batal)
                DB::raw('SUM(request_details.quantity) as total_requested_raw'),

                'items.code as item_code',
                'items.name as item_name',
                'items.unit as item_unit',
                'items.current_stock as current_stock',
                'items.buffer_min as buffer_min',
                'categories.name as category_name',
                'categories.id as category_id'
            )
            ->join('request_headers', 'request_details.request_header_id', '=', 'request_headers.id')
            ->join('items', 'request_details.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            
            // Filter: Semua Status KECUALI Batal (Include CLOSED)
            ->whereNotIn('request_headers.status', ['CANCELLED', 'REJECTED'])
            
            // Filter Tanggal Header
            ->whereBetween('request_headers.date', [$startDate, $endDate]);

        if ($allowedCodes !== null) {
            $query->whereIn('categories.code', $allowedCodes);
        }

        $recapItems = $query->groupBy(
                'request_details.item_id', 
                'items.code', 
                'items.name', 
                'items.unit',
                'items.current_stock',
                'items.buffer_min',
                'categories.name',
                'categories.id'
            )
            ->orderBy('categories.name', 'asc')
            ->orderBy('items.name', 'asc')
            ->get();

        $categories = $recapItems->pluck('category_name', 'category_id')->unique();

        // --- 3. DETAIL MAP (DRILL DOWN) ---
        $detailMap = [];
        if ($recapItems->count() > 0) {
            $itemIds = $recapItems->pluck('item_id')->toArray();
            
            $details = RequestDetail::select(
                    'request_details.item_id',
                    'departments.name as dept_name',
                    'request_headers.status',
                    'request_details.quantity'
                )
                ->join('request_headers', 'request_details.request_header_id', '=', 'request_headers.id')
                ->join('departments', 'request_headers.department_id', '=', 'departments.id')
                ->whereIn('request_details.item_id', $itemIds)
                ->whereNotIn('request_headers.status', ['CANCELLED', 'REJECTED'])
                ->whereBetween('request_headers.date', [$startDate, $endDate])
                ->get();

            foreach ($details as $d) {
                // Tampilkan data mentah per departemen
                $detailMap[$d->item_id][] = [
                    'dept'   => $d->dept_name,
                    'qty'    => (float)$d->quantity, // FIX: Float
                    'status' => $d->status
                ];
            }
        }

        // Hitung total dokumen
        $totalDocs = \App\RequestHeader::whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('status', ['CANCELLED', 'REJECTED'])
            ->count();

        return view('requests.recap', compact('recapItems', 'categories', 'detailMap', 'startDate', 'endDate', 'totalDocs'));
    }

    public function exportRecapExcel(Request $request)
    {
        $user = Auth::user();
        if ($user->role === 'USER') return redirect()->back();

        // 1. FILTER TANGGAL (Sama dengan Recap View)
        $startDate = $request->input('from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('to', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Format Tampilan Header (DD/MM/YY) - Contoh: 01/01/26
        $startFmt = date('d/m/y', strtotime($startDate));
        $endFmt   = date('d/m/y', strtotime($endDate));

        // Format Nama File (Aman OS: DD-MM-YY) - Contoh: Procurement_Plan_01-01-26_31-01-26
        $fileStart = date('d-m-y', strtotime($startDate));
        $fileEnd   = date('d-m-y', strtotime($endDate));
        $filename  = 'Procurement_Plan_' . $fileStart . '_' . $fileEnd;

        $allowedCodes = null;
        if (method_exists($user, 'categoryCodesForScope')) {
            $allowedCodes = $user->categoryCodesForScope();
        }

        // 2. QUERY RAW DATA (LOGIC BARU - RAW CALCULATION)
        // Menggunakan Logic yang sama dengan method recap() agar sinkron
        $query = RequestDetail::select(
                'items.code as item_code',
                'items.name as item_name',
                'items.unit as item_unit',
                'items.current_stock as current_stock',
                'items.buffer_min as buffer_min',
                'departments.name as dept_name',
                // Ambil Quantity ASLI (Raw Demand) untuk history yang akurat
                DB::raw('SUM(request_details.quantity) as qty_requested')
            )
            ->join('request_headers', 'request_details.request_header_id', '=', 'request_headers.id')
            ->join('items', 'request_details.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('departments', 'request_headers.department_id', '=', 'departments.id')
            
            // Filter: Semua Status KECUALI Batal (Include CLOSED/Lunas)
            ->whereNotIn('request_headers.status', ['CANCELLED', 'REJECTED'])
            // Filter Tanggal
            ->whereBetween('request_headers.date', [$startDate, $endDate]);

        if ($allowedCodes !== null) {
            $query->whereIn('categories.code', $allowedCodes);
        }

        // Group by Item & Dept untuk breakdown matrix
        $rawData = $query->groupBy('items.id', 'departments.id')->get();

        // 3. TRANSFORM DATA KE MATRIX
        $matrix = [];
        $allDepts = [];

        foreach ($rawData as $row) {
            $key = $row->item_code;
            if (!isset($matrix[$key])) {
                $matrix[$key] = [
                    'code'   => $row->item_code,
                    'name'   => $row->item_name,
                    'unit'   => $row->item_unit,
                    'stock'  => (float)$row->current_stock, // FIX: Float
                    'buffer' => (float)$row->buffer_min, // FIX: Float
                    'total_req' => 0, // Total Permintaan Asli
                    'breakdown' => []
                ];
            }
            // Simpan breakdown per departemen
            $matrix[$key]['breakdown'][$row->dept_name] = (float)$row->qty_requested; // FIX: Float
            
            // Akumulasi Total
            $matrix[$key]['total_req'] += (float)$row->qty_requested; // FIX: Float

            // Kumpulkan nama departemen untuk header dinamis
            if (!in_array($row->dept_name, $allDepts)) {
                $allDepts[] = $row->dept_name;
            }
        }
        sort($allDepts); // Urutkan nama departemen A-Z

        // 4. GENERATE EXCEL
        \Excel::create($filename, function($excel) use ($matrix, $allDepts, $startFmt, $endFmt) {
            $excel->sheet('Procurement Plan', function($sheet) use ($matrix, $allDepts, $startFmt, $endFmt) {
                
                // --- JUDUL ---
                // Merge sampai kolom dinamis (5 kolom statis + jml dept + 2 kolom akhir)
                $lastColIndex = 4 + count($allDepts) + 2; 
                $lastColChar = \PHPExcel_Cell::stringFromColumnIndex($lastColIndex); // Konversi ke Huruf (misal 'G', 'K')

                $sheet->mergeCells('A1:' . $lastColChar . '1');
                $sheet->row(1, ['REKAP KEBUTUHAN BARANG (PERIODE: ' . $startFmt . ' s/d ' . $endFmt . ')']);
                $sheet->row(1, function($row){ 
                    $row->setFontWeight('bold')->setFontSize(14)->setAlignment('center'); 
                });

                // --- HEADERS ---
                $headers = ['KODE', 'NAMA BARANG', 'SATUAN', 'BUFFER (MIN)', 'STOK GUDANG'];
                foreach ($allDepts as $dept) {
                    $headers[] = strtoupper($dept);
                }
                $headers[] = 'TOTAL DIMINTA';
                $headers[] = 'SARAN BELI';

                $headerRow = 3;
                $sheet->row($headerRow, $headers);
                
                // Style Header (Abu-abu, Bold, Center)
                $sheet->row($headerRow, function($row) {
                    $row->setBackground('#E5E7EB'); 
                    $row->setFontColor('#1F2937'); 
                    $row->setFontWeight('bold');
                    $row->setAlignment('center');
                    $row->setValignment('center');
                });
                
                $sheet->freezePane('A4'); // Bekukan header

                // --- DATA ROWS ---
                $rowIdx = 4;
                foreach ($matrix as $item) {
                    // Logic Hitung Saran Beli (RAW Calculation)
                    // Defisit = (Total Diminta + Buffer) - Stok Sekarang
                    $totalNeed = $item['total_req'] + $item['buffer'];
                    $defisit = max(0, $totalNeed - $item['stock']);
                    
                    $rowData = [
                        $item['code'], 
                        $item['name'], 
                        $item['unit'],
                        $item['buffer'], 
                        $item['stock']
                    ];

                    // Isi Breakdown Dept
                    foreach ($allDepts as $deptName) {
                        $qty = isset($item['breakdown'][$deptName]) ? $item['breakdown'][$deptName] : 0;
                        $rowData[] = $qty == 0 ? '-' : $qty;
                    }

                    $rowData[] = $item['total_req']; // Total Diminta
                    $rowData[] = $defisit;           // Saran Beli

                    $sheet->row($rowIdx, $rowData);

                    // --- STYLING BARIS ---
                    // 1. Alignment Center untuk Satuan s/d Saran Beli
                    // Kolom 0 & 1 (Kode, Nama) Left. Mulai kolom 2 (Satuan) dst Center.
                    $sheet->cell('C'.$rowIdx.':'.$lastColChar.$rowIdx, function($cell) {
                        $cell->setAlignment('center');
                    });

                    // 2. Conditional Formatting (Jika Defisit > 0)
                    if ($defisit > 0) {
                        $sheet->row($rowIdx, function($row) {
                            $row->setBackground('#FEF2F2'); // Merah Halus
                            $row->setFontColor('#B91C1C'); // Merah Text
                            // Font Weight Default (Tidak Bold sesuai request)
                        });
                    }
                    
                    $rowIdx++;
                }
                
                // Auto Width Column
                $sheet->setAutoSize(true);
            });
        })->export('xlsx');
    }

    public function closePeriod(Request $request)
    {
        // === SECURITY GATE: LEVEL 1 ===
        // Pastikan hanya ADMIN atau SUPER_ADMIN yang bisa akses.
        // Kalau USER biasa coba akses, langsung tolak.
        if (Auth::user()->role === 'USER') {
            return redirect()->back()
                ->with('error', 'AKSES DITOLAK: Anda tidak memiliki wewenang untuk menutup periode.');
        }

        // 1. Tentukan Cut-Off Date (Hari pertama bulan ini jam 00:00:00)
        $cutOffDate = date('Y-m-01 00:00:00'); 

        // 2. Ambil Request yang "Menggantung"
        $staleRequests = \App\RequestHeader::where('date', '<', $cutOffDate)
            ->whereNotIn('status', ['CLOSED', 'REJECTED', 'CANCELLED'])
            ->get();

        $count = 0;

        DB::beginTransaction();
        try {
            foreach ($staleRequests as $req) {
                $req->status = 'CLOSED';
                $req->save();
                $count++;
            }
            
            DB::commit();

            return redirect()->route('requests.index')
                ->with('success', "Periode berhasil ditutup! Total $count permintaan bulan lalu telah di-hanguskan (CLOSED).");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('requests.index')
                ->with('error', 'Gagal menutup periode: ' . $e->getMessage());
        }
    }

    /**
     * Buat BON header + details.
     *
     * IMPORTANT (Opsi 2):
     * - processed_qty RequestDetail TIDAK diubah di sini.
     * - processed_qty akan di-sync saat BON APPROVED/ISSUED (di BonController->approve via syncRequestProcessedQtyByRequestNumber).
     *
     * Schema kamu:
     * - bon_details: approved_quantity, issued_quantity
     * - tidak ada kolom remarks di bon_details
     */
    private function createBonHeaderAndDetails(RequestHeader $hdr, array $rows, $notes)
    {
        $bon = new BonHeader();
        $bon->bon_number    = $this->generateBonNumber(Carbon::now());
        $bon->date          = Carbon::now()->format('Y-m-d');
        $bon->department_id = $hdr->department_id;
        $bon->division_name = $hdr->division_name;
        $bon->request_id    = $hdr->id;
        $bon->notes         = $notes;
        $bon->status        = 'PENDING';
        $bon->save();

        foreach ($rows as $data) {
            $d   = $data['detail_obj'];
            // FIX: Float
            $qty = (float) $data['qty_to_process'];
            if ($qty <= 0) continue;

            $bd = new BonDetail();
            $bd->bon_header_id     = $bon->id;
            $bd->item_id           = (int) $d->item_id;
            $bd->quantity          = $qty;
            $bd->approved_quantity = 0; // baru diisi saat approve
            $bd->issued_quantity   = 0;
            $bd->save();
        }

        return $bon;
    }

    // ============================================================
    // RESERVED MAP: qty di BON PENDING untuk request ini
    // (mencegah double draft / tombol canProcessAny jadi akurat)
    // ============================================================
    private function getReservedPendingQtyMap($requestNumber)
    {
        $map = [];

        if (!$requestNumber) return $map;

        $rows = DB::table('bon_details as bd')
            ->join('bon_headers as bh', 'bd.bon_header_id', '=', 'bh.id')
            // FIX: Masukkan APPROVED ke dalam Reserved juga, karena belum ISSUED
            ->whereIn('bh.status', ['PENDING', 'APPROVED']) 
            ->where('bh.notes', 'ILIKE', '%From Req: ' . $requestNumber . '%')
            ->groupBy('bd.item_id')
            ->select('bd.item_id', DB::raw('COALESCE(SUM(bd.quantity),0) as total_qty')) 
            // Catatan: idealnya ambil approved_quantity kalau status Approved, tapi quantity juga aman untuk reserved.
            ->get();

        foreach ($rows as $r) {
            // FIX: Float
            $map[(int)$r->item_id] = (float)$r->total_qty;
        }

        return $map;
    }

    // ============================================================
    // HELPERS
    // ============================================================
    protected function generateBonNumber(Carbon $date)
    {
        $prefix = 'BON/' . $date->format('Ym') . '/';
        $last = BonHeader::where('bon_number', 'LIKE', $prefix . '%')
            ->orderBy('bon_number', 'desc')
            ->first();

        $next = $last ? ((int) substr($last->bon_number, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    protected function generateRequestNumber()
    {
        $date = Carbon::now();
        $prefix = 'REQ/' . $date->format('Ym') . '/';
        $last = RequestHeader::where('request_number', 'LIKE', $prefix . '%')
            ->orderBy('request_number', 'desc')
            ->first();

        $next = $last ? ((int) substr($last->request_number, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    protected function isAdmin($user)
    {
        // Kamu pakai inventory_scope via categoryCodesForScope
        return $user && method_exists($user, 'categoryCodesForScope') && $user->role !== 'USER';
    }

    protected function getAllowedItemIdsForCurrentUser()
    {
        $user = Auth::user();
        if (!$user || !method_exists($user, 'categoryCodesForScope')) return null;

        $codes = $user->categoryCodesForScope();
        if (!is_array($codes) || empty($codes)) return null;

        return Item::whereHas('category', function ($q) use ($codes) {
            $q->whereIn('code', $codes);
        })->pluck('id')->toArray();
    }
}
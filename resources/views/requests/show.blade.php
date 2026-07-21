@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL DETAIL REQUEST (USER & ADMIN) === --}}
@section('help-content')
    @php
        $role = auth()->user()->role;
        $isUser = ($role === 'USER');
    @endphp

    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>POSISI VITAL:</strong> Halaman ini adalah detail rincian dari satu Nomor Request.
    </div>

    @if($isUser)
        <h4 class="help-h"><i class="fa fa-eye text-primary"></i> Cara Membaca Status</h4>
        <ul class="help-list">
            <li>
                <strong>Kolom "Diproses":</strong> Menunjukkan jumlah barang yang sudah dibuatkan BON oleh Admin.
                <br><em>Jika angka ini lebih kecil dari "Qty Req", berarti barang baru datang sebagian (Partial).</em>
            </li>
            <li>
                <strong>Status PARTIAL:</strong> Artinya request Anda sedang dikerjakan secara bertahap. Mungkin sebagian barang (misal ATK) sudah ready, tapi sebagian lain (misal Seragam) masih menunggu stok.
            </li>
            <li>
                <strong>Tabel BON Terkait:</strong> Di bagian bawah, Anda bisa melihat daftar Nomor BON yang sudah terbit dari request ini sebagai bukti proses.
            </li>
        </ul>
    @else
        <h4 class="help-h"><i class="fa fa-cogs text-danger"></i> Panduan Admin (Eksekusi)</h4>
        <ul class="help-list">
            <li>
                <strong>Sistem Split Divisi:</strong> 
                Anda hanya melihat dan bisa memproses item yang sesuai dengan wewenang Anda (General vs Apparel). Item milik divisi lain akan terlihat <em>abu-abu/terkunci</em>.
            </li>
            <li>
                <strong>Indikator Warna Baris:</strong>
                <ul style="margin-top:5px; font-size:11px;">
                    <li><span style="background:#f0fdf4; color:#166534; padding:0 4px;">Hijau</span> : Item wewenang Anda. Siap diproses.</li>
                    <li><span style="background:#fee2e2; color:#b91c1c; padding:0 4px;">Merah</span> : Stok Gudang habis/kurang.</li>
                    <li><span style="background:#f9fafb; color:#94a3b8; padding:0 4px;">Abu-abu</span> : Item divisi lain (Read-only).</li>
                </ul>
            </li>
            <li>
                <strong>Fitur "Batal Approve":</strong> 
                Tombol ini (ikon kuning) hanya muncul jika Anda sudah terlanjur Approve tapi belum sempat membuat BON. Gunakan untuk merevisi status kembali ke OPEN.
            </li>
        </ul>
    @endif
@endsection

@section('content')
@php
    // Safety defaults (PHP 5.6 Compatible - No '??' operator)
    $hdr     = isset($hdr) ? $hdr : null;
    $user    = isset($user) ? $user : null;
    $details = (isset($hdr) && isset($hdr->details)) ? $hdr->details : collect();

    // Status Logic
    $status = ($hdr && isset($hdr->status)) ? strtoupper($hdr->status) : 'OPEN';
    $statusClass = 'st-open';
    if ($status === 'PENDING_APPROVAL') $statusClass = 'st-pending-approval';
    elseif ($status === 'PENDING') $statusClass = 'st-pending';
    elseif ($status === 'APPROVED') $statusClass = 'st-approved';
    elseif ($status === 'PARTIAL') $statusClass = 'st-partial';
    elseif ($status === 'REJECTED') $statusClass = 'st-rejected';
    elseif ($status === 'CANCELED') $statusClass = 'st-canceled';
    elseif ($status === 'CLOSED') $statusClass = 'st-closed';

    // Role Logic
    $role = ($user && isset($user->role)) ? strtoupper($user->role) : 'USER';
    $isProcessor = ($role !== 'USER'); 
    $isOwner = ($hdr && $user && isset($hdr->user_id) && isset($user->id) && $hdr->user_id == $user->id);
    $isSuperAdmin = ($role === 'SUPER_ADMIN');

    // SCOPE LOGIC VARIABLES (Dari Controller)
    $isMixedRequest = isset($isMixedRequest) ? $isMixedRequest : false;
    $mineTotalQty   = isset($mineTotalQty) ? $mineTotalQty : 0;
    $canProcessAny  = isset($canProcessAny) ? $canProcessAny : false;
    $allowedItemIds = isset($allowedItemIds) ? $allowedItemIds : null;
    
    // --- SMART BUTTON LOGIC ---
    $showApproveBtn = false;
    $showProcessMyItemBtn = false;
    $showCreateBonStandard = false;

    // --- APPROVER (ATASAN) BUTTON LOGIC ---
    $showApproverApproveBtn = false;
    $isDesignatedApprover = ($hdr && $hdr->approver_id && $user->id == $hdr->approver_id);
    if ($status === 'PENDING_APPROVAL' && ($isDesignatedApprover || $isSuperAdmin)) {
        $showApproverApproveBtn = true;
    }

    // Catatan: Tombol manual Admin dinonaktifkan karena BON dibuat otomatis setelah persetujuan Atasan.
    /*
    // Jika saya Admin & Bukan Pembuat Request
    $isAllowedApprover = (!$hdr->approver_id || $user->id == $hdr->approver_id || $isSuperAdmin);

    if ($isProcessor && !$isOwner && $isAllowedApprover) {
        
        // KONDISI 1: STATUS OPEN
        if ($status === 'OPEN') {
            if ($isSuperAdmin) {
                // Super Admin selalu bisa approve all
                $showApproveBtn = true;
            } else {
                // Admin Biasa
                if ($isMixedRequest) {
                    // Jika CAMPURAN -> Approve Header MATI, Ganti jadi "Proses Item Saya"
                    $showProcessMyItemBtn = true;
                } else {
                    // Jika MURNI punya saya -> Approve Header NYALA
                    if ($mineTotalQty > 0) {
                        $showApproveBtn = true;
                    }
                }
            }
        }
        // KONDISI 2: STATUS APPROVED / PARTIAL
        elseif (in_array($status, array('APPROVED', 'PARTIAL'))) {
            // Cek apakah masih ada sisa item SAYA yang belum dibuat BON
            if ($canProcessAny) {
                $showCreateBonStandard = true;
            }
        }
    }
    */

    // Totals untuk Ringkasan
    $totalItems = 0;
    $totalQtyReq = 0;
    $totalProcessedFinal = 0;
    
    if($details) {
        $totalItems = $details->count();
        // FIX: Sum float
        $totalQtyReq = $details->sum('quantity');
        $totalProcessedFinal = $details->sum('processed_qty');
    }
    
    $totalRemainFinal = max(0, $totalQtyReq - $totalProcessedFinal);

    // Meta Info
    $reqNumber = ($hdr && isset($hdr->request_number)) ? $hdr->request_number : '-';
    $reqDate = ($hdr && isset($hdr->date)) ? date('d/m/Y', strtotime($hdr->date)) : '-';
    $deptName = ($hdr && isset($hdr->department)) ? $hdr->department->name : '-';
    
    // Logic Clean Notes (FIX PHP 5.6)
    $rawNotes = ($hdr && isset($hdr->notes)) ? $hdr->notes : '';
    $cleanNotes = preg_replace('/\[System\]:.*?(Bisa Edit\)\.|Status kembali OPEN\.)\s*/s', '', $rawNotes);
    $notes = trim($cleanNotes) !== '' ? trim($cleanNotes) : '-';
    
    // NEW: Flash data untuk insufficient items
    $insufficientItems = session('insufficientItems');
    $approvedCount = session('approvedCount');
    $insufficientCount = session('insufficientCount');
@endphp

<style>
    /* UI VARIABLES */
    :root {
        --bg: #f5f7fb; --card: #ffffff; --text: #0f172a; --muted: #64748b; --line: #e2e8f0;
        --primary: #2563eb; --primary-hover: #1d4ed8;
        --success: #16a34a; --warning: #f59e0b; --danger: #ef4444;
        --radius: 14px; --shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
    }

    .rq-page { padding: 18px 26px 30px; }
    .rq-topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 14px; }
    .rq-titlewrap { display: flex; align-items: flex-start; gap: 14px; min-width: 0; }
    .rq-icon { width: 44px; height: 44px; border-radius: 12px; background: #eaf2ff; display: inline-flex; align-items: center; justify-content: center; color: var(--primary); flex: 0 0 auto; border: 1px solid #dbeafe; }
    .rq-title h1 { font-size: 22px; font-weight: 800; color: var(--text); margin: 0; line-height: 1.2; }
    .rq-meta { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px; }
    
    .pill { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #0f172a; padding: 7px 10px; border-radius: 999px; background: #ffffff; border: 1px solid var(--line); box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03); white-space: nowrap; }
    .pill .dot { width: 7px; height: 7px; border-radius: 50%; background: #94a3b8; display: inline-block; }
    .st-pending-approval .dot { background: #f97316; animation: pulse-dot 1.5s ease-in-out infinite; }
    @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    .st-open .dot { background: var(--warning); }
    .st-approved .dot { background: var(--success); }
    .st-partial .dot { background: #0ea5e9; }
    .st-rejected .dot { background: var(--danger); }
    .st-canceled .dot { background: #94a3b8; }
    .st-closed .dot { background: #334155; }

    /* Approver Button Style */
    .btnx-approve-atasan { background: #f97316; color: #fff; border-color: #ea580c; box-shadow: 0 4px 10px rgba(249, 115, 22, 0.25); }
    .btnx-approve-atasan:hover { background: #ea580c; transform: translateY(-1px); }

    .rq-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
    .btnx { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 800; border: 1px solid transparent; cursor: pointer; text-decoration: none; user-select: none; transition: all .18s ease; white-space: nowrap; }
    .btnx-ghost { background: #ffffff; color: var(--text); border-color: var(--line); }
    .btnx-ghost:hover { background: #f8fafc; border-color: #cbd5e1; }
    .btnx-primary { background: var(--primary); color: #fff; border-color: #1d4ed8; }
    .btnx-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btnx-success { background: #16a34a; color: #fff; border-color: #15803d; }
    .btnx-success:hover { background: #15803d; transform: translateY(-1px); }
    .btnx-danger { background: #ef4444; color: #fff; border-color: #dc2626; }
    .btnx-danger:hover { background: #dc2626; transform: translateY(-1px); }
    
    /* Tombol Spesial "Proses Item Saya" */
    .btnx-special { background: #4f46e5; color: #fff; border-color: #4338ca; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2); }
    .btnx-special:hover { background: #4338ca; transform: translateY(-1px); }

    .rq-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 16px; }
    .rq-cardhead { padding: 14px 18px; border-bottom: 1px solid var(--line); background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .rq-cardhead .ttl { display: inline-flex; align-items: center; gap: 10px; font-weight: 900; color: var(--text); font-size: 13px; letter-spacing: 0.2px; }
    .rq-cardbody { padding: 16px 18px 18px; }
    
    .rq-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 12px; }
    .rq-box { border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; background: #fff; }
    .rq-box .lbl { font-size: 11px; font-weight: 900; color: #64748b; letter-spacing: 0.6px; text-transform: uppercase; margin-bottom: 6px; }
    .rq-box .val { color: var(--text); font-weight: 900; font-size: 14px; line-height: 1.3; }
    .rq-kpis { margin-top: 12px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .kpi { border: 1px solid var(--line); background: #fff; border-radius: 12px; padding: 12px 12px; }
    .kpi .k { font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 7px; }
    .kpi .v { font-size: 18px; font-weight: 950; color: var(--text); }

    .rq-tablewrap { border: 1px solid var(--line); border-radius: 12px; overflow: hidden; background: #fff; }
    table.rq-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .rq-table thead th { background: #f8fafc; border-bottom: 1px solid var(--line); padding: 11px 12px; font-size: 11px; color: #64748b; font-weight: 950; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; white-space: nowrap; }
    .rq-table thead th.th-center { text-align: center; }
    .rq-table tbody td { border-bottom: 1px solid #f1f5f9; padding: 12px 12px; vertical-align: middle; color: #0f172a; font-weight: 700; }
    .text-center { text-align: center !important; }
    .rq-table tbody tr:last-child td { border-bottom: none; }

    /* --- LOGIC SCOPE VISUAL --- */
    .row-disabled td { background: #f9fafb; color: #94a3b8 !important; }
    .row-disabled .codepill { background: #e2e8f0; color: #64748b; border-color: #cbd5e1; }
    .row-disabled .numtag { opacity: 0.6; }
    .row-disabled .lock-icon { display:inline-block; }
    
    .lock-icon { display:none; color: #cbd5e1; margin-left:6px; font-size:12px; }

    /* HIGHLIGHT WARNA HIJAU HALUS UNTUK ITEM SCOPE SAYA */
    .row-highlight td { background: #f0fdf4 !important; color: #166534 !important; }
    .row-highlight .codepill { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }

    .codepill { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 950; }
    .subtxt { margin-top: 4px; color: var(--muted); font-size: 11px; font-weight: 800; }
    .numtag { display: inline-flex; min-width: 34px; justify-content: center; padding: 4px 10px; border-radius: 999px; border: 1px solid var(--line); background: #fff; font-weight: 950; font-size: 11px; color: #0f172a; }
    .tag-blue { border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8; }
    .tag-red { border-color: #fecaca; background: #fff1f2; color: #b91c1c; }
    .tag-amber { border-color: #fde68a; background: #fffbeb; color: #92400e; }
    .tag-green { border-color: #bbf7d0; background: #f0fdf4; color: #166534; }
    .tag-gray { background: #f1f5f9; color: #64748b; border:none; }
    
    .stok-badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 800; text-transform: uppercase; margin-left: 6px; }
    .stok-ok { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .stok-low { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .rq-note { color: var(--muted); font-size: 12px; font-weight: 700; }
    .rq-divider { height: 1px; background: var(--line); margin: 14px 0; }

    /* === NEW: WARNING PANEL (Clean - No Emoji) === */
    .warning-panel { 
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); 
        border: 1px solid #fcd34d; 
        border-radius: 12px; 
        padding: 16px 20px; 
        margin-bottom: 16px;
    }
    .warning-panel .panel-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }
    .warning-panel .panel-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d97706;
        font-size: 18px;
    }
    .warning-panel .panel-title {
        font-size: 15px;
        font-weight: 900;
        color: #92400e;
        margin: 0;
    }
    .warning-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 12px;
    }
    .stat-box {
        background: white;
        border-radius: 10px;
        padding: 12px 14px;
        border: 1px solid #fde68a;
    }
    .stat-box .stat-label {
        font-size: 10px;
        font-weight: 900;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    .stat-box .stat-value {
        font-size: 20px;
        font-weight: 950;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .stat-box.success .stat-value { color: #16a34a; }
    .stat-box.warning .stat-value { color: #d97706; }
    
    .insufficient-detail-toggle {
        background: white;
        border: 1px solid #fcd34d;
        border-radius: 8px;
        padding: 10px 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all .2s ease;
    }
    .insufficient-detail-toggle:hover {
        background: #fffbeb;
        border-color: #fbbf24;
    }
    .insufficient-detail-toggle .toggle-text {
        font-size: 12px;
        font-weight: 800;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .insufficient-detail-toggle .toggle-icon {
        color: #fbbf24;
        transition: transform .2s ease;
    }
    .insufficient-detail-toggle.active .toggle-icon {
        transform: rotate(180deg);
    }
    
    .insufficient-items-list {
        margin-top: 12px;
        background: white;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 14px 16px;
        display: none;
    }
    .insufficient-items-list.show {
        display: block;
    }
    .insufficient-item {
        padding: 8px 0;
        border-bottom: 1px solid #fee2e2;
        font-size: 12px;
        color: #991b1b;
        font-weight: 700;
    }
    .insufficient-item:last-child {
        border-bottom: none;
    }
    .insufficient-item .item-name {
        font-weight: 900;
        color: #7f1d1d;
    }
    .insufficient-item .item-stats {
        margin-top: 4px;
        color: #b91c1c;
        font-size: 11px;
    }

    /* MODAL */
    .modal-clean { border-radius: 16px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; }
    .modal-head-clean { padding: 24px 24px 0; background: white; border: none; }
    .modal-icon-box { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
    .modal-icon-box.primary { background: #e0e7ff; color: var(--primary); }
    .modal-icon-box.success { background: #dcfce7; color: var(--success); }
    .modal-icon-box.danger { background: #fee2e2; color: var(--danger); }
    .modal-icon-box.warn { background: #fffbeb; color: #d97706; }
    .modal-body-clean { padding: 0 30px 24px; text-align: center; }
    .modal-title-clean { font-size: 18px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
    .modal-desc-clean { font-size: 14px; color: var(--muted); line-height: 1.5; }
    .modal-foot-clean { background: #f8fafc; padding: 16px 24px; border-top: 1px solid var(--line); display: flex; justify-content: center; gap: 12px; }
    .btn-modal { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; border: 1px solid var(--line); cursor: pointer; background: white; color: var(--muted); }
    .btn-modal:hover { background: #f1f5f9; }
    .btn-modal.confirm { border: none; color: white; }
    .btn-modal.confirm.primary { background: var(--primary); }
    .btn-modal.confirm.primary:hover { background: var(--primary-hover); }
    .btn-modal.confirm.success { background: var(--success); }
    .btn-modal.confirm.danger { background: var(--danger); }
    .btn-modal.confirm.warning { background: #f59e0b; color:white; }

    @media (max-width: 992px) {
        .rq-grid { grid-template-columns: 1fr; }
        .rq-kpis { grid-template-columns: repeat(2, 1fr); }
        .rq-actions { justify-content: flex-start; }
        .warning-stats { grid-template-columns: 1fr; }
    }
</style>

<div class="rq-page">
    <div class="rq-topbar">
        <div class="rq-titlewrap">
            <div class="rq-icon">
                <i class="fa fa-file-text-o"></i>
            </div>
            <div class="rq-title" style="min-width:0;">
                <h1>Detail Request</h1>

                <div class="rq-meta">
                    <div class="pill">
                        <span class="dot"></span> <span>No: {{ $reqNumber }}</span>
                    </div>
                    <div class="pill">
                        <span class="dot"></span> <span>Tanggal: {{ $reqDate }}</span>
                    </div>
                    <div class="pill {{ $statusClass }}">
                        <span class="dot"></span> <span>Status: {{ $status === 'PENDING' ? 'PENDING GUDANG' : $status }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rq-actions">
            <a href="{{ route('requests.index') }}" class="btnx btnx-ghost">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>

            {{-- 0. APPROVER (ATASAN) APPROVE/REJECT - hanya saat PENDING_APPROVAL --}}
            @if ($hdr && $showApproverApproveBtn)
                <button type="button" class="btnx btnx-approve-atasan" onclick="openModal('modalApproverApprove')">
                    <i class="fa fa-check-circle"></i> Setujui (Atasan)
                </button>
                <button type="button" class="btnx btnx-danger" onclick="openModal('modalApproverReject')">
                    <i class="fa fa-times-circle"></i> Tolak (Atasan)
                </button>
            @endif

            {{-- 1. LOGIC APPROVE HEADER (Hanya jika MURNI milik saya / SuperAdmin) --}}
            @if ($hdr && $showApproveBtn)
                <button type="button" class="btnx btnx-success" onclick="openModal('modalApprove')">
                    <i class="fa fa-check"></i> Approve
                </button>
                <button type="button" class="btnx btnx-danger" onclick="openModal('modalReject')">
                    <i class="fa fa-times"></i> Reject
                </button>
            @endif
            
            {{-- 2. LOGIC MIXED REQUEST (Muncul Tombol Proses Item Saya) --}}
            @if ($hdr && $showProcessMyItemBtn)
                {{-- Ini akan mentrigger Form Create BON --}}
                <button type="button" class="btnx btnx-special" onclick="openModal('modalProcessMyItem')">
                    <i class="fa fa-cubes"></i> Proses Item Saya
                </button>
            @endif

            {{-- 3. UNAPPROVE (Jika sudah approved tapi belum bon issued) --}}
            @if ($isProcessor && $status === 'APPROVED' && $totalProcessedFinal <= 0)
                <button type="button" class="btnx btnx-ghost" style="color: #d97706; border-color: #fcd34d;"
                        onclick="openModal('modalUnapprove')">
                    <i class="fa fa-undo"></i> Batal Approve
                </button>
            @endif

            {{-- 4. BUAT BON STANDAR (Setelah Approved/Partial) --}}
            @if ($hdr && $showCreateBonStandard)
                <button type="button" class="btnx btnx-primary" onclick="openModal('modalCreateBon')">
                    <i class="fa fa-file-text"></i> Buat BON
                </button>
            @endif

            {{-- 5. EDIT PARSIAL (Super Admin Only - APPROVED/PARTIAL) --}}
            @if ($hdr && $isSuperAdmin && in_array($status, array('APPROVED', 'PARTIAL')))
                <a href="{{ route('requests.editPartial', $hdr->id) }}" class="btnx" style="background:#fff7ed; color:#b45309; border-color:#fcd34d;">
                    <i class="fa fa-pencil"></i> Edit Item
                </a>
            @endif
        </div>
    </div>

    {{-- === NEW: WARNING PANEL (Only show if insufficientItems exist from flash) === --}}
    @if($insufficientItems && count($insufficientItems) > 0)
        <div class="warning-panel">
            <div class="panel-header">
                <div class="panel-icon">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <h3 class="panel-title">Perhatian: Stok Tidak Mencukupi</h3>
            </div>
            
            <div class="warning-stats">
                <div class="stat-box success">
                    <div class="stat-label">Item Stok Tersedia</div>
                    <div class="stat-value">
                        {{ $approvedCount }} item
                    </div>
                </div>
                <div class="stat-box warning">
                    <div class="stat-label">Item Stok Kurang</div>
                    <div class="stat-value">
                        {{ $insufficientCount }} item
                    </div>
                </div>
            </div>
            
            <div class="insufficient-detail-toggle" onclick="toggleInsufficientDetail()">
                <div class="toggle-text">
                    <i class="fa fa-list"></i>
                    <span>Lihat Detail Item dengan Stok Kurang</span>
                </div>
                <i class="fa fa-chevron-down toggle-icon" id="toggle-icon"></i>
            </div>
            
            <div class="insufficient-items-list" id="insufficient-items-list">
                <div style="font-size:11px; font-weight:900; color:#991b1b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px;">
                    Daftar Item yang Perlu LPB:
                </div>
                @foreach($insufficientItems as $item)
                    <div class="insufficient-item">
                        <div class="item-name">{{ $item['name'] }}</div>
                        <div class="item-stats">
                            Diminta: {{ (float)$item['requested'] }} | 
                            Stok Tersedia: {{ (float)$item['stock'] }} | 
                            Kurang: {{ (float)$item['deficit'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    {{-- ALERT KHUSUS MIXED REQUEST --}}
    @if($isMixedRequest && $status === 'OPEN' && $isProcessor)
        <div class="alert" style="background:#eff6ff; color:#1e40af; border:1px solid #dbeafe; border-radius:10px; margin-bottom:15px; font-size:13px; display:flex; align-items:center; gap:10px;">
            <i class="fa fa-info-circle" style="font-size:18px;"></i>
            <div>
                <strong>Request Campuran:</strong> Dokumen ini berisi item dari kategori lain. Anda hanya dapat memproses item sesuai wewenang Anda.
            </div>
        </div>
    @endif

    <div class="rq-card">
        <div class="rq-cardhead">
            <div class="ttl">
                <i class="fa fa-info-circle" style="color:#2563eb;"></i>
                <span>Ringkasan</span>
            </div>
            <div class="rq-note">
                @if($status === 'PENDING_APPROVAL') <span style="color:#f97316; font-weight:900;"><i class="fa fa-clock-o"></i> Menunggu persetujuan atasan</span>
                @elseif($status === 'OPEN') Request belum disetujui.
                @elseif(in_array($status, array('APPROVED','PARTIAL')) && $totalRemainFinal > 0) Masih ada sisa item.
                @elseif($status === 'CLOSED') Selesai - Semua item sudah diproses.
                @endif
            </div>
        </div>

        <div class="rq-cardbody">
            <div class="rq-grid" style="grid-template-columns: repeat(4, 1fr);">
                <div class="rq-box">
                    <div class="lbl">Departemen</div>
                    <div class="val">{{ $deptName }}</div>
                </div>
                <div class="rq-box">
                    <div class="lbl">Divisi / Bagian</div>
                    <div class="val">{{ $hdr->division_name ?: '-' }}</div>
                </div>
                <div class="rq-box">
                    <div class="lbl">Approval Oleh</div>
                    <div class="val">{{ $hdr->approver ? $hdr->approver->name : '-' }}</div>
                </div>
                <div class="rq-box">
                    <div class="lbl">Status Approval Atasan</div>
                    <div class="val">
                        @if($hdr->approved_by_approver_at)
                            <span style="color:#16a34a; font-weight:900;"><i class="fa fa-check-circle"></i> Disetujui ({{ $hdr->approved_by_approver_at->format('d/m/Y H:i') }})</span>
                        @elseif($status === 'REJECTED')
                            <span style="color:#ef4444; font-weight:900;"><i class="fa fa-times-circle"></i> Ditolak / Reject</span>
                        @else
                            <span style="color:#f97316; font-weight:900;"><i class="fa fa-clock-o"></i> Menunggu</span>
                        @endif
                    </div>
                </div>
                
            </div>
            <div class="rq-kpis">
                <div class="kpi">
                    <div class="k">Total Item</div>
                    <div class="v">{{ (int)$totalItems }}</div>
                </div>
                <div class="kpi">
                    <div class="k">Total Qty Request</div>
                    {{-- FIX: Float Display --}}
                    <div class="v">{{ (float)$totalQtyReq }}</div>
                </div>
                <div class="kpi">
                    <div class="k">Sudah Diproses</div>
                    {{-- FIX: Float Display --}}
                    <div class="v">{{ (float)$totalProcessedFinal }}</div>
                </div>
                <div class="kpi">
                    <div class="k">Sisa</div>
                    {{-- FIX: Float Display --}}
                    <div class="v">{{ (float)$totalRemainFinal }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="rq-card">
        <div class="rq-cardhead">
            <div class="ttl">
                <i class="fa fa-list" style="color:#2563eb;"></i>
                <span>Daftar Item</span>
            </div>
        </div>

        <div class="rq-cardbody">
            <div class="rq-tablewrap">
                <table class="rq-table">
                    <thead>
                        <tr>
                            <th style="width:60px;">No</th>
                            <th style="width:110px;">Kode</th>
                            <th>Nama Item</th>
                            <th style="width:100px;" class="th-center">Unit</th>
                            
                            @if($isProcessor)
                                <th style="width:100px;" class="th-center">Stok Gudang</th>
                            @endif

                            <th style="width:120px;" class="th-center">Qty Req</th>
                            <th style="width:140px;" class="th-center">Diproses</th>
                            <th style="width:120px;" class="th-center">Sisa</th>
                            <th style="width:200px;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($details && count($details) > 0)
                            @foreach($details as $i => $d)
                                @php
                                    $itemCode = (isset($d->item) && isset($d->item->code)) ? $d->item->code : '-';
                                    $itemName = (isset($d->item) && isset($d->item->name)) ? $d->item->name : '-';
                                    $unit = (isset($d->item) && isset($d->item->unit)) ? $d->item->unit : '-';
                                    
                                    // FIX: Float
                                    $currentStock = (isset($d->item) && isset($d->item->current_stock)) ? (float)$d->item->current_stock : 0;
                                    $qtyReq = (float) $d->quantity;
                                    $doneFinal = (float) $d->processed_qty;
                                    $remain = max(0, $qtyReq - $doneFinal);
                                    
                                    // FIX PHP 5.6: No '??'
                                    $remarks = (isset($d->remarks) && $d->remarks !== null) ? $d->remarks : '-';
                                    $remainClass = ($remain <= 0) ? 'tag-green' : 'tag-amber';

                                    // STOK CHECK
                                    $isStockEnough = $currentStock >= $qtyReq;
                                    $stockClass = $isStockEnough ? 'stok-ok' : 'stok-low';
                                    $stockLabel = $isStockEnough ? 'Cukup' : 'Kurang';
                                    
                                    // VISUAL JIKA STOK HABIS (0)
                                    if ($currentStock <= 0) {
                                        $stockClass = 'stok-low';
                                        $stockLabel = 'KOSONG';
                                    }

                                    // SCOPE VISUAL LOGIC
                                    $rowClass = '';
                                    
                                    if ($isProcessor && !$isSuperAdmin && is_array($allowedItemIds)) {
                                        if (in_array((int)$d->item_id, $allowedItemIds)) {
                                            $rowClass = 'row-highlight'; // FIX: Warna hijau halus
                                        } else {
                                            $rowClass = 'row-disabled'; // Warna abu
                                        }
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <span class="codepill">{{ $itemCode }}</span>
                                        @if($rowClass == 'row-disabled')
                                            <i class="fa fa-lock lock-icon" title="Di luar wewenang Anda"></i>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="font-weight:950;">{{ $itemName }}</div>
                                        @if(isset($d->item) && isset($d->item->category) && isset($d->item->category->name))
                                            <div class="subtxt">Kategori: {{ $d->item->category->name }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $unit }}</td>

                                    @if($isProcessor)
                                        <td class="text-center">
                                            {{-- FIX: Float Display --}}
                                            <span class="numtag tag-gray" style="font-weight:bold;">{{ (float)$currentStock }}</span>
                                            
                                            {{-- FIX: Tampilkan label stok hanya di row aktif & status OPEN/APPROVED --}}
                                            @if(in_array($status, ['OPEN', 'APPROVED']) && $rowClass !== 'row-disabled') 
                                                <div class="stok-badge {{ $stockClass }}">{{ $stockLabel }}</div>
                                            @endif
                                        </td>
                                    @endif

                                    <td class="text-center"><span class="numtag tag-blue">{{ (float)$qtyReq }}</span></td>
                                    <td class="text-center"><span class="numtag tag-red">{{ (float)$doneFinal }}</span></td>
                                    <td class="text-center"><span class="numtag {{ $remainClass }}">{{ (float)$remain }}</span></td>
                                    <td>{{ $remarks }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="9" style="padding:18px; color:#64748b; font-weight:800; text-align:center;">
                                    Tidak ada detail item pada request ini.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            <div class="rq-divider"></div>
            
            {{-- FIX: TAMPILKAN BON TERKAIT UNTUK USER JUGA (TAPI VIEW ONLY) --}}
            @if(isset($relatedBons) && count($relatedBons) > 0)
                <div class="rq-note" style="margin-bottom:10px;">BON terkait:</div>
                <div class="rq-tablewrap">
                    <table class="rq-table">
                        <thead>
                            <tr>
                                <th style="width:70px;">No</th>
                                <th>No BON</th>
                                <th style="width:140px;">Tanggal</th>
                                <th style="width:120px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($relatedBons as $bi => $bon)
                                <tr>
                                    <td>{{ $bi + 1 }}</td>
                                    <td>
                                        @if($role !== 'USER')
                                            {{-- ADMIN: BISA KLIK --}}
                                            <a href="{{ route('bons.show', $bon->id) }}" style="color:#2563eb; font-weight:900; text-decoration:none;">
                                                {{ $bon->bon_number }}
                                            </a>
                                        @else
                                            {{-- USER: VIEW ONLY (TEXT) SUPAYA GAK AMNESIA --}}
                                            <span style="color:#0f172a; font-weight:900;">
                                                {{ $bon->bon_number }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ date('d/m/Y', strtotime($bon->date)) }}</td>
                                    <td>{{ strtoupper($bon->status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- HIDDEN FORMS & MODALS --}}
@if ($hdr)
    <form id="form-approve" action="{{ route('requests.approve', $hdr->id) }}" method="POST" style="display:none;">{{ csrf_field() }}</form>
    <form id="form-reject" action="{{ route('requests.reject', $hdr->id) }}" method="POST" style="display:none;">{{ csrf_field() }}</form>
    <form id="form-create-bon" action="{{ route('requests.createBon', $hdr->id) }}" method="POST" style="display:none;">{{ csrf_field() }}</form>
    <form id="form-unapprove" action="{{ route('requests.unapprove', $hdr->id) }}" method="POST" style="display:none;">{{ csrf_field() }}</form>
    <form id="form-approver-approve" action="{{ route('requests.approverApprove', $hdr->id) }}" method="POST" style="display:none;">{{ csrf_field() }}</form>
    <form id="form-approver-reject" action="{{ route('requests.approverReject', $hdr->id) }}" method="POST" style="display:none;">{{ csrf_field() }}</form>
@endif

<div class="modal fade" id="modalGeneric" tabindex="-1" role="dialog" style="z-index: 10000;">
    <div class="modal-dialog modal-sm" role="document" style="margin-top: 10%;">
        <div class="modal-content modal-clean">
            <div class="modal-head-clean" id="gen-icon-wrapper"></div>
            <div class="modal-body-clean">
                <h3 class="modal-title-clean" id="gen-title"></h3>
                <p class="modal-desc-clean" id="gen-desc"></p>
            </div>
            <div class="modal-foot-clean">
                <button type="button" class="btn-modal" data-dismiss="modal">Batal</button>
                <button type="button" class="btn-modal confirm" id="btn-gen-confirm">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // NEW: Toggle insufficient items detail
    function toggleInsufficientDetail() {
        var list = document.getElementById('insufficient-items-list');
        var icon = document.getElementById('toggle-icon');
        var toggle = document.querySelector('.insufficient-detail-toggle');
        
        if (list.classList.contains('show')) {
            list.classList.remove('show');
            toggle.classList.remove('active');
        } else {
            list.classList.add('show');
            toggle.classList.add('active');
        }
    }

    function openModal(type) {
        var modal = $('#modalGeneric');
        var iconClass = '', iconContent = '', title = '', desc = '', btnClass = '', submitId = '';

        if (type === 'modalApprove') {
            iconClass = 'success';
            iconContent = '<i class="fa fa-check text-success" style="font-size:24px;"></i>';
            title = 'Approve Request?';
            desc = 'Status request akan berubah menjadi APPROVED. Silakan buat BON untuk memproses barang.';
            btnClass = 'btn-modal confirm success';
            submitId = '#form-approve';
        } else if (type === 'modalReject') {
            iconClass = 'danger';
            iconContent = '<i class="fa fa-times text-danger" style="font-size:24px;"></i>';
            title = 'Tolak Request?';
            desc = 'Status request akan berubah menjadi REJECTED. Seluruh request akan ditolak.';
            btnClass = 'btn-modal confirm danger';
            submitId = '#form-reject';
        } else if (type === 'modalCreateBon') {
            iconClass = 'primary';
            iconContent = '<i class="fa fa-file-text text-primary" style="font-size:24px;"></i>';
            title = 'Buat BON?';
            desc = 'Sistem akan membuat BON untuk item yang stoknya tersedia.';
            btnClass = 'btn-modal confirm primary';
            submitId = '#form-create-bon';
        } else if (type === 'modalProcessMyItem') {
            iconClass = 'primary';
            iconContent = '<i class="fa fa-cubes text-primary" style="font-size:24px;"></i>';
            title = 'Proses Item Saya?';
            desc = 'Sistem akan membuat BON untuk item dalam wewenang Anda yang stoknya tersedia.';
            btnClass = 'btn-modal confirm primary';
            submitId = '#form-create-bon'; 
        } else if (type === 'modalUnapprove') {
            iconClass = 'warn';
            iconContent = '<i class="fa fa-undo text-warning" style="font-size:24px;"></i>';
            title = 'Batalkan Approval?';
            desc = 'Status akan kembali menjadi OPEN. Pastikan tidak ada BON aktif yang terhubung.';
            btnClass = 'btn-modal confirm warning';
            submitId = '#form-unapprove';
        } else if (type === 'modalApproverApprove') {
            iconClass = 'success';
            iconContent = '<i class="fa fa-check-circle text-success" style="font-size:24px;"></i>';
            title = 'Setujui Request Ini?';
            desc = 'Anda menyetujui permintaan ini sebagai atasan. Request akan diteruskan ke Admin untuk diproses.';
            btnClass = 'btn-modal confirm success';
            submitId = '#form-approver-approve';
        } else if (type === 'modalApproverReject') {
            iconClass = 'danger';
            iconContent = '<i class="fa fa-times-circle text-danger" style="font-size:24px;"></i>';
            title = 'Tolak Request Ini?';
            desc = 'Anda menolak permintaan ini sebagai atasan. Status request akan berubah menjadi REJECTED.';
            btnClass = 'btn-modal confirm danger';
            submitId = '#form-approver-reject';
        }

        $('#gen-icon-wrapper').html('<div class="modal-icon-box ' + iconClass + '">' + iconContent + '</div>');
        $('#gen-title').text(title);
        $('#gen-desc').text(desc);
        
        $('#btn-gen-confirm').attr('class', btnClass).off('click').on('click', function(){
            $(submitId).submit();
        });

        modal.modal('show');
    }
</script>
@endsection
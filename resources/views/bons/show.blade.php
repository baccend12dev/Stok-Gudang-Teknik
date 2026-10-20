@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL DETAIL BON (PROSES & APPROVAL) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>POSISI VITAL:</strong> Halaman ini adalah "Meja Eksekusi". Di sinilah stok fisik benar-benar keluar dan budget departemen terpotong.
    </div>

    <h4 class="help-h"><i class="fa fa-gavel text-primary"></i> Tahap 1: Validasi & Approval (Status PENDING)</h4>
    <p class="help-p">
        Saat status <strong>PENDING</strong>, tugas Admin adalah menentukan <strong>Jumlah Disetujui</strong>.
    </p>
    <ul class="help-list">
        <li>
            <strong>Kasus Stok Kurang:</strong> Jika User minta 100 tapi stok gudang cuma 80, ubah angka di kolom "Setuju" menjadi 80 (atau kurang). <br><em>Sistem akan memberi border merah jika input melebihi stok.</em>
        </li>
        <li>
            <strong>Kasus Over Budget:</strong> Jika muncul peringatan <span style="color:#B45309; font-weight:700;"><i class="fa fa-exclamation-triangle"></i> Over Limit</span>, artinya permintaan ini melebihi jatah bulanan departemen. 
            <br>Admin memiliki kuasa untuk melakukan <strong>Override (Persetujuan Khusus)</strong> saat klik tombol Approve.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-handshake-o text-primary"></i> Tahap 2: Penyerahan Barang (Status ISSUED)</h4>
    <p class="help-p">
        Jangan klik tombol <strong>"Issue Barang"</strong> sebelum barang fisik berpindah tangan! Ikuti urutan SOP ini:
    </p>
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:10px; border-radius:8px; font-size:13px; color:#166534; margin-bottom:15px;">
        <strong>1. Picking:</strong> Siapkan barang di gudang sesuai "Qty Setuju".<br>
        <strong>2. Cetak:</strong> Klik tombol <i class="fa fa-print"></i> Cetak Tanda Terima.<br>
        <strong>3. Serah Terima:</strong> Minta User tanda tangan di kertas.<br>
        <strong>4. Finalisasi:</strong> Baru klik tombol <strong>ISSUED</strong> di sistem.
    </div>

    <h4 class="help-h"><i class="fa fa-undo text-danger"></i> Darurat / Salah Input</h4>
    <p class="help-p">
        Jika sudah terlanjur Issued tapi salah, gunakan tombol <strong>Rollback</strong> di pojok kanan bawah. Ini akan mengembalikan stok ke gudang dan status kembali ke Pending.
    </p>
@endsection

@section('content')
<style>
    /* --- 1. DESIGN TOKENS (Sistem Warna & Variabel) --- */
    :root {
        --primary: #4F46E5;       /* Indigo Modern */
        --primary-dark: #4338CA;
        --secondary: #64748B;     /* Slate Gray */
        --success: #10B981;       /* Emerald */
        --danger: #EF4444;        /* Rose Red */
        --warning: #F59E0B;       /* Amber */
        --dark: #111827;          /* Deep Black */
        --surface: #FFFFFF;       /* White */
        --background: #F1F5F9;    /* Cloud Gray */
        --border: #E2E8F0;
        
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        --radius: 12px;
        --font-main: 'Inter', system-ui, sans-serif;
    }

    /* --- 2. LAYOUT RESET --- */
    body { background-color: var(--background); color: var(--dark); font-family: var(--font-main); }
    .content-header { display: none; } /* Hide default AdminLTE header */
    .app-container { padding-bottom: 100px; /* Space for floating buttons */ }

    /* --- 3. PAGE HEADER & NAV --- */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 24px 0;
        margin-bottom: 20px;
    }
    .header-title h1 {
        font-size: 24px;
        font-weight: 800;
        color: var(--dark);
        margin: 0;
        letter-spacing: -0.5px;
    }
    .header-meta {
        display: flex;
        gap: 15px;
        margin-top: 6px;
        font-size: 13px;
        color: var(--secondary);
        font-weight: 500;
    }
    .header-meta i { margin-right: 5px; color: var(--primary); }

    /* Tombol Navigasi Header (Outline Style) */
    .btn-nav {
        background: white;
        border: 1px solid var(--border);
        color: var(--dark);
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        text-decoration: none !important;
        box-shadow: var(--shadow-sm);
    }
    .btn-nav:hover { background: #F8FAFC; border-color: #CBD5E1; transform: translateY(-1px); }
    .btn-nav.primary { border-color: var(--primary); color: var(--primary); background: #EFF6FF; }
    .btn-nav.primary:hover { background: #DBEAFE; }

    /* --- 4. STEPPER (Timeline Visual) --- */
    /* --- 4. STEPPER (Timeline Visual - Compact) --- */
    .stepper-card {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 16px 24px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
        margin-bottom: 18px;
    }
    .step-list { display: flex; justify-content: space-between; position: relative; }
    .step-line {
        position: absolute; top: 14px; left: 0; right: 0; height: 3px;
        background: #F1F5F9; z-index: 0;
    }
    .step-item { position: relative; z-index: 1; text-align: center; background: white; padding: 0 10px; }
    .step-circle {
        width: 30px; height: 30px; border-radius: 50%;
        background: white; border: 3px solid #E2E8F0;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 6px; font-weight: 700; font-size: 11px; color: #94A3B8;
        transition: all 0.3s;
    }
    .step-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #94A3B8; letter-spacing: 0.5px; }
    
    /* Active & Done States */
    .step-item.active .step-circle { border-color: var(--primary); color: var(--primary); background: #EEF2FF; box-shadow: 0 0 0 4px #E0E7FF; }
    .step-item.active .step-label { color: var(--primary); }
    
    .step-item.done .step-circle { background: var(--success); border-color: var(--success); color: white; }
    .step-item.done .step-label { color: var(--success); }

    /* --- 5. HEADER INFO CARD (Clean Horizontal Strip) --- */
    .bon-header-card {
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        padding: 16px 20px;
        margin-bottom: 18px;
        box-shadow: var(--shadow-sm);
    }
    .bon-header-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    .bon-header-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .bon-h-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .bon-h-val {
        font-size: 13px;
        font-weight: 700;
        color: var(--dark);
        word-break: break-word;
    }
    @media (max-width: 900px) {
        .bon-header-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 500px) {
        .bon-header-grid { grid-template-columns: 1fr; }
    }

    /* STATUS PILLS */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.3px;
    }
    .status-pill .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    .status-pending { background: #FFF7ED; color: #C2410C; border: 1px solid #FFEDD5; }
    .status-approved { background: #EFF6FF; color: #1D4ED8; border: 1px solid #DBEAFE; }
    .status-issued { background: #ECFDF5; color: #047857; border: 1px solid #D1FAE5; }
    .status-rejected { background: #FEF2F2; color: #DC2626; border: 1px solid #FEE2E2; }
    .status-canceled { background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; }

    /* BADGES & TAGS */
    .numtag {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 800;
    }
    .numtag.tag-blue { background: #EFF6FF; color: #1D4ED8; border: 1px solid #DBEAFE; }
    .numtag.tag-green { background: #ECFDF5; color: #047857; border: 1px solid #D1FAE5; }
    .numtag.tag-red { background: #FEF2F2; color: #DC2626; border: 1px solid #FEE2E2; }

    .codepill {
        font-size: 11px;
        font-family: monospace;
        font-weight: 800;
        background: #F1F5F9;
        color: #334155;
        padding: 2px 7px;
        border-radius: 4px;
        border: 1px solid #E2E8F0;
        display: inline-block;
    }

    .stok-badge {
        display: inline-block;
        font-size: 9px;
        font-weight: 800;
        padding: 1px 6px;
        border-radius: 4px;
        text-transform: uppercase;
        margin-top: 3px;
    }
    .stok-ok { background: #DCFCE7; color: #16A34A; }
    .stok-low { background: #FEF3C7; color: #B45309; }
    .stok-zero { background: #FEE2E2; color: #B91C1C; }

    /* TABLE CARD (Full Width & Clean) */
    .table-card {
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        display: flex; flex-direction: column;
    }
    .table-card .card-head {
        background: #F8FAFC;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .table-responsive { overflow-x: auto; }
    
    /* Modern Table Styling */
    .app-table { width: 100%; border-collapse: collapse; }
    .app-table th {
        background: #F8FAFC;
        padding: 12px 18px;
        text-align: left;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        color: #475569;
        border-bottom: 1px solid var(--border);
        letter-spacing: 0.5px;
    }
    .app-table td {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        font-size: 13px;
        color: var(--dark);
    }
    .app-table tbody tr:hover { background-color: #F8FAFC; }
    .app-table tr:last-child td { border-bottom: none; }

    /* Alignment Classes */
    .text-center { text-align: center !important; }
    .text-right { text-align: right !important; }
    
    /* Highlight Rows */
    .row-warning { background-color: #FFFBEB !important; } /* Over Quota */
    .row-danger { background-color: #FEF2F2 !important; } /* Low Stock */
    .row-info-zero { background-color: #F8FAFC !important; } /* Stock 0, Input 0 - Wajar */
    .row-warn-forgot { background-color: #FFF7ED !important; border-left: 3px solid #F59E0B !important; } /* Stock > 0, Input 0 - Possibly Forgot */
    .row-zero-stock { background-color: #F8FAFC !important; } /* Approved 0 / Kosong */
    .row-zero-stock td { color: #64748B; }

    /* Badges untuk Status Item Kosong / Sebagian */
    .badge-out-of-stock {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 700;
        color: #B91C1C;
        background: #FEE2E2;
        border: 1px solid #FECACA;
        padding: 2px 8px;
        border-radius: 4px;
        margin-top: 4px;
    }
    .badge-partial-approved {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 700;
        color: #92400E;
        background: #FEF3C7;
        border: 1px solid #FDE68A;
        padding: 2px 8px;
        border-radius: 4px;
        margin-top: 4px;
    }
    .qty-zero-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-weight: 800;
        font-size: 12px;
        color: #DC2626;
        background: #FEE2E2;
        border: 1px solid #FECACA;
        padding: 4px 10px;
        border-radius: 6px;
    }

    /* Item Styling */
    .item-title { font-weight: 700; font-size: 14px; margin-bottom: 2px; white-space: normal; word-break: break-word; }
    .item-sub { font-size: 11px; color: var(--secondary); background: #F1F5F9; padding: 2px 6px; border-radius: 4px; display: inline-block; }
    
    /* 🔥 NEW: Real-time Over Limit Badge */
    .item-over-limit-badge {
        font-size: 10px;
        color: #B45309;
        font-weight: 700;
        margin-top: 4px;
        display: none; /* Hidden by default, show via JS */
    }
    .item-over-limit-badge.show {
        display: block;
    }
    
    /* Quota Visuals */
    .quota-wrap { width: 100%; min-width: 200px; }
    .quota-meta { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 5px; color: var(--secondary); }
    .quota-meta strong { color: var(--dark); }
    
    .bar-bg { width: 100%; height: 6px; background: #E2E8F0; border-radius: 99px; overflow: hidden; display: flex; }
    .bar-used { background: #94A3B8; height: 100%; }
    .bar-req { background: var(--primary); height: 100%; }
    .bar-req.danger { background: var(--danger); }
    
    .quota-res { margin-top: 5px; font-size: 11px; font-weight: 600; }

    /* Input Modern */
    .input-modern {
        background: #fff;
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 10px 14px;
        width: 100%;
        max-width: 150px;
        min-width: 110px;
        display: inline-block;
        text-align: center; /* Center Text inside input */
        font-weight: 700;
        font-size: 14px;
        color: var(--dark);
        transition: all 0.2s;
    }
    .input-modern:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    .input-modern.error { border-color: var(--danger); background: #FEF2F2; color: var(--danger); }

    /* --- 7. SUMMARY FOOTER (Moved INSIDE Card) --- */
    .table-summary-panel {
        background: #F8FAFC;
        border-top: 1px solid var(--border);
        padding: 20px 30px;
        display: flex;
        justify-content: flex-end; /* Align Right */
        gap: 40px;
    }
    .sum-item { text-align: right; }
    .sum-label { display: block; font-size: 11px; font-weight: 700; color: var(--secondary); text-transform: uppercase; margin-bottom: 4px; }
    .sum-val { font-size: 22px; font-weight: 800; color: var(--dark); letter-spacing: -0.5px; }
    .sum-val.blue { color: var(--primary); }

    /* --- 8. FLOATING ACTION DOCK (The Solution) --- */
    .floating-dock {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        padding: 10px;
        border-radius: 16px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0,0,0,0.05);
        display: flex;
        gap: 10px;
        z-index: 9999;
        transition: all 0.3s ease;
    }
    .floating-dock:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15); }

    /* Action Buttons */
    .btn-action {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        border: none;
        cursor: pointer;
        display: flex; align-items: center; gap: 8px;
        transition: transform 0.1s;
    }
    .btn-action:active { transform: scale(0.95); }
    
    .btn-approve { background: var(--primary); color: white; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3); }
    .btn-approve:hover { background: var(--primary-dark); }
    
    .btn-issue { background: var(--success); color: white; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3); }
    .btn-issue:hover { background: #059669; }
    
    .btn-reject { background: white; color: var(--danger); border: 1px solid #FECACA; }
    .btn-reject:hover { background: #FEF2F2; }

    .btn-rollback { background: var(--warning); color: white; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3); }
    .btn-rollback:hover { background: #D97706; }

    /* --- 9. CUSTOM MODALS (Clean UI) - FIXED WIDTH --- */
    .modal-clean { border-radius: 16px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; }
    
    /* FIX: Modal Dialog Width - Dari modal-sm jadi lebih lebar */
    .modal-dialog-wider {
        max-width: 420px !important; /* 🔥 Lebih lebar untuk accommodate summary box */
        width: 90% !important;
        margin: 8% auto !important;
    }
    
    .modal-head-clean { padding: 24px 24px 0; background: white; border: none; }
    .modal-icon-box {
        width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px;
    }
    .modal-icon-box.warn { background: #FFFBEB; color: var(--warning); }
    .modal-icon-box.danger { background: #FEF2F2; color: var(--danger); }
    .modal-icon-box.success { background: #ECFDF5; color: var(--success); }
    
    .modal-body-clean { padding: 0 30px 24px; text-align: center; }
    .modal-title-clean { font-size: 18px; font-weight: 800; color: var(--dark); margin-bottom: 8px; }
    .modal-desc-clean { font-size: 14px; color: var(--secondary); line-height: 1.5; }
    
    .modal-foot-clean {
        background: #F8FAFC; padding: 16px 24px; border-top: 1px solid var(--border);
        display: flex; justify-content: center; gap: 12px;
    }
    .btn-modal { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; border: 1px solid var(--border); cursor: pointer; background: white; }
    .btn-modal.confirm { border: none; color: white; }
    .btn-modal.confirm.primary { background: var(--primary); }
    .btn-modal.confirm.danger { background: var(--danger); }
    .btn-modal.confirm.success { background: var(--success); }
    .btn-modal.confirm.warning { background: var(--warning); }
    
    /* 🔥 NEW: Override Summary Box (PERFEKSIONIS DESIGN!) */
    #gk-alert-override {
        background: linear-gradient(135deg, #FFF7ED 0%, #FFEDD5 100%);
        border: 2px solid #FDBA74;
        border-radius: 12px;
        padding: 20px;
        margin-top: 16px;
        text-align: center;
        box-shadow: 0 4px 6px -1px rgba(251, 146, 60, 0.15);
    }
    
    .override-header {
        font-size: 11px;
        font-weight: 700;
        color: #9A3412;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }
    
    .override-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 12px;
    }
    
    .stat-box {
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid #FED7AA;
        border-radius: 8px;
        padding: 12px;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 900;
        color: #C2410C;
        line-height: 1;
        margin-bottom: 4px;
        letter-spacing: -1px;
    }
    
    .stat-label {
        font-size: 10px;
        font-weight: 600;
        color: #9A3412;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .override-note {
        font-size: 11px;
        color: #78350F;
        font-weight: 500;
        line-height: 1.4;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 6px;
        padding: 8px 12px;
        margin-top: 12px;
    }
    
    .override-note strong {
        font-weight: 700;
        color: #C2410C;
    }
</style>

<?php
    // --- SERVER SIDE LOGIC ---
    $step = 0;
    if ($bon->status == 'PENDING') $step = 1;
    elseif ($bon->status == 'APPROVED') $step = 2;
    elseif ($bon->status == 'ISSUED') $step = 3;
    elseif ($bon->status == 'REJECTED' || $bon->status == 'CANCELLED') $step = 4;

    $totalReq = 0;
    $totalAppr = 0;
    
    // VARIABEL BARU: Menghitung total persetujuan awal secara PHP
    $grandTotalApprovedInitial = 0;

    $countOverQuota = 0;
    $countLowStock = 0;
    $totalItemsCount = count($bon->details);

    foreach($bon->details as $d) {
        $totalReq += (float)$d->quantity; // FIX: Cast ke float
        $totalAppr += (float)$d->approved_quantity; // FIX: Cast ke float
        
        $item = $d->item;
        $stock = $item ? (float)$item->current_stock : 0; // FIX: Cast ke float
        
        $qa = isset($quotaAnalysis[$d->id]) ? $quotaAnalysis[$d->id] : null;
        
        if ($qa && isset($qa['is_over']) && $qa['is_over']) $countOverQuota++;
        if($stock < $d->quantity) $countLowStock++;
    }
?>

<div class="app-container">
    
    <div class="page-header">
        <div class="header-title">
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <h1>Detail Permintaan (BON)</h1>
                @if($bon->status == 'PENDING')
                    <span class="status-pill status-pending"><span class="dot"></span> PENDING (Menunggu Persetujuan)</span>
                @elseif($bon->status == 'APPROVED')
                    <span class="status-pill status-approved"><span class="dot"></span> APPROVED (Siap Di-issue)</span>
                @elseif($bon->status == 'ISSUED')
                    <span class="status-pill status-issued"><span class="dot"></span> ISSUED (Barang Dikeluarkan)</span>
                @elseif($bon->status == 'REJECTED')
                    <span class="status-pill status-rejected"><span class="dot"></span> REJECTED (Ditolak)</span>
                @elseif($bon->status == 'CANCELLED')
                    <span class="status-pill status-canceled"><span class="dot"></span> CANCELLED (Dibatalkan)</span>
                @endif
            </div>
            <div class="header-meta">
                <span><i class="fa fa-hashtag"></i> <strong>{{ $bon->bon_number }}</strong></span>
                <span><i class="fa fa-calendar"></i> {{ date('d M Y', strtotime($bon->date)) }}</span>
                <span><i class="fa fa-building"></i> {{ $bon->department ? $bon->department->name : ($bon->division_name ?: '-') }}</span>
            </div>
        </div>
        
        <div style="display:flex; gap:10px; align-items:center;">
            @if($bon->status === 'ISSUED')
                <a href="{{ route('bons.print', $bon->id) }}" target="_blank" class="btn-nav" style="color:#0a0908ff;">
                    <i class="fa fa-print"></i> Cetak Tanda Terima
                </a>
            @endif
            @if($bon->status == 'PENDING')
                <a href="{{ route('bons.edit', $bon->id) }}" class="btn-nav" style="color:#0a0908ff;">
                    <i class="fa fa-pencil"></i> Edit
                </a>
            @endif
            <a href="{{ route('bons.index') }}" class="btn-nav" style="color:#0a0908ff;">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    {{-- STEPPER STATUS (COMPACT) --}}
    <div class="stepper-card">
        <div class="step-list">
            <div class="step-line"></div>
            <div class="step-item done"><div class="step-circle"><i class="fa fa-pencil"></i></div><div class="step-label">Draft</div></div>
            <div class="step-item {{ $step >= 1 ? 'done' : ($step==1?'active':'') }}"><div class="step-circle">1</div><div class="step-label">Pending</div></div>
            <div class="step-item {{ $step >= 2 ? 'done' : ($step==2?'active':'') }}"><div class="step-circle">2</div><div class="step-label">Approved</div></div>
            <div class="step-item {{ $step >= 3 ? 'done' : ($step==3?'active':'') }}"><div class="step-circle">3</div><div class="step-label">{{ ($step==4) ? 'Void' : 'Issued' }}</div></div>
        </div>
    </div>

    {{-- INFORMASI DOKUMEN BON (RINGKAS & HORIZONTAL) --}}
    <div class="bon-header-card">
        <div class="bon-header-grid">
            <div class="bon-header-item">
                <span class="bon-h-label"><i class="fa fa-user"></i> Pembuat Request</span>
                <span class="bon-h-val">{{ ($bon->requestReference && $bon->requestReference->user) ? $bon->requestReference->user->name : ($bon->user ? $bon->user->name : '-') }}</span>
            </div>
            <div class="bon-header-item">
                <span class="bon-h-label"><i class="fa fa-building-o"></i> Divisi / Bagian</span>
                <span class="bon-h-val">{{ $bon->division_name ?: ($bon->department ? $bon->department->name : '-') }}</span>
            </div>
            <div class="bon-header-item">
                <span class="bon-h-label"><i class="fa fa-user-circle-o"></i> Disetujui Oleh (Atasan)</span>
                <span class="bon-h-val">{{ $bon->requestReference && $bon->requestReference->approver ? $bon->requestReference->approver->name : '-' }}</span>
            </div>
            <div class="bon-header-item">
                <span class="bon-h-label"><i class="fa fa-file-text-o"></i> Permintaan Terkait</span>
                <span class="bon-h-val">
                    @if($bon->requestReference)
                        <a href="{{ route('requests.show', $bon->requestReference->id) }}" style="color:var(--primary); font-weight:700; text-decoration:none;" title="Lihat Dokumen Request">
                            {{ $bon->requestReference->request_number }} <i class="fa fa-external-link" style="font-size:11px;"></i>
                        </a>
                    @else
                        -
                    @endif
                </span>
            </div>
            <div class="bon-header-item">
                <span class="bon-h-label"><i class="fa fa-comment-o"></i> Catatan Pemohon</span>
                <span class="bon-h-val" style="font-style:italic; color:#475569;">"{{ $bon->notes ?: 'Tidak ada catatan.' }}"</span>
            </div>
        </div>
    </div>

    {{-- TABEL ITEM (FOKUS UTAMA & LEBAR PENUH) --}}
    <div class="table-card">
        <div class="card-head">
            <div style="display:flex; align-items:center; gap:8px;">
                <i class="fa fa-cubes text-primary" style="font-size:16px;"></i> 
                <span style="font-size:15px; font-weight:800; color:var(--dark);">Daftar Item Barang</span>
                <span style="background:#EEF2FF; color:var(--primary); font-size:11px; font-weight:800; padding:2px 8px; border-radius:12px; margin-left:4px;">
                    {{ count($bon->details) }} Item
                </span>
            </div>
            @if($bon->status == 'PENDING')
                <div style="font-size:12px; color:#475569; font-weight:600;">
                    <i class="fa fa-info-circle text-primary"></i> Tentukan jumlah disetujui. Input <strong style="color:#DC2626;">0</strong> jika barang kosong / tidak ada.
                </div>
            @endif
        </div>

        @if($bon->status == 'PENDING')
        <form action="{{ route('bons.approve', $bon->id) }}" method="POST" id="form-approve">
            {{ csrf_field() }}
            <input type="hidden" name="override_quota" id="input-override-quota" value="0">
            <input type="hidden" name="confirm_zero_items" id="input-confirm-zero" value="0">
        @endif

        <div class="table-responsive">
            <table class="app-table">
                <thead>
                    <tr>
                        <th style="width:45px;" class="text-center">No</th>
                        <th style="width:28%;">Barang & Kode</th>
                        <th class="text-center" style="width:120px;">Stok Gudang</th>
                        <th>Catatan / Keterangan</th>
                        <th class="text-center" style="width:110px;">Qty Minta</th>
                        <th class="text-center" style="width:180px;">Qty Disetujui</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bon->details as $d)
                        <?php 
                            $item = $d->item;
                            $stock = $item ? (float)$item->current_stock : 0;
                            
                            $qa = isset($quotaAnalysis[$d->id]) ? $quotaAnalysis[$d->id] : null;
                            $qTotal = ($qa && isset($qa['limit'])) ? (float)$qa['limit'] : 0; 
                            $qUsed  = ($qa && isset($qa['used'])) ? (float)$qa['used'] : 0;
                            $qSisaAwal = ($qa && isset($qa['sisa_awal'])) ? (float)$qa['sisa_awal'] : 0;
                            $qReqQty = ($qa && isset($qa['req_qty'])) ? (float)$qa['req_qty'] : 0;
                            $qSisaAkhir = ($qa && isset($qa['sisa_akhir'])) ? (float)$qa['sisa_akhir'] : 0;
                            
                            $isOverQuota = ($qa && isset($qa['is_over'])) ? $qa['is_over'] : false;
                            $isLowStock  = ($stock < $d->quantity);

                            $isZeroApproved = ($bon->status != 'PENDING' && (float)$d->approved_quantity <= 0);
                            $isPartialApproved = ($bon->status != 'PENDING' && (float)$d->approved_quantity > 0 && (float)$d->approved_quantity < (float)$d->quantity);
                            
                            $pctUsed = 0; $pctReq = 0;
                            if ($qTotal > 0) {
                                $vizUsed = max(0, min(100, ($qUsed / $qTotal) * 100));
                                $vizReq  = max(0, min(100, ($qReqQty / $qTotal) * 100));
                                if(($vizUsed + $vizReq) > 100) $vizReq = 100 - $vizUsed;
                                $pctUsed = $vizUsed;
                                $pctReq = $vizReq;
                            }

                            $valApproved = old('details.' . $d->id . '.approved_quantity');
                            
                            if ($bon->status == 'PENDING' && $valApproved === null) {
                                $valApproved = (float)$d->approved_quantity;
                                if ($valApproved === null || $valApproved == 0) {
                                    $valApproved = 0;
                                }
                            } elseif ($bon->status != 'PENDING') {
                                $valApproved = (float)$d->approved_quantity;
                            }

                            $grandTotalApprovedInitial += (float)$valApproved;

                            $reqDetail = null;
                            if ($bon->requestReference) {
                                $reqDetail = $bon->requestReference->details
                                    ->where('item_id', $d->item_id)
                                    ->first();
                            }
                            $remarks = $reqDetail ? $reqDetail->remarks : '-';
                        ?>
                        <tr class="{{ $isZeroApproved ? 'row-zero-stock' : ($isLowStock ? 'row-danger' : ($isOverQuota ? 'row-warning' : '')) }} item-row detail-row" 
                            data-detail-id="{{ $d->id }}"
                            data-item-id="{{ $d->item_id }}" 
                            data-item-name="{{ $item ? $item->name : 'Item #'.$d->item_id }}"
                            data-stock="{{ $stock }}"
                            data-quota-limit="{{ $qTotal }}"
                            data-quota-used="{{ $qUsed }}"
                            data-quota-sisa-awal="{{ $qSisaAwal }}"
                            data-quota-req-qty="{{ $qReqQty }}"
                            data-quota-sisa-akhir="{{ $qSisaAkhir }}">
                            
                            <td class="text-center" style="font-weight:700; color:#94A3B8;">{{ $loop->iteration }}</td>

                            <td>
                                <div class="item-title" style="{{ $isZeroApproved ? 'color:#94a3b8;' : '' }}">
                                    {{ $item ? $item->name : 'Item #'.$d->item_id }}
                                </div>
                                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-top:2px;">
                                    <span class="codepill">{{ $item ? $item->code : '-' }}</span>
                                    @if($item && $item->category)
                                        <span style="font-size:11px; color:#64748B;">{{ $item->category->name }}</span>
                                    @endif
                                </div>

                                @if($isZeroApproved)
                                    <div>
                                        <span class="badge-out-of-stock">
                                            <i class="fa fa-times-circle"></i> Barang Tidak Ada / Kosong
                                        </span>
                                    </div>
                                @elseif($isPartialApproved)
                                    <div>
                                        <span class="badge-partial-approved">
                                            <i class="fa fa-adjust"></i> Disetujui Sebagian ({{ (float)$d->approved_quantity }} dari {{ (float)$d->quantity }})
                                        </span>
                                    </div>
                                @elseif($bon->status == 'PENDING' && $stock <= 0)
                                    <div>
                                        <span class="badge-out-of-stock">
                                            <i class="fa fa-exclamation-circle"></i> Stok Gudang Kosong (0)
                                        </span>
                                    </div>
                                @endif

                                {{-- 🔥 Real-time Over Limit Badge --}}
                                <div class="item-over-limit-badge {{ $isOverQuota ? 'show' : '' }}">
                                    <i class="fa fa-exclamation-triangle"></i> Over Limit
                                </div>
                            </td>

                            <td class="text-center">
                                <div style="font-weight:800; font-size:14px; {{ ($isLowStock || $stock <= 0) ? 'color:var(--danger);' : 'color:var(--dark);' }}">
                                    {{ (float)$stock }}
                                </div>
                                <div style="font-size:10px; color:var(--secondary); font-weight:600;">{{ $item ? $item->unit : '-' }}</div>
                                @if($stock <= 0)
                                    <span class="stok-badge stok-zero">Kosong (0)</span>
                                @elseif($isLowStock)
                                    <span class="stok-badge stok-low">Kurang</span>
                                @else
                                    <span class="stok-badge stok-ok">Cukup</span>
                                @endif
                            </td>

                            <td>
                                <div style="font-size:12px; font-weight:600; color:#334155; white-space:normal; line-height:1.4;">
                                    {{ $remarks }}
                                </div>
                            </td>

                            <td class="text-center">
                                <span class="numtag tag-blue" style="{{ $isZeroApproved ? 'opacity:0.6;' : '' }}">{{ (float)$d->quantity }}</span>
                            </td>

                            <td class="text-center">
                                @if($bon->status == 'PENDING')
                                    <div style="display:flex; flex-direction:column; align-items:center; gap:4px;">
                                        <input type="number" 
                                               name="details[{{ $d->id }}][approved_quantity]" 
                                               value="{{ $valApproved }}" 
                                               class="input-modern js-calc approved-qty-input {{ ($isLowStock || $stock <= 0) ? 'error' : '' }}"
                                               min="0"
                                               step="1"
                                               data-item-name="{{ $item ? $item->name : 'Item' }}"
                                               data-max-stock="{{ $stock }}"
                                               data-max-req="{{ $d->quantity }}"
                                               onfocus="if(Number(this.value) === 0) this.value = '';"
                                               onblur="if(this.value === '') { this.value = '0'; validateInput(this); }"
                                               oninput="validateInput(this)">
                                        @if($stock > 0)
                                            <div style="display:flex; gap:4px;">
                                                <button type="button" class="btn btn-xs btn-default" style="font-size:10px; padding:1px 6px;" onclick="var inp=this.closest('td').querySelector('.approved-qty-input'); inp.value=Math.min({{ $stock }}, {{ (float)$d->quantity }}); validateInput(inp);" title="Isi sesuai stok/permintaan">Maks ({{ min((float)$stock, (float)$d->quantity) }})</button>
                                                <button type="button" class="btn btn-xs btn-default" style="font-size:10px; padding:1px 6px; color:#DC2626;" onclick="var inp=this.closest('td').querySelector('.approved-qty-input'); inp.value=0; validateInput(inp);" title="Set ke 0 (Barang Kosong)">0</button>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    @if($isZeroApproved)
                                        <span class="qty-zero-badge">
                                            <i class="fa fa-ban"></i> 0 (Tidak Ada)
                                        </span>
                                    @else
                                        <span class="numtag tag-green" style="font-size:14px;">
                                            {{ (float)$d->approved_quantity }}
                                        </span>
                                        @if($bon->status == 'ISSUED')
                                            <div style="font-size:11px; color:var(--success); font-weight:700; margin-top:3px;">
                                                <i class="fa fa-check"></i> Diserahkan: {{ (float)$d->issued_quantity }}
                                            </div>
                                        @endif
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="table-summary-panel">
            <div class="sum-item">
                <span class="sum-label">Total Item</span>
                <span class="sum-val">{{ count($bon->details) }} Item</span>
            </div>
            <div class="sum-item">
                <span class="sum-label">Total Diminta</span>
                <span class="sum-val">{{ (float)$totalReq }}</span>
            </div>
            <div class="sum-item">
                <span class="sum-label">Total Disetujui</span>
                <span class="sum-val blue" id="summary-total">
                    {{ (float)($bon->status == 'PENDING' ? $grandTotalApprovedInitial : $totalAppr) }}
                </span>
            </div>
        </div>

        @if($bon->status == 'PENDING')
        </form>
        @endif
    </div>
</div>

{{-- FLOATING DOCK --}}
<div class="floating-dock">
    @if($bon->status == 'PENDING')
        <form action="{{ route('bons.reject', $bon->id) }}" method="POST" id="form-reject">
            {{ csrf_field() }}
            <button type="button" class="btn-action btn-reject" onclick="openModal('modalReject')">
                <i class="fa fa-times"></i> Reject
            </button>
        </form>
        <button type="button" class="btn-action btn-approve" onclick="triggerGatekeeper()">
            <i class="fa fa-check-circle"></i> Approve
        </button>
    @elseif($bon->status == 'APPROVED')
        <form action="{{ route('bons.cancel', $bon->id) }}" method="POST" id="form-cancel">
            {{ csrf_field() }}
            <button type="button" class="btn-action btn-reject" onclick="openModal('modalCancel')">
                <i class="fa fa-ban"></i> Cancel
            </button>
        </form>
        <form action="{{ route('bons.issue', $bon->id) }}" method="POST" id="form-issue">
            {{ csrf_field() }}
            <button type="button" class="btn-action btn-issue" onclick="openModal('modalIssue')">
                <i class="fa fa-cube"></i> Issue Barang
            </button>
        </form>
    @elseif($bon->status == 'ISSUED')
        <form action="{{ route('bons.rollback', $bon->id) }}" method="POST" id="form-rollback">
            {{ csrf_field() }}
            <button type="button" class="btn-action btn-rollback" onclick="openModal('modalRollback')">
                <i class="fa fa-undo"></i> Rollback / Batal Issue
            </button>
        </form>
    @endif
</div>

{{-- MODAL GENERIC (FIXED WIDTH) --}}
<div class="modal fade" id="modalGeneric" tabindex="-1" role="dialog" style="z-index: 10000;">
    <div class="modal-dialog modal-dialog-wider" role="document">
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

{{-- MODAL GATEKEEPER (FIXED WIDTH) --}}
@if($bon->status == 'PENDING')
<div class="modal fade" id="modalGatekeeper" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-dialog-wider" role="document">
        <div class="modal-content modal-clean">
            <div class="modal-head-clean" id="gk-icon-wrapper"></div>
            <div class="modal-body-clean">
                <h3 class="modal-title-clean" id="gk-title">Title</h3>
                <p class="modal-desc-clean" id="gk-desc">Description</p>
                
                {{-- 🔥 Alert Box: Possibly Forgot (Zero Items) --}}
                <div id="gk-alert-forgot" style="display:none; background:#FFF7ED; border:1px solid #FDBA74; padding:12px; border-radius:8px; margin-top:16px; text-align:center;">
                    <div style="font-size:13px; font-weight:700; color:#C2410C; margin-bottom:4px;">
                        <i class="fa fa-exclamation-triangle"></i> <span id="count-forgot-items">0</span> item dengan Stok Tersedia tapi Disetujui 0
                    </div>
                    <div style="font-size:11px; color:#9A3412; font-style:italic;">
                        Item dengan jumlah 0 akan ditandai sebagai <strong>Barang Tidak Ada (Stok Kosong)</strong> dan tidak memotong stok gudang.
                    </div>
                </div>
                
                {{-- 🔥 NEW: Alert Box Override (SUMMARY ONLY - PERFEKSIONIS!) --}}
                <div id="gk-alert-override" style="display:none;">
                    <div class="override-header">
                        ⚠️ PERINGATAN DEFISIT PLAFON BULANAN
                    </div>
                    
                    <div class="override-stats">
                        <div class="stat-box">
                            <div class="stat-value" id="override-count">0</div>
                            <div class="stat-label">Item Over Limit</div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-value" id="override-deficit">0</div>
                            <div class="stat-label">Total Defisit</div>
                        </div>
                    </div>
                    
                    <div class="override-note">
                        Approval ini akan dicatat sebagai <strong>Override (Urgent/Emergency)</strong> dan melebihi alokasi plafon bulanan.
                    </div>
                </div>
            </div>
            <div class="modal-foot-clean">
                <button type="button" class="btn-modal" data-dismiss="modal">Periksa Kembali</button>
                <button type="button" class="btn-modal confirm" id="btn-modal-confirm" onclick="confirmApproval()">
                    Lanjut Approve
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ===========================================================================================
    // 🔥 FIX CRITICAL: REAL-TIME QUOTA VALIDATION - Deteksi override berdasarkan INPUT TERKINI!
    // ===========================================================================================
    
    // Variabel global untuk menyimpan data quota dari backend (SUDAH EXCLUDE BON INI!)
    var quotaDataBackend = {};
    
    // Inisialisasi data quota dari backend saat page load
    @if($bon->status == 'PENDING')
        @foreach($bon->details as $d)
            <?php 
                $qa = isset($quotaAnalysis[$d->id]) ? $quotaAnalysis[$d->id] : null;
                $qTotal = ($qa && isset($qa['limit'])) ? (float)$qa['limit'] : 0; 
                $qUsed  = ($qa && isset($qa['used'])) ? (float)$qa['used'] : 0;
                $qSisaAwal = ($qa && isset($qa['sisa_awal'])) ? (float)$qa['sisa_awal'] : 0;
            ?>
            quotaDataBackend[{{ $d->id }}] = {
                limit: {{ $qTotal }},
                used: {{ $qUsed }},
                sisaAwal: {{ $qSisaAwal }},
                itemName: "{{ $item ? addslashes($item->name) : 'Item' }}"
            };
        @endforeach
    @endif

    // --- 1. Realtime Calculation & SMART Validation ---
    function validateInput(input) {
        // Prevent Negative
        if(input.value < 0) input.value = 0;

        var val = parseFloat(input.value) || 0;
        var maxStock = parseFloat(input.getAttribute('data-max-stock'));
        var maxReq = parseFloat(input.getAttribute('data-max-req'));
        var row = input.closest('tr.item-row');
        
        // Reset row classes
        row.classList.remove('row-info-zero', 'row-warn-forgot');
        
        // Logic: Jika nilai input melebihi jumlah permintaan (Request), kembalikan ke nilai Request.
        if (val > maxReq) {
            val = maxReq;
            input.value = maxReq;
        }
        
        // Visual Warning jika melebihi stok (ERROR - Red)
        if (val > maxStock) {
            input.classList.add('error');
        } else {
            input.classList.remove('error');
        }

        // SMART VALIDATION untuk Input 0
        if (val === 0) {
            if (maxStock === 0) {
                // Stock gudang = 0, Input 0 → WAJAR (Info - Gray)
                row.classList.add('row-info-zero');
            } else {
                // Stock gudang > 0, Input 0 → POSSIBLY FORGOT (Warning - Orange)
                row.classList.add('row-warn-forgot');
            }
        }
        
        // 🔥 NEW: Update real-time quota display & badge
        updateQuotaDisplayRealtime(row);
        
        updateTotalApproved();
        
        // 🔥 NEW: Update KPI Card "Melebihi Plafon" secara real-time!
        updateKpiCardOverQuota();
    }

    // 🔥 FIXED: Function untuk update tampilan quota secara real-time (SYNC DENGAN CONTROLLER!)
    function updateQuotaDisplayRealtime(row) {
        var detailId = row.getAttribute('data-detail-id');
        var quotaData = quotaDataBackend[detailId];
        
        if (!quotaData || quotaData.limit <= 0) {
            return; // Skip jika tidak ada quota limit
        }
        
        // FIX: parseFloat
        var inputApproved = parseFloat(row.querySelector('.approved-qty-input').value) || 0;
        var limit = quotaData.limit;
        var sisaAwal = quotaData.sisaAwal; // 🔥 FIX: Pakai sisa awal dari backend (sudah exclude BON ini!)
        
        // 🔥 FIX: Kalkulasi sisa akhir (sisaAwal sudah benar dari controller!)
        var sisaAkhir = sisaAwal - inputApproved;
        
        // Update text display sisa (Format ID)
        var sisaDisplay = row.querySelector('.sisa-akhir-display strong');
        if (sisaDisplay) {
            sisaDisplay.textContent = new Intl.NumberFormat('id-ID').format(sisaAkhir);
        }
        
        // 🔥 NEW: Update "Over Limit" badge below Item & Kode (REAL-TIME!)
        var overLimitBadge = row.querySelector('.item-over-limit-badge');
        if (overLimitBadge) {
            if (sisaAkhir < 0) {
                overLimitBadge.classList.add('show'); // Show badge
            } else {
                overLimitBadge.classList.remove('show'); // Hide badge
            }
        }
        
        // Update bar visual
        var used = quotaData.used;
        var pctUsed = (used / limit) * 100;
        var pctReq = (inputApproved / limit) * 100;
        
        if ((pctUsed + pctReq) > 100) {
            pctReq = 100 - pctUsed;
        }
        
        var barUsed = row.querySelector('.quota-bar-used');
        var barReq = row.querySelector('.quota-bar-req');
        
        if (barUsed) {
            barUsed.style.width = pctUsed + '%';
        }
        
        if (barReq) {
            barReq.style.width = pctReq + '%';
            
            // Update class danger jika over
            if (sisaAkhir < 0) {
                barReq.classList.add('danger');
            } else {
                barReq.classList.remove('danger');
            }
        }
    }

    function updateTotalApproved() {
        var total = 0;
        $('.js-calc').each(function() { total += parseFloat($(this).val()) || 0; });
        
        // Format ID
        var formatted = new Intl.NumberFormat('id-ID').format(total);
        $('#summary-total').text(formatted);
    }

    // 🔥 FIXED: Function untuk calculate quota override secara real-time (SYNC DENGAN CONTROLLER!)
    function calculateQuotaOverrideRealtime() {
        var overItems = [];
        
        $('.detail-row').each(function() {
            var row = $(this);
            var detailId = row.data('detail-id');
            var quotaData = quotaDataBackend[detailId];
            
            if (!quotaData || quotaData.limit <= 0) {
                return; // Skip jika tidak ada quota limit
            }
            
            // FIX: parseFloat
            var inputApproved = parseFloat(row.find('.approved-qty-input').val()) || 0;
            
            if (inputApproved === 0) {
                return; // Skip item dengan qty 0
            }
            
            var limit = quotaData.limit;
            var sisaAwal = quotaData.sisaAwal; // 🔥 FIX: Pakai sisa awal dari backend!
            var sisaAkhir = sisaAwal - inputApproved;
            
            // Detect override: sisa akhir < 0
            if (sisaAkhir < 0) {
                overItems.push({
                    itemName: quotaData.itemName,
                    limit: limit,
                    used: quotaData.used,
                    requested: inputApproved,
                    available: sisaAwal,
                    deficit: Math.abs(sisaAkhir)
                });
            }
        });
        
        return overItems;
    }

    // 🔥 NEW FUNCTION: Hitung real-time berapa item yang over quota
    function countOverQuotaRealtime() {
        var overItems = calculateQuotaOverrideRealtime();
        return overItems.length;
    }

    // 🔥 NEW FUNCTION: Update tampilan KPI Card "Melebihi Plafon" secara real-time!
    function updateKpiCardOverQuota() {
        var kpiCard = $('#kpi-over-quota');
        if (!kpiCard.length) return;
        
        var count = countOverQuotaRealtime();
        var kpiValue = kpiCard.find('h4');
        var kpiIcon = kpiCard.find('.kpi-icon i');
        
        // Update value
        kpiValue.text(count + ' Item');
        
        // Update warna card & icon (hijau jika 0, merah jika > 0)
        if (count > 0) {
            kpiCard.removeClass('bg-green-light').addClass('bg-red-light');
            kpiIcon.removeClass('fa-check-circle').addClass('fa-exclamation-triangle');
        } else {
            kpiCard.removeClass('bg-red-light').addClass('bg-green-light');
            kpiIcon.removeClass('fa-exclamation-triangle').addClass('fa-check-circle');
        }
    }

    // --- 2. Gatekeeper Logic (SMART VALIDATION) dengan REAL-TIME QUOTA CHECK ---
    function triggerGatekeeper() {
        var hasStockError = false;
        var itemsStockError = [];
        var itemsPossiblyForgot = [];
        var totalProcessed = 0;
        
        $('.js-calc').each(function() {
            var val = parseFloat($(this).val()) || 0;
            var max = parseFloat($(this).attr('data-max-stock'));
            var name = $(this).attr('data-item-name');

            // Count item yang akan diproses (> 0)
            if (val > 0) {
                totalProcessed++;
                
                // Cek Stock Error (Input > Stock Gudang)
                if(val > max) {
                    hasStockError = true;
                    itemsStockError.push({
                        name: name,
                        input: val,
                        stock: max
                    });
                }
            } else {
                // val = 0
                // Cek apakah Stock Gudang > 0 (Possibly Forgot)
                if (max > 0) {
                    itemsPossiblyForgot.push({
                        name: name,
                        stock: max
                    });
                }
            }
        });

        var btn = $('#btn-modal-confirm');
        var wrapper = $('#gk-icon-wrapper');
        var modal = $('#modalGatekeeper');
        var alertForgot = $('#gk-alert-forgot');
        var alertOverride = $('#gk-alert-override');

        $('#input-override-quota').val('0'); 
        $('#input-confirm-zero').val('0'); // RESET FLAG

        // VALIDASI 1: Kasus Seluruh Item Diinput 0 (Semua Barang Kosong/Tidak Ada)
        if(totalProcessed === 0) {
            wrapper.html('<div class="modal-icon-box warn"><i class="fa fa-exclamation-circle text-warning" style="font-size:24px;"></i></div>');
            $('#gk-title').text('Konfirmasi Semua Barang Kosong');
            $('#gk-desc').html('Seluruh item disetujui dengan <strong>Qty 0 (Stok Kosong / Tidak Ada)</strong>.<br><br>BON ini akan tetap disetujui (<strong>APPROVED</strong>) dengan tanda bahwa seluruh barang tidak tersedia, dan <strong>tidak akan memotong stok gudang</strong>.<br><br>Lanjutkan proses approval?');
            alertForgot.hide();
            alertOverride.hide();
            $('#input-confirm-zero').val('1');
            btn.show().attr('class', 'btn-modal confirm warning').text('Ya, Tetap Approve (Barang Kosong)');
            modal.modal('show');
            return;
        }

        // VALIDASI 2: Stock Error (BLOCKER)
        if(hasStockError) {
            var errorList = '<ul style="margin:8px 0; padding-left:20px; text-align:left;">';
            itemsStockError.forEach(function(item) {
                errorList += '<li><strong>' + item.name + '</strong>: Input ' + item.input + ', Stok ' + item.stock + '</li>';
            });
            errorList += '</ul>';
            
            wrapper.html('<div class="modal-icon-box danger"><i class="fa fa-times text-danger" style="font-size:24px;"></i></div>');
            $('#gk-title').text('Stok Tidak Cukup!');
            $('#gk-desc').html('Jumlah disetujui <strong>melebihi stok fisik gudang</strong> untuk item berikut:' + errorList + 'Mohon kurangi jumlahnya agar sesuai stok tersedia.');
            alertForgot.hide();
            alertOverride.hide();
            btn.hide();
            modal.modal('show');
            return;
        }

        // 🔥 VALIDASI 3: Show "Possibly Forgot" Warning (SMART SUMMARY - NO LIST!)
        if (itemsPossiblyForgot.length > 0) {
            $('#count-forgot-items').text(itemsPossiblyForgot.length);
            alertForgot.show();
            $('#input-confirm-zero').val('1');
        } else {
            alertForgot.hide();
            $('#input-confirm-zero').val('0');
        }

        // 🔥 VALIDASI 4: REAL-TIME QUOTA CHECK (SUMMARY ONLY - PERFEKSIONIS!)
        var overItems = calculateQuotaOverrideRealtime();
        
        if(overItems.length > 0) {
            // Calculate total deficit
            var totalDeficit = 0;
            overItems.forEach(function(item) {
                totalDeficit += item.deficit;
            });
            
            // Warning: Over Plafon (Bisa Override) - SUMMARY MODE!
            wrapper.html('<div class="modal-icon-box warn"><i class="fa fa-exclamation-triangle text-warning" style="font-size:24px;"></i></div>');
            $('#gk-title').text('Konfirmasi Override Plafon');
            $('#gk-desc').text('Permintaan ini akan menyebabkan defisit pada plafon bulanan:');
            
            // Populate Override Summary Box
            $('#override-count').text(overItems.length);
            $('#override-deficit').text(new Intl.NumberFormat('id-ID').format(totalDeficit));
            alertOverride.show();
            
            $('#input-override-quota').val('1');
            btn.show().attr('class', 'btn-modal confirm warning').text('Ya, Override (Urgent)');
        } else {
            // Safe
            wrapper.html('<div class="modal-icon-box success"><i class="fa fa-check text-success" style="font-size:24px;"></i></div>');
            $('#gk-title').text('Validasi Sukses');
            $('#gk-desc').text('Stok aman dan Plafon mencukupi. Lanjutkan proses approval?');
            alertOverride.hide();
            btn.show().attr('class', 'btn-modal confirm primary').text('Ya, Approve');
        }

        modal.modal('show');
    }

    // --- NEW: Confirmation Function ---
    function confirmApproval() {
        $('#modalGatekeeper').modal('hide');
        $('#form-approve').submit();
    }

    // --- 3. Generic Modal Logic ---
    function openModal(type) {
        var title, desc, iconClass, iconContent, btnClass, submitId;
        
        if (type === 'modalReject') {
            iconClass = 'danger';
            iconContent = '<i class="fa fa-times text-danger" style="font-size:24px;"></i>';
            title = 'Tolak Permintaan?';
            desc = 'Status berubah menjadi REJECTED. Stok tidak berkurang.';
            btnClass = 'btn-modal confirm danger';
            submitId = '#form-reject';
        } else if (type === 'modalIssue') {
            iconClass = 'success';
            iconContent = '<i class="fa fa-check text-success" style="font-size:24px;"></i>';
            title = 'Issue Barang';
            desc = 'Pastikan fisik barang sudah siap. Stok sistem akan dipotong permanen.';
            btnClass = 'btn-modal confirm success';
            submitId = '#form-issue';
        } else if (type === 'modalCancel') {
            iconClass = 'danger';
            iconContent = '<i class="fa fa-ban text-danger" style="font-size:24px;"></i>';
            title = 'Batalkan BON?';
            desc = 'Dokumen yang sudah disetujui akan dibatalkan.';
            btnClass = 'btn-modal confirm danger';
            submitId = '#form-cancel';
        } else if (type === 'modalRollback') {
            iconClass = 'warn';
            iconContent = '<i class="fa fa-undo text-warning" style="font-size:24px;"></i>';
            title = 'Rollback Status?';
            desc = 'Status akan dikembalikan ke PENDING agar bisa diedit kembali. Stok barang akan dikembalikan ke sistem.';
            btnClass = 'btn-modal confirm warning';
            submitId = '#form-rollback';
        }

        $('#gen-icon-wrapper').html('<div class="modal-icon-box ' + iconClass + '">' + iconContent + '</div>');
        $('#gen-title').text(title);
        $('#gen-desc').text(desc);
        
        // Remove old classes first, then add new ones
        var btn = document.getElementById('btn-gen-confirm');
        btn.className = 'btn-modal confirm ' + btnClass.replace('btn-modal confirm ', '');
        
        // Unbind old events & Bind new one
        var newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', function() {
            $(submitId).submit();
        });

        $('#modalGeneric').modal('show');
    }

    // --- 4. Auto-validate on page load ---
    document.addEventListener('DOMContentLoaded', function() {
        $('.js-calc').each(function() {
            validateInput(this);
        });
    });
</script>
@endsection
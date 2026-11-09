@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL HALAMAN LPB (PENERIMAAN BARANG) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i> 
        <strong>PRINSIP DASAR:</strong> LPB adalah dokumen pencatatan barang masuk. Menyimpan LPB berarti <strong>MENAMBAH STOK FISIK</strong> di gudang secara otomatis.
    </div>

    <h4 class="help-h"><i class="fa fa-list text-primary"></i> Navigasi Tabel</h4>
    <ul class="help-list">
        <li>
            <strong>Lihat Rincian Cepat:</strong> 
            Klik tombol panah <i class="fa fa-angle-down" style="color:#64748b; border:1px solid #cbd5e1; border-radius:50%; padding:2px 5px; font-size:10px;"></i> di kolom paling kiri untuk melihat daftar item dalam LPB tersebut tanpa perlu pindah halaman.
        </li>
        <li>
            <strong>Status & Scope:</strong> 
            Data yang tampil sudah difilter sesuai wewenang Anda (Admin General / Apparel). Super Admin dapat melihat semua data.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-exclamation-triangle text-primary"></i> Manajemen Data (Edit & Hapus)</h4>
    <div style="background:#fff7ed; border-left:4px solid #f97316; padding:10px 15px; border-radius:6px; margin-bottom:15px; font-size:13px; color:#9a3412;">
        <strong>PERINGATAN ROLLBACK SYSTEM:</strong><br>
        Menghapus atau Mengedit kuantitas pada LPB lama akan menyebabkan <strong>Stok Master berubah otomatis</strong>.
        <br><em>Contoh: Jika LPB dihapus, stok barang terkait akan dikurangi kembali. Pastikan barang tersebut belum terpakai di BON (Barang Keluar) agar stok tidak minus.</em>
    </div>

    <h4 class="help-h"><i class="fa fa-search text-primary"></i> Pencarian & Laporan</h4>
    <p class="help-p">
        Gunakan <strong>Filter Tanggal</strong> untuk melihat rekapitulasi masuk bulanan. Gunakan tombol <strong>"Laporan Item"</strong> di pojok kanan atas untuk audit history per barang.
    </p>
@endsection

@section('content')
{{-- Load Flatpickr CSS (Format d/m/Y) --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    /* --- MODERN RESET & VARIABLES --- */
    :root {
        --primary: #2563eb;
        --primary-hover: #1d4ed8;
        --bg-soft: #f8fafc;
        --border-color: #e2e8f0;
        --text-main: #334155;
        --text-muted: #64748b;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    body {
        background-color: #f1f5f9;
        color: var(--text-main);
    }
    
    /* --- FIX: PERKECIL UKURAN FLATPICKR --- */
    .flatpickr-calendar {
        font-size: 12px !important; /* Perkecil base font */
        width: 310px !important;    /* Perkecil lebar total */
    }
    .flatpickr-rContainer, .flatpickr-days, .dayContainer {
        width: 310px !important;    /* Sesuaikan lebar container hari */
    }
    .flatpickr-day {
        height: 32px !important;    /* Perkecil area klik tanggal */
        line-height: 32px !important;
        max-width: 42px !important;
    }
    .flatpickr-current-month {
        font-size: 110% !important; /* Sesuaikan ukuran font bulan */
        padding-top: 10px !important;
    }

    /* --- LAYOUT UTAMA (FULL WIDTH / COMPACT MARGIN) --- */
    .page-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0 0 20px 0;
        box-sizing: border-box;
    }

    /* --- HEADER SECTION --- */
    .header-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .icon-box {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: var(--primary);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: var(--shadow-sm);
        border: 1px solid #bfdbfe;
    }

    .title-text h1 {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        letter-spacing: -0.4px;
    }

    .title-text p {
        font-size: 12.5px;
        color: var(--text-muted);
        margin: 2px 0 0 0;
    }

    /* --- ACTION BUTTONS (UPDATED) --- */
    .btn-action-group {
        display: flex;
        gap: 10px;
    }

    /* Shared style untuk tombol header biar tingginya sama */
    .btn-header-shared {
        height: 38px;
        padding: 0 16px;
        border-radius: 7px;
        font-weight: 600;
        font-size: 12.5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        text-decoration: none !important;
        white-space: nowrap;
        transition: all 0.2s ease;
    }

    /* FIX: Button Glass (Laporan) - Lebih Tegas */
    .btn-glass {
        background: white;
        border: 1px solid #cbd5e1;
        color: #475569;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .btn-glass:hover, .btn-glass:focus {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #1e293b;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        outline: none;
    }

    /* FIX: Button Primary (Tambah LPB) - Lebih Rapi */
    .btn-primary-modern {
        background: var(--primary);
        border: 1px solid var(--primary);
        color: white !important;
        box-shadow: 0 3px 5px -1px rgba(37, 99, 235, 0.25);
    }

    .btn-primary-modern:hover, 
    .btn-primary-modern:focus {
        background: var(--primary-hover);
        border-color: var(--primary-hover);
        color: white !important;
        transform: translateY(-1px);
        box-shadow: 0 5px 10px -2px rgba(37, 99, 235, 0.3);
        outline: none;
    }
    
    .btn-header-shared i {
        font-size: 13px;
        position: relative;
        top: -1px;
    }

    /* --- CARD CONTAINER --- */
    .modern-card {
        background: white;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 20px;
        width: 100%;
    }

    /* --- FILTER SECTION --- */
    .filter-container {
        padding: 14px 18px;
        background: #fff;
        border-bottom: 1px solid var(--border-color);
    }

    .filter-grid {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-wrap: wrap;
        width: 100%;
    }

    .form-group-modern {
        display: flex;
        flex-direction: column;
        gap: 5px;
        flex: 1;
        min-width: 160px;
    }

    .form-group-modern label {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0;
    }

    .form-control-modern {
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        padding: 6px 12px;
        font-size: 13px;
        color: #1e293b;
        transition: border-color 0.15s;
        height: 38px;
        width: 100%;
    }

    .form-control-modern:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    .form-control-modern.flatpickr-input { background-color: #fff !important; }

    .filter-buttons {
        display: flex;
        gap: 8px;
        margin-left: auto;
    }

    .btn-filter {
        height: 38px;
        padding: 0 16px;
        border-radius: 7px;
        font-size: 12.5px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        outline: none;
    }

    .btn-apply { 
        background: var(--primary); 
        color: white !important; 
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }
    .btn-apply:hover, .btn-apply:focus, .btn-apply:active { 
        background: var(--primary-hover); 
        color: white !important;
    }
    
    .btn-reset { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; text-decoration: none; }
    .btn-reset:hover { background: #e2e8f0; color: #475569; }

    /* --- TABLE STYLING --- */
    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }

    .table-modern {
        width: 100%;
        border-collapse: collapse;
    }

    .table-modern thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 9px 12px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    .table-modern tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }

    .table-modern tbody tr:hover {
        background: #f8fafc;
    }

    .table-modern td {
        padding: 8px 12px;
        font-size: 13px;
        color: #334155;
        vertical-align: middle;
    }

    /* --- TOGGLE BUTTON --- */
    .btn-toggle-round {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s;
        outline: none;
        font-size: 12px;
        padding: 0;
    }

    .btn-toggle-round:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: #eff6ff;
    }

    .btn-toggle-round.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
        transform: rotate(180deg);
    }

    /* --- BADGES & CHIPS --- */
    .badge-modern {
        padding: 2px 8px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.3px;
        display: inline-block;
    }
    
    .badge-gray { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .badge-item-count {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .action-chips {
        display: flex;
        gap: 5px;
        align-items: center;
        justify-content: center;
        flex-wrap: nowrap;
    }

    .chip-btn {
        padding: 4px 9px;
        border-radius: 5px;
        font-size: 11.5px;
        font-weight: 600;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s;
        border: 1px solid transparent;
        cursor: pointer;
        line-height: 1.25;
    }

    .chip-edit { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
    .chip-edit:hover { background: #ffedd5; color: #9a3412; }

    .chip-print { background: #f0f9ff; color: #0284c7; border-color: #bae6fd; }
    .chip-print:hover { background: #e0f2fe; color: #0369a1; }

    .chip-delete { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .chip-delete:hover { background: #fee2e2; color: #b91c1c; }

    /* FIX: Button Jatah Dept */
    .chip-alloc { 
        background: linear-gradient(to right, #4f46e5, #4338ca); 
        color: white !important;
        box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
    }
    .chip-alloc:hover, .chip-alloc:focus, .chip-alloc:active { 
        background: linear-gradient(to right, #4338ca, #3730a3); 
        color: white !important;
        transform: translateY(-1px);
        text-decoration: none !important;
        outline: none;
    }

    /* --- DETAIL ROW --- */
    .detail-row-content {
        background: #f8fafc;
        padding: 10px 14px;
        box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.02);
        border-top: 1px solid #e2e8f0;
    }

    .detail-card-inner {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .detail-table-inner th {
        background: #f1f5f9;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        padding: 7px 12px;
        border-bottom: 1px solid #e2e8f0;
        letter-spacing: 0.5px;
    }

    .detail-table-inner td {
        font-size: 12px;
        padding: 7px 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }

    /* --- PAGINATION WRAPPER --- */
    .bon-pagination-wrap .pagination { margin: 0; }
    .bon-pagination-wrap .pagination > li > a,
    .bon-pagination-wrap .pagination > li > span {
        padding: 4px 10px;
        font-size: 12px;
        color: #475569;
    }
    .bon-pagination-wrap .pagination > .active > a,
    .bon-pagination-wrap .pagination > .active > span {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    /* --- MODAL --- */
    .modern-modal .modal-content {
        border: none;
        border-radius: 14px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }
    .modern-modal .modal-header {
        background: #fff;
        border-bottom: none;
        padding: 18px 20px 5px 20px;
    }
    .modern-modal .modal-body {
        padding: 5px 20px 20px 20px;
        background: #fff;
    }
    .modern-modal .modal-footer {
        background: #f8fafc;
        padding: 14px 20px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .btn-modal-cancel {
        padding: 7px 14px;
        border-radius: 7px;
        background: white;
        border: 1px solid #cbd5e1;
        color: #475569;
        font-weight: 600;
        font-size: 12.5px;
    }
    .btn-modal-danger {
        padding: 7px 14px;
        border-radius: 7px;
        background: #ef4444;
        border: none;
        color: white;
        font-weight: 600;
        font-size: 12.5px;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
    }

    /* --- EMPTY STATE --- */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #94a3b8;
    }
    .empty-icon {
        font-size: 48px;
        margin-bottom: 12px;
        color: #cbd5e1;
    }

    @media (max-width: 992px) {
        .filter-grid { flex-direction: column; align-items: stretch; gap: 12px; }
        .filter-buttons { margin-left: 0; margin-top: 6px; }
    }
</style>

<div class="page-container">

    {{-- HEADER --}}
    <div class="header-wrapper">
        <div class="page-title">
            <div class="icon-box">
                <i class="fa fa-download"></i>
            </div>
            <div class="title-text">
                <h1>Penerimaan Barang (LPB)</h1>
                <p>Kelola data barang masuk, atur jatah departemen, dan cetak bukti.</p>
            </div>
        </div>
        <div class="btn-action-group">
            <a href="{{ route('reports.lpb-items') }}" class="btn-header-shared btn-glass">
                <i class="fa fa-file-text-o"></i> Laporan Item
            </a>
            <a href="{{ route('lpbs.create') }}" class="btn-header-shared btn-primary-modern">
                <i class="fa fa-plus-circle"></i> Tambah LPB Baru
            </a>
        </div>
    </div>

    {{-- MAIN CONTENT CARD --}}
    <div class="modern-card">
        
        {{-- FILTER SECTION --}}
        <div class="filter-container">
            <form method="GET" action="{{ route('lpbs.index') }}">
                <div class="filter-grid">
                    
                    {{-- SEARCH --}}
                    <div class="form-group-modern" style="flex: 2;">
                        <label>Pencarian Cepat</label>
                        <input type="text" name="q" class="form-control-modern" 
                               placeholder="Cari No. LPB atau Catatan..." value="{{ $q }}">
                    </div>

                    {{-- DATE RANGE --}}
                    <div class="form-group-modern">
                        <label>Dari Tanggal</label>
                        <input type="text" name="from" class="form-control-modern datepicker-flat" 
                               value="{{ $from }}" placeholder="dd/mm/yyyy">
                    </div>
                    <div class="form-group-modern">
                        <label>Sampai Tanggal</label>
                        <input type="text" name="to" class="form-control-modern datepicker-flat" 
                               value="{{ $to }}" placeholder="dd/mm/yyyy">
                    </div>

                    {{-- PER PAGE --}}
                    <div class="form-group-modern" style="flex: 0 0 120px;">
                        <label>Baris</label>
                        <select name="perPage" class="form-control-modern">
                            <option value="10"{{ $perPage == 10 ? ' selected' : '' }}>10</option>
                            <option value="25"{{ $perPage == 25 ? ' selected' : '' }}>25</option>
                            <option value="50"{{ $perPage == 50 ? ' selected' : '' }}>50</option>
                            <option value="100"{{ $perPage == 100 ? ' selected' : '' }}>100</option>
                        </select>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="filter-buttons">
                        <button type="submit" class="btn-filter btn-apply">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('lpbs.index') }}" class="btn-filter btn-reset">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE SECTION --}}
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <i class="fa fa-angle-down" style="font-size:13px; color:#94a3b8;" title="Lihat Detail"></i>
                        </th>
                        <th style="width: 48px; text-align: center;">No</th>
                        <th style="width: 170px;">Nomor LPB</th>
                        <th style="width: 130px;">Tanggal Terima</th>
                        <th style="min-width: 160px;">Supplier / Sumber</th>
                        <th>Catatan</th>
                        <th style="text-align: center; width: 100px;">Total Qty</th>
                        <th style="text-align: center; width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lpbs as $index => $lpb)
                        @php
                            $rowNumber = ($lpbs->currentPage() - 1) * $lpbs->perPage() + $index + 1;
                            
                            $totalQty = $lpb->total_qty;
                            $detailCount = 0;
                            $detailTotalQty = 0;
                            
                            if (isset($lpb->details)) {
                                foreach ($lpb->details as $d) {
                                    $detailCount++;
                                    // FIX: GUNAKAN FLOAT AGAR MUNCUL 0.5
                                    $detailTotalQty += (float) $d->quantity;
                                }
                                if (!$totalQty) $totalQty = $detailTotalQty;
                            }
                        @endphp

                        {{-- MAIN ROW --}}
                        <tr class="main-row">
                            <td style="text-align: center;">
                                @if($detailCount > 0)
                                    <button type="button" class="btn-toggle-round" 
                                            onclick="toggleDetail({{ $lpb->id }}, this)"
                                            title="Tampilkan/Sembunyikan Rincian">
                                        <i class="fa fa-angle-down"></i>
                                    </button>
                                @else
                                    <span style="color:#cbd5e1; font-size:11px;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center; font-weight: 500; color: #64748b; font-size:12px;">
                                {{ $rowNumber }}
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #1e293b; font-size:13px; letter-spacing:-0.2px;">{{ $lpb->lpb_number }}</div>
                                @if(!empty($lpb->no_po))
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                                        <i class="fa fa-file-text-o" style="color: #3b82f6; font-size:10px;"></i> 
                                        <span>PO: <strong>{{ $lpb->no_po }}</strong></span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:6px; font-size:12.5px; color:#475569; white-space:nowrap;">
                                    <i class="fa fa-calendar-o" style="color: #94a3b8; font-size:11px;"></i>
                                    <span>{{ $lpb->date ? date('d M Y', strtotime($lpb->date)) : '-' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($lpb->vendor || $lpb->supplier)
                                    <div style="font-weight: 500; color: #1e293b; font-size:13px; line-height: 1.3;">{{ $lpb->vendor ?: $lpb->supplier }}</div>
                                @else
                                    <span class="badge-modern badge-gray">-</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-style: italic; color: #64748b; font-size:12px;">
                                    {{ str_limit($lpb->notes, 40) ?: '-' }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-item-count">
                                    {{ (float)$totalQty }} <span style="font-size:10px; font-weight:normal; opacity:0.8;">Unit</span>
                                </span>
                            </td>
                            <td>
                                <div class="action-chips">
                                    <a href="{{ route('lpbs.edit', $lpb->id) }}" class="chip-btn chip-edit" title="Edit Data">
                                        <i class="fa fa-pencil"></i> Edit
                                    </a>
                                    
                                    <button type="button" class="chip-btn chip-delete" title="Hapus Data"
                                            onclick="openDeleteModal('{{ $lpb->id }}', '{{ $lpb->lpb_number }}')">
                                        <i class="fa fa-trash-o"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- DETAIL ROW --}}
                        <tr id="detail-row-{{ $lpb->id }}" style="display: none;">
                            <td colspan="8" style="padding: 0; border: none;">
                                <div class="detail-row-content">
                                    <div class="detail-card-inner">
                                        <div style="padding: 8px 14px; background: #fff; border-bottom: 1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                                            <span style="font-weight: 700; color: #334155; font-size: 12px; display:flex; align-items:center; gap:6px;">
                                                <i class="fa fa-list text-primary"></i> Rincian Item: {{ $lpb->lpb_number }}
                                            </span>
                                            <span style="font-size: 11.5px; color: #64748b;">
                                                Total <strong>{{ $detailCount }}</strong> Barang
                                            </span>
                                        </div>
                                        <table class="table-modern detail-table-inner" style="margin: 0;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 40px; text-align: center;">No</th>
                                                    <th style="width: 140px;">Kode Barang</th>
                                                    <th>Nama Barang</th>
                                                    <th>Kategori</th>
                                                    <th style="text-align: left;">Lokasi Penyimpanan</th>
                                                    <th style="text-align: center; width: 100px;">Qty Masuk</th>
                                                    <th style="text-align: center; width: 80px;">Satuan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($lpb->details as $idx => $d)
                                                <tr>
                                                    <td style="text-align: center; font-size: 11.5px;">{{ $idx + 1 }}</td>
                                                    <td>
                                                        <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #475569; font-size:11px;">
                                                            {{ $d->item ? $d->item->code : '-' }}
                                                        </code>
                                                    </td>
                                                    <td style="font-weight:500; font-size:12px;">{{ $d->item ? $d->item->name : 'Item #'.$d->item_id }}</td>
                                                    
                                                    <td>
                                                        @if($d->item && $d->item->category)
                                                            <span class="badge-modern badge-gray">{{ $d->item->category->name }}</span>
                                                        @else
                                                            <span style="color:#cbd5e1;">-</span>
                                                        @endif
                                                    </td>

                                                    <td style="color: #475569; font-size:12px;">
                                                        @if($d->item && $d->item->location)
                                                            <i class="fa fa-map-marker" style="color:#94a3b8; margin-right:4px;"></i> {{ $d->item->location }}
                                                        @else
                                                            <span style="color:#cbd5e1;">-</span>
                                                        @endif
                                                    </td>

                                                    <td style="text-align: center; font-weight: 700; color: #2563eb; font-size:12px;">
                                                        {{ (float)$d->quantity }}
                                                    </td>
                                                    <td style="text-align: center; font-size: 11.5px; color: #64748b;">
                                                        {{ $d->item ? $d->item->unit : '-' }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa fa-folder-open-o"></i></div>
                                    <h4 style="font-size: 16px; font-weight: 600; margin: 0 0 6px 0; color: #475569;">Data Tidak Ditemukan</h4>
                                    <p style="font-size: 13px; margin: 0;">Silakan ubah filter pencarian atau buat LPB baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div style="padding: 12px 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #fff; flex-wrap: wrap; gap: 10px;">
            <div style="font-size: 12.5px; color: #64748b;">
                Menampilkan <strong>{{ $lpbs->count() }}</strong> dari <strong>{{ $lpbs->total() }}</strong> data
            </div>
            <div class="bon-pagination-wrap">
                {{ $lpbs->appends(['q' => $q, 'from' => $from, 'to' => $to, 'perPage' => $perPage])->links() }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL DELETE --}}
<div class="modal fade modern-modal" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 480px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-weight: 700; color: #ef4444; display: flex; align-items: center; gap: 10px; font-size:18px;">
                    <i class="fa fa-exclamation-triangle"></i> Konfirmasi Hapus
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p style="font-size: 15px; color: #334155; margin-bottom: 20px; line-height:1.5;">
                    Apakah Anda yakin ingin menghapus data LPB <strong id="modal-lpb-num" style="color: #0f172a;"></strong>?
                </p>
                <div style="background: #fff7ed; border-left: 4px solid #f97316; padding: 16px; border-radius: 6px;">
                    <p style="font-size: 13px; color: #9a3412; margin: 0; line-height:1.5;">
                        <strong>Peringatan Sistem:</strong><br>
                        Penghapusan ini akan <strong>mengurangi stok fisik</strong> barang secara otomatis (Rollback System).
                        Pastikan barang belum digunakan dalam transaksi lain.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-dismiss="modal">Batal</button>
                <button type="button" class="btn-modal-danger" onclick="submitDelete()">
                    <i class="fa fa-trash"></i> Ya, Hapus Permanen
                </button>
            </div>
        </div>
    </div>
</div>

<form id="delete-form" action="" method="POST" style="display: none;">
    {{ csrf_field() }}
    {{ method_field('DELETE') }}
</form>

{{-- Script Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    // FIX: Inisialisasi Flatpickr
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr(".datepicker-flat", {
            altInput: true,      // Menampilkan input alternatif (untuk visual)
            altFormat: "d/m/Y",  // Format visual: DAY/MONTH/YEAR
            dateFormat: "Y-m-d", // Format data yang dikirim ke server: YYYY-MM-DD
            locale: "id",        // Bahasa Indonesia
            allowInput: true     // Mengizinkan pengetikan manual
        });
    });

    function toggleDetail(id, btn) {
        var row = document.getElementById('detail-row-' + id);
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
            btn.classList.add('active');
        } else {
            row.style.display = 'none';
            btn.classList.remove('active');
        }
    }

    function openDeleteModal(id, number) {
        document.getElementById('modal-lpb-num').innerText = number;
        var form = document.getElementById('delete-form');
        form.action = "{{ url('lpbs') }}/" + id;
        // Jika jQuery tersedia (biasanya di layout Laravel), gunakan modal bootstrap
        if(typeof jQuery !== 'undefined') {
            jQuery('#deleteModal').modal('show');
        }
    }

    function submitDelete() {
        document.getElementById('delete-form').submit();
    }
</script>
@endsection
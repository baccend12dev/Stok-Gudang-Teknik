@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL HALAMAN BON (PENGELUARAN BARANG) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>KONSEP DASAR:</strong> BON adalah dokumen bukti pengeluaran barang. Status <strong>ISSUED</strong> menandakan barang sudah resmi keluar dari gudang dan stok berkurang.
    </div>

    <h4 class="help-h"><i class="fa fa-refresh text-primary"></i> Siklus Status BON</h4>
    <ul class="help-list">
        <li>
            <span class="status-badge st-pending">PENDING</span> : 
            BON baru dibuat atau berasal dari Request. Barang belum diserahkan. Admin masih bisa mengedit jumlah barang.
        </li>
        <li>
            <span class="status-badge st-approved">APPROVED</span> : 
            BON sudah disetujui oleh atasan/admin (stok tersedia & jatah aman). Siap untuk proses pengambilan.
        </li>
        <li>
            <span class="status-badge st-issued">ISSUED</span> : 
            <strong>FINAL.</strong> Barang sudah diambil user. Stok fisik di sistem sudah terpotong otomatis. Data terkunci.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-print text-primary"></i> Prosedur Penyerahan Barang</h4>
    <p class="help-p">
        1. Buka Detail BON (Klik tombol <strong>Lihat</strong>).<br>
        2. Klik tombol <strong>Cetak Tanda Terima</strong>.<br>
        3. Minta User tanda tangan di kertas.<br>
        4. Baru klik tombol <strong>ISSUED / Serahkan Barang</strong> di sistem untuk memotong stok.
    </p>

    <h4 class="help-h"><i class="fa fa-trash text-danger"></i> Aturan Hapus (Rollback)</h4>
    <p class="help-p">
        Menghapus BON yang berstatus <strong>ISSUED</strong> akan otomatis <strong>MENGEMBALIKAN STOK (RESTORE)</strong> ke gudang. Hati-hati melakukan ini kecuali memang terjadi pembatalan transaksi fisik.
    </p>
@endsection

@section('content')
{{-- Load Select2 CSS --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
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
        
        /* Status Colors */
        --st-pending-bg: #fff7ed; --st-pending-text: #c2410c; --st-pending-border: #ffedd5;
        --st-approved-bg: #eff6ff; --st-approved-text: #1d4ed8; --st-approved-border: #dbeafe;
        --st-issued-bg: #f0fdf4; --st-issued-text: #15803d; --st-issued-border: #bbf7d0;
        --st-rejected-bg: #fef2f2; --st-rejected-text: #b91c1c; --st-rejected-border: #fecaca;
    }

    body {
        background-color: #f1f5f9;
        color: var(--text-main);
        font-family: 'Inter', sans-serif;
    }

    /* --- LAYOUT UTAMA --- */
    .page-container {
        width: 100%;
        padding: 24px 32px;
        box-sizing: border-box;
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

    /* --- HEADER SECTION --- */
    .header-wrapper {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 24px; flex-wrap: wrap; gap: 16px;
    }

    .page-title { display: flex; align-items: center; gap: 16px; }

    .icon-box {
        width: 48px; height: 48px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #4338ca; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; box-shadow: var(--shadow-sm); border: 1px solid #c7d2fe;
    }

    .title-text h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: -0.5px; }
    .title-text p { font-size: 13px; color: var(--text-muted); margin: 4px 0 0 0; }

    /* --- BUTTONS --- */
    .btn-action-group { display: flex; gap: 12px; }
    
    .btn-header-shared {
        height: 42px; padding: 0 20px; border-radius: 8px;
        font-weight: 600; font-size: 13px; display: inline-flex; align-items: center;
        justify-content: center; gap: 8px; text-decoration: none !important;
        transition: all 0.2s ease;
    }

    .btn-glass {
        background: white; border: 1px solid #cbd5e1; color: #475569;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .btn-glass:hover { background: #f8fafc; border-color: #94a3b8; color: #1e293b; transform: translateY(-1px); }

    .btn-primary-modern {
        background: var(--primary); border: 1px solid var(--primary); color: white !important;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.25);
    }
    .btn-primary-modern:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(37, 99, 235, 0.3); }
    
    /* --- CARD & FILTER --- */
    .modern-card {
        background: white; border-radius: 12px; border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px;
    }

    .filter-container { padding: 24px; background: #fff; border-bottom: 1px solid var(--border-color); }
    
    .filter-grid { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; width: 100%; }

    .form-group-modern { display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 180px; }
    .form-group-modern label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .form-control-modern {
        border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px;
        font-size: 13px; color: #1e293b; height: 42px; width: 100%;
    }
    .form-control-modern:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
    /* FIX: Input Flatpickr agar background putih */
    .form-control-modern.flatpickr-input { background-color: #fff !important; }

    /* --- CUSTOM SELECT2 OVERRIDES (PERFECT POSITION) --- */
    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        display: flex;
        align-items: center;
        background-color: #fff;
        position: relative; /* Penting untuk positioning X */
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px !important;
        color: #1e293b !important;
        font-size: 13px;
        padding-left: 14px;
        padding-right: 45px !important; /* Kasih space kanan biar teks gak nabrak X */
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 8px !important;
    }
    
    /* FIX POSISI TOMBOL X (CLEAR) */
    .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        right: 30px !important; /* Geser ke kiri panah */
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin-right: 0 !important;
        color: #ef4444 !important; /* Merah Modern */
        font-weight: bold;
        font-size: 18px;
        z-index: 2;
        line-height: 1;
        height: auto;
        width: auto;
        cursor: pointer;
    }
    .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #dc2626 !important;
    }

    .select2-dropdown {
        border-color: var(--primary) !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .select2-search__field {
        border-radius: 6px !important;
        padding: 8px !important;
        border: 1px solid #cbd5e1 !important;
    }
    .select2-results__option {
        padding: 8px 12px;
        font-size: 13px;
    }
    .select2-results__option--highlighted[aria-selected] {
        background-color: #eff6ff !important; /* Light Blue Hover */
        color: var(--primary) !important;
    }
    /* Fix width 100% untuk select2 container */
    .select2-container { width: 100% !important; }

    .filter-buttons { display: flex; gap: 10px; margin-left: auto; }
    .btn-filter {
        height: 42px; padding: 0 20px; border-radius: 8px; font-size: 13px;
        font-weight: 600; border: none; cursor: pointer; display: inline-flex;
        align-items: center; gap: 8px; outline: none;
    }
    .btn-apply { background: var(--primary); color: white !important; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2); }
    .btn-apply:hover { background: var(--primary-hover); }
    .btn-reset { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; text-decoration: none; }
    .btn-reset:hover { background: #e2e8f0; color: #475569; }

    /* --- TABLE --- */
    .table-responsive { overflow-x: auto; width: 100%; }
    .table-modern { width: 100%; border-collapse: collapse; }
    .table-modern thead th {
        background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px;
        text-transform: uppercase; letter-spacing: 0.5px; padding: 16px 20px;
        border-bottom: 1px solid var(--border-color); white-space: nowrap;
    }
    .table-modern tbody tr { border-bottom: 1px solid var(--border-color); transition: background 0.1s; }
    .table-modern tbody tr:hover { background: #f8fafc; }
    .table-modern td { padding: 14px 20px; font-size: 13px; color: #334155; vertical-align: middle; }

    /* --- CHIPS & BADGES --- */
    .chip-btn {
        padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;
        text-decoration: none !important; display: inline-flex; align-items: center; gap: 6px;
        border: 1px solid transparent; cursor: pointer;
    }
    .chip-view { background: #f0f9ff; color: #0369a1; border-color: #e0f2fe; }
    .chip-view:hover { background: #e0f2fe; }
    
    .chip-edit { background: #fff7ed; color: #c2410c; border-color: #ffedd5; }
    .chip-edit:hover { background: #ffedd5; }
    
    .chip-delete { background: #fef2f2; color: #b91c1c; border-color: #fee2e2; }
    .chip-delete:hover { background: #fee2e2; }

    /* Status Badges */
    .status-badge { padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; border: 1px solid transparent; letter-spacing: 0.5px; }
    .st-pending { background: var(--st-pending-bg); color: var(--st-pending-text); border-color: var(--st-pending-border); }
    .st-approved { background: var(--st-approved-bg); color: var(--st-approved-text); border-color: var(--st-approved-border); }
    .st-issued { background: var(--st-issued-bg); color: var(--st-issued-text); border-color: var(--st-issued-border); }
    .st-rejected, .st-cancelled { background: var(--st-rejected-bg); color: var(--st-rejected-text); border-color: var(--st-rejected-border); }

    /* Detail Toggle */
    .btn-toggle-round {
        width: 32px; height: 32px; border-radius: 50%; border: 1px solid #cbd5e1;
        background: white; display: flex; align-items: center; justify-content: center;
        color: #64748b; cursor: pointer; transition: all 0.2s; outline: none;
    }
    .btn-toggle-round:hover { border-color: var(--primary); color: var(--primary); background: #eff6ff; }
    .btn-toggle-round.active { background: var(--primary); border-color: var(--primary); color: white; transform: rotate(180deg); }

    /* --- DETAIL INNER --- */
    .detail-row-content { background: #f8fafc; padding: 20px 24px; box-shadow: inset 0 4px 6px -1px rgba(0,0,0,0.02); border-top: 1px solid #e2e8f0; }
    .detail-card-inner { background: white; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .detail-table-inner th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; color: #64748b; padding: 10px 16px; border-bottom: 1px solid #e2e8f0; }
    .detail-table-inner td { font-size: 12px; padding: 10px 16px; border-bottom: 1px solid #f1f5f9; color: #475569; }

    /* --- MODAL --- */
    .modern-modal .modal-content { border: none; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .modern-modal .modal-header { background: #fff; border-bottom: none; padding: 20px 20px 5px 20px; }
    .modern-modal .modal-body { padding: 5px 24px 24px 24px; background: #fff; }
    .modern-modal .modal-footer { background: #f8fafc; padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 12px; }
    .btn-modal-cancel { padding: 8px 16px; border-radius: 8px; background: white; border: 1px solid #cbd5e1; color: #475569; font-weight: 600; }
    .btn-modal-danger { padding: 8px 16px; border-radius: 8px; background: #ef4444; border: none; color: white; font-weight: 600; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2); }

    /* Empty State */
    .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
    .empty-icon { font-size: 56px; margin-bottom: 16px; color: #e2e8f0; }

    @media (max-width: 992px) {
        .filter-grid { flex-direction: column; align-items: stretch; }
        .filter-buttons { margin-left: 0; margin-top: 8px; }
    }
</style>

<div class="page-container">

    {{-- HEADER --}}
    <div class="header-wrapper">
        <div class="page-title">
            <div class="icon-box">
                <i class="fa fa-paper-plane"></i>
            </div>
            <div class="title-text">
                <h1>BON (Barang Keluar)</h1>
                <p>Kelola permintaan barang, persetujuan, dan pengeluaran stok.</p>
            </div>
        </div>
        <div class="btn-action-group">
            <a href="{{ route('reports.bon-items') }}" class="btn-header-shared btn-glass">
                <i class="fa fa-file-text-o"></i> Laporan Item
            </a>
            <a href="{{ route('bons.create') }}" class="btn-header-shared btn-primary-modern">
                <i class="fa fa-plus-circle"></i> Buat BON Baru
            </a>
        </div>
    </div>

    {{-- MAIN CONTENT CARD --}}
    <div class="modern-card">
        
        {{-- FILTER SECTION --}}
        <div class="filter-container">
            <form method="GET" action="{{ route('bons.index') }}">
                <div class="filter-grid">
                    
                    {{-- SEARCH --}}
                    <div class="form-group-modern" style="flex: 2;">
                        <label>Pencarian Cepat</label>
                        <input type="text" name="q" class="form-control-modern" 
                               placeholder="Cari No. BON atau Catatan..." value="{{ $q }}">
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

                    {{-- DEPARTEMEN (SELECT2) --}}
                    <div class="form-group-modern" style="flex: 1.5;">
                        <label>Departemen</label>
                        <select name="department" class="form-control-modern select2-search">
                            <option value="">-- Semua --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $department == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- STATUS (SELECT2 NO SEARCH) --}}
                    <div class="form-group-modern" style="flex: 1;">
                        <label>Status</label>
                        <select name="status" class="form-control-modern select2-nosearch">
                            <option value="">-- Semua --</option>
                            <option value="PENDING" {{ $status == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            <option value="APPROVED" {{ $status == 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                            <option value="ISSUED" {{ $status == 'ISSUED' ? 'selected' : '' }}>ISSUED</option>
                            <option value="REJECTED" {{ $status == 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                            <option value="CANCELLED" {{ $status == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                        </select>
                    </div>

                    {{-- PER PAGE (SELECT2 NO SEARCH) --}}
                    <div class="form-group-modern" style="flex: 0 0 80px;">
                        <label>Baris</label>
                        <select name="perPage" class="form-control-modern select2-nosearch">
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
                        <a href="{{ route('bons.index') }}" class="btn-filter btn-reset">
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
                        <th style="width: 50px; text-align: center;">
                            <i class="fa fa-angle-down" style="font-size:14px; color:#94a3b8;"></i>
                        </th>
                        <th style="width: 60px; text-align: center;">No</th>
                        <th style="width: 200px;">No. BON</th>
                        <th style="width: 300px;">Tanggal</th>
                        <th style="width: 300px;">Departemen / Divisi</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center;">Total Item</th>
                        <th style="text-align: center; width: 270px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bons as $index => $bon)
                        @php
                            $rowNumber = ($bons->currentPage() - 1) * $bons->perPage() + $index + 1;
                            $detailCount = $bon->details->count();
                            
                            // Status Styles Logic
                            $statusClass = 'st-pending'; // Default
                            if($bon->status == 'APPROVED') $statusClass = 'st-approved';
                            if($bon->status == 'ISSUED') $statusClass = 'st-issued';
                            if($bon->status == 'REJECTED' || $bon->status == 'CANCELLED') $statusClass = 'st-rejected';
                        @endphp

                        {{-- MAIN ROW --}}
                        <tr class="main-row">
                            <td style="text-align: center;">
                                @if($detailCount > 0)
                                    <button type="button" class="btn-toggle-round" 
                                            onclick="toggleDetail({{ $bon->id }}, this)">
                                        <i class="fa fa-angle-down"></i>
                                    </button>
                                @else
                                    <span style="color:#cbd5e1;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center; font-weight: 500; color: #64748b;">
                                {{ $rowNumber }}
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #1e293b; font-size:14px;">{{ $bon->bon_number }}</div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <i class="fa fa-calendar-o" style="color: #94a3b8; font-size:12px;"></i>
                                    {{-- Format tanggal PHP tetap jalan normal --}}
                                    <span style="font-weight:500;">{{ $bon->date ? $bon->date->format('d M Y') : '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #334155;">
                                    {{ $bon->department ? $bon->department->name : '-' }}
                                </div>
                                @if($bon->division_name)
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                        <i class="fa fa-users" style="font-size:10px; margin-right:4px;"></i> {{ $bon->division_name }}
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <span class="status-badge {{ $statusClass }}">
                                    {{ $bon->status }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span style="font-weight: 700; color: #334155;">{{ $detailCount }}</span>
                            </td>
                            <td>
                                <div class="action-chips" style="justify-content: center;">
                                    {{-- DETAIL / PROSES --}}
                                    <a href="{{ route('bons.show', $bon->id) }}" class="chip-btn chip-view" title="Lihat Detail / Proses">
                                        <i class="fa fa-eye"></i> Lihat
                                    </a>

                                    {{-- EDIT (Hanya jika PENDING) --}}
                                    @if($bon->status == 'PENDING')
                                        <a href="{{ route('bons.edit', $bon->id) }}" class="chip-btn chip-edit" title="Edit Data">
                                            <i class="fa fa-pencil"></i> Edit
                                        </a>
                                    @endif

                                    {{-- DELETE --}}
                                    <button type="button" class="chip-btn chip-delete" title="Hapus Data"
                                            onclick="openDeleteModal('{{ $bon->id }}', '{{ $bon->bon_number }}', '{{ $bon->status }}')">
                                        <i class="fa fa-trash-o"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- DETAIL ROW --}}
                        <tr id="detail-row-{{ $bon->id }}" style="display: none;">
                            <td colspan="8" style="padding: 0; border: none;">
                                <div class="detail-row-content">
                                    <div class="detail-card-inner">
                                        <div style="padding: 16px 20px; background: #fff; border-bottom: 1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                                            <span style="font-weight: 700; color: #334155; font-size: 13px;">
                                                <i class="fa fa-list text-primary" style="margin-right:6px;"></i> Rincian Permintaan
                                            </span>
                                            <span style="font-size: 12px; color: #64748b; font-style:italic;">
                                                Catatan: {{ $bon->notes ?: '-' }}
                                            </span>
                                        </div>
                                        <table class="table-modern detail-table-inner" style="margin: 0;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 40px; text-align: center;">No</th>
                                                    <th>Kode Barang</th>
                                                    <th>Nama Barang</th>
                                                    <th style="text-align: center;">Qty Diminta</th>
                                                    <th style="text-align: center;">Qty Disetujui</th>
                                                    <th style="text-align: center;">Satuan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($bon->details as $idx => $d)
                                                <tr>
                                                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                                                    <td>
                                                        <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #475569; font-size:11px;">
                                                            {{ $d->item ? $d->item->code : '-' }}
                                                        </code>
                                                    </td>
                                                    <td style="font-weight:500;">{{ $d->item ? $d->item->name : 'Item #'.$d->item_id }}</td>
                                                    <td style="text-align: center; font-weight: 600;">
                                                        {{ (float)$d->quantity }} {{-- FIX CAST FLOAT --}}
                                                    </td>
                                                    <td style="text-align: center; font-weight: 600; color: var(--primary);">
                                                        {{ (float)$d->approved_quantity }} {{-- FIX CAST FLOAT --}}
                                                    </td>
                                                    <td style="text-align: center;">{{ $d->item ? $d->item->unit : '-' }}</td>
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
                                    <h4 style="font-size: 18px; font-weight: 600; margin: 0 0 8px 0; color: #475569;">Data Tidak Ditemukan</h4>
                                    <p style="font-size: 14px; margin: 0;">Silakan ubah filter pencarian atau buat BON baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div style="padding: 20px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 13px; color: #64748b;">
                Menampilkan <strong>{{ $bons->count() }}</strong> dari <strong>{{ $bons->total() }}</strong> data
            </div>
            <div>
                {{ $bons->appends(['q' => $q, 'from' => $from, 'to' => $to, 'department' => $department, 'status' => $status, 'perPage' => $perPage])->links() }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL DELETE --}}
<div class="modal fade modern-modal" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-weight: 700; color: #ef4444; display: flex; align-items: center; gap: 10px; font-size:18px;">
                    <i class="fa fa-exclamation-triangle"></i> Konfirmasi Hapus BON
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p style="font-size: 15px; color: #334155; margin-bottom: 16px; line-height:1.5;">
                    Apakah Anda yakin ingin menghapus data BON <strong id="modal-bon-num" style="color: #0f172a;"></strong>?
                </p>

                {{-- ALERT KHUSUS JIKA ISSUED --}}
                <div id="alert-issued" style="display:none; background: #fff7ed; border-left: 4px solid #f97316; padding: 16px; border-radius: 6px; margin-bottom: 10px;">
                    <p style="font-size: 13px; color: #9a3412; margin: 0; line-height:1.5;">
                        <strong>PERHATIAN (Status ISSUED):</strong><br>
                        BON ini sudah dikeluarkan. Menghapusnya akan otomatis <strong>MENGEMBALIKAN STOK</strong> barang ke gudang (Rollback).<br>
                        Pastikan barang fisik benar-benar batal keluar.
                    </p>
                </div>
                
                {{-- ALERT NORMAL --}}
                <div id="alert-normal" style="display:none; background: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 6px; margin-bottom: 10px;">
                    <p style="font-size: 13px; color: #1e40af; margin: 0; line-height:1.5;">
                        <strong>Info:</strong> Stok barang belum berkurang (belum Issued). Penghapusan aman dilakukan.
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

{{-- SCRIPT AREA --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // FIX: Inisialisasi Flatpickr
        flatpickr(".datepicker-flat", {
            altInput: true,      
            altFormat: "d/m/Y",  // FORMAT YANG DIMINTA: DAY/MONTH/YEAR
            dateFormat: "Y-m-d", 
            locale: "id",        
            allowInput: true     
        });

        // 1. Fungsi Cek Apakah jQuery Layout Sudah Siap
        var waitForJQuery = setInterval(function () {
            if (typeof window.jQuery !== 'undefined') {
                clearInterval(waitForJQuery);
                // Kalau jQuery sudah siap, baru kita load plugin Select2 & Script lainnya
                initPlugins(window.jQuery);
            }
        }, 100); // Cek setiap 100ms

        function initPlugins($) {
            // --- A. LOAD SELECT2 JS SECARA DINAMIS (Agar urutannya benar) ---
            var script = document.createElement('script');
            script.src = "https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js";
            script.onload = function() {
                // Saat Select2 selesai di-download, langsung eksekusi settingannya
                
                // Init Select2 Search (Departemen)
                $('.select2-search').select2({
                    theme: "default",
                    width: '100%',
                    placeholder: '-- Pilih --',
                    allowClear: true
                });

                // Init Select2 No Search (Status & Baris)
                $('.select2-nosearch').select2({
                    theme: "default",
                    width: '100%',
                    minimumResultsForSearch: Infinity
                });
                
                console.log("Select2 Loaded Successfully!");
            };
            document.head.appendChild(script);

            // --- B. LOGIKA MODAL DELETE (YANG SUDAH FIX) ---
            window.openDeleteModal = function(id, number, status) {
                // Set Text
                $('#modal-bon-num').text(number);
                
                // Set Action Form URL
                var form = document.getElementById('delete-form');
                // Pastikan URL generated blade dirender di sini
                var baseUrl = "{{ url('bons') }}"; 
                form.action = baseUrl + "/" + id;
                
                // Logika Alert Status
                if(status === 'ISSUED') {
                    $('#alert-issued').show();
                    $('#alert-normal').hide();
                } else {
                    $('#alert-issued').hide();
                    $('#alert-normal').show();
                }

                // Panggil Modal
                $('#deleteModal').modal('show');
            };

            window.submitDelete = function() {
                document.getElementById('delete-form').submit();
            };

            // --- C. LOGIKA TOGGLE DETAIL ---
            window.toggleDetail = function(id, btn) {
                var row = $('#detail-row-' + id);
                if (row.is(':visible')) {
                    row.hide();
                    $(btn).removeClass('active');
                } else {
                    row.show();
                    $(btn).addClass('active');
                }
            };
        }
    });
</script>
@endsection
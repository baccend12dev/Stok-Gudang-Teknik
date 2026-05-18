@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL LAPORAN LPB PER ITEM === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>FUNGSI UTAMA:</strong> Laporan ini digunakan untuk melacak <strong>Riwayat Masuk (Inbound History)</strong> secara detail per item atau global.
    </div>

    <h4 class="help-h"><i class="fa fa-search text-primary"></i> Cara Menggunakan Filter</h4>
    <ul class="help-list">
        <li>
            <strong>Filter Barang (Spesifik):</strong> 
            Pilih nama barang tertentu untuk melihat "Kartu Stok Masuk" item tersebut. Anda bisa melihat kapan saja barang tersebut dibeli/diterima dan berapa jumlahnya.
        </li>
        <li>
            <strong>Filter Tanggal (Periode):</strong> 
            Tentukan rentang tanggal (Dari - Sampai) untuk membatasi data. Kosongkan filter barang jika ingin melihat rekapitulasi <em>semua barang</em> yang masuk dalam periode tersebut.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-file-excel-o text-primary"></i> Export Data</h4>
    <p class="help-p">
        Tombol <strong>"Export XLS"</strong> (Warna Hijau) akan mengunduh data sesuai dengan filter yang sedang aktif di layar. Gunakan ini untuk kebutuhan Audit Stok Opname atau Laporan Bulanan ke manajemen.
    </p>

    <h4 class="help-h"><i class="fa fa-lightbulb-o text-primary"></i> Tips Analisa</h4>
    <p class="help-p">
        Jika total <strong>Qty Masuk</strong> terlihat aneh/berlebihan, cek kolom <strong>No LPB</strong>. Mungkin ada input ganda (Double Input) pada tanggal yang sama.
    </p>
@endsection

@section('content')

{{-- =======================  LOGIC: DATA FETCHING (VIEW SIDE)  ======================= --}}
@php
    $catMap = [];
    try {
        $catMap = \Illuminate\Support\Facades\DB::table('items')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->pluck('categories.name', 'items.code')
            ->toArray();
    } catch(\Exception $e) {
        $catMap = [];
    }
@endphp

{{-- Load Select2 CSS --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
{{-- Load Flatpickr CSS (Untuk Format Tanggal d/m/Y) --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
{{-- Load Google Fonts (Inter) --}}
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* --- MODERN VARIABLES & RESET --- */
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --primary-gradient: linear-gradient(145deg, #3b82f6, #1d4ed8);
        --success-gradient: linear-gradient(145deg, #10b981, #059669);
        
        --bg-body: #f1f5f9;
        --bg-card: #ffffff;
        --text-dark: #0f172a;
        --text-gray: #64748b;
        --border-color: #e2e8f0;
        --danger: #ef4444;
        
        --radius-md: 8px;
        --radius-lg: 12px;
        
        --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    }

    body {
        background-color: var(--bg-body) !important;
        font-family: 'Inter', sans-serif;
        color: var(--text-dark);
        -webkit-font-smoothing: antialiased;
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

    .main-content { background-color: transparent !important; }

    .page-container {
        width: 100%;
        padding: 10px 40px 30px; 
        box-sizing: border-box;
    }

    /* --- HEADER SECTION --- */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .header-title-wrapper {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .header-visual-icon {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4f46e5;
        font-size: 24px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.6), 0 4px 6px -1px rgba(79, 70, 229, 0.1);
    }

    .header-content h1 {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .header-content p {
        font-size: 13px;
        color: var(--text-gray);
        margin-top: 2px;
        margin-bottom: 0;
    }

    /* Breadcrumb Custom */
    .custom-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--text-gray);
        margin-top: 4px;
    }
    .custom-breadcrumb a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }
    .custom-breadcrumb a:hover { text-decoration: underline; }
    .custom-breadcrumb .separator { color: #cbd5e1; }
    .custom-breadcrumb .active { color: var(--text-gray); font-weight: 600; }

    /* Back Button */
    .btn-back {
        background: white;
        color: var(--text-dark);
        padding: 10px 18px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        box-shadow: var(--shadow-soft);
        border: 1px solid white;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        line-height: 1;
        height: 40px;
    }
    .btn-back i { font-size: 14px; margin-top: -1px; }
    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: var(--primary);
        text-decoration: none;
    }

    /* --- STAT CARD --- */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        padding: 24px;
        box-shadow: var(--shadow-soft);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.2s ease;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }
    
    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #16a34a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .stat-content { flex-grow: 1; }
    .stat-label { font-size: 13px; color: var(--text-gray); font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px; }
    .stat-value { font-size: 28px; font-weight: 700; color: var(--text-dark); line-height: 1; }
    .stat-meta { font-size: 12px; color: var(--text-gray); margin-top: 6px; }

    /* --- FILTER SECTION --- */
    .card-filter {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-soft);
        border: 1px solid rgba(226, 232, 240, 0.8);
        margin-bottom: 24px;
        padding: 24px;
    }
    .filter-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 15px;
    }
    .filter-header h3 { font-size: 15px; font-weight: 600; margin: 0; color: var(--text-dark); }
    .filter-header i { color: var(--primary); }

    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: flex-end;
    }
    
    .filter-group { flex: 1; min-width: 180px; }
    .filter-group.fg-item { flex: 2; min-width: 250px; }
    .filter-group.fg-perpage { flex: 0 0 100px; min-width: 100px; }
    .filter-group.fg-action { flex: 0 0 auto; display: flex; gap: 10px; }

    .form-label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px; }
    .form-control {
        width: 100%; height: 42px; padding: 8px 12px; font-size: 13px;
        border: 1px solid var(--border-color); border-radius: var(--radius-md);
        background-color: #fff; color: var(--text-dark); transition: all 0.2s ease;
    }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); outline: none; }
    
    /* FIX: Input Flatpickr agar background putih */
    .form-control.flatpickr-input { background-color: #fff !important; }

    /* Filter Buttons */
    .btn-filter {
        height: 42px; padding: 0 20px; border-radius: var(--radius-md); font-weight: 600;
        font-size: 13px; border: none; cursor: pointer; display: inline-flex;
        align-items: center; gap: 6px; text-decoration: none; transition: all 0.2s;
    }
    .btn-primary { background: var(--primary-gradient); color: white; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2); }
    .btn-primary:hover { box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3); transform: translateY(-1px); color: white; }
    .btn-light { background: white; border: 1px solid var(--border-color); color: var(--text-gray); }
    .btn-light:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--text-dark); text-decoration: none; }
    .btn-success { background: var(--success-gradient); color: white; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2); }
    .btn-success:hover { box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3); transform: translateY(-1px); color: white; text-decoration: none; }

    /* --- TABLE SECTION --- */
    .card-table {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-soft);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }
    
    .table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
    .table thead { background-color: #f8fafc; }
    .table th {
        padding: 14px 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;
        color: var(--text-gray); letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color); white-space: nowrap;
    }
    .table td {
        padding: 12px 20px; font-size: 13px; color: var(--text-dark);
        border-bottom: 1px solid #f1f5f9; vertical-align: middle;
    }
    .table tbody tr:hover { background-color: #f8fafc; }
    .table tbody tr:last-child td { border-bottom: none; }

    .col-center { text-align: center; }
    .col-right { text-align: right; }
    .col-bold { font-weight: 600; }
    .col-code { 
        font-family: 'Monaco', 'Consolas', monospace; font-size: 12px; color: var(--primary); 
        background: rgba(37, 99, 235, 0.05); padding: 4px 8px; border-radius: 4px; display: inline-block;
    }
    .col-category {
        color: #4b5563; font-size: 11px; font-weight: 600; width: 160px;
        background: #f3f4f6; border-radius: 50px; padding: 4px 10px; display: inline-block; text-align: center;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;
    }

    /* --- SELECT2 CUSTOMIZATION --- */
    .select2-container .select2-selection--single {
        height: 42px !important; border: 1px solid var(--border-color) !important;
        border-radius: var(--radius-md) !important; display: flex; align-items: center; background-color: #fff;
        position: relative;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px !important; color: var(--text-dark) !important; font-size: 13px;
        padding-left: 12px;
        padding-right: 45px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; right: 8px !important; }
    
    /* FIX: TOMBOL X (CLEAR) */
    .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        right: 32px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin-right: 0 !important;
        color: #ef4444 !important;
        font-weight: bold;
        font-size: 18px;
        z-index: 99;
        line-height: 1;
        height: auto;
        width: 20px;
        text-align: center;
        cursor: pointer;
    }
    .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #dc2626 !important;
    }

    .select2-dropdown {
        border-color: var(--primary) !important; border-radius: 8px !important;
        box-shadow: var(--shadow-hover) !important; padding: 5px;
    }
    .select2-results__option { padding: 8px 12px; font-size: 13px; border-radius: 4px; }
    .select2-results__option--highlighted[aria-selected] { background-color: #eff6ff !important; color: var(--primary) !important; font-weight: 500; }

    .pagination-wrapper { padding: 16px 24px; border-top: 1px solid var(--border-color); background: #fff; }
    .pagination { margin: 0; display: flex; justify-content: flex-end; gap: 5px; }
    .pagination li a, .pagination li span {
        border-radius: 6px; border: 1px solid #e2e8f0; padding: 6px 12px; color: var(--text-gray); font-size: 12px;
    }
    .pagination li.active span { background-color: var(--primary); border-color: var(--primary); color: white; }

    @media (max-width: 992px) {
        .filter-row { flex-direction: column; align-items: stretch; gap: 15px; }
        .filter-group, .filter-group.fg-item, .filter-group.fg-perpage { width: 100%; min-width: 100%; flex: auto; }
        .filter-group.fg-action { justify-content: flex-start; margin-top: 10px; }
    }
</style>

<div class="page-container">

    {{-- HEADER --}}
    <div class="dashboard-header">
        <div class="header-title-wrapper">
            <div class="header-visual-icon">
                <i class="fa fa-bar-chart"></i>
            </div>
            <div class="header-content">
                <h1>Laporan LPB per Item</h1>
                <div class="custom-breadcrumb">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <span class="separator">/</span>
                    <a href="{{ route('lpbs.index') }}">LPB</a>
                    <span class="separator">/</span>
                    <span class="active">Laporan Item</span>
                </div>
            </div>
        </div>
        <div>
            <a href="{{ route('lpbs.index') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> <span>Kembali ke LPB</span>
            </a>
        </div>
    </div>

    {{-- STAT CARD --}}
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Unit Masuk</div>
                <div class="stat-value">{{ number_format($totalQty, 2, ',', '.') }}</div>
                
                <div class="stat-meta">
                    @if($from || $to || $itemId)
                        <i class="fa fa-filter"></i> Filter Aktif:
                        @if($from || $to)
                            <span>{{ $from ? date('d/m/y', strtotime($from)) : '...' }} s/d {{ $to ? date('d/m/y', strtotime($to)) : '...' }}</span>
                        @endif
                        @if($itemId)
                            @php
                                $currentItem = null;
                                foreach($items as $i) { if($i->id == $itemId) { $currentItem = $i; break; } }
                            @endphp
                            <span>• {{ $currentItem ? str_limit($currentItem->name, 15) : 'Item' }}</span>
                        @endif
                    @else
                        <span>Menampilkan seluruh data</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="card-filter">
        <div class="filter-header">
            <i class="fa fa-sliders"></i>
            <h3>Filter Data Laporan</h3>
        </div>
        <form method="GET" action="{{ route('reports.lpb-items') }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="form-label">Dari Tanggal</label>
                    {{-- FIX: Karena controller sudah set default, kita panggil $from saja --}}
                    <input type="text" name="from" 
                           value="{{ $from }}" 
                           class="form-control datepicker-flat" placeholder="dd/mm/yyyy">
                </div>

                <div class="filter-group">
                    <label class="form-label">Sampai Tanggal</label>
                    {{-- FIX: Karena controller sudah set default, kita panggil $to saja --}}
                    <input type="text" name="to" 
                           value="{{ $to }}" 
                           class="form-control datepicker-flat" placeholder="dd/mm/yyyy">
                </div>

                <div class="filter-group fg-item" id="item-group">
                    <label class="form-label">Nama Barang</label>
                    <select name="item_id" id="item_id" class="form-control select2-basic">
                        <option value="">-- Semua Barang --</option>
                        @foreach($items as $it)
                            @php
                                $labelCode = $it->code ? $it->code : '';
                                $sel = (isset($itemId) && (string)$itemId === (string)$it->id) ? 'selected' : '';
                            @endphp
                            <option value="{{ $it->id }}" {{ $sel }}>
                                @if($labelCode) [{{ $labelCode }}] @endif {{ $it->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group fg-perpage">
                    <label class="form-label">Tampil</label>
                    <select name="perPage" class="form-control select2-nosearch">
                        @foreach([25, 50, 100, 250] as $pp)
                            <option value="{{ $pp }}" {{ (isset($perPage) && (int)$perPage === $pp) || (!isset($perPage) && $pp === 100) ? 'selected' : '' }}>
                                {{ $pp }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group fg-action">
                    <button type="submit" class="btn-filter btn-primary">
                        <i class="fa fa-search"></i> Cari
                    </button>
                    <a href="{{ route('reports.lpb-items') }}" class="btn-filter btn-light">
                        Reset
                    </a>
                    @if(count($rows) > 0)
                        <a href="{{ route('reports.lpb-items.export', request()->query()) }}" class="btn-filter btn-success">
                            <i class="fa fa-file-excel-o"></i> Export XLS
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- TABLE SECTION --}}
    <div class="card-table">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-center" style="width: 50px;">No</th>
                        <th class="col-center" style="width: 120px;">Tanggal</th>
                        <th class="col-center" style="width: 120px;">No LPB</th>
                        <th class="col-center" style="width: 100px;">Kode</th>
                        <th>Nama Barang</th>
                        <th style="width: 110px;">Satuan</th>
                        <th class="col-center" style="width: 250px;">Kategori</th>
                        <th class="col-center" style="width: 120px;">Qty Masuk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        @php
                            $tgl = $row->date ? date('d M Y', strtotime($row->date)) : '-';
                            $qty = (float)$row->quantity;
                            $unitLabel = $row->item_unit ? $row->item_unit : '-';
                            
                            $codeKey = $row->item_code;
                            $kategoriName = (isset($codeKey) && isset($catMap[$codeKey])) ? $catMap[$codeKey] : '-';
                        @endphp
                        <tr>
                            <td class="col-center">{{ $rows->firstItem() + $index }}</td>
                            <td class="col-center">{{ $tgl }}</td>
                            <td class="col-center">
                                <span style="font-weight: 500; color: var(--text-dark);">{{ $row->lpb_number }}</span>
                            </td>
                            <td class="col-center">
                                <span class="col-code">{{ $row->item_code ? $row->item_code : '-' }}</span>
                            </td>
                            <td style="font-weight: 500;">
                                {{ $row->item_name ? $row->item_name : '-' }}
                            </td>
                            <td>{{ $unitLabel }}</td>
                            {{-- KATEGORI PINDAH KE SINI --}}
                            <td class="col-center">
                                <span class="col-category">{{ $kategoriName }}</span>
                            </td>
                            <td class="col-center col-bold" style="color: #16a34a;">
                                {{ number_format($qty, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-gray);">
                                <div style="margin-bottom: 10px; font-size: 32px; color: #cbd5e1;">
                                    <i class="fa fa-folder-open-o"></i>
                                </div>
                                <div>Belum ada data LPB yang sesuai dengan filter Anda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($rows->hasPages())
            <div class="pagination-wrapper">
                {{ $rows->appends(request()->except('page'))->links() }}
            </div>
        @endif
    </div>

</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
{{-- Script Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    (function($) {
        'use strict';
        
        $(document).ready(function() {
            // Select2 dengan Search (Untuk Barang)
            $('.select2-basic').select2({
                placeholder: 'Pilih opsi',
                allowClear: true,
                width: '100%',
                theme: "default"
            });

            // Select2 Tanpa Search (Untuk Per Page)
            $('.select2-nosearch').select2({
                minimumResultsForSearch: Infinity,
                width: '100%',
                theme: "default"
            });

            // FIX: Inisialisasi Flatpickr untuk Tanggal (d/m/Y)
            $(".datepicker-flat").flatpickr({
                altInput: true,      // Menampilkan input alternatif (untuk visual)
                altFormat: "d/m/Y",  // Format visual: DAY/MONTH/YEAR
                dateFormat: "Y-m-d", // Format data yang dikirim ke server: YYYY-MM-DD
                locale: "id",        // Bahasa Indonesia (Sen, Sel, Rab...)
                allowInput: true     // Mengizinkan pengetikan manual
            });
        });
    })(jQuery);
</script>
@endsection
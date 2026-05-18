@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL HALAMAN DETAIL (DRILL DOWN) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>INFO KONTEKS:</strong> Halaman ini adalah rincian mendalam <em>(Drill Down)</em> dari kartu KPI yang Anda klik di Dashboard.
    </div>

    <h4 class="help-h"><i class="fa fa-list-alt text-primary"></i> Cara Membaca Data Tabel</h4>
    <p class="help-p">
        Tabel ini mengurutkan barang dari yang paling aktif. Gunakan data ini untuk analisa Pareto (mencari item <em>Fast Moving</em>).
    </p>

    <ul class="help-list">
        <li>
            <strong>Kolom "Transaksi" (Frekuensi):</strong>
            Menunjukkan <em>berapa kali</em> item ini muncul dalam dokumen (BON/LPB) yang berbeda.
            <br><span style="font-size:11px; color:#64748b;">
                <i class="fa fa-lightbulb-o text-warning"></i> <strong>Tips:</strong> Item dengan transaksi tinggi (misal: 20x) artinya sering diminta/dipakai, meskipun total qty-nya mungkin kecil.
            </span>
        </li>
        <li>
            <strong>Kolom "Total Qty" (Volume):</strong>
            Akumulasi jumlah fisik barang yang bergerak dalam periode terpilih.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-filter text-primary"></i> Filter & Export Laporan</h4>
    <ul class="help-list">
        <li>
            <strong>Filter Barang:</strong> Gunakan dropdown "Cari Barang" jika Anda ingin mengaudit riwayat satu item spesifik saja dalam rentang tanggal tertentu.
        </li>
        <li>
            <strong>Export Excel:</strong> Klik tombol hijau di kanan atas untuk mengunduh data tabel ini ke Excel. Format laporan sudah disesuaikan untuk kebutuhan audit stok.
        </li>
    </ul>
@endsection

@section('content')
{{-- 1. Load CSS Select2 --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
{{-- Load Flatpickr CSS (Format d/m/Y) --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    /* GLOBAL STYLE FIXES */
    .detail-container { width: 100%; padding: 0; }
    
    .detail-header { 
        display: flex; justify-content: space-between; align-items: center; 
        margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;
        flex-wrap: wrap; gap: 15px;
    }
    .detail-title { font-size: 24px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 12px; }
    .detail-subtitle { font-size: 14px; color: #64748b; margin-top: 6px; }
    
    /* FILTER BOX STYLE */
    .filter-box {
        background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px;
        margin-bottom: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    .filter-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
    .form-group-filter { display: flex; flex-direction: column; gap: 6px; position: relative; }
    .form-group-filter label { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
    
    /* INPUT STYLE */
    .form-control-filter {
        padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #334155;
        min-width: 150px; height: 42px; box-sizing: border-box;
    }
    /* FIX: Input Flatpickr agar background putih */
    .form-control-filter.flatpickr-input { background-color: #fff !important; }

    /* --- FIX: PERKECIL UKURAN FLATPICKR --- */
    .flatpickr-calendar { font-size: 12px !important; width: 310px !important; }
    .flatpickr-rContainer, .flatpickr-days, .dayContainer { width: 310px !important; }
    .flatpickr-day { height: 32px !important; line-height: 32px !important; max-width: 42px !important; }
    .flatpickr-current-month { font-size: 110% !important; padding-top: 10px !important; }

    /* --- SELECT2 CUSTOM STYLING (TAMPILAN SEARCHABLE, RAPI, ENAK DIPAKAI) --- */
    /* Container utama Select2 */
    .select2-container .select2-selection--single {
        height: 42px !important; 
        border: 1px solid #cbd5e1 !important; 
        border-radius: 6px !important;
        display: flex !important;
        align-items: center !important;
        background-color: #fff !important;
        position: relative; /* Penting untuk absolute positioning anak elemen */
    }
    
    /* Teks di dalam dropdown */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155 !important;
        line-height: 42px !important;
        font-size: 14px !important;
        padding-left: 12px !important;
        /* FIX: Tambah padding kanan agar teks tidak nabrak tombol X */
        padding-right: 45px !important; 
    }
    
    /* Panah dropdown */
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 8px !important;
        top: 1px !important;
    }

    /* FIX: Tombol X (Clear) - Dipindah ke kiri panah */
    .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        right: 30px !important; /* Geser ke kiri panah (panah di right:8px) */
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin-right: 0 !important;
        color: #000000ff !important; 
        font-weight: bold;
        font-size: 18px;
        z-index: 99; /* Pastikan di atas elemen lain */
        line-height: 1;
        height: auto;
        width: auto;
        cursor: pointer;
    }
    .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #dc2626 !important;
    }

    /* Dropdown list saat dibuka */
    .select2-dropdown {
        border: 1px solid #3b82f6 !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        z-index: 9999 !important;
    }

    /* Search input di dalam dropdown */
    .select2-container--default .select2-search--dropdown {
        padding: 10px !important;
        border-bottom: 1px solid #e2e8f0 !important;
        background: #fff !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        height: 38px !important;
        padding: 8px 10px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        font-size: 13px !important;
        outline: none !important;
        box-shadow: none !important;
    }

    /* Batasi tinggi list biar nyaman discroll */
    .select2-results__options { max-height: 260px !important; }
    /* Item list padding */
    .select2-results__option { padding: 8px 12px !important; font-size: 13px !important; }
    /* Item hover state */
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #eff6ff !important;
        color: #2563eb !important;
        font-weight: 500 !important;
    }

    /* Item terpilih */
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #e2e8f0 !important;
        color: #0f172a !important;
    }

    /* BUTTONS */
    .btn-filter {
        padding: 0 20px; height: 42px; border-radius: 6px; font-weight: 600; font-size: 14px; border: none; cursor: pointer;
        background: #3b82f6; color: #fff; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    }
    .btn-filter:hover { background: #2563eb; }
    
    .btn-excel {
        padding: 0 20px; height: 42px; border-radius: 6px; font-weight: 600; font-size: 14px; border: none; cursor: pointer;
        background: #10b981; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        outline: none !important; box-shadow: none !important;
    }
    .btn-excel:hover { background: #059669; color: #fff; text-decoration: none; }
    /* FIX: biar setelah diklik (focus/active/visited) warnanya tetap normal (nggak jadi aneh) */
    .btn-excel:focus, .btn-excel:active, .btn-excel:visited, .btn-excel:focus-visible {
        background: #10b981 !important; color: #fff !important; text-decoration: none !important; outline: none !important; box-shadow: none !important;
    }
    .btn-excel i { color: #fff !important; }

    .btn-reset {
        padding: 0 16px; height: 42px; border-radius: 6px; font-weight: 600; font-size: 14px; text-decoration: none;
        background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center;
    }
    .btn-reset:hover { background: #e2e8f0; color: #1e293b; }

    /* TABLE STYLES */
    .card-table { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; }
    
    .table-custom { width: 100%; border-collapse: collapse; font-size: 14px; }
    .table-custom thead { background: #f1f5f9; border-bottom: 2px solid #e2e8f0; }
    .table-custom th { 
        padding: 16px 20px; text-align: left; font-weight: 700; color: #475569; 
        text-transform: uppercase; font-size: 11px; letter-spacing: 0.8px; 
    }
    /* Alignment Classes */
    .text-center { text-align: center !important; }
    .text-right { text-align: right !important; }

    .table-custom td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .table-custom tr:last-child td { border-bottom: none; }
    .table-custom tr:hover { background: #f8fafc; }

    /* Rank Badge - Grey Style */
    .rank-badge { 
        width: 28px; height: 28px; border-radius: 50%; 
        background: #e2e8f0; color: #64748b; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 12px; font-weight: 700; margin: 0 auto;
    }

    .btn-back {
        padding: 10px 18px; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px;
        color: #475569; font-weight: 600; font-size: 13px; text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .btn-back:hover { background: #f8fafc; color: #1e293b; border-color: #94a3b8; }

    /* Icons Themes */
    .sc-orange .detail-icon { color: #ea580c; background: #fff7ed; padding: 10px; border-radius: 10px; }
    .sc-blue .detail-icon { color: #2563eb; background: #eff6ff; padding: 10px; border-radius: 10px; }
</style>

<div class="detail-container">
    {{-- Header Section --}}
    <div class="detail-header">
        <div>
            <div class="detail-title {{ isset($themeClass) ? $themeClass : '' }}">
                <i class="fa {{ isset($pageIcon) ? $pageIcon : 'fa-list' }} detail-icon"></i>
                {{ isset($pageTitle) ? $pageTitle : 'Detail Data' }}
            </div>
            <div class="detail-subtitle">
                Menampilkan data periode: <strong style="color:#1e293b">{{ isset($periodLabel) ? $periodLabel : '-' }}</strong>
            </div>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="filter-box">
        <form action="" method="GET" class="filter-form">
            <div class="form-group-filter">
                <label><i class="fa fa-calendar"></i> Dari Tanggal</label>
                {{-- FIX: Ganti date -> text & tambah class datepicker-flat --}}
                <input type="text" name="start_date" class="form-control-filter datepicker-flat" 
                       value="{{ isset($startStr) ? $startStr : '' }}" placeholder="dd/mm/yyyy" required>
            </div>
            
            <div class="form-group-filter">
                <label><i class="fa fa-calendar"></i> Sampai Tanggal</label>
                {{-- FIX: Ganti date -> text & tambah class datepicker-flat --}}
                <input type="text" name="end_date" class="form-control-filter datepicker-flat" 
                       value="{{ isset($endStr) ? $endStr : '' }}" placeholder="dd/mm/yyyy" required>
            </div>

            {{-- Dropdown Search Item (Select2) --}}
            <div class="form-group-filter" style="flex-grow: 1; min-width: 250px;">
                <label><i class="fa fa-box"></i> Cari Barang</label>
                <select name="item_id" class="select2-basic" style="width: 100%;">
                    <option value="">-- Semua Barang --</option>
                    @if(isset($allItems) && count($allItems) > 0)
                        @foreach($allItems as $opt)
                            <option value="{{ $opt->id }}" {{ (isset($itemId) && $itemId == $opt->id) ? 'selected' : '' }}>
                                [{{ $opt->code }}] {{ $opt->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div class="form-group-filter">
                <button type="submit" class="btn-filter">
                    <i class="fa fa-filter"></i> Terapkan
                </button>
            </div>
            
            <div class="form-group-filter">
                <a href="{{ url()->current() }}" class="btn-reset">
                    Reset
                </a>
            </div>

            {{-- Tombol Export Excel --}}
            <div class="form-group-filter" style="margin-left: auto;">
                <a href="{{ isset($exportRoute) ? $exportRoute : '#' }}" class="btn-excel" onclick="this.blur();">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </a>
            </div>
        </form>
    </div>

    {{-- Data Table --}}
    <div class="card-table">
        <table class="table-custom">
            <thead>
                <tr>
                    <th width="60" class="text-center">NO</th> 
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    {{-- HEADER CENTER --}}
                    <th class="text-center">Transaksi</th>
                    <th class="text-center">Total Qty</th>
                    <th width="100" class="text-center">Satuan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                @php $rank = $index + 1; @endphp
                <tr>
                    <td class="text-center">
                        <div class="rank-badge">
                            {{ $rank }}
                        </div>
                    </td>
                    <td style="font-weight: 700; color: #2563eb; font-family: monospace; font-size: 13px;">
                        {{ $item->code }}
                    </td>
                    <td style="font-weight: 600; color: #1e293b;">
                        {{ $item->name }}
                    </td>
                    <td>
                        <span class="badge" style="background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">
                            {{ isset($item->category_name) ? $item->category_name : '-' }}
                        </span>
                    </td>
                    {{-- CONTENT CENTER (Transaksi) --}}
                    <td class="text-center">
                        <span style="font-weight: 700; color: #334155; font-size:15px;">{{ $item->freq }}</span>
                        <span style="font-size: 11px; color: #94a3b8; margin-left:2px;">x</span>
                    </td>
                    {{-- CONTENT CENTER (Total Qty) --}}
                    <td class="text-center" style="font-weight: 700; font-size: 15px; color:#0f172a;">
                        {{-- FIX: Float number_format --}}
                        {{ number_format((float)$item->total_qty, (floor($item->total_qty) == $item->total_qty ? 0 : 2), ',', '.') }}
                    </td>
                    <td class="text-center" style="color: #64748b; font-weight:500;">
                        {{ $item->unit }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 60px; color: #94a3b8;">
                        <div style="margin-bottom:15px; opacity:0.5;">
                            <i class="fa fa-folder-open-o" style="font-size: 48px;"></i>
                        </div>
                        <h4 style="font-size:16px; font-weight:600; margin-bottom:5px;">Tidak ada data ditemukan</h4>
                        <p style="font-size:13px;">Coba ubah filter tanggal atau barang.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- SCRIPT SECTION --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    (function() {
        function loadScript(src, cb) {
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = cb;
            document.head.appendChild(s);
        }

        function initSelect2() {
            if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;

            $(document).ready(function() {
                // Init Select2 pada dropdown cari barang
                $('.select2-basic').select2({
                    placeholder: "-- Semua Barang --",
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 0, // Search selalu tampil
                    dropdownAutoWidth: true,
                    language: {
                        noResults: function() {
                            return "Barang tidak ditemukan";
                        }
                    }
                });

                // FIX: Inisialisasi Flatpickr
                flatpickr(".datepicker-flat", {
                    altInput: true,      
                    altFormat: "d/m/Y",  // FORMAT YANG DIMINTA: DAY/MONTH/YEAR
                    dateFormat: "Y-m-d", 
                    locale: "id",        
                    allowInput: true     
                });
            });
        }

        // Pastikan jQuery ada (hindari double-load kalau layout sudah punya)
        if (typeof window.jQuery === 'undefined') {
            loadScript('https://code.jquery.com/jquery-2.2.4.min.js', function() {
                // Pastikan Select2 ter-load
                loadScript('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', initSelect2);
            });
            return;
        }

        // Kalau jQuery sudah ada, load Select2 hanya kalau belum ada
        if (!jQuery.fn || !jQuery.fn.select2) {
            loadScript('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', initSelect2);
        } else {
            initSelect2();
        }
    })();
</script>
@endsection
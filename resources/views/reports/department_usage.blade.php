@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL PEMAKAIAN DEPARTEMEN (THE ANALYST) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-pie-chart"></i>
        <strong>FUNGSI UTAMA:</strong> Halaman ini digunakan untuk menganalisa konsumsi barang per departemen dalam periode tertentu (Cost Center Analysis).
    </div>

    <h4 class="help-h"><i class="fa fa-eye text-primary"></i> Cara Membaca Data</h4>
    <ul class="help-list">
        <li>
            <strong>Total Diambil:</strong> Akumulasi jumlah barang yang sudah dikeluarkan (Issued) dari gudang untuk departemen tersebut. Permintaan yang masih pending/draft tidak dihitung.
        </li>
        <li>
            <strong>Barang Terboros (KPI):</strong> Kartu merah di atas otomatis mendeteksi barang apa yang paling banyak dikonsumsi oleh departemen ini. Berguna untuk evaluasi efisiensi.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-list-alt text-primary"></i> Lihat Rincian (Drill Down)</h4>
    <p class="help-p">
        Klik tombol <i class="fa fa-plus-square text-primary"></i> di sebelah kiri nama barang untuk melihat rincian: Kapan barang diambil? Berapa banyak? Dan apa Nomor BON-nya?
    </p>
@endsection

@section('content')

{{-- Load Select2 --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
{{-- Load Flatpickr CSS (Format d/m/Y) --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    /* Global Layout */
    body { background-color: #f5f5f5 !important; }
    .page-container { max-width: 100%; margin: 0 auto; padding: 0 24px 24px; box-sizing: border-box; }
    .dashboard-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .dashboard-title { font-size: 20px; font-weight: 700; margin: 0; color: #111827; }
    
    /* FIX: PERKECIL UKURAN FLATPICKR */
    .flatpickr-calendar { font-size: 12px !important; width: 310px !important; }
    .flatpickr-rContainer, .flatpickr-days, .dayContainer { width: 310px !important; }
    .flatpickr-day { height: 32px !important; line-height: 32px !important; max-width: 42px !important; }
    .flatpickr-current-month { font-size: 110% !important; padding-top: 10px !important; }

    /* Section & Filter */
    .section { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); padding: 16px; }
    
    /* Filter Box Layout */
    .filter-box { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 150px; }
    .filter-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0; }
    
    /* Input Style */
    .form-control-sm { height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; width: 100%; }
    /* FIX: Input Flatpickr background putih */
    .form-control-sm.flatpickr-input { background-color: #fff !important; }
    
    /* === FIX TAMPILAN SELECT2 (CLEAN LOOK) === */
    .select2-container { width: 100% !important; display: block; }
    
    .select2-container .select2-selection--single { 
        height: 38px !important; 
        border: 1px solid #d1d5db !important; 
        border-radius: 8px !important; 
        padding: 4px 0; 
        background-color: #fff;
        position: relative; 
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered { 
        line-height: 28px !important; 
        padding-left: 12px; 
        padding-right: 35px; 
        font-size: 13px; 
        color: #374151; 
    }
    
    /* === X TANPA BACKGROUND KOTAK ABU === */
    .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        top: 50% !important;
        right: 30px !important;
        transform: translateY(-50%);
        width: auto !important;
        height: auto !important;
        line-height: 1 !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        text-align: center;
        color: #9ca3af !important;
        font-size: 18px !important;
        font-weight: bold;
        cursor: pointer;
        z-index: 10; 
    }
    .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #dc2626 !important; 
        background: transparent !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow { 
        position: absolute !important;
        height: 36px !important; 
        right: 8px !important; 
        top: 1px !important;
    }

    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }

    /* Buttons */
    .btn-filter { 
        height: 38px; padding: 0 20px; background: #2563eb; color: #fff; border: none; border-radius: 8px; 
        font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; 
        width: auto; flex: none; /* FIX: BIAR GAK MELAR */
    }
    .btn-filter:hover { background: #1d4ed8; }
    
    .btn-export { 
        height: 38px; padding: 0 20px; background: #10b981; color: #fff; border: none; border-radius: 8px; 
        font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; 
    }
    .btn-export:hover { background: #059669; }

    /* Tombol Kembali */
    .btn-back {
        height: 38px; padding: 0 16px; background: #fff; border: 1px solid #d1d5db; 
        border-radius: 8px; color: #374151; font-size: 13px; font-weight: 600; 
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-back:hover { background: #f3f4f6; color: #111827; }

    /* Summary Cards */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 16px; }
    .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
    .stat-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
    .stat-value { font-size: 20px; color: #0f172a; font-weight: 800; margin-top: auto; }
    .sc-purple { border-left: 4px solid #8b5cf6; }
    .sc-orange { border-left: 4px solid #f97316; }
    .sc-red { border-left: 4px solid #ef4444; }

    /* Table & Accordion */
    .table-container { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; overflow: hidden; }
    .table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .table th { background: #f8fafc; padding: 12px 16px; text-align: left; font-weight: 700; color: #475467; border-bottom: 2px solid #e2e8f0; }
    .table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }
    
    /* Toggle Button Style */
    .toggle-btn { 
        cursor: pointer; color: #2563eb; font-size: 16px; width: 24px; height: 24px; 
        text-align: center; display: inline-flex; align-items: center; justify-content: center;
        transition: transform 0.2s; 
    }
    .toggle-btn:hover { color: #1d4ed8; transform: scale(1.1); }
    
    /* Detail Row Hidden by Default */
    .tr-detail { display: none; background-color: #f8fafc; }
    .detail-wrapper { padding: 15px 25px; border-left: 3px solid #2563eb; margin: 10px 20px; background: #fff; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    
    .sub-table { width: 100%; font-size: 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; margin-top: 8px; }
    .sub-table th { background: #f1f5f9; padding: 8px 12px; font-weight: 600; color: #64748b; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .sub-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

    .text-right { text-align: right !important; }
    .text-center { text-align: center !important; }
    .text-bold { font-weight: 700; }
</style>

<div class="page-container">
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Laporan Pemakaian Departemen</h1>
            <p style="font-size:12px; color:#64748b; margin-top:4px;">Analisa pengeluaran barang per departemen.</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('reports.index') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> Kembali ke Laporan
            </a>
            @if($department)
                 <a href="{{ route('reports.department-usage.export', request()->all()) }}" class="btn-export">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                 </a>
            @endif
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="section">
        <form method="GET" action="{{ route('reports.department-usage') }}" class="filter-box">
            <div class="filter-item" style="flex: 2;">
                <label class="filter-label">Pilih Departemen</label>
                <select name="department_id" id="dept_id" class="form-control-sm select2-enable" required>
                    <option value="">-- Pilih Departemen --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item">
                <label class="filter-label">Dari Tanggal</label>
                {{-- FIX: Flatpickr d/m/Y --}}
                <input type="text" name="start_date" 
                       value="{{ $start }}" 
                       class="form-control-sm datepicker-flat" placeholder="dd/mm/yyyy">
            </div>
            <div class="filter-item">
                <label class="filter-label">Sampai Tanggal</label>
                {{-- FIX: Flatpickr d/m/Y --}}
                <input type="text" name="end_date" 
                       value="{{ $end }}" 
                       class="form-control-sm datepicker-flat" placeholder="dd/mm/yyyy">
            </div>
            
            {{-- FIX TOMBOL TAMPILKAN BIAR COMPACT --}}
            <div style="flex: none; width: auto; padding-bottom: 2px;">
                <button type="submit" class="btn-filter"><i class="fa fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>

    @if($department)
        {{-- SUMMARY CARDS --}}
        <div class="stat-grid">
            <div class="stat-card sc-purple">
                <div class="stat-label">Jenis Item Keluar</div>
                <div class="stat-value">{{ number_format($summary['total_items'], 0, ',', '.') }} <span style="font-size:12px; color:#64748b; font-weight:400;">Jenis</span></div>
            </div>
            <div class="stat-card sc-orange">
                <div class="stat-label">Total Unit (Qty)</div>
                {{-- FIX: Tampilkan float --}}
                <div class="stat-value">{{ (float)$summary['total_qty'] }} <span style="font-size:12px; color:#64748b; font-weight:400;">Pcs</span></div>
            </div>
            <div class="stat-card sc-red">
                <div class="stat-label">Barang Terboros</div>
                <div class="stat-value" style="font-size:16px; line-height:1.4;">{{ $summary['top_item'] }}</div>
            </div>
        </div>

        {{-- MAIN TABLE (GROUPED BY ITEM) --}}
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th width="100" class="text-center">Detail</th>
                        <th width="200">Kode Barang</th>
                        <th>Nama Barang</th>
                        <th width="200" class="text-center">Satuan</th>
                        <th width="250" class="text-center">Total Diambil</th>
                        <th width="200" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupedData as $data)
                    <tr>
                        {{-- TOMBOL TOGGLE (PLUS) --}}
                        <td class="text-center">
                            <i class="fa fa-plus-square toggle-btn" data-target="#detail-{{ $data['item_id'] }}"></i>
                        </td>
                        <td style="font-weight:600; color:#4b5563;">{{ $data['code'] }}</td>
                        <td>{{ $data['name'] }}</td>
                        <td class="text-center text-muted">{{ $data['unit'] }}</td>
                        <td class="text-center text-bold" style="font-size:14px; color:#dc2626;">
                            {{-- FIX: Tampilkan float --}}
                            {{ (float)$data['total_qty'] }}
                        </td>
                        <td class="text-center">
                            <a href="{{ route('reports.stock-card', ['item_id' => $data['item_id'], 'start_date' => $start, 'end_date' => $end]) }}" class="btn btn-link btn-xs" style="color:#2563eb; text-decoration:none; font-weight:600;">
                                Lihat Kartu
                            </a>
                        </td>
                    </tr>
                    
                    {{-- DETAIL ROW (ACCORDION) --}}
                    <tr class="tr-detail" id="detail-{{ $data['item_id'] }}">
                        <td colspan="6" style="padding:0;">
                            <div class="detail-wrapper">
                                <h5 style="margin:0 0 8px 0; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Riwayat Pengambilan:</h5>
                                <table class="sub-table">
                                    <thead>
                                        <tr>
                                            <th width="120">Tanggal</th>
                                            <th width="150">No BON</th>
                                            <th class="text-right" width="100">Qty</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['history'] as $hist)
                                        <tr>
                                            <td>{{ $hist->bonHeader->date->format('d/m/Y') }}</td>
                                            <td>
                                                <a href="{{ route('bons.show', $hist->bonHeader->id) }}" target="_blank" style="font-weight:700; color:#2563eb; text-decoration:underline;">
                                                    {{ $hist->bonHeader->bon_number }}
                                                </a>
                                            </td>
                                            {{-- FIX: Tampilkan float --}}
                                            <td class="text-right font-weight-bold">{{ (float)$hist->issued_quantity }}</td>
                                            <td style="color:#64748b;">{{ $hist->bonHeader->notes ?: '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding:40px; color:#94a3b8;">
                            <i class="fa fa-info-circle" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            Tidak ada barang keluar untuk departemen ini pada periode tersebut.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- EMPTY STATE --}}
        <div style="text-align:center; padding: 80px 20px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; color:#64748b;">
            <i class="fa fa-building-o" style="font-size:48px; margin-bottom:20px; color:#e2e8f0;"></i>
            <h3 style="margin:0 0 8px 0; font-size:18px; font-weight:700; color:#111827;">Pilih Departemen</h3>
            <p style="margin:0; font-size:14px;">Silakan pilih departemen di atas untuk melihat analisa pemakaian barang.</p>
        </div>
    @endif
</div>

{{-- SCRIPT LOADER --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
{{-- Script Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
jQuery(document).ready(function($) {
    // FIX: Inisialisasi Flatpickr
    $(".datepicker-flat").flatpickr({
        altInput: true,      
        altFormat: "d/m/Y",  // Format visual: DAY/MONTH/YEAR
        dateFormat: "Y-m-d", // Format data: YYYY-MM-DD
        locale: "id",        
        allowInput: true     
    });

    // Init Select2
    $('.select2-enable').select2({
        width: '100%',
        placeholder: "-- Pilih Departemen --",
        allowClear: true
    });

    // FIX ACCORDION CLICK
    $(document).on('click', '.toggle-btn', function(){
        var target = $(this).data('target');
        var $icon = $(this);
        var $row = $(target);

        if($row.is(':visible')) {
            $row.hide();
            $icon.removeClass('fa-minus-square').addClass('fa-plus-square');
        } else {
            $row.show();
            $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
        }
    });
});
</script>
@endsection
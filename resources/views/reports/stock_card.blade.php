@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL KARTU STOK (THE DETECTIVE) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-history"></i>
        <strong>FUNGSI UTAMA:</strong> Halaman ini digunakan untuk menelusuri riwayat pergerakan (Keluar-Masuk) dari <strong>SATU ITEM</strong> spesifik secara kronologis.
    </div>

    <h4 class="help-h"><i class="fa fa-calculator text-primary"></i> Memahami Logika Saldo</h4>
    <ul class="help-list">
        <li>
            <strong>Saldo Awal:</strong> Angka ini adalah sisa stok dari periode sebelumnya (akumulasi transaksi <strong>sebelum</strong> "Tanggal Mulai" yang Anda pilih).
        </li>
        <li>
            <strong>Rumus Perhitungan:</strong>
            <br><code>Saldo Akhir = Saldo Awal + Total Masuk - Total Keluar</code>
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-file-text-o text-primary"></i> Jenis Dokumen</h4>
    <ul class="help-list">
        <li><span style="background:#dbeafe; color:#1e40af; padding:0 4px; font-size:10px; font-weight:700;">LPB</span> : Penerimaan Barang (Masuk). Menambah stok.</li>
        <li><span style="background:#ffedd5; color:#9a3412; padding:0 4px; font-size:10px; font-weight:700;">BON</span> : Permintaan User (Keluar). Mengurangi stok.</li>
        <li><span style="background:#f3e8ff; color:#6b21a8; padding:0 4px; font-size:10px; font-weight:700;">SO</span> : Stock Opname (Adjustment). Bisa menambah atau mengurangi tergantung selisih fisik.</li>
    </ul>

    <h4 class="help-h"><i class="fa fa-search text-primary"></i> Tips Investigasi</h4>
    <p class="help-p">
        Jika stok fisik tidak sesuai dengan sistem, gunakan kartu ini untuk mencari tahu: "Siapa yang mengambil barang terakhir kali?" atau "Kapan terakhir kali barang ini dibeli?".
    </p>
@endsection

@section('content')

{{-- 1. LOAD CSS SELECT2 & FLATPICKR --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
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

    /* Section */
    .section { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); padding: 16px; }
    
    /* Filter Box */
    .filter-box { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 150px; }
    .filter-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0; }
    
    /* Input Biasa */
    .form-control-sm { height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; width: 100%; }
    /* FIX: Input Flatpickr background putih */
    .form-control-sm.flatpickr-input { background-color: #fff !important; }
    
    /* === FIX TOTAL TAMPILAN SELECT2 (CLEAN LOOK) === */
    .select2-container { width: 100% !important; display: block; }
    
    /* 1. Main Box (Input) */
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        background-color: #fff;
        outline: none !important;
        box-shadow: none !important;
    }
    
    /* 2. Teks di dalam */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #374151 !important;
        line-height: 36px !important;
        font-size: 13px;
        padding-left: 12px;
        padding-right: 30px;
    }
    
    /* 3. Tombol 'X' (Clear) */
    .select2-container--default .select2-selection--single .select2-selection__clear {
        height: 36px !important;
        line-height: 36px !important;
        margin-right: 25px !important;
        font-size: 16px !important;
        color: #9ca3af !important;
        background-color: transparent !important;
        border: none !important;
        font-weight: normal !important;
        cursor: pointer;
        padding: 0 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #dc2626 !important;
    }

    /* 4. Panah Dropdown */
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
        top: 0 !important;
    }
    
    /* 5. Saat Aktif/Fokus */
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1) !important;
    }

    /* Buttons */
    .btn-filter { 
        height: 38px; 
        padding: 0 20px; 
        background: #2563eb; 
        color: #fff; 
        border: none; 
        border-radius: 8px; 
        font-weight: 600; 
        font-size: 13px; 
        cursor: pointer; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center;
        gap: 6px; 
        white-space: nowrap; 
        width: auto; 
    }
    .btn-filter:hover { background: #1d4ed8; }

    .btn-export { 
        height: 38px; 
        padding: 0 20px; 
        background: #10b981; 
        color: #fff; 
        border: none; 
        border-radius: 8px; 
        font-weight: 600; 
        font-size: 13px; 
        text-decoration: none; 
        display: inline-flex; 
        align-items: center; 
        gap: 6px; 
    }
    .btn-export:hover { background: #059669; }

    /* Tombol Kembali */
    .btn-back {
        height: 38px; padding: 0 16px; background: #fff; border: 1px solid #d1d5db; 
        border-radius: 8px; color: #374151; font-size: 13px; font-weight: 600; 
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-back:hover { background: #f3f4f6; color: #111827; }

    /* Table */
    .table-container { max-height: 65vh; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; }
    .table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .table th { position: sticky; top: 0; background: #f8fafc; z-index: 10; padding: 12px 16px; text-align: left; font-weight: 700; color: #475467; border-bottom: 2px solid #e2e8f0; }
    .table td { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }
    
    /* Alignment & Color */
    .text-green { color: #16a34a; font-weight: 700; }
    .text-red { color: #dc2626; font-weight: 700; }
    .text-blue { color: #2563eb; font-weight: 700; text-decoration: underline; }
    .text-right { text-align: right !important; }
    .text-center { text-align: center !important; }

    /* Summary Stats */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 16px; }
    .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
    .stat-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
    .stat-value { font-size: 24px; color: #0f172a; font-weight: 800; margin-top: auto; }
    
    .sc-blue { border-left: 4px solid #3b82f6; }
    .sc-green { border-left: 4px solid #10b981; }
    .sc-red { border-left: 4px solid #ef4444; }
    .sc-dark { border-left: 4px solid #334155; }
</style>

<div class="page-container">
    {{-- HEADER --}}
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Kartu Stok (Stock Card)</h1>
            <p style="font-size:12px; color:#64748b; margin-top:4px;">Lacak riwayat pergerakan barang secara detail.</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('reports.index') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> Kembali ke Laporan
            </a>
            @if($selectedItem)
                 <a href="{{ route('reports.stock-card.export', request()->all()) }}" class="btn-export">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                 </a>
            @endif
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="section">
        <form method="GET" action="{{ route('reports.stock-card') }}" class="filter-box">
            {{-- ITEM SELECT --}}
            <div class="filter-item" style="flex: 2;">
                <label class="filter-label">Pilih Barang</label>
                <select name="item_id" id="item_id_select" class="form-control-sm" required>
                    <option value="">-- Cari Nama / Kode Barang --</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->name }} ({{ $item->code }}) - [{{ $item->unit }}]
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item">
                <label class="filter-label">Dari Tanggal</label>
                {{-- FIX: Flatpickr d/m/Y --}}
                <input type="text" name="start_date" 
                       value="{{ $startDate }}" 
                       class="form-control-sm datepicker-flat" placeholder="dd/mm/yyyy">
            </div>
            <div class="filter-item">
                <label class="filter-label">Sampai Tanggal</label>
                {{-- FIX: Flatpickr d/m/Y --}}
                <input type="text" name="end_date" 
                       value="{{ $endDate }}" 
                       class="form-control-sm datepicker-flat" placeholder="dd/mm/yyyy">
            </div>
            <div class="filter-item" style="flex: 0;">
                <label class="filter-label">&nbsp;</label>
                <button type="submit" class="btn-filter"><i class="fa fa-search"></i> Tampilkan</button>
            </div>
        </form>
    </div>

    @if($selectedItem)
        {{-- SUMMARY INFO --}}
        <div class="stat-grid">
            <div class="stat-card sc-blue">
                <div class="stat-label">Saldo Awal</div>
                <div class="stat-value" style="color:#2563eb">
                    {{-- FIX: Float --}}
                    {{ (float)$openingBalance }} <small style="font-size:12px; color:#64748b">{{ $selectedItem->unit }}</small>
                </div>
                <div style="font-size:11px; color:#9ca3af; margin-top:4px;">Per {{ date('d M Y', strtotime($startDate)) }}</div>
            </div>
            <div class="stat-card sc-green">
                <div class="stat-label">Total Masuk</div>
                <div class="stat-value" style="color:#16a34a">
                    {{-- FIX: Float --}}
                    +{{ (float)$totalIn }}
                </div>
            </div>
            <div class="stat-card sc-red">
                <div class="stat-label">Total Keluar</div>
                <div class="stat-value" style="color:#dc2626">
                    {{-- FIX: Float --}}
                    -{{ (float)$totalOut }}
                </div>
            </div>
            <div class="stat-card sc-dark">
                <div class="stat-label">Saldo Akhir</div>
                <div class="stat-value">
                    {{-- FIX: Float --}}
                    {{ (float)$endingBalance }} <small style="font-size:12px; color:#64748b">{{ $selectedItem->unit }}</small>
                </div>
                <div style="font-size:11px; color:#9ca3af; margin-top:4px;">Per {{ date('d M Y', strtotime($endDate)) }}</div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:150px;">Tanggal</th>
                        <th style="width:130px;">No. Dokumen</th>
                        <th class="text-center" style="width:130px;">Tipe</th>
                        <th style="width:200px;">Departemen</th> 
                        <th>Keterangan</th>
                        <th class="text-center" style="width:130px;">Masuk</th>
                        <th class="text-center" style="width:130px; background:#f8fafc;">Keluar</th>
                        <th class="text-center" style="width:130px;">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- BARIS SALDO AWAL --}}
                    <tr style="background-color: #f9fafb; color:#64748b; font-style:italic;">
                        <td class="text-center">{{ date('d/m/Y', strtotime($startDate)) }}</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td>-</td>
                        <td><strong>SALDO AWAL</strong></td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center" style="background:#f1f5f9; font-weight:bold; color:#334155;">
                            {{-- FIX: Float --}}
                            {{ (float)$openingBalance }}
                        </td>
                    </tr>

                    @forelse($transactions as $t)
                    <tr>
                        <td class="text-center">{{ date('d/m/Y', strtotime($t->date)) }}</td>
                        <td>
                            @if($t->link && $t->link !== '#')
                                <a href="{{ $t->link }}" target="_blank" class="text-blue">{{ $t->number }}</a>
                            @else
                                {{ $t->number }}
                            @endif
                        </td>
                        <td class="text-center">
                            @if($t->type == 'LPB') <span style="background:#dbeafe; color:#1e40af; padding:3px 8px; border-radius:4px; font-size:11px; font-weight:700;">LPB</span>
                            @elseif($t->type == 'BON') <span style="background:#ffedd5; color:#9a3412; padding:3px 8px; border-radius:4px; font-size:11px; font-weight:700;">BON</span>
                            @elseif($t->type == 'SO') <span style="background:#f3e8ff; color:#6b21a8; padding:3px 8px; border-radius:4px; font-size:11px; font-weight:700;">SO</span>
                            @endif
                        </td>
                        <td>
                            @if($t->department && $t->department != '-')
                                <strong style="color:#0f172a;">{{ $t->department }}</strong>
                            @else
                                <span style="color:#9ca3af;">-</span>
                            @endif
                        </td>
                        <td style="color:#475467;">{{ $t->description ?: '-' }}</td>
                        <td class="text-center">
                            {{-- FIX: Float --}}
                            @if($t->in > 0) <span class="text-green">+{{ (float)$t->in }}</span> @else - @endif
                        </td>
                        <td class="text-center">
                            {{-- FIX: Float --}}
                            @if($t->out > 0) <span class="text-red">-{{ (float)$t->out }}</span> @else - @endif
                        </td>
                        <td class="text-center" style="background:#f9fafb; font-weight:700; color:#1e293b;">
                            {{-- FIX: Float --}}
                            {{ (float)$t->balance }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding:30px; color:#94a3b8;">
                            Tidak ada transaksi pada periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- EMPTY STATE --}}
        <div style="text-align:center; padding: 80px 20px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; color:#64748b;">
            <i class="fa fa-search" style="font-size:48px; margin-bottom:20px; color:#e2e8f0;"></i>
            <h3 style="margin:0 0 8px 0; font-size:18px; font-weight:700; color:#111827;">Silakan Pilih Barang</h3>
            <p style="margin:0; font-size:14px;">Gunakan kolom pencarian di atas untuk melihat histori pergerakan stok.</p>
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

    var $select = $('#item_id_select').select2({
        placeholder: "-- Ketik Nama / Kode Barang --",
        allowClear: true,
        width: '100%'
    });

    $select.on('select2:open', function (e) {
        window.setTimeout(function () {
            document.querySelector('.select2-container--open .select2-search__field').focus();
        }, 0);
    });
});
</script>
@endsection
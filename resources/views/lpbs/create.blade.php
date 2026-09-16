@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL TAMBAH LPB (CREATE) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>PENTING:</strong> Data yang Anda simpan di sini akan langsung <strong>MENAMBAH STOK FISIK</strong> di Master Barang. Pastikan hitungan fisik sudah sesuai.
    </div>

    <h4 class="help-h"><i class="fa fa-keyboard-o text-primary"></i> Cara Pengisian Form</h4>
    <ul class="help-list">
        <li>
            <strong>Nomor LPB:</strong> Isi sesuai format standar perusahaan (Contoh: LPB/Tahun/Bulan/Urut). Pastikan nomor unik.
        </li>
        <li>
            <strong>Input Barang (Multi-Row):</strong>
            <ul style="margin-top:5px;">
                <li>Gunakan tombol <strong>"+ Tambah Baris"</strong> untuk memasukkan banyak item sekaligus dalam satu dokumen LPB.</li>
                <li>Pilih barang dari dropdown, maka <strong>Satuan (Unit)</strong> akan terisi otomatis.</li>
            </ul>
        </li>
        <li>
            <strong>Jumlah Diterima:</strong> Masukkan angka riil barang yang diterima dari vendor. Jangan input barang reject/rusak di sini (kembalikan ke vendor).
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-lightbulb-o text-primary"></i> Tips Admin</h4>
    <p class="help-p">
        Jika barang belum ada di dropdown "Cari Barang", berarti item tersebut belum terdaftar. Silakan ke menu <strong>Master Data > Daftar Barang</strong> terlebih dahulu untuk mendaftarkan item baru.
    </p>
@endsection

@section('content')
{{-- Load Select2 CSS --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
{{-- Load Flatpickr CSS (Format d/m/Y) --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
{{-- Load Google Fonts (Inter) --}}
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* --- MODERN VARIABLES & RESET --- */
    :root {
        /* Warna Biru yang lebih solid dan premium */
        --primary: #2563eb; 
        --primary-dark: #1d4ed8;
        --primary-gradient: linear-gradient(145deg, #3b82f6, #1d4ed8);
        
        --bg-body: #f1f5f9;
        --bg-card: #ffffff;
        --text-dark: #0f172a;
        --text-gray: #64748b;
        --border-color: #e2e8f0;
        --danger: #ef4444;
        --danger-hover: #dc2626;
        --success: #10b981;
        --radius-md: 8px;
        --radius-lg: 12px;
        
        --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Inter', sans-serif;
        color: var(--text-dark);
        -webkit-font-smoothing: antialiased;
    }

    /* --- FIX: PERKECIL UKURAN FLATPICKR (Sesuai Request) --- */
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

    /* --- LAYOUT FULL WIDTH --- */
    .page-container {
        width: 100%;
        padding: 30px 40px;
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
    
    /* Ikon Header Besar */
    .header-visual-icon {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, #e0e7ff 0%, #dbeafe 100%);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 24px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.6), 0 4px 6px -1px rgba(37, 99, 235, 0.1);
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
    
    /* Tombol Kembali - Fixed Alignment */
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
    .btn-back i {
        font-size: 14px;
        margin-top: -1px;
    }
    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: var(--primary);
        text-decoration: none;
    }

    /* --- CARD STYLING --- */
    .card-section {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: var(--shadow-soft);
        margin-bottom: 24px;
        overflow: hidden;
    }
    
    .card-header {
        padding: 16px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 12px;
        background: #fff;
    }
    .header-icon-wrapper-small {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    .card-title {
        font-size: 15px;
        font-weight: 600;
        margin: 0;
        color: var(--text-dark);
    }
    .card-body {
        padding: 24px;
    }

    /* --- FORM ELEMENTS --- */
    .form-group {
        margin-bottom: 16px;
    }
    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .form-control {
        width: 100%;
        height: 44px;
        padding: 10px 14px;
        font-size: 14px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        background-color: #fff;
        color: var(--text-dark);
        transition: all 0.2s ease;
    }
    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }
    .form-control[readonly] {
        background-color: #f8fafc;
        border-color: #f1f5f9;
        color: #94a3b8;
        cursor: default;
    }
    /* FIX: Input Flatpickr agar background putih */
    .form-control.flatpickr-input { background-color: #fff !important; }

    textarea.form-control {
        height: auto;
        min-height: 80px;
        resize: vertical;
        line-height: 1.5;
    }

    /* --- TABLE --- */
    .table-container {
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
    }
    .table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
        border-spacing: 0;
    }
    .table thead {
        background-color: #f8fafc;
    }
    .table thead th {
        padding: 14px 16px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-gray);
        border-bottom: 1px solid var(--border-color);
        text-align: left;
    }
    .table tbody td {
        padding: 10px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle !important; 
    }
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .row-number {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        color: #94a3b8;
        font-weight: 600;
        font-size: 13px;
    }

    .table .form-control {
        height: 40px; 
        font-size: 13px;
        border-radius: 6px;
    }

    /* --- BUTTONS --- */
    .btn-submit {
        background: var(--primary-gradient);
        color: white;
        padding: 0 32px;
        height: 44px;
        border: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        box-shadow: 0 4px 10px -2px rgba(37, 99, 235, 0.4);
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        line-height: 1;
    }
    .btn-submit i {
        font-size: 16px;
        margin-top: -1px;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.5);
        background: linear-gradient(145deg, #2563eb, #1e40af);
    }

    .btn-add-row {
        background: white;
        color: var(--primary);
        border: 1px dashed var(--primary);
        padding: 0 20px;
        height: 36px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        line-height: 1;
    }
    .btn-add-row:hover {
        background: #eff6ff;
        border-style: solid;
    }

    .btn-delete {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: none;
        background: #fee2e2;
        color: var(--danger);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-delete i {
        font-size: 14px;
    }
    .btn-delete:hover {
        background: var(--danger);
        color: white;
        box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3);
    }

    /* --- SELECT2 FIXES & STYLING --- */
    .select2-container .select2-selection--single {
        height: 40px !important;
        border: 1px solid var(--border-color) !important;
        border-radius: var(--radius-md) !important;
        display: flex;
        align-items: center;
        background-color: #fff;
        position: relative;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        color: var(--text-dark) !important;
        font-size: 13px;
        padding-left: 12px;
        padding-right: 35px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px !important;
        right: 8px !important;
    }
    
    /* Tombol X Fix - Right Aligned Perfectly */
    .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        right: 25px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin-right: 0 !important;
        color: var(--danger) !important;
        font-weight: bold;
        font-size: 18px;
        z-index: 10;
        line-height: 0;
        height: auto;
        width: auto;
    }

    .select2-dropdown {
        border: 1px solid var(--border-color) !important;
        border-radius: 8px !important;
        box-shadow: var(--shadow-hover) !important;
        padding: 5px;
    }
    .select2-search__field {
        border-radius: 6px !important;
        padding: 8px !important;
        border: 1px solid var(--border-color) !important;
    }
    .select2-results__option {
        padding: 8px 12px;
        font-size: 13px;
        border-radius: 4px;
    }
    .select2-results__option--highlighted[aria-selected] {
        background-color: #eff6ff !important;
        color: var(--primary) !important;
        font-weight: 500;
    }

    /* Grid Layout Helpers */
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .full-width {
        grid-column: span 2;
    }

    @media (max-width: 768px) {
        .page-container { padding: 20px; }
        .grid-2 { grid-template-columns: 1fr; gap: 16px; }
        .full-width { grid-column: span 1; }
        .dashboard-header { flex-direction: column; align-items: flex-start; gap: 15px; }
        .btn-back { width: 100%; }
        .header-title-wrapper { width: 100%; }
    }
</style>

<div class="page-container">

    {{-- HEADER WITH VISUAL --}}
    <div class="dashboard-header">
        <div class="header-title-wrapper">
            <div class="header-visual-icon">
                <i class="fa fa-plus-circle"></i>
            </div>
            <div class="header-content">
                <h1>Tambah LPB Baru</h1>
                <p>Catat penerimaan barang masuk ke gudang dengan sistematis.</p>
            </div>
        </div>
        <div>
            <a href="{{ route('lpbs.index') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> <span>Kembali ke Daftar</span>
            </a>
        </div>
    </div>

    {{-- ERROR ALERT --}}
    @if ($errors->any())
        <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-md); padding: 15px; margin-bottom: 20px;">
            <strong style="display:flex; align-items:center; gap:8px; margin-bottom:5px;"><i class="fa fa-exclamation-triangle"></i> Perhatian!</strong>
            <ul style="margin:0; padding-left: 25px; font-size:13px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('lpbs.store') }}" method="POST" id="lpb-form">
        {{ csrf_field() }}

        {{-- SECTION 1: HEADER --}}
        <div class="card-section">
            <div class="card-header" style="justify-content: space-between;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="header-icon-wrapper-small">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <h3 class="card-title">Informasi Header LPB</h3>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-gray);"><i class="fa fa-calendar text-primary"></i> Tahun LPB:</span>
                    <select name="search_year" id="search_year_select" class="form-control" style="height: 36px; width: 130px; font-size: 13px; padding: 4px 10px; border-radius: 6px; background-color: #fff;">
                        @php $curYear = (int) date('Y'); @endphp
                        @for ($y = $curYear; $y >= 2018; $y--)
                            <option value="{{ $y }}" {{ $y == $curYear ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                        <option value="">Semua Tahun</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nomor LPB <span style="color: var(--danger)">*</span></label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" name="lpb_number" id="lpb_number_input" class="form-control"
                                   placeholder="Contoh: 1150"
                                   value="{{ old('lpb_number') }}" required autocomplete="off">
                            <button type="button" class="btn" id="btn-check-lpb" style="height: 44px; padding: 0 22px; border-radius: var(--radius-md); background: var(--primary); color: white; border: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; white-space: nowrap; transition: all 0.2s; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                                <i class="fa fa-search" id="icon-check-lpb"></i> <span id="text-check-lpb">Check</span>
                            </button>
                        </div>
                        <small style="font-size: 11px; color: var(--text-gray); margin-top: 5px; display: block;">
                            <i class="fa fa-info-circle text-primary"></i> Masukkan No. LPB lalu klik <strong>Check</strong> untuk menarik data dari Otto Master.
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Penerimaan <span style="color: var(--danger)">*</span></label>
                        <input type="text" name="date" id="date-input" class="form-control datepicker-flat"
                               value="{{ old('date') ? old('date') : date('Y-m-d') }}" 
                               placeholder="dd/mm/yyyy" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor PO (Dari Otto Master)</label>
                        <input type="text" name="no_po" id="po-number-input" class="form-control"
                               placeholder="Nomor PO akan terisi otomatis"
                               value="{{ old('no_po') }}" readonly style="background-color: #f8fafc; font-weight: 600; color: #1e293b;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pemasok / Vendor</label>
                        <input type="text" name="vendor" id="vendor-input" class="form-control"
                               placeholder="Nama Supplier"
                               value="{{ old('vendor') }}">
                    </div>
                    <div class="form-group full-width" style="margin-bottom:0;">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="notes" id="notes-input" class="form-control"
                                  placeholder="Keterangan kondisi barang atau informasi penting lainnya...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 2: ITEMS --}}
        <div class="card-section">
            <div class="card-header" style="justify-content: space-between;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="header-icon-wrapper-small" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <h3 class="card-title">Detail Barang Masuk</h3>
                </div>
                <button type="button" class="btn-add-row" id="btn-add-row">
                    <i class="fa fa-plus"></i> Tambah Baris
                </button>
            </div>
            
            <div class="card-body" style="padding:0;">
                <div style="padding: 20px;">
                    <div class="table-container">
                        <table class="table" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px; text-align: center;">No</th>
                                    <th style="width: 180px;">Kode Barang</th>
                                    <th style="min-width: 320px;">Nama / Deskripsi Barang</th>
                                    <th style="width: 110px;">Satuan</th>
                                    <th style="width: 130px;">Jumlah</th>
                                    <th style="width: 150px; text-align: right;">Nilai / Total</th>
                                    <th style="width: 60px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="empty-hint-row">
                                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 35px 20px;">
                                        <i class="fa fa-search" style="font-size: 24px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                        Masukkan <strong>Nomor LPB</strong> di atas dan klik tombol <strong>Check</strong> untuk memuat daftar barang dari sistem Otto, atau klik <strong>+ Tambah Baris</strong> untuk input manual.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 20px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; align-items: center;">
                    <button type="submit" class="btn-submit">
                        <i class="fa fa-save"></i> <span>Simpan LPB</span>
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

{{-- JAVASCRIPT --}}
<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
{{-- Script Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    (function($) {
        'use strict';

        var fpDate = null;

        // Format angka ke format Rupiah / Ribuan
        function formatNumber(num) {
            if (num === null || num === undefined || num === '') return '0';
            return parseFloat(num).toLocaleString('id-ID');
        }

        // Renumber Rows
        function renumberRows() {
            var rows = $('#items-table tbody tr:not(#empty-hint-row)');
            if (rows.length === 0) {
                if ($('#empty-hint-row').length === 0) {
                    $('#items-table tbody').html(`
                        <tr id="empty-hint-row">
                            <td colspan="7" style="text-align: center; color: #94a3b8; padding: 35px 20px;">
                                <i class="fa fa-search" style="font-size: 24px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                Masukkan <strong>Nomor LPB</strong> di atas dan klik tombol <strong>Check</strong> untuk memuat daftar barang, atau klik <strong>+ Tambah Baris</strong>.
                            </td>
                        </tr>
                    `);
                }
                return;
            }

            rows.each(function(index) {
                $(this).find('.row-number').text(index + 1);
                $(this).find('select, input').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        let newName = name.replace(/items\[\d+\]/, 'items[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        }

        // Tambah Baris Manual
        function addManualRow(index) {
            $('#empty-hint-row').remove();
            let tbody = $('#items-table tbody');
            let newRow = `
                <tr>
                    <td style="text-align: center;">
                        <div class="row-number">${index + 1}</div>
                    </td>
                    <td>
                        <input type="text" name="items[${index}][item_code]" class="form-control" placeholder="Kode Barang">
                        <input type="hidden" name="items[${index}][foreign_item_id]" value="">
                    </td>
                    <td>
                        <input type="text" name="items[${index}][item_name]" class="form-control" placeholder="Nama / Deskripsi Barang">
                    </td>
                    <td>
                        <input type="text" name="items[${index}][unit]" class="form-control unit-input" value="PCS" placeholder="Satuan">
                    </td>
                    <td>
                        <input type="number" name="items[${index}][quantity]" class="form-control qty-input" min="0.01" step="0.01" value="1">
                    </td>
                    <td>
                        <input type="text" name="items[${index}][price]" class="form-control price-input" value="0" style="text-align: right;">
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-delete btn-remove-row" title="Hapus Baris">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(newRow);
        }

        // Render List Items dari Otto Master Foreign Table
        function renderForeignItems(items) {
            let tbody = $('#items-table tbody');
            tbody.empty();

            if (!items || items.length === 0) {
                tbody.html(`
                    <tr id="empty-hint-row">
                        <td colspan="7" style="text-align: center; color: #ef4444; padding: 25px;">
                            <i class="fa fa-exclamation-circle"></i> Tidak ada rincian barang untuk No. LPB ini.
                        </td>
                    </tr>
                `);
                return;
            }

            items.forEach(function(item, index) {
                let qty = item.units ? item.units : (item.unitb ? item.unitb : 1);
                let price = item.th_unitb ? parseFloat(item.th_unitb) : 0;
                let formattedPrice = formatNumber(price);

                let rowHtml = `
                    <tr>
                        <td style="text-align: center;">
                            <div class="row-number">${index + 1}</div>
                        </td>
                        <td>
                            <input type="text" name="items[${index}][item_code]" class="form-control" 
                                   value="${item.item_code || ''}" readonly 
                                   style="background: #f8fafc; font-weight: 600; color: #1e293b;">
                            <input type="hidden" name="items[${index}][foreign_item_id]" value="${item.item_id || ''}">
                        </td>
                        <td>
                            <input type="text" name="items[${index}][item_name]" class="form-control" 
                                   value="${item.item_desc || ''}" readonly 
                                   style="background: #f8fafc;" title="${item.item_desc || ''}">
                        </td>
                        <td>
                            <input type="text" name="items[${index}][unit]" class="form-control unit-input" 
                                   value="PCS" placeholder="Satuan">
                        </td>
                        <td>
                            <input type="number" name="items[${index}][quantity]" class="form-control qty-input" 
                                   min="0.01" step="0.01" value="${qty}">
                        </td>
                        <td>
                            <input type="text" name="items[${index}][price]" class="form-control price-input" 
                                   value="${formattedPrice}" readonly 
                                   style="background: #f8fafc; text-align: right; font-weight: 500;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-delete btn-remove-row" title="Hapus Baris">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(rowHtml);
            });
        }

        $(document).ready(function() {
            // Inisialisasi Flatpickr
            fpDate = flatpickr(".datepicker-flat", {
                altInput: true,      
                altFormat: "d/m/Y",  
                dateFormat: "Y-m-d", 
                locale: "id",        
                allowInput: true     
            });

            // Add Row Manual
            $('#btn-add-row').on('click', function() {
                let rowCount = $('#items-table tbody tr:not(#empty-hint-row)').length;
                addManualRow(rowCount);
            });

            // Remove Row
            $('#items-table').on('click', '.btn-remove-row', function() {
                $(this).closest('tr').remove();
                renumberRows();
            });

            // Tombol Check LPB
            $('#btn-check-lpb').on('click', function() {
                let noLpb = $('#lpb_number_input').val().trim();
                let year = $('#search_year_select').val();

                if (!noLpb) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Nomor LPB Kosong',
                            text: 'Silakan ketik nomor LPB terlebih dahulu.'
                        });
                    } else {
                        alert('Silakan ketik nomor LPB terlebih dahulu.');
                    }
                    $('#lpb_number_input').focus();
                    return;
                }

                let $btn = $(this);
                let $icon = $('#icon-check-lpb');
                let $text = $('#text-check-lpb');

                // Loading State
                $btn.prop('disabled', true).css('opacity', '0.7');
                $icon.removeClass('fa-search').addClass('fa-spinner fa-spin');
                $text.text('Mengecek...');

                $.ajax({
                    url: '{{ route("lpbs.check-foreign") }}',
                    type: 'GET',
                    data: {
                        no_lpb: noLpb,
                        year: year
                    },
                    dataType: 'json',
                    success: function(response) {
                        $btn.prop('disabled', false).css('opacity', '1');
                        $icon.removeClass('fa-spinner fa-spin').addClass('fa-search');
                        $text.text('Check');

                        if (response.status === 'success') {
                            // Isi tanggal jika ada
                            if (response.header && response.header.formatted_date && fpDate) {
                                fpDate.setDate(response.header.formatted_date);
                            }

                            // Isi No PO
                            if (response.header && response.header.no_po) {
                                $('#po-number-input').val(response.header.no_po);
                            }

                            // Isi Supplier / Vendor
                            if (response.header && response.header.nama_supplier) {
                                $('#vendor-input').val(response.header.nama_supplier);
                            }

                            // Render list item ke tabel
                            renderForeignItems(response.items);

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Data Ditemukan!',
                                    text: 'Berhasil menarik ' + response.items.length + ' item untuk LPB No. ' + noLpb,
                                    timer: 2200,
                                    showConfirmButton: false
                                });
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).css('opacity', '1');
                        $icon.removeClass('fa-spinner fa-spin').addClass('fa-search');
                        $text.text('Check');

                        let errMsg = 'Data LPB tidak ditemukan di Otto Master.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Perhatian',
                                text: errMsg
                            });
                        } else {
                            alert(errMsg);
                        }
                    }
                });
            });

            // Tekan Enter pada input No LPB langsung trigger tombol Check
            $('#lpb_number_input').on('keydown', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#btn-check-lpb').click();
                }
            });

        });

    })(jQuery);
</script>
@endsection
@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL EDIT LPB (UPDATE) === --}}
@section('help-content')
    <div class="help-alert" style="background:#fee2e2; border-left-color:#ef4444; color:#991b1b;">
        <i class="fa fa-exclamation-triangle"></i>
        <strong>WARNING: SISTEM ROLLBACK!</strong><br>
        Mengedit data LPB ini akan mereset stok barang terkait (stok lama dikurangi dulu, lalu ditambah dengan inputan baru Anda).
    </div>

    <h4 class="help-h"><i class="fa fa-pencil text-primary"></i> Kapan Harus Mengedit?</h4>
    <ul class="help-list">
        <li><strong>Salah Input Qty:</strong> Misal fisik yang diterima 10, tapi terinput 100. Segera perbaiki agar stok master tidak <em>blunder</em>.</li>
        <li><strong>Salah Pilih Barang:</strong> Mengganti item yang salah dengan yang benar.</li>
        <li><strong>Revisi Tanggal:</strong> Menyesuaikan tanggal terima dengan surat jalan yang valid.</li>
    </ul>

    <h4 class="help-h"><i class="fa fa-check-circle text-primary"></i> Tips Keamanan Data</h4>
    <p class="help-p">
        Sebelum menyimpan perubahan, pastikan barang yang akan Anda kurangi jumlahnya <strong>BELUM DIPAKAI (ISSUED)</strong> oleh user lain via BON. Jika stok fisik sudah terlanjur keluar, edit ini bisa menyebabkan stok menjadi minus.
    </p>
@endsection

@section('content')
{{-- Load Select2 CSS --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css" rel="stylesheet">
{{-- Load Flatpickr CSS (Format d/m/Y) --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* --- CSS SAMA PERSIS DENGAN CREATE UNTUK KONSISTENSI --- */
    :root {
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
        font-size: 12px !important;
        width: 310px !important;
    }
    .flatpickr-rContainer, .flatpickr-days, .dayContainer {
        width: 310px !important;
    }
    .flatpickr-day {
        height: 32px !important;
        line-height: 32px !important;
        max-width: 42px !important;
    }
    .flatpickr-current-month {
        font-size: 110% !important;
        padding-top: 10px !important;
    }

    /* --- LAYOUT UTAMA: Diperluas tanpa margin kosong berlebih --- */
    .page-container {
        width: 100%;
        padding: 0 0 20px 0;
        box-sizing: border-box;
    }

    /* Category indicators */
    .dot-cat { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    .dot-gen { background-color: #16a34a; box-shadow: 0 0 0 2px #dcfce7; } 
    .dot-app { background-color: #d97706; box-shadow: 0 0 0 2px #fef3c7; }

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
    
    /* Header Icon Edit Variation (Orange) */
    .header-visual-icon {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d97706; /* Dark Orange */
        font-size: 24px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.6), 0 4px 6px -1px rgba(245, 158, 11, 0.1);
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
    
    /* Button Back Fixed */
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
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
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

    /* --- FORM ELEMENTS FIXED GAP --- */
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
        cursor: not-allowed;
    }
    /* FIX: Input Flatpickr agar background putih */
    .form-control.flatpickr-input { background-color: #fff !important; }

    textarea.form-control {
        height: auto;
        min-height: 80px;
        resize: vertical;
        line-height: 1.5;
    }

    /* --- TABLE ALIGNMENT FIXED --- */
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
        vertical-align: middle !important; /* CENTER FIX */
    }
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    .table .form-control {
        height: 40px;
        font-size: 13px;
        border-radius: 6px;
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
                <i class="fa fa-pencil-square-o"></i>
            </div>
            <div class="header-content">
                <h1>Edit LPB <span class="badge-lpb">{{ $hdr->lpb_number }}</span></h1>
                <p>Perbarui informasi penerimaan barang dengan seksama.</p>
            </div>
        </div>
        <div>
            <a href="{{ route('lpbs.index') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> <span>Kembali ke Daftar</span>
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-md); padding: 15px; margin-bottom: 20px;">
            <strong style="display:flex; align-items:center; gap:8px; margin-bottom:5px;"><i class="fa fa-exclamation-triangle"></i> Terjadi Kesalahan!</strong>
            <ul style="margin:0; padding-left: 25px; font-size:13px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('lpbs.update', $hdr->id) }}" method="POST" id="lpb-form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        {{-- SECTION 1: HEADER --}}
        <div class="card-section">
            <div class="card-header">
                <div class="header-icon-wrapper-small">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <h3 class="card-title">Edit Informasi Header LPB</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nomor LPB</label>
                        <input type="text" name="lpb_number" class="form-control"
                               value="{{ old('lpb_number', $hdr->lpb_number) }}" readonly
                               style="background-color: #f1f5f9; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Penerimaan <span style="color: var(--danger)">*</span></label>
                        {{-- FIX: Ganti type="date" ke type="text" & tambah class datepicker-flat --}}
                        <input type="text" name="date" class="form-control datepicker-flat"
                               value="{{ old('date', $hdr->date ? $hdr->date->format('Y-m-d') : date('Y-m-d')) }}" 
                               placeholder="dd/mm/yyyy" required>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Pemasok / Referensi</label>
                        <input type="text" name="vendor" class="form-control"
                               value="{{ old('vendor', isset($hdr->vendor) ? $hdr->vendor : '') }}">
                    </div>
                    <div class="form-group full-width" style="margin-bottom:0;">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="notes" class="form-control">{{ old('notes', isset($hdr->notes) ? $hdr->notes : '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 2: DETAIL --}}
        <div class="card-section">
            <div class="card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="header-icon-wrapper-small" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <h3 class="card-title">Detail Barang</h3>
                </div>
                <div style="font-size:11px; color:#64748b; font-weight:600;">
                    Pilih item dari dropdown di bawah untuk menambah ke tabel.
                </div>
            </div>
            
            <div class="card-body" style="padding:0;">
                <div style="padding: 20px;">
                    {{-- CARI & TAMBAH ITEM (AJAX ON-DEMAND SEPERTI REQUESTS/CREATE) --}}
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" style="font-weight: 700;">Cari & Tambah Item</label>
                        <select id="itemPicker" class="form-control" style="width: 100%;"></select>
                        <small style="font-size: 11px; color: var(--text-gray); margin-top: 4px; display: block;">
                            <i class="fa fa-info-circle text-primary"></i> Ketik kode atau nama barang untuk mencari via server (cepat & hemat memori), lalu klik barang untuk menambahkannya ke tabel.
                        </small>
                    </div>

                    <div class="table-container">
                        <table class="table" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px; text-align: center;">No</th>
                                    <th>Nama Barang</th>
                                    <th style="width: 140px; text-align: center;">Satuan</th>
                                    <th style="width: 180px;">Jumlah</th>
                                    <th style="width: 60px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $rows = [];
                                    if(old('items')) {
                                        $oldItems = old('items');
                                        $itemIds = array_filter(array_map(function($r) { return isset($r['item_id']) ? $r['item_id'] : null; }, $oldItems));
                                        $itemModels = count($itemIds) ? \App\Item::with('category')->whereIn('id', $itemIds)->get()->keyBy('id') : collect([]);
                                        foreach($oldItems as $r) {
                                            $iId = isset($r['item_id']) ? $r['item_id'] : null;
                                            $rows[] = [
                                                'item_id' => $iId,
                                                'item'    => $iId && isset($itemModels[$iId]) ? $itemModels[$iId] : null,
                                                'unit'    => isset($r['unit']) ? $r['unit'] : '',
                                                'quantity'=> isset($r['quantity']) ? $r['quantity'] : ''
                                            ];
                                        }
                                    } else {
                                        foreach($hdr->details as $d) {
                                            $rows[] = [
                                                'item_id' => $d->item_id,
                                                'item'    => $d->item,
                                                'unit'    => isset($d->item->unit) ? $d->item->unit : '',
                                                'quantity'=> (float) $d->quantity
                                            ];
                                        }
                                    }
                                @endphp

                                @forelse($rows as $idx => $row)
                                    @php
                                        $valId   = isset($row['item_id']) ? $row['item_id'] : '';
                                        $valItem = isset($row['item']) ? $row['item'] : null;
                                        $valUnit = isset($row['unit']) ? $row['unit'] : ($valItem ? $valItem->unit : '');
                                        $valQty  = isset($row['quantity']) ? $row['quantity'] : '';
                                        $code    = $valItem ? ($valItem->harmoni_code ?: $valItem->code) : '';
                                        $name    = $valItem ? $valItem->name : 'Item #'.$valId;
                                        $catCode = ($valItem && $valItem->category) ? strtoupper($valItem->category->code) : '';
                                        $isApp   = in_array($catCode, ['AK', 'PK']);
                                    @endphp
                                    <tr class="item-row" data-item-id="{{ $valId }}">
                                        <td style="text-align: center; vertical-align: middle;">
                                            <div class="row-number">{{ $idx + 1 }}</div>
                                            <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $valId }}" class="item-id-input">
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <div style="font-weight: 700; color: #1e293b; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
                                                <span class="dot-cat {{ $isApp ? 'dot-app' : 'dot-gen' }}" title="{{ $isApp ? 'Apparel' : 'General' }}"></span>
                                                <span>@if($code)[{{ $code }}] @endif{{ $name }}</span>
                                            </div>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <input type="text" name="items[{{ $idx }}][unit]"
                                                   class="form-control unit-input"
                                                   value="{{ $valUnit }}" readonly tabindex="-1"
                                                   style="background-color: #f8fafc; text-align: center; font-weight: 600;">
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <input type="number" name="items[{{ $idx }}][quantity]"
                                                   class="form-control qty-input"
                                                   min="0.01" step="0.01"
                                                   value="{{ $valQty }}" required>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <button type="button" class="btn-delete btn-remove-row" title="Hapus Baris">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="empty-state-row">
                                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">
                                            <i class="fa fa-inbox" style="font-size: 24px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>
                                            Belum ada barang. Gunakan pencarian di atas untuk menambahkan barang ke tabel.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 20px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; align-items: center;">
                    <button type="submit" class="btn-submit">
                        <i class="fa fa-save"></i> <span>Perbarui Data LPB</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- SCRIPT AREA --}}
<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
{{-- Script Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    (function($) {
        'use strict';

        var APPAREL_CODES = ['AK', 'PK'];
        function isApparel(catCode) {
            if(!catCode) return false;
            return APPAREL_CODES.indexOf(catCode.toUpperCase()) > -1;
        }

        function renumberRows() {
            var rows = $('#items-table tbody tr.item-row');
            if (rows.length === 0) {
                if ($('#empty-state-row').length === 0) {
                    $('#items-table tbody').html(
                        '<tr id="empty-state-row">' +
                            '<td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">' +
                                '<i class="fa fa-inbox" style="font-size: 24px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>' +
                                'Belum ada barang. Gunakan pencarian di atas untuk menambahkan barang ke tabel.' +
                            '</td>' +
                        '</tr>'
                    );
                }
                return;
            }

            $('#empty-state-row').remove();
            rows.each(function(index) {
                $(this).find('.row-number').text(index + 1);
                $(this).find('input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        var newName = name.replace(/items\[\d+\]/, 'items[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        }

        function existsItemId(itemId) {
            var found = false;
            $('#items-table tbody input.item-id-input').each(function() {
                if (parseInt($(this).val(), 10) === parseInt(itemId, 10)) {
                    found = true;
                }
            });
            return found;
        }

        function addItemToTable(it) {
            if (!it || !it.id) return;
            var itemId = it.id;

            if (existsItemId(itemId)) {
                alert('Barang sudah ada di dalam tabel.');
                return;
            }

            $('#empty-state-row').remove();

            var rowCount = $('#items-table tbody tr.item-row').length;
            var isApp = isApparel(it.cat_code);
            var dotClass = isApp ? 'dot-app' : 'dot-gen';
            var typeLabel = isApp ? 'Apparel' : 'General';
            var displayCode = it.harmoni_code || it.code || '-';

            var tr = 
                '<tr class="item-row" data-item-id="' + itemId + '">' +
                    '<td style="text-align: center; vertical-align: middle;">' +
                        '<div class="row-number">' + (rowCount + 1) + '</div>' +
                        '<input type="hidden" name="items[' + rowCount + '][item_id]" value="' + itemId + '" class="item-id-input">' +
                    '</td>' +
                    '<td style="vertical-align: middle;">' +
                        '<div style="font-weight: 700; color: #1e293b; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">' +
                            '<span class="dot-cat ' + dotClass + '" title="' + typeLabel + '"></span> ' +
                            '<span>[' + displayCode + '] ' + (it.name || '') + '</span>' +
                        '</div>' +
                    '</td>' +
                    '<td style="text-align: center; vertical-align: middle;">' +
                        '<input type="text" name="items[' + rowCount + '][unit]" class="form-control unit-input" value="' + (it.unit || '') + '" readonly tabindex="-1" style="background-color: #f8fafc; text-align: center; font-weight: 600;">' +
                    '</td>' +
                    '<td style="vertical-align: middle;">' +
                        '<input type="number" name="items[' + rowCount + '][quantity]" class="form-control qty-input" min="0.01" step="0.01" value="1" required>' +
                    '</td>' +
                    '<td style="text-align: center; vertical-align: middle;">' +
                        '<button type="button" class="btn-delete btn-remove-row" title="Hapus Baris">' +
                            '<i class="fa fa-trash"></i>' +
                        '</button>' +
                    '</td>' +
                '</tr>';

            $('#items-table tbody').append(tr);
            renumberRows();
        }

        $(document).ready(function() {
            // Flatpickr
            flatpickr(".datepicker-flat", {
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                locale: "id",
                allowInput: true
            });

            // SELECT2 ITEM PICKER (AJAX / On-Demand seperti requests/create)
            var $picker = $('#itemPicker');
            $picker.select2({
                theme: 'bootstrap',
                width: '100%',
                placeholder: 'Pilih atau cari barang...',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route("lpbs.lookup.items") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                },
                templateResult: function(item) {
                    if (item.loading) return item.text;
                    if (!item.id) return item.text;

                    var isApp = isApparel(item.cat_code);
                    var dotClass = isApp ? 'dot-app' : 'dot-gen';
                    var typeLabel = isApp ? 'Apparel' : 'General';
                    var displayCode = item.harmoni_code || item.code || '';

                    return $(
                        '<div style="padding:4px 0;">' +
                            '<div class="s2-code" style="font-weight:bold; font-size:13px; color:#1e293b;">' +
                                '<span class="dot-cat ' + dotClass + '" title="' + typeLabel + '"></span> [' + displayCode + '] ' + (item.name || '') +
                            '</div>' +
                            '<div class="s2-sub" style="font-size:11px; color:#64748b;">Unit: ' + (item.unit || '-') + '</div>' +
                        '</div>'
                    );
                },
                templateSelection: function(item) {
                    return item.text || 'Ketik kode / nama item...';
                }
            });

            $picker.on('select2:select', function(e) {
                addItemToTable(e.params.data);
                $(this).val(null).trigger('change');
            });

            // Remove Row
            $('#items-table').on('click', '.btn-remove-row', function() {
                if ($('#items-table tbody tr.item-row').length > 1) {
                    $(this).closest('tr').remove();
                    renumberRows();
                } else {
                    alert("Minimal harus ada satu baris item.");
                }
            });

            // Validasi sebelum submit
            $('#lpb-form').on('submit', function(e) {
                if ($('#items-table tbody tr.item-row').length === 0) {
                    e.preventDefault();
                    alert('Harap masukkan minimal 1 barang ke dalam tabel.');
                    return false;
                }
            });
        });

    })(jQuery);
</script>
@endsection
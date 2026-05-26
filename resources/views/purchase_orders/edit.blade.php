@extends('layouts.app')

@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>EDIT PURCHASE ORDER:</strong> Di sini Anda dapat mengubah informasi header, menambah/mengurangi barang, atau mengubah status menjadi Dalam Pemesanan.
    </div>
@endsection

@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
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

    .page-container {
        width: 100%;
        padding: 10px 20px;
        box-sizing: border-box;
    }

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
    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: var(--primary);
        text-decoration: none;
    }

    .card-section {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: var(--shadow-soft);
        margin-bottom: 16px;
        overflow: hidden;
    }
    
    .card-header {
        padding: 10px 16px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 10px;
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
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        color: var(--text-dark);
    }
    .card-body {
        padding: 16px;
    }

    .form-group {
        margin-bottom: 10px;
    }
    .form-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .form-control {
        width: 100%;
        height: 38px;
        padding: 8px 12px;
        font-size: 13px;
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
    .form-control.datepicker-flat { background-color: #fff !important; }

    textarea.form-control {
        height: auto;
        min-height: 60px;
        resize: vertical;
        line-height: 1.4;
    }

    .table-container {
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow-x: auto;
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
        padding: 10px 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-gray);
        border-bottom: 1px solid var(--border-color);
        text-align: left;
    }
    .table tbody td {
        padding: 6px 12px;
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

    .btn-submit {
        background: var(--primary-gradient);
        color: white;
        padding: 0 24px;
        height: 38px;
        border: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        box-shadow: 0 4px 10px -2px rgba(37, 99, 235, 0.4);
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        line-height: 1;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.5);
    }
    .btn-draft {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 0 24px;
        height: 38px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-draft:hover {
        background: #e2e8f0;
        color: #1e293b;
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
    .btn-delete:hover {
        background: var(--danger);
        color: white;
    }

    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid var(--border-color) !important;
        border-radius: var(--radius-md) !important;
        display: flex;
        align-items: center;
        background-color: #fff;
        position: relative;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
        color: var(--text-dark) !important;
        font-size: 13px;
        padding-left: 12px;
        padding-right: 35px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        right: 25px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: var(--danger) !important;
        font-weight: bold;
        font-size: 18px;
        z-index: 10;
        line-height: 0;
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

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .full-width { grid-column: span 2; }

    @media (max-width: 768px) {
        .page-container { padding: 10px; }
        .grid-2 { grid-template-columns: 1fr; gap: 16px; }
        .full-width { grid-column: span 1; }
        .dashboard-header { flex-direction: column; align-items: flex-start; gap: 15px; }
        .btn-back { width: 100%; }
        .header-title-wrapper { width: 100%; }
    }
</style>

<div class="page-container">
    <div class="dashboard-header">
        <div class="header-title-wrapper">
            <div class="header-visual-icon">
                <i class="fa fa-shopping-bag"></i>
            </div>
            <div class="header-content">
                <h1>Edit Purchase Order (PO)</h1>
                <p>Ubah informasi PO #{{ $po->po_number }} sebelum direalisasikan.</p>
            </div>
        </div>
        <div>
            <a href="{{ route('purchase-orders.show', $po->id) }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> <span>Kembali ke Detail</span>
            </a>
        </div>
    </div>

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

    <form action="{{ route('purchase-orders.update', $po->id) }}" method="POST" id="po-form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="card-section">
            <div class="card-header">
                <div class="header-icon-wrapper-small"><i class="fa fa-file-text-o"></i></div>
                <h3 class="card-title">Informasi Header PO</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nomor PO <span style="color: var(--danger)">*</span></label>
                        <input type="text" name="po_number" class="form-control"
                               value="{{ $po->po_number }}" required readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pemesanan <span style="color: var(--danger)">*</span></label>
                        <input type="text" name="date" class="form-control datepicker-flat"
                               value="{{ old('date') ? old('date') : $po->date }}" 
                               placeholder="dd/mm/yyyy" required>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Nama Supplier / Pemasok</label>
                        <input type="text" name="supplier_name" class="form-control"
                               placeholder="Contoh: PT. Sumber Teknik Jaya"
                               value="{{ old('supplier_name') ? old('supplier_name') : $po->supplier_name }}">
                    </div>
                    <div class="form-group full-width" style="margin-bottom:0;">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="notes" class="form-control"
                                  placeholder="Tulis instruksi khusus supplier atau catatan internal...">{{ old('notes') ? old('notes') : $po->notes }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-section">
            <div class="card-header" style="justify-content: space-between;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="header-icon-wrapper-small" style="background: rgba(16, 185, 129, 0.1); color: var(--success);"><i class="fa fa-cubes"></i></div>
                    <h3 class="card-title">Detail Barang Pesanan</h3>
                </div>
                <button type="button" class="btn-add-row" id="btn-add-row">
                    <i class="fa fa-plus"></i> Tambah Baris
                </button>
            </div>
            
            <div class="card-body" style="padding:0;">
                <div style="padding: 12px;">
                    <div class="table-container">
                        <table class="table" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">No</th>
                                    <th style="min-width: 320px;">Nama Barang</th>
                                    <th style="width: 120px; text-align: center;">Satuan</th>
                                    <th style="width: 180px; text-align: center;">Kuantitas Pesan</th>
                                    <th style="width: 250px;">Catatan Item (Opsional)</th>
                                    <th style="width: 80px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldItems = old('items');
                                    if (!is_array($oldItems) || count($oldItems) == 0) {
                                        $oldItems = [];
                                        foreach($po->details as $d) {
                                            $oldItems[] = [
                                                'item_id' => $d->item_id,
                                                'unit' => $d->item->unit,
                                                'quantity' => (float)$d->quantity,
                                                'notes' => $d->notes
                                            ];
                                        }
                                    }
                                @endphp

                                @foreach($oldItems as $idx => $row)
                                    @php
                                        $oldItemId = isset($row['item_id']) ? $row['item_id'] : '';
                                        $oldUnit   = isset($row['unit']) ? $row['unit'] : '';
                                        $oldQty    = isset($row['quantity']) ? $row['quantity'] : '';
                                        $oldNotes  = isset($row['notes']) ? $row['notes'] : '';
                                    @endphp
                                    <tr>
                                        <td style="text-align: center;">
                                            <div class="row-number">{{ $idx + 1 }}</div>
                                        </td>
                                        <td>
                                            <select name="items[{{ $idx }}][item_id]" class="form-control item-select">
                                                <option value="">-- Cari Barang --</option>
                                                @foreach($allItems as $item)
                                                    <option value="{{ $item->id }}" data-unit="{{ $item->unit }}"
                                                        {{ $oldItemId == $item->id ? 'selected' : '' }}>
                                                        [{{ $item->code }}] {{ $item->name }}
                                                     </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $idx }}][unit]"
                                                   class="form-control unit-input"
                                                   value="{{ $oldUnit }}" readonly tabindex="-1"
                                                   placeholder="Auto" style="text-align: center;">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][quantity]"
                                                   class="form-control qty-input"
                                                   min="0.01" step="0.01" placeholder="0"
                                                   value="{{ $oldQty }}" style="text-align: center;" required>
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $idx }}][notes]"
                                                   class="form-control"
                                                   placeholder="Catatan ukuran, merk, dll..."
                                                   value="{{ $oldNotes }}">
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="btn-delete btn-remove-row" title="Hapus Baris">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 10px 16px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; align-items: center; gap:12px;">
                    <button type="submit" name="action" value="save" class="btn-draft">
                        <i class="fa fa-save"></i> <span>Simpan Perubahan</span>
                    </button>
                    @if($po->status === 'DRAFT')
                        <button type="submit" name="action" value="ordered" class="btn-submit">
                            <i class="fa fa-send"></i> <span>Simpan & Kirim Pesanan</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

    </form>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    (function($) {
        'use strict';

        function initSelect2($element) {
            $element.select2({
                placeholder: "-- Cari Barang --",
                allowClear: true,
                width: '100%'
            });
        }

        function renumberRows() {
            $('#items-table tbody tr').each(function(index) {
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

        $(document).ready(function() {
            flatpickr(".datepicker-flat", {
                altInput: true,      
                altFormat: "d/m/Y",  
                dateFormat: "Y-m-d", 
                locale: "id",        
                allowInput: true     
            });

            $('.item-select').each(function() {
                initSelect2($(this));
            });

            // Set unit untuk item pre-filled dari database
            $('#items-table tbody tr').each(function() {
                let $row = $(this);
                let $select = $row.find('.item-select');
                let unit = $select.find('option:selected').data('unit');
                if (unit) {
                    $row.find('.unit-input').val(unit);
                }
            });

            $('#btn-add-row').on('click', function() {
                let tbody = $('#items-table tbody');
                let rowCount = tbody.find('tr').length;
                
                let newRow = `
                    <tr>
                        <td style="text-align: center;">
                            <div class="row-number">${rowCount + 1}</div>
                        </td>
                        <td>
                            <select name="items[${rowCount}][item_id]" class="form-control item-select">
                                <option value="">-- Cari Barang --</option>
                                @foreach($allItems as $item)
                                    <option value="{{ $item->id }}" data-unit="{{ $item->unit }}">
                                        [{{ $item->code }}] {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" name="items[${rowCount}][unit]" class="form-control unit-input" readonly tabindex="-1" placeholder="Auto" style="text-align: center;">
                        </td>
                        <td>
                            <input type="number" name="items[${rowCount}][quantity]" class="form-control qty-input" min="0.01" step="0.01" placeholder="0" style="text-align: center;" required>
                        </td>
                        <td>
                            <input type="text" name="items[${rowCount}][notes]" class="form-control" placeholder="Catatan ukuran, merk, dll...">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-delete btn-remove-row" title="Hapus Baris">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;

                let $newRowObj = $(newRow);
                tbody.append($newRowObj);
                initSelect2($newRowObj.find('.item-select'));
            });

            $('#items-table').on('click', '.btn-remove-row', function() {
                if ($('#items-table tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    renumberRows();
                } else {
                    let row = $(this).closest('tr');
                    row.find('select').val(null).trigger('change');
                    row.find('input').val('');
                }
            });

            $('#items-table').on('change', '.item-select', function() {
                let $select = $(this);
                let $row = $select.closest('tr');
                let selectedOption = $select.find('option:selected');
                let unit = selectedOption.data('unit');
                $row.find('.unit-input').val(unit ? unit : '');
            });
        });

    })(jQuery);
</script>
@endsection

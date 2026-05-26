@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL TAMBAH BON (CREATE) === --}}
@section('help-content')
    <div class="help-alert" style="background:#fff7ed; border-left-color:#ea580c; color:#9a3412;">
        <i class="fa fa-info-circle"></i>
        <strong>PENTING:</strong> Data yang disimpan di sini akan berstatus <strong>PENDING</strong>. Stok belum berkurang sampai status diubah menjadi <strong>ISSUED</strong>.
    </div>

    <h4 class="help-h"><i class="fa fa-keyboard-o text-primary"></i> Cara Pengisian Form</h4>
    <ul class="help-list">
        <li>
            <strong>Departemen & Divisi:</strong> 
            Pilih departemen peminta terlebih dahulu, lalu pilih divisi/bagian spesifik (jika ada). Ini penting untuk pembebanan biaya (Cost Center).
        </li>
        <li>
            <strong>Input Barang (Multi-Row):</strong>
            <ul style="margin-top:5px;">
                <li>Gunakan tombol <strong>"+ Tambah Baris"</strong> untuk mencatat beberapa item dalam satu BON.</li>
                <li>Saat memilih barang, perhatikan satuan (Unit) yang muncul otomatis agar tidak salah persepsi jumlah.</li>
            </ul>
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-lightbulb-o text-primary"></i> Tips Admin</h4>
    <p class="help-p">
        Untuk permintaan rutin bulanan, disarankan menggunakan fitur <strong>"Buat BON dari Request"</strong> di halaman <em>Permintaan Barang</em> agar otomatis tervalidasi dengan plafon/budget departemen. Form manual ini sebaiknya hanya untuk permintaan insidentil/urgent.
    </p>
@endsection

@section('content')
{{-- Load Select2 CSS --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css" rel="stylesheet">
{{-- Load Flatpickr CSS (Format d/m/Y) --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    /* --- GLOBAL VARIABLES & RESET --- */
    :root {
        --primary: #2563eb; --primary-hover: #1d4ed8; --bg-soft: #f8fafc;
        --border-color: #e2e8f0; --text-main: #334155; --text-muted: #64748b;
        --danger: #ef4444; --danger-soft: #fee2e2; --radius: 12px;
    }
    body { background-color: #f1f5f9; color: var(--text-main); font-family: 'Inter', sans-serif; }
    
    /* FIX: Mengubah padding atas menjadi 0 agar full height ke atas */
    .page-container { width: 100%; padding: 0 40px 30px; box-sizing: border-box; }

    /* --- FIX: PERKECIL UKURAN FLATPICKR --- */
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

    /* HEADER */
    .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-top: 16px; } /* Ditambah padding-top dikit biar ga terlalu nempel banget */
    .header-title-wrapper { display: flex; align-items: center; gap: 16px; }
    .header-icon-box {
        width: 48px; height: 48px; border-radius: 12px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #4338ca; display: flex; align-items: center; justify-content: center;
        font-size: 20px; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.4);
    }
    .header-text h1 { font-size: 22px; font-weight: 700; margin: 0; color: var(--text-main); }
    .header-text p { font-size: 13px; color: var(--text-muted); margin: 2px 0 0; }
    .btn-back { background: white; color: var(--text-main); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .btn-back:hover { background: #f8fafc; border-color: #cbd5e1; }

    /* CARD */
    .card-section { background: white; border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 24px; overflow: hidden; }
    .card-header { padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; gap: 10px; }
    .card-header i { color: var(--primary); }
    .card-title { font-size: 15px; font-weight: 700; color: var(--text-main); margin: 0; }
    .card-body { padding: 24px; }

    /* FORM */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .full-width { grid-column: span 2; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control { width: 100%; height: 42px; padding: 8px 12px; font-size: 14px; color: var(--text-main); border: 1px solid #cbd5e1; border-radius: 8px; transition: all 0.2s; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); outline: none; }
    /* FIX: Input Flatpickr agar background putih */
    .form-control.flatpickr-input { background-color: #fff !important; }
    textarea.form-control { height: auto; min-height: 80px; resize: vertical; }
    .help-text { font-size: 11px; color: #94a3b8; margin-top: 4px; }

    /* TABLE FIXED ALIGNMENT */
    .table-container { border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; margin-top: 10px; }
    .table { width: 100%; border-collapse: collapse; }
    .table thead { background: #f8fafc; }
    .table th { padding: 12px 16px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--border-color); }
    .table td { 
        padding: 10px 16px; border-bottom: 1px solid #f1f5f9; 
        vertical-align: middle !important; /* Center Vertikal Fix */
        font-size: 13px; color: #334155;
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table .form-control { height: 36px; font-size: 13px; }
    .btn-icon-danger { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--danger); background: var(--danger-soft); border: none; cursor: pointer; transition: 0.2s; }
    .btn-icon-danger:hover { background: var(--danger); color: white; }

    /* BUTTONS */
    .btn-primary { background: var(--primary); color: white; padding: 12px 24px; border-radius: 8px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
    .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }

    /* SELECT2 CUSTOMIZATION */
    .select2-container .select2-selection--single { height: 42px !important; border: 1px solid #cbd5e1 !important; border-radius: 8px !important; display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { padding-left: 12px; font-size: 14px; color: var(--text-main); }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; }
    .select2-dropdown { border-color: var(--primary) !important; border-radius: 8px !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    
    /* FIX DROPDOWN HOVER VISIBILITY */
    .select2-results__option { padding: 8px 12px; }
    .select2-results__option--highlighted[aria-selected] { 
        background-color: var(--primary) !important; 
        color: #ffffff !important; 
    }
    .select2-results__option--highlighted[aria-selected] .s2-item-code,
    .select2-results__option--highlighted[aria-selected] .s2-item-sub,
    .select2-results__option--highlighted[aria-selected] span,
    .select2-results__option--highlighted[aria-selected] div {
        color: #ffffff !important;
    }

    @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } }
</style>

<div class="page-container">

    <div class="dashboard-header">
        <div class="header-title-wrapper">
            <div class="header-icon-box"><i class="fa fa-plus-circle"></i></div>
            <div class="header-text">
                <h1>Buat BON Baru</h1>
                <p>Input permintaan barang keluar dari gudang.</p>
            </div>
        </div>
        <a href="{{ route('bons.index') }}" class="btn-back"><i class="fa fa-arrow-left"></i> Kembali</a>
    </div>

    @if ($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #991b1b;">
            <strong style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <i class="fa fa-exclamation-circle"></i> Terdapat Kesalahan Input
            </strong>
            <ul style="margin:0; padding-left:20px; font-size:13px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('bons.store') }}" method="POST" id="bonForm">
        {{ csrf_field() }}

        <div class="card-section">
            <div class="card-header">
                <i class="fa fa-file-text-o"></i>
                <h3 class="card-title">Informasi Header BON</h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>No. BON (Opsional)</label>
                        <input type="text" name="bon_number" class="form-control" 
                               value="{{ old('bon_number') }}" 
                               placeholder="Otomatis jika dikosongkan">
                        <div class="help-text">Format otomatis: BON/YYYYMM/XXXX</div>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Permintaan <span style="color:var(--danger)">*</span></label>
                        {{-- FIX: Ganti date -> text, class datepicker-flat, default hari ini --}}
                        <input type="text" name="date" class="form-control datepicker-flat" 
                               value="{{ old('date', date('Y-m-d')) }}" placeholder="dd/mm/yyyy" required>
                    </div>

                    <div class="form-group">
                        <label>Departemen Peminta <span style="color:var(--danger)">*</span></label>
                        <select name="department_id" id="department_id" class="form-control select2-basic" required>
                            <option value="">-- Pilih Departemen --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Divisi / Bagian</label>
                        <select name="division_name" id="division_name" class="form-control select2-basic" disabled>
                            <option value="">-- Pilih Departemen Terlebih Dahulu --</option>
                        </select>
                        <input type="hidden" id="old_division" value="{{ old('division_name') }}">
                    </div>

                    <div class="form-group full-width">
                        <label>Catatan / Keperluan</label>
                        <textarea name="notes" class="form-control" placeholder="Contoh: Untuk keperluan maintenance mesin...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-section">
            <div class="card-header" style="justify-content: space-between;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="fa fa-cubes"></i>
                    <h3 class="card-title">Detail Barang</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Cari & Tambah Barang</label>
                    <select id="itemPicker" class="form-control"></select>
                    <div class="help-text">Pilih barang langsung dari daftar, atau ketik kata kunci untuk memfilter hasil.</div>
                </div>

                <div class="table-container">
                    <table class="table" id="items-table">
                        <thead>
                            <tr>
                                <th style="min-width: 250px;">Nama Barang</th>
                                <th style="width: 80px; text-align: center;">Buffer</th>
                                <th style="width: 100px; text-align: center;">Satuan</th>
                                <th style="width: 150px;">Qty Diminta</th>
                                <th style="width: 60px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $rowIdx = 0;
                            @endphp
                            @if(old('items'))
                                @foreach(old('items') as $idx => $row)
                                    @php $rowIdx++; @endphp
                                    <tr>
                                        <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $row['item_id'] }}">
                                        {{-- Data statis akan diisi JS via resolve --}}
                                        <td class="item-name-cell">Loading...</td>
                                        <td style="text-align: center;" class="item-buffer-cell">-</td>
                                        <td style="text-align: center;" class="item-unit-cell">-</td>
                                        <td>
                                            {{-- FIX: step="0.01" untuk support decimal --}}
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control" 
                                                   value="{{ $row['quantity'] }}" min="0.01" step="0.01" required>
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="btn-icon-danger btn-remove"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <div id="empty-row-msg" style="text-align: center; padding: 30px; color: #94a3b8; {{ old('items') ? 'display:none;' : '' }}">
                    <i class="fa fa-shopping-basket" style="font-size: 24px; margin-bottom: 8px; color: #cbd5e1;"></i>
                    <div>Belum ada barang yang dipilih.</div>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-primary">
                        <i class="fa fa-save"></i> Simpan BON
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
{{-- Script Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    (function($) {
        'use strict';

        // 1. CASCADING DROPDOWN
        var $deptSelect = $('#department_id');
        var $divSelect = $('#division_name');
        var oldDivision = $('#old_division').val();

        function loadDivisions(deptId, selectedVal) {
            $divSelect.empty().append('<option value="">Memuat...</option>').prop('disabled', true);
            
            if(!deptId) {
                $divSelect.empty().append('<option value="">-- Pilih Departemen Terlebih Dahulu --</option>');
                return;
            }

            $.ajax({
                url: '{{ route("api.divisions") }}',
                type: 'GET',
                data: { department_id: deptId },
                success: function(data) {
                    $divSelect.empty().append('<option value="">-- Pilih Divisi / Bagian --</option>');
                    if(data.length > 0) {
                        $.each(data, function(i, div) {
                            var isSel = (selectedVal && selectedVal == div.name) ? 'selected' : '';
                            $divSelect.append('<option value="'+div.name+'" '+isSel+'>'+div.name+'</option>');
                        });
                        $divSelect.prop('disabled', false);
                    } else {
                        $divSelect.append('<option value="">-- Tidak ada divisi --</option>');
                        $divSelect.prop('disabled', false);
                    }
                },
                error: function() {
                    $divSelect.empty().append('<option value="">Gagal memuat data</option>');
                }
            });
        }

        $('.select2-basic').select2({ theme: 'bootstrap', width: '100%' });
        $deptSelect.on('change', function() { loadDivisions($(this).val(), null); });
        if($deptSelect.val()) { loadDivisions($deptSelect.val(), oldDivision); }

        // 2. ITEM PICKER
        $('#itemPicker').select2({
            theme: 'bootstrap', width: '100%', placeholder: 'Pilih atau cari barang...', allowClear: true, minimumInputLength: 0,
            ajax: {
                url: '{{ route("bons.lookup.items") }}', dataType: 'json', delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; },
                cache: true
            },
            templateResult: function(item) {
                if (item.loading) return item.text;
                return $(
                    '<div style="padding:4px 0;">' +
                        '<div class="s2-item-code" style="font-weight:bold; font-size:13px; color:#1e293b;">[' + (item.code||'') + '] ' + (item.name||'') + '</div>' +
                        '<div class="s2-item-sub" style="font-size:11px; color:#64748b;">Unit: ' + (item.unit||'-') + '</div>' +
                    '</div>'
                );
            },
            templateSelection: function(item) { return item.text || 'Ketik Nama Barang...'; }
        });

        $('#itemPicker').on('select2:select', function(e) {
            addItemToTable(e.params.data);
            $(this).val(null).trigger('change');
        });

        // 3. TABLE
        var rowIdx = {{ old('items') ? count(old('items')) : 0 }};

        function addItemToTable(item) {
            var tbody = $('#items-table tbody');
            if(tbody.find('input[value="'+item.id+'"]').length > 0) { alert('Barang sudah ada.'); return; }
            $('#empty-row-msg').hide();

            // FIX: Tambah step="0.01" di input
            var tr = 
                '<tr>' +
                    '<input type="hidden" name="items['+rowIdx+'][item_id]" value="'+item.id+'">' +
                    '<td><div style="font-weight:600; color:#334155;">['+item.code+'] '+item.name+'</div></td>' +
                    '<td style="text-align: center;">'+(item.buffer_min || '-')+'</td>' +
                    '<td style="text-align: center;">'+(item.unit || '-')+'</td>' +
                    '<td><input type="number" name="items['+rowIdx+'][quantity]" class="form-control" value="1" min="0.01" step="0.01" required></td>' +
                    '<td style="text-align: center;"><button type="button" class="btn-icon-danger btn-remove"><i class="fa fa-trash"></i></button></td>' +
                '</tr>';
            tbody.append(tr);
            rowIdx++;
        }

        $('#items-table').on('click', '.btn-remove', function() {
            $(this).closest('tr').remove();
            if($('#items-table tbody tr').length === 0) $('#empty-row-msg').show();
        });

        // 4. RESOLVE OLD ITEMS
        var oldItems = {!! json_encode(old('items', [])) !!};
        if (oldItems.length > 0) {
            var itemIds = [];
            for(var i=0; i<oldItems.length; i++) { if(oldItems[i].item_id) itemIds.push(oldItems[i].item_id); }
            
            if(itemIds.length > 0) {
                $.ajax({
                    url: '{{ route("bons.resolve.items") }}', type: 'POST',
                    data: { ids: itemIds, _token: '{{ csrf_token() }}' },
                    success: function(items) {
                        var itemMap = {};
                        for(var j=0; j<items.length; j++) { itemMap[items[j].id] = items[j]; }
                        $('#items-table tbody tr').each(function() {
                            var idInput = $(this).find('input[type="hidden"]');
                            var id = idInput.val();
                            var it = itemMap[id];
                            if(it) {
                                $(this).find('.item-name-cell').html('<div style="font-weight:600; color:#334155;">[' + it.code + '] ' + it.name + '</div>');
                                $(this).find('.item-buffer-cell').text(it.buffer_min || '-');
                                $(this).find('.item-unit-cell').text(it.unit || '-');
                            }
                        });
                    }
                });
            }
        }

        $(document).ready(function() {
            // FIX: Inisialisasi Flatpickr
            $(".datepicker-flat").flatpickr({
                altInput: true,      
                altFormat: "d/m/Y",  // Format visual: DAY/MONTH/YEAR
                dateFormat: "Y-m-d", // Format data: YYYY-MM-DD
                locale: "id",        
                allowInput: true     
            });
        });

    })(jQuery);
</script>
@endsection
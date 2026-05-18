@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL EDIT REQUEST (UPDATE) === --}}
@section('help-content')
    <div class="help-alert" style="background:#fff7ed; border-left-color:#ea580c; color:#9a3412;">
        <i class="fa fa-pencil"></i>
        <strong>MODE REVISI:</strong> Anda sedang mengubah permintaan yang statusnya masih <strong>OPEN</strong>. Perubahan ini akan langsung mempengaruhi data rekapitulasi pengadaan.
    </div>

    <h4 class="help-h"><i class="fa fa-question-circle text-primary"></i> Kapan Perlu Mengedit?</h4>
    <ul class="help-list">
        <li><strong>Salah Input Jumlah:</strong> Jika Anda tidak sengaja meminta 100 padahal butuh 10, segera koreksi di sini sebelum Admin menarik data PR.</li>
        <li><strong>Item Terlupa:</strong> Tambahkan item yang tertinggal dalam satu nomor request yang sama agar dokumen tidak tercecer.</li>
    </ul>

    <h4 class="help-h"><i class="fa fa-lock text-primary"></i> Batasan Sistem</h4>
    <p class="help-p">
        Jika Admin sudah mengubah status request menjadi <strong>APPROVED</strong> atau <strong>PARTIAL</strong>, maka akses edit akan terkunci otomatis demi menjaga integritas data pengadaan yang sudah berjalan.
    </p>
@endsection

@section('content')
{{-- Select2 --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css" rel="stylesheet">

{{-- Flatpickr --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

@php
    $userRole = auth()->user()->role;
    $canEditDate = ($userRole === 'SUPER_ADMIN' || $userRole === 'ADMIN'); 
    $departmentName = isset($hdr->department) ? $hdr->department->name : '-';

    // Build map item untuk JS (Simple Info)
    $itemsMap = [];
    if (isset($items) && $items) {
        foreach ($items as $it) {
            $catCode = ($it->category && $it->category->code) ? strtoupper($it->category->code) : '';
            $catName = ($it->category && $it->category->name) ? strtoupper($it->category->name) : '';

            $itemsMap[(int)$it->id] = [
                'id'          => (int)$it->id,
                'code'        => (string)$it->code,
                'name'        => (string)$it->name,
                'unit'        => (string)$it->unit,
                'cat_code'    => $catCode,
                'cat_name'    => $catName
            ];
        }
    }
    
    // Pastikan item yang sudah ada di detail juga termuat
    foreach($hdr->details as $d) {
        if(!isset($itemsMap[$d->item_id]) && $d->item) {
            $catCode = ($d->item->category && $d->item->category->code) ? strtoupper($d->item->category->code) : '';
            $catName = ($d->item->category && $d->item->category->name) ? strtoupper($d->item->category->name) : '';

            $itemsMap[(int)$d->item_id] = [
                'id'          => (int)$d->item->id,
                'code'        => (string)$d->item->code,
                'name'        => (string)$d->item->name,
                'unit'        => (string)$d->item->unit,
                'cat_code'    => $catCode,
                'cat_name'    => $catName
            ];
        }
    }
@endphp

<style>
    :root{
        --primary:#2563eb; --primary-hover:#1d4ed8;
        --bg:#f1f5f9; --card:#ffffff; --border:#e2e8f0;
        --text:#0f172a; --muted:#64748b;
        --success:#16a34a; --danger:#ef4444;
        --warning:#f59e0b;
        --radius:14px;
        --shadow:0 1px 2px rgba(15,23,42,.06), 0 10px 20px rgba(15,23,42,.06);
    }
    body{ background:var(--bg); color:var(--text); }
    .page-wrap{ padding: 18px 24px 70px; }
    
    .topbar{ display:flex; align-items:center; justify-content:space-between; margin-bottom: 14px; }
    .titlebox{ display:flex; align-items:center; gap:14px; }
    .iconbox{
        width:46px; height:46px; border-radius:12px;
        background: linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%);
        color:#1e40af; display:flex; align-items:center; justify-content:center;
        font-size:18px; box-shadow: inset 0 0 0 1px rgba(255,255,255,.5);
    }
    .title h1{ margin:0; font-size:22px; font-weight:800; letter-spacing:-.2px; }
    .title p{ margin:2px 0 0; color:var(--muted); font-size:13px; font-weight:600; }
    
    .btn-back{
        background:#fff; border:1px solid var(--border);
        padding:10px 14px; border-radius:10px; font-weight:700; font-size:13px;
        color:var(--text); text-decoration:none; display:inline-flex; align-items:center; gap:8px;
        transition:.15s;
    }
    .btn-back:hover{ background:#f8fafc; border-color:#cbd5e1; transform: translateY(-1px); }

    .cardx{ background:var(--card); border:1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 16px; overflow:hidden; }
    .cardx-h{ padding:14px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background: #fff; }
    .cardx-h .left{ display:flex; align-items:center; gap:10px; }
    .cardx-h .left .h-icon{ color:var(--primary); }
    .cardx-h .left .h-title{ margin:0; font-size:13px; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:.7px; }
    .cardx-b{ padding: 16px 18px; }

    .grid2{ display:grid; grid-template-columns: 1.2fr 1fr; gap: 14px; }
    .grid1{ display:grid; grid-template-columns: 1fr; gap: 14px; }

    .fg label{ display:block; font-size:11px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; margin-bottom:6px; }
    .ctl{ width:100%; height:42px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; padding: 8px 12px; font-size:14px; font-weight:700; color:#0f172a; transition:.15s; }
    .ctl:focus{ outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .help{ margin-top:6px; font-size:11px; color:#94a3b8; font-weight:600; }

    /* LOCKED INPUT STYLE */
    .ctl.locked-input { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; border-color: #e2e8f0; pointer-events: none; }

    .badge-auto{ display:inline-flex; align-items:center; gap:8px; background:#ecfdf5; border:1px solid #bbf7d0; color:#065f46; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; }

    .alert-info-box { background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; padding: 14px 18px; border-radius: 12px; font-size: 13px; line-height: 1.6; display: flex; align-items: flex-start; gap: 12px; font-weight: 500; transition: all 0.3s ease; }
    .alert-info-box i { font-size: 20px; margin-top: 2px; color: var(--primary); }
    .alert-info-box.mixed-mode { }

    .tablebox{ border:1px solid var(--border); border-radius:12px; overflow:hidden; }
    table{ width:100%; border-collapse:collapse; table-layout:fixed; }
    thead th{ background:#f8fafc; border-bottom:1px solid var(--border); font-size:11px; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.6px; padding: 10px 12px; }
    tbody td{ border-bottom:1px solid #f1f5f9; padding: 12px 12px; font-size:13px; vertical-align:middle; color:#0f172a; }
    tbody tr:last-child td{ border-bottom:none; }

    .col-no{ width:56px; text-align:center; }
    .col-unit{ width:120px; text-align:center; }
    .col-qty{ width:160px; }
    .col-remarks{ width:280px; }
    .col-act{ width:86px; text-align:center; }

    .item-title{ font-weight:900; font-size:13px; line-height:1.2; display:flex; align-items:center; gap:8px; }
    .item-sub{ display:inline-flex; margin-top:6px; background:#f1f5f9; border:1px solid #e2e8f0; padding:2px 8px; border-radius:8px; color:#475569; font-weight:900; font-size:11px; }
    
    .dot-cat { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .dot-gen { background-color: #16a34a; box-shadow: 0 0 0 2px #dcfce7; } 
    .dot-app { background-color: #d97706; box-shadow: 0 0 0 2px #fef3c7; } 

    .qty{ height:40px; border-radius:10px; border:1px solid #cbd5e1; padding: 8px 10px; font-weight:900; width:100%; }
    .qty:focus{ outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.12); }

    .rm{ width:40px; height:40px; border-radius:10px; background:#fff5f5; border:1px solid #fecaca; color:#ef4444; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:.12s; }
    .rm:hover{ background:#fee2e2; transform: translateY(-1px); }

    .footer-actions{ display:flex; justify-content:flex-end; align-items:center; gap:12px; margin-top: 14px; }
    .btn-save{ background: #16a34a; border:1px solid #15803d; color:#fff; padding: 11px 16px; border-radius:12px; font-weight:900; font-size:13px; display:inline-flex; align-items:center; gap:8px; box-shadow: 0 6px 14px rgba(22,163,74,.18); cursor:pointer; transition:.12s; }
    .btn-save:hover{ background:#15803d; transform: translateY(-1px); }

    .select2-container .select2-selection--single{ height:42px !important; border-radius:10px !important; border:1px solid #cbd5e1 !important; display:flex !important; align-items:center !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered{ padding-left:12px !important; font-weight:800; color:#0f172a; }
    .select2-container--default .select2-selection--single .select2-selection__arrow{ height:40px !important; }
    .select2-dropdown{ border-radius:12px !important; border-color: var(--primary) !important; box-shadow: 0 12px 24px rgba(15,23,42,.12); overflow:hidden; }
    .select2-results__option{ padding:10px 12px; }
    .select2-results__option--highlighted[aria-selected]{ background: var(--primary) !important; color:#fff !important; }
    .s2-code{ font-weight:900; }
    .s2-sub{ font-size:11px; font-weight:800; opacity:.9; margin-top:2px; }
    .s2-hist{ font-size:10px; color:#2563eb; font-weight:700; margin-top:3px; display:block; }

    @media (max-width: 992px){
        .grid2{ grid-template-columns: 1fr; }
        .page-wrap{ padding: 16px 14px 70px; }
        .col-remarks{ width:200px; }
    }
</style>

<div class="page-wrap">

    <div class="topbar">
        <div class="titlebox">
            <div class="iconbox"><i class="fa fa-pencil"></i></div>
            <div class="title">
                <h1>Edit Request</h1>
                <p>Ubah detail permintaan barang (Status: OPEN).</p>
            </div>
        </div>
        <a href="{{ route('requests.index') }}" class="btn-back">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert-err">
            <strong><i class="fa fa-exclamation-circle"></i> Terdapat kesalahan input</strong>
            <ul style="margin:8px 0 0; padding-left:18px; font-size:13px; font-weight:700;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('requests.update', $hdr->id) }}" method="POST" id="requestForm" autocomplete="off">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="cardx">
            <div class="cardx-h">
                <div class="left">
                    <i class="fa fa-info-circle h-icon"></i>
                    <div class="h-title">Informasi Request</div>
                </div>
                <div class="badge-auto">
                    <i class="fa fa-hashtag"></i> {{ $hdr->request_number }}
                </div>
            </div>
            <div class="cardx-b">
                <div class="grid2">
                    <div class="fg">
                        <label>Departemen</label>
                        <input type="text" class="ctl" value="{{ $departmentName }}" readonly>
                        <div class="help">Departemen tidak dapat diubah.</div>
                    </div>

                    <div class="fg">
                        <label>Tanggal Request</label>
                        @if($canEditDate)
                            {{-- ADMIN/SUPER ADMIN: Boleh Edit Tanggal (Pake JS-DATE) --}}
                            <input type="text" name="date" id="reqDate" 
                                   class="ctl js-date" 
                                   value="{{ $hdr->date->format('Y-m-d') }}" required>
                            <div class="help">Pilih tanggal request.</div>
                        @else
                            {{-- USER BIASA: LOCKED (Readonly, Tampilan d/m/Y) --}}
                            <input type="text" id="reqDateDisplay" class="ctl locked-input" 
                                   value="{{ $hdr->date->format('d/m/Y') }}" readonly>
                            
                            {{-- Hidden input value Y-m-d untuk dikirim ke server & dibaca JS --}}
                            <input type="hidden" name="date" id="reqDate" 
                                   value="{{ $hdr->date->format('Y-m-d') }}">
                            <div class="help"><i class="fa fa-lock"></i> Tanggal terkunci.</div>
                        @endif
                    </div>
                </div>

                <div class="grid1" style="margin-top:14px;">
                    <div class="fg">
                        {{-- SMART INFO BOX ESTIMASI --}}
                        <label>Estimasi & Informasi (Smart Info)</label>
                        <div id="smartInfoBox" class="alert-info-box">
                            <i class="fa fa-info-circle"></i>
                            <div id="estInfoContent">
                                Menunggu data item...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cardx">
            <div class="cardx-h">
                <div class="left">
                    <i class="fa fa-list h-icon"></i>
                    <div class="h-title">Daftar Item</div>
                </div>
                <div style="font-size:11px; color:#94a3b8; font-weight:800;">
                    Pilih item dari dropdown untuk menambah ke tabel.
                </div>
            </div>

            <div class="cardx-b">
                <div class="fg" style="margin-bottom:12px;">
                    <label>Cari Item</label>
                    <select id="itemPicker" class="ctl" style="width:100%;">
                        <option value=""></option>
                        @foreach($items as $it)
                            <option value="{{ $it->id }}">
                                {{ $it->code }} - {{ $it->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="help">
                        <span style="display:inline-block; width:8px; height:8px; background:#16a34a; border-radius:50%; margin-right:4px;"></span>General (ATK) &nbsp; 
                        <span style="display:inline-block; width:8px; height:8px; background:#d97706; border-radius:50%; margin-right:4px;"></span>Apparel (Seragam/Sepatu)
                    </div>
                </div>

                <div class="tablebox">
                    <table id="itemsTable">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th>Item</th>
                                <th class="col-unit">Unit</th>
                                <th class="col-qty">Qty</th>
                                <th class="col-remarks">Remarks</th>
                                <th class="col-act">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $existingItems = old('items') ? old('items') : $hdr->details; 
                                $idx = 0;
                            @endphp

                            @foreach($existingItems as $row)
                                @php
                                    // Support untuk data dari DB (object) atau dari old input (array)
                                    $isObj = is_object($row);
                                    $itemId = $isObj ? $row->item_id : (isset($row['item_id']) ? $row['item_id'] : '');
                                    
                                    // FIX: Float
                                    $qty    = $isObj ? (float)$row->quantity : (isset($row['quantity']) ? (float)$row['quantity'] : 1);
                                    if ($qty < 0.01) $qty = 1;
                                    
                                    $rem    = $isObj ? $row->remarks : (isset($row['remarks']) ? $row['remarks'] : '');
                                @endphp
                                
                                <tr>
                                    <td class="col-no js-no">-</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $itemId }}">
                                        {{-- Placeholder Loading, akan di-replace oleh JS hydrateOldRows --}}
                                        <div class="item-title js-item-title">Loading...</div>
                                        <div class="item-sub js-item-code">...</div>
                                    </td>
                                    <td class="col-unit">
                                        <div style="font-weight:900;" class="js-item-unit">-</div>
                                    </td>
                                    <td class="col-qty">
                                        {{-- FIX: step="0.01" --}}
                                        <input type="number" name="items[{{ $idx }}][quantity]" class="qty"
                                               value="{{ (float)$qty }}" min="0.01" step="0.01" required>
                                    </td>
                                    <td class="col-remarks">
                                        <input type="text" name="items[{{ $idx }}][remarks]" class="ctl" style="height:40px;"
                                               value="{{ $rem }}" placeholder="Opsional">
                                    </td>
                                    <td class="col-act">
                                        <button type="button" class="rm js-remove" title="Hapus">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @php $idx++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="emptyState" style="text-align:center; padding:18px; color:#94a3b8; font-weight:800; display:none;">
                    <i class="fa fa-inbox" style="font-size:18px; margin-bottom:6px; color:#cbd5e1;"></i><br>
                    Belum ada item. Silakan pilih dari dropdown di atas.
                </div>

                <div class="footer-actions">
                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
(function($){
    'use strict';

    // INIT FLATPICKR (HANYA JIKA ADA CLASS .js-date, yaitu untuk ADMIN)
    if ($('.js-date').length > 0) {
        flatpickr(".js-date", {
            altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", locale: "id", allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                updateSmartInfo();
            }
        });
    }

    var ITEMS = {!! json_encode($itemsMap) !!};

    // --- LOGIC SMART CART ---
    var APPAREL_CODES = ['AK', 'PK'];

    function isApparel(catCode) {
        if(!catCode) return false;
        return APPAREL_CODES.indexOf(catCode.toUpperCase()) > -1;
    }

    // FIX: ESTIMASI LOGIC (1-7 vs >7)
    function getEstimationText() {
        // Ambil value dari input hidden atau input text (yang penting ID nya reqDate dan formatnya Y-m-d)
        var dateStr = $('#reqDate').val(); 
        
        if (!dateStr) return "...";
        
        // Split berdasarkan strip
        var parts = dateStr.split('-');
        if(parts.length < 3) return "...";

        var year  = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10) - 1; // JS Month index (0-11)
        var day   = parseInt(parts[2], 10);
        
        var targetMonthIndex;
        // Logic: Jika 1-7 = Bulan Depan (M+1). Jika > 7 = 2 Bulan Kedepan (M+2).
        if (day <= 7) {
            targetMonthIndex = month + 1;
        } else {
            targetMonthIndex = month + 2;
        }
        
        // Logic Simple: Estimasi berdasarkan tanggal
        var future = new Date(year, targetMonthIndex, 1);
        
        var targetYear = future.getFullYear();
        var targetMonth = future.getMonth(); // 0-11

        var monthsName = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        return "Dijadwalkan tiba <strong>" + monthsName[targetMonth] + " " + targetYear + "</strong>.";
    }

    function updateSmartInfo() {
        var hasGeneral = false;
        var hasApparel = false;
        var hasItems = false;

        $('#itemsTable tbody tr').each(function(){
            hasItems = true;
            var hid = $(this).find('input[type="hidden"][name$="[item_id]"]');
            if (hid.length > 0) {
                var id = parseInt(hid.val(), 10);
                var it = ITEMS[id];
                if (it) {
                    if (isApparel(it.cat_code)) {
                        hasApparel = true;
                    } else {
                        hasGeneral = true;
                    }
                }
            }
        });

        var $box = $('#smartInfoBox');
        var $content = $('#estInfoContent');
        var estText = getEstimationText();

        $box.removeClass('mixed-mode');
        
        if (!hasItems) {
            $content.html('Belum ada item yang dipilih. Silakan tambah item.');
            return;
        }

        if (hasGeneral && !hasApparel) {
            $content.html('<strong style="color:#15803d;">Item General (ATK/Umum):</strong><br>' + estText);
        } 
        else if (!hasGeneral && hasApparel) {
            $content.html('<strong style="color:#b45309;">Item Apparel (Seragam/Sepatu):</strong><br>Tidak ada estimasi otomatis. Stok tergantung vendor/gudang. Mohon konfirmasi ke Admin Apparel (Bu Shinta).');
        } 
        else if (hasGeneral && hasApparel) {
            var html = '<strong>Permintaan Campuran:</strong><ul style="margin:4px 0 0 0; padding:0; list-style:none;">';
            html += '<li style="margin-bottom:4px;"><span style="color:#16a34a;">●</span> <span style="color:#16a34a; font-weight:700;">Item General:</span> ' + estText + '</li>';
            html += '<li><span style="color:#d97706;">●</span> <span style="color:#d97706; font-weight:700;">Item Apparel:</span> Konfirmasi ketersediaan ke Admin Apparel (Bu Shinta).</li>';
            html += '</ul>';
            $content.html(html);
        }
    }

    // SELECT2 SIMPLE (FOR DEPT/USER)
    $('.select2-simple').select2({
        theme: 'bootstrap', width: '100%'
    });

    // SELECT2 ITEM PICKER
    var $picker = $('#itemPicker');
    $picker.select2({
        theme: 'bootstrap',
        width: '100%',
        placeholder: 'Ketik kode / nama item...',
        allowClear: true,
        minimumInputLength: 1,
        matcher: function(params, data) {
            if ($.trim(params.term) === '') return data;
            if (typeof data.text === 'undefined') return null;
            if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) return data;
            return null;
        },
        templateResult: function(state){
            if (!state.id) return state.text;
            var it = ITEMS[parseInt(state.id,10)];
            if (!it) return state.text;

            var isApp = isApparel(it.cat_code);
            var dotClass = isApp ? 'dot-app' : 'dot-gen';
            var typeLabel = isApp ? 'Apparel' : 'General';

            var $el = $(
                '<div style="padding:2px 0;">' +
                    '<div class="s2-code"><span class="dot-cat ' + dotClass + '" title="' + typeLabel + '"></span> [' + (it.code || '') + '] ' + (it.name || '') + '</div>' +
                    '<div class="s2-sub">Unit: ' + (it.unit || '-') + '</div>' +
                '</div>'
            );
            return $el;
        },
        templateSelection: function(state){
            if (!state.id) return 'Ketik kode / nama item...';
            var it = ITEMS[parseInt(state.id,10)];
            if (!it) return state.text;
            return it.code + ' - ' + it.name;
        }
    });

    function hardResetPicker(){
        $picker.val(null).trigger('change.select2');
        $picker.find('option').prop('selected', false);
    }
    
    // FIX VITAL: JALANKAN DI LUAR $(document).ready() AGAR LEBIH CEPAT
    $(document).ready(function(){
        hardResetPicker();
        setTimeout(hardResetPicker, 50);
        updateSmartInfo();
    });

    var rowIdx = {{ count($existingItems) }};

    function renumber(){
        var n = 1;
        $('#itemsTable tbody tr').each(function(){
            $(this).find('.js-no').text(n++);
        });
    }

    function showEmptyIfNeeded(){
        if ($('#itemsTable tbody tr').length === 0) $('#emptyState').show();
        else $('#emptyState').hide();
    }

    function existsItemId(itemId){
        var found = false;
        $('#itemsTable tbody input[type="hidden"][name$="[item_id]"]').each(function(){
            if (parseInt($(this).val(),10) === parseInt(itemId,10)) found = true;
        });
        return found;
    }

    function addRow(itemId){
        var it = ITEMS[parseInt(itemId,10)];
        if (!it) return;

        if (existsItemId(itemId)) {
            alert('Item sudah ada di tabel.');
            return;
        }

        var isApp = isApparel(it.cat_code);
        var dotClass = isApp ? 'dot-app' : 'dot-gen';
        var typeLabel = isApp ? 'Apparel' : 'General';

        // FIX: Template baris baru dengan step="0.01"
        var tr = '' +
        '<tr>' +
            '<td class="col-no js-no">-</td>' +
            '<td>' +
                '<input type="hidden" name="items['+rowIdx+'][item_id]" value="'+it.id+'">' +
                '<div class="item-title">' + 
                    '<span class="dot-cat ' + dotClass + '" title="' + typeLabel + '"></span> ' + 
                    (it.name || ('Item #' + it.id)) + 
                '</div>' +
                '<div class="item-sub">[' + (it.code || '-') + ']</div>' +
            '</td>' +
            '<td class="col-unit">' +
                '<div style="font-weight:900;">' + (it.unit || '-') + '</div>' +
            '</td>' +
            '<td class="col-qty">' +
                '<input type="number" name="items['+rowIdx+'][quantity]" class="qty" value="1" min="0.01" step="0.01" required>' +
            '</td>' +
            '<td class="col-remarks">' +
                '<input type="text" name="items['+rowIdx+'][remarks]" class="ctl" style="height:40px;" placeholder="Opsional">' +
            '</td>' +
            '<td class="col-act">' +
                '<button type="button" class="rm js-remove" title="Hapus"><i class="fa fa-trash"></i></button>' +
            '</td>' +
        '</tr>';

        $('#itemsTable tbody').append(tr);
        rowIdx++;

        renumber();
        showEmptyIfNeeded();
        updateSmartInfo();
    }

    $picker.on('select2:select', function(e){
        var itemId = e.params.data.id;
        if (itemId) addRow(itemId);
        hardResetPicker();
    });

    $('#itemsTable').on('click', '.js-remove', function(){
        $(this).closest('tr').remove();
        renumber();
        showEmptyIfNeeded();
        updateSmartInfo();
    });

    function hydrateOldRows(){
        $('#itemsTable tbody tr').each(function(){
            var $tr = $(this);
            var $hid = $tr.find('input[type="hidden"][name$="[item_id]"]');
            if ($hid.length === 0) return;

            var id = parseInt($hid.val(),10);
            var it = ITEMS[id];
            if (!it) return;

            var isApp = isApparel(it.cat_code);
            var dotClass = isApp ? 'dot-app' : 'dot-gen';
            var typeLabel = isApp ? 'Apparel' : 'General';
            
            // GANTI TULISAN "Loading..." DENGAN NAMA ITEM ASLI
            var titleHtml = '<span class="dot-cat ' + dotClass + '" title="' + typeLabel + '"></span> ' + (it.name || ('Item #' + id));
            $tr.find('.js-item-title').html(titleHtml);

            // GANTI TULISAN "..." DENGAN KODE ITEM
            $tr.find('.js-item-code').text('[' + (it.code || '-') + ']');
            
            // GANTI TULISAN "-" DENGAN UNIT
            $tr.find('.js-item-unit').text(it.unit || '-');
        });
        // Panggil updateSmartInfo setelah data terisi agar "Menunggu data item..." hilang
        updateSmartInfo();
    }

    // EKSEKUSI LANGSUNG SAAT SCRIPT DI-LOAD (SETELAH HTML TABEL RENDER)
    hydrateOldRows();
    renumber();
    showEmptyIfNeeded();

})(jQuery);
</script>
@endsection
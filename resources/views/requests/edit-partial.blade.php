@extends('layouts.app')
@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

@php
    $departmentName = isset($hdr->department) ? $hdr->department->name : '-';
    $itemsMap = [];
    if (isset($items)) {
        foreach ($items as $it) {
            $catCode = ($it->category && $it->category->code) ? strtoupper($it->category->code) : '';
            $catName = ($it->category && $it->category->name) ? strtoupper($it->category->name) : '';
            $itemsMap[(int)$it->id] = [
                'id' => (int)$it->id, 'code' => (string)$it->code, 'name' => (string)$it->name,
                'unit' => (string)$it->unit, 'cat_code' => $catCode, 'cat_name' => $catName
            ];
        }
    }
    foreach($lockedDetails as $d) {
        if(!isset($itemsMap[(int)$d->item_id]) && $d->item) {
            $catCode = ($d->item->category && $d->item->category->code) ? strtoupper($d->item->category->code) : '';
            $catName = ($d->item->category && $d->item->category->name) ? strtoupper($d->item->category->name) : '';
            $itemsMap[(int)$d->item_id] = [
                'id' => (int)$d->item->id, 'code' => (string)$d->item->code, 'name' => (string)$d->item->name,
                'unit' => (string)$d->item->unit, 'cat_code' => $catCode, 'cat_name' => $catName
            ];
        }
    }
@endphp

<style>
:root { --primary:#2563eb; --border:#e2e8f0; --text:#0f172a; --muted:#64748b; --success:#16a34a; --danger:#ef4444; --warning:#f59e0b; --radius:14px; }
body { background:#f1f5f9; }
.page-wrap { padding:18px 24px 70px; }
.topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.titlebox { display:flex; align-items:center; gap:14px; }
.iconbox { width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg,#fef3c7,#fde68a); color:#b45309; display:flex; align-items:center; justify-content:center; font-size:18px; }
.title h1 { margin:0; font-size:22px; font-weight:800; }
.title p { margin:2px 0 0; color:var(--muted); font-size:13px; font-weight:600; }
.btn-back { background:#fff; border:1px solid var(--border); padding:10px 14px; border-radius:10px; font-weight:700; font-size:13px; color:var(--text); text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:.15s; }
.btn-back:hover { background:#f8fafc; }
.cardx { background:#fff; border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 2px rgba(15,23,42,.06),0 10px 20px rgba(15,23,42,.06); margin-bottom:16px; overflow:hidden; }
.cardx-h { padding:14px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
.cardx-h .left { display:flex; align-items:center; gap:10px; }
.cardx-h .h-title { margin:0; font-size:13px; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:.7px; }
.cardx-b { padding:16px 18px; }
.grid2 { display:grid; grid-template-columns:1.2fr 1fr; gap:14px; }
.fg label { display:block; font-size:11px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; margin-bottom:6px; }
.ctl { width:100%; height:42px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; padding:8px 12px; font-size:14px; font-weight:700; color:#0f172a; transition:.15s; }
.ctl:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
.ctl.locked-input { background-color:#f1f5f9; color:#64748b; cursor:not-allowed; border-color:#e2e8f0; }
.badge-auto { display:inline-flex; align-items:center; gap:8px; background:#fffbeb; border:1px solid #fcd34d; color:#92400e; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; }
.warn-box { background:linear-gradient(135deg,#fffbeb,#fef3c7); border:1px solid #fcd34d; border-radius:12px; padding:14px 18px; margin-bottom:16px; font-size:13px; color:#92400e; display:flex; align-items:flex-start; gap:12px; font-weight:600; line-height:1.6; }
.warn-box i { font-size:20px; margin-top:2px; color:#d97706; }
.tablebox { border:1px solid var(--border); border-radius:12px; overflow:hidden; }
table { width:100%; border-collapse:collapse; table-layout:fixed; }
thead th { background:#f8fafc; border-bottom:1px solid var(--border); font-size:11px; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.6px; padding:10px 12px; }
tbody td { border-bottom:1px solid #f1f5f9; padding:12px; font-size:13px; vertical-align:middle; color:#0f172a; }
tbody tr:last-child td { border-bottom:none; }
.col-no { width:56px; text-align:center; }
.col-unit { width:120px; text-align:center; }
.col-qty { width:160px; }
.col-remarks { width:240px; }
.col-act { width:86px; text-align:center; }
.col-proc { width:100px; text-align:center; }
.item-title { font-weight:900; font-size:13px; }
.item-sub { display:inline-flex; margin-top:6px; background:#f1f5f9; border:1px solid #e2e8f0; padding:2px 8px; border-radius:8px; color:#475569; font-weight:900; font-size:11px; }
.qty { height:40px; border-radius:10px; border:1px solid #cbd5e1; padding:8px 10px; font-weight:900; width:100%; }
.qty:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
.qty-locked { background:#fef3c7; border-color:#fcd34d; }
.rm { width:40px; height:40px; border-radius:10px; background:#fff5f5; border:1px solid #fecaca; color:#ef4444; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:.12s; }
.rm:hover { background:#fee2e2; }
.footer-actions { display:flex; justify-content:flex-end; align-items:center; gap:12px; margin-top:14px; }
.btn-save { background:#16a34a; border:1px solid #15803d; color:#fff; padding:11px 16px; border-radius:12px; font-weight:900; font-size:13px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; transition:.12s; }
.btn-save:hover { background:#15803d; transform:translateY(-1px); }
.locked-row td { background:#fffbeb !important; }
.locked-badge { display:inline-flex; align-items:center; gap:4px; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:2px 8px; border-radius:999px; font-size:10px; font-weight:800; }
.select2-container .select2-selection--single { height:42px !important; border-radius:10px !important; border:1px solid #cbd5e1 !important; display:flex !important; align-items:center !important; }
.select2-container--default .select2-selection--single .select2-selection__rendered { padding-left:12px !important; font-weight:800; color:#0f172a; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height:40px !important; }
.select2-dropdown { border-radius:12px !important; border-color:var(--primary) !important; box-shadow:0 12px 24px rgba(15,23,42,.12); overflow:hidden; }
.select2-results__option--highlighted[aria-selected] { background:var(--primary) !important; }
@media(max-width:992px) { .grid2 { grid-template-columns:1fr; } }
</style>

<div class="page-wrap">
    <div class="topbar">
        <div class="titlebox">
            <div class="iconbox"><i class="fa fa-pencil"></i></div>
            <div class="title">
                <h1>Edit Request (Mode Parsial)</h1>
                <p>Super Admin &mdash; Edit item yang belum diproses.</p>
            </div>
        </div>
        <a href="{{ route('requests.show', $hdr->id) }}" class="btn-back"><i class="fa fa-arrow-left"></i> Kembali</a>
    </div>

    <div class="warn-box">
        <i class="fa fa-exclamation-triangle"></i>
        <div>
            <strong>Mode Edit Parsial:</strong> Item yang sudah diproses (BON) ditandai warna kuning. Anda bisa menambah qty-nya tapi <strong>tidak boleh mengurangi</strong> di bawah jumlah yang sudah diproses. Item yang belum diproses bisa diedit/hapus bebas, dan Anda juga bisa menambah item baru.
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible" role="alert" style="background:#fee2e2; border:1px solid #fecaca; border-radius:12px; padding:14px 18px; margin-bottom:16px; color:#991b1b; font-size:13px; font-weight:700; position: relative;">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: absolute; right: 10px; top: 20px; opacity: 0.5;"><span aria-hidden="true">&times;</span></button>
            <i class="fa fa-exclamation-circle"></i> <strong>Terdapat kesalahan:</strong>
            <ul style="margin:8px 0 0; padding-left:18px;">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('requests.updatePartial', $hdr->id) }}" method="POST" id="requestForm" autocomplete="off">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        {{-- INFO HEADER --}}
        <div class="cardx">
            <div class="cardx-h">
                <div class="left"><i class="fa fa-info-circle" style="color:var(--primary);"></i><div class="h-title">Informasi Request</div></div>
                <div class="badge-auto"><i class="fa fa-hashtag"></i> {{ $hdr->request_number }} &mdash; {{ strtoupper($hdr->status) }}</div>
            </div>
            <div class="cardx-b">
                <div class="grid2">
                    <div class="fg">
                        <label>Departemen</label>
                        <input type="text" class="ctl locked-input" value="{{ $departmentName }}" readonly>
                    </div>
                    <div class="fg">
                        <label>Tanggal Request</label>
                        <input type="text" name="date" id="reqDate" class="ctl js-date" value="{{ $hdr->date->format('Y-m-d') }}" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 1: ITEM YANG SUDAH DIPROSES (LOCKED / PARTIALLY EDITABLE) --}}
        @if(count($lockedDetails) > 0)
        <div class="cardx">
            <div class="cardx-h" style="background:#fffbeb;">
                <div class="left"><i class="fa fa-lock" style="color:#d97706;"></i><div class="h-title">Item Sudah Diproses (Qty Terbatas)</div></div>
                <div style="font-size:11px; color:#92400e; font-weight:800;">{{ count($lockedDetails) }} item</div>
            </div>
            <div class="cardx-b">
                <div class="tablebox">
                    <table>
                        <thead><tr>
                            <th class="col-no">No</th><th>Item</th><th class="col-unit">Unit</th>
                            <th class="col-proc">Diproses</th><th class="col-qty">Qty Request</th><th class="col-remarks">Remarks</th>
                        </tr></thead>
                        <tbody>
                        @foreach($lockedDetails as $i => $d)
                            @php
                                $itemCode = ($d->item) ? $d->item->code : '-';
                                $itemName = ($d->item) ? $d->item->name : '-';
                                $unit = ($d->item) ? $d->item->unit : '-';
                                $processedQty = (float) $d->processed_qty;
                                $currentQty = (float) $d->quantity;
                                $remarks = ($d->remarks !== null) ? $d->remarks : '';
                            @endphp
                            <tr class="locked-row">
                                <td class="col-no">{{ $i + 1 }}</td>
                                <td>
                                    <div class="item-title">{{ $itemName }} <span class="locked-badge"><i class="fa fa-lock"></i> BON</span></div>
                                    <div class="item-sub">[{{ $itemCode }}]</div>
                                </td>
                                <td class="col-unit" style="font-weight:900;">{{ $unit }}</td>
                                <td class="col-proc"><span style="background:#fee2e2; color:#b91c1c; padding:4px 10px; border-radius:999px; font-weight:900; font-size:12px;">{{ $processedQty }}</span></td>
                                <td class="col-qty">
                                    <input type="number" name="locked_items[{{ $d->id }}][quantity]" class="qty qty-locked"
                                           value="{{ $currentQty }}" min="{{ $processedQty }}" step="0.01" required
                                           title="Minimum: {{ $processedQty }} (sudah diproses)">
                                </td>
                                <td class="col-remarks">
                                    <input type="text" name="locked_items[{{ $d->id }}][remarks]" class="ctl" style="height:40px;" value="{{ $remarks }}" placeholder="Opsional">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- SECTION 2: ITEM BELUM DIPROSES (FULLY EDITABLE) --}}
        <div class="cardx">
            <div class="cardx-h">
                <div class="left"><i class="fa fa-list" style="color:var(--primary);"></i><div class="h-title">Item Belum Diproses (Bisa Diedit)</div></div>
                <div style="font-size:11px; color:#94a3b8; font-weight:800;">Pilih item dari dropdown untuk menambah.</div>
            </div>
            <div class="cardx-b">
                <div class="fg" style="margin-bottom:12px;">
                    <label>Cari & Tambah Item</label>
                    <select id="itemPicker" class="ctl" style="width:100%;">
                        <option value=""></option>
                        @foreach($items as $it)
                            <option value="{{ $it->id }}">{{ $it->code }} - {{ $it->name }}</option>
                        @endforeach
                    </select>
                    <div style="margin-top:6px; font-size:11px; color:#94a3b8; font-weight:600;">
                        <span style="display:inline-block; width:8px; height:8px; background:#16a34a; border-radius:50%; margin-right:4px;"></span>General (ATK) &nbsp;
                        <span style="display:inline-block; width:8px; height:8px; background:#d97706; border-radius:50%; margin-right:4px;"></span>Apparel (Seragam/Sepatu)
                    </div>
                </div>

                <div class="tablebox">
                    <table id="itemsTable">
                        <thead><tr>
                            <th class="col-no">No</th><th>Item</th><th class="col-unit">Unit</th>
                            <th class="col-qty">Qty</th><th class="col-remarks">Remarks</th><th class="col-act">Aksi</th>
                        </tr></thead>
                        <tbody>
                        @php $idx = 0; @endphp
                        @foreach($editableDetails as $d)
                            @php
                                $itemId = $d->item_id;
                                $qty = (float)$d->quantity;
                                if ($qty < 0.01) $qty = 1;
                                $rem = ($d->remarks !== null) ? $d->remarks : '';
                            @endphp
                            <tr>
                                <td class="col-no js-no">-</td>
                                <td>
                                    <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $itemId }}">
                                    <div class="item-title js-item-title">Loading...</div>
                                    <div class="item-sub js-item-code">...</div>
                                </td>
                                <td class="col-unit"><div style="font-weight:900;" class="js-item-unit">-</div></td>
                                <td class="col-qty"><input type="number" name="items[{{ $idx }}][quantity]" class="qty" value="{{ $qty }}" min="0.01" step="0.01" required></td>
                                <td class="col-remarks"><input type="text" name="items[{{ $idx }}][remarks]" class="ctl" style="height:40px;" value="{{ $rem }}" placeholder="Opsional"></td>
                                <td class="col-act"><button type="button" class="rm js-remove" title="Hapus"><i class="fa fa-trash"></i></button></td>
                            </tr>
                            @php $idx++; @endphp
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="emptyState" style="text-align:center; padding:18px; color:#94a3b8; font-weight:800; display:none;">
                    <i class="fa fa-inbox" style="font-size:18px; margin-bottom:6px; color:#cbd5e1;"></i><br>
                    Belum ada item baru. Pilih dari dropdown di atas.
                </div>

                <div class="footer-actions">
                    <a href="{{ route('requests.show', $hdr->id) }}" class="btn-back">Batal</a>
                    <button type="submit" class="btn-save"><i class="fa fa-save"></i> Simpan Perubahan</button>
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

    if ($('.js-date').length > 0) {
        flatpickr(".js-date", { altInput:true, altFormat:"d/m/Y", dateFormat:"Y-m-d", locale:"id", allowInput:true });
    }

    var ITEMS = {!! json_encode($itemsMap) !!};
    var APPAREL_CODES = ['AK', 'PK'];

    function isApparel(catCode) {
        if (!catCode) return false;
        return APPAREL_CODES.indexOf(catCode.toUpperCase()) > -1;
    }

    // --- SELECT2 ITEM PICKER (SAMA PERSIS DENGAN CREATE PAGE) ---
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
            var it = ITEMS[parseInt(state.id, 10)];
            if (!it) return state.text;

            var isApp = isApparel(it.cat_code);
            var dotColor = isApp ? '#d97706' : '#16a34a';
            var dotShadow = isApp ? '0 0 0 2px #fef3c7' : '0 0 0 2px #dcfce7';

            var $el = $(
                '<div style="padding:2px 0;">' +
                    '<div style="font-weight:900;">' +
                        '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + dotColor + ';box-shadow:' + dotShadow + ';margin-right:6px;"></span>' +
                        '[' + (it.code || '') + '] ' + (it.name || '') +
                    '</div>' +
                    '<div style="font-size:11px; font-weight:800; opacity:.9; margin-top:2px;">Unit: ' + (it.unit || '-') + '</div>' +
                '</div>'
            );
            return $el;
        },
        templateSelection: function(state){
            if (!state.id) return 'Ketik kode / nama item...';
            var it = ITEMS[parseInt(state.id, 10)];
            if (!it) return state.text;
            return it.code + ' - ' + it.name;
        }
    });

    function hardResetPicker(){
        $picker.val(null).trigger('change.select2');
        $picker.find('option').prop('selected', false);
    }
    $(document).ready(function(){ hardResetPicker(); setTimeout(hardResetPicker, 50); });

    var rowIdx = {{ count($editableDetails) }};

    function renumber(){
        var n = 1;
        $('#itemsTable tbody tr').each(function(){ $(this).find('.js-no').text(n++); });
    }

    function showEmpty(){
        if ($('#itemsTable tbody tr').length === 0) $('#emptyState').show();
        else $('#emptyState').hide();
    }

    function existsItemId(id){
        var f = false;
        $('#itemsTable tbody input[type="hidden"][name$="[item_id]"]').each(function(){
            if (parseInt($(this).val(), 10) === parseInt(id, 10)) f = true;
        });
        return f;
    }

    function addRow(itemId){
        var it = ITEMS[parseInt(itemId, 10)];
        if (!it) return;
        if (existsItemId(itemId)){ alert('Item sudah ada di tabel.'); return; }

        var isApp = isApparel(it.cat_code);
        var dotColor = isApp ? '#d97706' : '#16a34a';
        var dotShadow = isApp ? '0 0 0 2px #fef3c7' : '0 0 0 2px #dcfce7';
        var typeLabel = isApp ? 'Apparel' : 'General';

        var tr =
        '<tr>' +
            '<td class="col-no js-no">-</td>' +
            '<td>' +
                '<input type="hidden" name="items['+rowIdx+'][item_id]" value="'+it.id+'">' +
                '<div class="item-title">' +
                    '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:'+dotColor+';box-shadow:'+dotShadow+';margin-right:6px;" title="'+typeLabel+'"></span> ' +
                    (it.name || ('Item #' + it.id)) +
                '</div>' +
                '<div class="item-sub">[' + (it.code || '-') + ']</div>' +
            '</td>' +
            '<td class="col-unit"><div style="font-weight:900;">' + (it.unit || '-') + '</div></td>' +
            '<td class="col-qty"><input type="number" name="items['+rowIdx+'][quantity]" class="qty" value="1" min="0.01" step="0.01" required></td>' +
            '<td class="col-remarks"><input type="text" name="items['+rowIdx+'][remarks]" class="ctl" style="height:40px;" placeholder="Opsional"></td>' +
            '<td class="col-act"><button type="button" class="rm js-remove" title="Hapus"><i class="fa fa-trash"></i></button></td>' +
        '</tr>';

        $('#itemsTable tbody').append(tr);
        rowIdx++;
        renumber();
        showEmpty();
    }

    $picker.on('select2:select', function(e){
        var itemId = e.params.data.id;
        if (itemId) addRow(itemId);
        hardResetPicker();
    });

    $('#itemsTable').on('click', '.js-remove', function(){
        $(this).closest('tr').remove();
        renumber();
        showEmpty();
    });

    // HYDRATE: Replace "Loading..." dengan data item asli
    function hydrateOldRows(){
        $('#itemsTable tbody tr').each(function(){
            var $tr = $(this);
            var $hid = $tr.find('input[type="hidden"][name$="[item_id]"]');
            if ($hid.length === 0) return;

            var id = parseInt($hid.val(), 10);
            var it = ITEMS[id];
            if (!it) return;

            var isApp = isApparel(it.cat_code);
            var dotColor = isApp ? '#d97706' : '#16a34a';
            var dotShadow = isApp ? '0 0 0 2px #fef3c7' : '0 0 0 2px #dcfce7';
            var typeLabel = isApp ? 'Apparel' : 'General';

            var titleHtml = '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:'+dotColor+';box-shadow:'+dotShadow+';margin-right:6px;" title="'+typeLabel+'"></span> ' + (it.name || ('Item #' + id));
            $tr.find('.js-item-title').html(titleHtml);
            $tr.find('.js-item-code').text('[' + (it.code || '-') + ']');
            $tr.find('.js-item-unit').text(it.unit || '-');
        });
    }

    // EKSEKUSI LANGSUNG
    hydrateOldRows();
    renumber();
    showEmpty();

})(jQuery);
</script>
@endsection


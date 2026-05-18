@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL EDIT SO (KOREKSI DRAFT) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-pencil-square-o"></i>
        <strong>MODE EDIT DRAFT:</strong> Anda sedang mengubah angka fisik pada dokumen yang belum diproses.
    </div>

    <h4 class="help-h"><i class="fa fa-search text-primary"></i> Navigasi Cepat</h4>
    <ul class="help-list">
        <li>
            <strong>Filter Selisih:</strong> Gunakan tombol <span style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-size:10px;">Selisih Only</span> di toolbar untuk fokus hanya pada barang yang tidak cocok (Fisik ≠ Sistem).
        </li>
        <li>
            <strong>Kolom Input Fisik:</strong> Pastikan angka yang Anda masukkan sesuai dengan hitungan riil di gudang. Jika kolom dikosongkan, sistem akan otomatis menganggapnya <strong>0</strong>.
        </li>
        <li>
            <strong>Peringatan Simpan:</strong> Klik "Simpan Perubahan" untuk memperbarui draft. Stok gudang <strong>BELUM BERUBAH</strong> sampai Anda melakukan "Proses & Update Stok" di halaman Detail.
        </li>
    </ul>
@endsection

@section('content')
{{-- Load Flatpickr CSS --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    /* --- SYSTEM STYLES (MATCHING CREATE) --- */
    :root {
        --primary: #2563eb; --primary-hover: #1d4ed8; --primary-light: #eff6ff;
        --border: #e2e8f0; --text: #334155; --muted: #64748b;
        --danger: #ef4444; --success: #10b981;
    }
    body { background-color: #f1f5f9 !important; color: var(--text); font-family: 'Inter', sans-serif; }
    .page-container { width: 100%; padding: 24px 32px; box-sizing: border-box; }
    
    /* UTILS */
    *:focus { outline: none !important; }
    a { text-decoration: none !important; }

    /* HEADER */
    .header-wrap { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
    .header-left { display: flex; align-items: center; gap: 16px; }
    
    /* ICON BOX - UPDATED TO BLUE THEME */
    .header-icon-box {
        width: 48px; height: 48px; 
        background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%); 
        border: 1px solid #dbeafe; 
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: var(--primary); 
        font-size: 20px;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1);
        flex-shrink: 0;
    }

    .header-title h1 { font-size: 24px; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; letter-spacing: -0.5px; }
    .header-title p { font-size: 13px; color: var(--muted); margin: 2px 0 0; font-weight: 500; }
    
    .header-ref { 
        font-family: monospace; font-size: 16px; color: var(--primary); 
        background: var(--primary-light); padding: 2px 8px; border-radius: 6px; 
    }

    /* CARDS */
    .card-modern { 
        background: #fff; border-radius: 12px; border: 1px solid var(--border); 
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; 
        overflow: visible; position: relative;
    }
    .card-bd { padding: 24px; }

    /* FORMS */
    .form-group { margin-bottom: 0; }
    .form-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px; letter-spacing: 0.5px; }
    .form-control-mod {
        width: 100%; height: 42px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 8px;
        font-size: 13px; color: #1e293b; transition: all 0.2s; background: #fff;
    }
    .form-control-mod:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
    .form-control-mod.flatpickr-input { background-color: #fff !important; }

    /* TOOLBAR EDIT */
    .toolbar { 
        display: flex; justify-content: space-between; align-items: center; 
        padding: 14px 24px; background: #f8fafc; border-bottom: 1px solid var(--border); 
        border-radius: 12px 12px 0 0; flex-wrap: wrap; gap: 12px;
    }
    .btn-group-mod { display: flex; background: #e2e8f0; padding: 4px; border-radius: 10px; }
    .btn-toggle { 
        border: none; background: transparent; padding: 6px 16px; font-size: 12px; 
        font-weight: 700; color: #64748b; border-radius: 8px; cursor: pointer; transition: 0.2s; 
    }
    .btn-toggle.active { background: #fff; color: var(--primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

    /* TABLE */
    .table-wrap { max-height: 60vh; overflow-y: auto; border-bottom: 1px solid var(--border); }
    .table-perf { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-perf th {
        background: #f8fafc; position: sticky; top: 0; z-index: 10;
        padding: 14px 16px; border-bottom: 1px solid var(--border);
        color: #475569; font-size: 11px; font-weight: 800; text-transform: uppercase;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02); letter-spacing: 0.5px;
    }
    .table-perf td { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle; }
    .table-perf tr:hover td { background: #f8fafc; }

    /* INPUTS */
    .inp-fisik {
        width: 100%; text-align: right; font-weight: 700; color: #0f172a;
        border: 1px solid #cbd5e1; height: 38px; border-radius: 8px; padding: 0 12px;
        transition: all 0.2s; background: #fff; /* White background for edit */
    }
    .inp-fisik:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2); }
    
    /* DIFF BADGE */
    .badge-diff { font-size: 12px; font-weight: 800; padding: 6px 12px; border-radius: 8px; min-width: 45px; display: inline-block; text-align: center; }
    .bd-zero { color: #cbd5e1; background: #f8fafc; }
    .bd-plus { background: #dcfce7; color: #15803d; }
    .bd-minus { background: #fee2e2; color: #b91c1c; }

    /* Row Highlights */
    .row-diff td { background-color: #fff7ed; } /* Orange tipis kalau ada selisih */
    .row-diff:hover td { background-color: #ffedd5 !important; }

    /* BUTTONS */
    .btn-action { height: 42px; padding: 0 20px; border-radius: 10px; font-weight: 600; font-size: 13px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none !important; transition: 0.2s; }
    .btn-blue { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(37,99,235,0.25); }
    .btn-blue:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-white { background: #fff; border: 1px solid #cbd5e1; color: #475569; }
    .btn-white:hover { background: #f8fafc; border-color: #94a3b8; color: #1e293b; }

    /* --- CUSTOM OVERLAY MODAL COMPACT (NO BOOTSTRAP DEPENDENCY) --- */
    .custom-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6); z-index: 99990;
        display: none; align-items: center; justify-content: center;
        backdrop-filter: blur(4px);
    }
    .custom-modal-box {
        background: #fff; width: 100%; max-width: 380px; 
        border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden; animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        transform: scale(0.95); opacity: 0;
    }
    .custom-modal-box.show { transform: scale(1); opacity: 1; }
    
    @keyframes modalPop { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    .cm-header { padding: 24px 24px 8px; text-align: center; } 
    .cm-icon { 
        width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 28px; margin: 0 auto 12px; 
    }
    .cm-icon.info { background: #eff6ff; color: #2563eb; border: 4px solid #dbeafe; }
    .cm-icon.question { background: #fefce8; color: #ca8a04; border: 4px solid #fef08a; }

    .cm-title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; }
    .cm-body { padding: 0 32px 24px; text-align: center; font-size: 14px; line-height: 1.5; color: #475569; }
    .cm-footer { background: #f8fafc; padding: 16px 24px; display: flex; gap: 12px; justify-content: center; border-top: 1px solid #f1f5f9; }
    
    .cm-btn { padding: 8px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; border: 1px solid transparent; }
    .cm-btn-cancel { background: #fff; border-color: #cbd5e1; color: #475569; }
    .cm-btn-cancel:hover { background: #f1f5f9; }
    .cm-btn-confirm { background: #2563eb; color: #fff; border: none; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
    .cm-btn-confirm:hover { background: #1d4ed8; }
</style>

<div class="page-container">
    {{-- HEADER --}}
    <div class="header-wrap">
        <div class="header-left">
            {{-- Icon Pensil dengan tema Biru --}}
            <div class="header-icon-box"><i class="fa fa-pencil"></i></div>
            <div class="header-title">
                <h1>Edit Draft SO <span class="header-ref">#{{ $opname->id }}</span></h1>
                <p>Perbarui jumlah fisik sebelum diproses.</p>
            </div>
        </div>
        <a href="{{ route('stock-opnames.show', $opname->id) }}" class="btn-action btn-white">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('stock-opnames.update', $opname->id) }}" id="soForm">
        {{ csrf_field() }} {{ method_field('PUT') }}
        <input type="hidden" name="items_json" id="items_json">
        
        {{-- CONFIG --}}
        <div class="card-modern">
            <div class="card-bd">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Opname</label>
                        <input type="text" name="opname_date" class="form-control-mod datepicker-flat" 
                               value="{{ $opname->opname_date->format('Y-m-d') }}" placeholder="dd/mm/yyyy" required>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" class="form-control-mod" value="{{ $opname->notes }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- WORKSPACE --}}
        <div class="card-modern" style="min-height: 500px; display: flex; flex-direction: column;">
            <div class="toolbar">
                <div class="btn-group-mod">
                    <button type="button" class="btn-toggle active" data-filter="all">Show All</button>
                    <button type="button" class="btn-toggle" data-filter="diff">Selisih Only</button>
                </div>
                <div style="display:flex; align-items:center; gap:15px; flex-wrap: wrap;">
                    <div style="font-size:12px; color:#64748b; font-weight: 500;">
                        Mode Edit: <strong>{{ count($opname->details) }} Items</strong>
                    </div>
                    <input type="text" id="tableSearch" class="form-control-mod" style="width: 250px; height: 38px;" placeholder="Cari kode / nama...">
                </div>
            </div>
            
            <div class="table-wrap">
                <table class="table-perf">
                    <thead>
                        <tr>
                            <th width="50" class="text-center">No</th>
                            <th width="100" class="text-center">Kode</th>
                            <th>Nama Barang</th>
                            <th width="100">Lokasi</th>
                            <th width="100" class="text-center">Sat</th>
                            <th width="120" class="text-center">Sistem</th>
                            <th width="140" class="text-center" style="background:#eff6ff; border-bottom:2px solid #2563eb;">Input Fisik</th>
                            <th width="100" class="text-center">Selisih</th>
                            <th width="200">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="tbody"></tbody>
                </table>
            </div>
            <div style="padding: 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; text-align: right;">
                <button type="button" class="btn-action btn-blue" id="btnUpdate"><i class="fa fa-save"></i> Simpan Perubahan</button>
            </div>
        </div>
    </form>
</div>

{{-- MODAL ALERT (Manual) --}}
<div id="cm-alert" class="custom-modal-overlay">
    <div class="custom-modal-box">
        <div class="cm-header">
            <div class="cm-icon info" id="cm-alert-icon"><i class="fa fa-info"></i></div>
            <h3 class="cm-title" id="cm-alert-title">Info</h3>
        </div>
        <div class="cm-body" id="cm-alert-msg">Pesan...</div>
        <div class="cm-footer">
            <button type="button" class="cm-btn cm-btn-confirm" onclick="closeCustomModal('cm-alert')">OK, Paham</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    // --- CUSTOM MODAL UTILS ---
    function openCustomModal(id) {
        var $el = $('#' + id);
        $el.css('display', 'flex').hide().fadeIn(200);
        $el.find('.custom-modal-box').removeClass('show');
        setTimeout(function(){ $el.find('.custom-modal-box').addClass('show'); }, 10);
    }
    
    function closeCustomModal(id) {
        var $el = $('#' + id);
        $el.find('.custom-modal-box').removeClass('show');
        setTimeout(function(){ $el.fadeOut(200); }, 200);
    }

    jQuery(function($){
        // 1. Flatpickr
        flatpickr(".datepicker-flat", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", locale: "id", allowInput: true });

        // 2. Render Existing Data
        var existingDetails = {!! isset($detailsJson) ? json_encode($detailsJson) : '[]' !!};

        function renderTable() {
            var rows = [];
            for (var i = 0; i < existingDetails.length; i++) {
                var d = existingDetails[i];
                // FIX: Float
                var sys = parseFloat(d.system);
                var fis = parseFloat(d.physical);
                var diff = fis - sys;
                
                // Format diff decimal
                var diffDisplay = (diff % 1 === 0) ? diff : diff.toFixed(2);
                
                var cls = (diff > 0) ? 'bd-plus' : ((diff < 0) ? 'bd-minus' : 'bd-zero');
                var sign = (diff > 0) ? '+' : '';
                var loc = d.location ? d.location : '-';
                
                // Highlight row if diff
                var rowCls = (diff !== 0) ? 'row-diff' : 'row-normal';

                rows.push(`
                <tr class="item-row ${rowCls}" data-id="${d.item_id}" data-sys="${sys}">
                    <td class="text-center" style="color:#94a3b8;">${i + 1}</td>
                    <td class="text-center" style="font-weight:600; color:#475569;">${d.code}</td>
                    <td><span class="item-name" style="font-weight:600; color:#1e293b;">${d.name}</span></td>
                    <td style="color:#64748b; font-size:12px;"><i class="fa fa-map-marker"></i> ${loc}</td>
                    <td class="text-center text-muted">${d.unit || '-'}</td>
                    <td class="text-center" style="font-weight:700; color:#64748b;">${sys}</td>
                    <td style="background:#eff6ff; padding:8px;">
                        {{-- FIX: Input step 0.01 --}}
                        <input type="number" class="inp-fisik" value="${fis}" min="0" step="0.01" tabindex="${i + 1}">
                    </td>
                    <td class="text-center">
                        <span class="badge-diff ${cls}">${sign}${diffDisplay}</span>
                    </td>
                    <td>
                        <input type="text" class="form-control-mod" style="height:32px; font-size:12px;" value="${d.notes || ''}" placeholder="...">
                    </td>
                </tr>`);
            }
            $('#tbody').html(rows.join(''));
        }
        renderTable();

        // 3. Logic Update Selisih & Visual
        $('#tbody').on('input keyup', '.inp-fisik', function(){
            var $row = $(this).closest('tr');
            // FIX: Float
            var sys = parseFloat($row.data('sys')) || 0;
            var val = $(this).val();
            var fis = (val === '') ? 0 : parseFloat(val);
            var diff = fis - sys;
            
            var diffDisplay = (diff % 1 === 0) ? diff : diff.toFixed(2);
            var sign = diff > 0 ? '+' : '';
            
            var $diffCell = $row.find('.badge-diff');
            $diffCell.text(sign + diffDisplay);
            
            $diffCell.removeClass('bd-plus bd-minus bd-zero');
            $row.removeClass('row-diff row-normal');

            if(diff === 0) { 
                $diffCell.addClass('bd-zero'); 
                $row.addClass('row-normal'); 
            } else { 
                $diffCell.addClass(diff > 0 ? 'bd-plus' : 'bd-minus'); 
                $row.addClass('row-diff'); 
            }
        });

        // 4. Toggle Filter Buttons
        $('.btn-toggle').click(function(){
            $('.btn-toggle').removeClass('active');
            $(this).addClass('active');
            var filter = $(this).data('filter');
            var rows = $('#tbody tr.item-row');
            
            if(filter === 'all') {
                rows.show();
            } else if (filter === 'diff') {
                rows.hide();
                rows.filter('.row-diff').show();
            }
        });

        // 5. Search
        $('#tableSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $("#tbody tr.item-row").filter(function() {
                var text = $(this).find('.item-name').text().toLowerCase() + ' ' + $(this).find('td:eq(1)').text().toLowerCase();
                $(this).toggle(text.indexOf(value) > -1)
            });
        });
        
        // 6. Submit
        $('#btnUpdate').click(function(e) {
            e.preventDefault();
            var data = [];
            $('.item-row').each(function() {
                var row = $(this);
                // Kita ambil value input, jika kosong string "" maka kirim 0 (default behavior edit)
                var val = row.find('.inp-fisik').val();
                if(val === '') val = 0; 

                data.push({
                    item_id: row.data('id'),
                    system: row.data('sys'),
                    physical: val,
                    notes: row.find('input[type=text]').val()
                });
            });
            $('#items_json').val(JSON.stringify(data));
            $('#soForm').submit();
        });

        // Keyboard Nav (Enter)
        $('#tbody').on('keydown', '.inp-fisik', function(e) {
            if (e.which === 13) { 
                e.preventDefault();
                var $nextTr = $(this).closest('tr').next('tr');
                if ($nextTr.length) {
                    var $nextInp = $nextTr.find('.inp-fisik');
                    $nextInp.focus().select();
                    $nextInp[0].scrollIntoView({behavior: "smooth", block: "center"});
                }
            }
        });
        
        // Prevent default submit enter
        $(window).keydown(function(event){
            if(event.keyCode == 13 && event.target.nodeName != 'TEXTAREA') { event.preventDefault(); return false; }
        });
    });
</script>
@endsection
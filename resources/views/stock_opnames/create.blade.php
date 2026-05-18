@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL BUAT SO (INPUT FISIK) === --}}
@section('help-content')
    <div class="help-alert" style="background:#eff6ff; border-left-color:#2563eb; color:#1e40af;">
        <i class="fa fa-snowflake-o"></i>
        <strong>PROSEDUR FREEZE MODE (WAJIB):</strong><br>
        Saat melakukan Stock Opname, <strong>DILARANG</strong> melakukan transaksi barang masuk (LPB) atau barang keluar (BON) fisik maupun sistem.
        <br><em>Jeda waktu antara input fisik dan transaksi lain akan menyebabkan selisih stok yang tidak valid.</em>
    </div>

    <h4 class="help-h"><i class="fa fa-users text-danger"></i> Aturan Akun & Scope</h4>
    <p class="help-p">
        <strong>PENTING:</strong> Mohon lakukan SO menggunakan akun Admin Divisi masing-masing (General / Apparel).
    </p>
    <div style="background:#fff1f2; border:1px solid #fecaca; padding:8px; border-radius:6px; font-size:12px; color:#991b1b; margin-bottom:10px;">
        <i class="fa fa-ban"></i> <strong>Larangan Super Admin:</strong> Jangan membuat SO menggunakan akun Super Admin. Hal ini akan memuat seluruh item database sekaligus yang berpotensi menyebabkan <em>System Crash</em> atau kesalahan kategori adjustment.
    </div>

    <h4 class="help-h"><i class="fa fa-keyboard-o text-primary"></i> Cara Input Data</h4>
    <ul class="help-list">
        <li>
            <strong>Load Data:</strong> Pilih kategori atau mode filter, lalu klik tombol "Load Data" untuk memunculkan daftar item beserta stok sistem saat ini (Snapshot).
        </li>
        <li>
            <strong>Input Fisik:</strong> Masukkan jumlah riil yang ada di rak ke kolom "Input Fisik".
            <br><em>(Tips: Kolom berwarna kuning menandakan inputan Anda. Jika kosong akan dianggap 0).</em>
        </li>
        <li>
            <strong>Simpan Draft:</strong> Tombol "Simpan Draft SO" di bawah <strong>BELUM MENGUBAH STOK</strong>. Ini hanya menyimpan kertas kerja Anda untuk diperiksa ulang di halaman Detail.
        </li>
    </ul>
@endsection

@section('content')
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    /* --- SYSTEM STYLES --- */
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
    .d-none { display: none !important; }

    /* HEADER */
    .header-wrap { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
    .header-left { display: flex; align-items: center; gap: 16px; }
    .header-icon-box {
        width: 48px; height: 48px; 
        background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
        border: 1px solid #dbeafe; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: var(--primary); font-size: 20px;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1);
        flex-shrink: 0;
    }
    .header-title h1 { font-size: 24px; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; letter-spacing: -0.5px; }
    .header-title p { font-size: 13px; color: var(--muted); margin: 2px 0 0; font-weight: 500; }

    /* CARDS */
    .card-modern { 
        background: #fff; border-radius: 12px; border: 1px solid var(--border); 
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; 
        overflow: visible; position: relative;
    }
    .card-hd { padding: 16px 24px; background: #fff; border-bottom: 1px solid var(--border); font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; font-size: 14px; border-radius: 12px 12px 0 0; }
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
    
    .helper-container {
        height: 42px; display: flex; align-items: center; 
        color: var(--muted); font-size: 12px; font-style: italic;
    }
    .helper-container i { margin-right: 6px; font-size: 14px; color: var(--primary); }

    /* FILTER BAR */
    .filter-bar { display: flex; gap: 12px; align-items: center; background: #f8fafc; padding: 14px 24px; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
    .select-mod {
        height: 40px; padding: 0 32px 0 14px; border: 1px solid #cbd5e1; border-radius: 8px;
        font-size: 13px; color: #334155; background-color: #fff; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 14px center; -webkit-appearance: none; font-weight: 500;
    }
    .select-mod:focus { border-color: var(--primary); }
    .select-mod.primary { border-color: #bfdbfe; background-color: #eff6ff; color: #1d4ed8; font-weight: 700; }

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
        transition: all 0.2s; background: #fffbeb; 
    }
    .inp-fisik:focus { background: #fff; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2); }
    .inp-notes { width: 100%; border: 1px solid #e2e8f0; height: 38px; border-radius: 8px; padding: 0 12px; font-size: 13px; }
    .inp-notes:focus { border-color: var(--primary); }

    .badge-diff { font-size: 12px; font-weight: 800; padding: 6px 12px; border-radius: 8px; min-width: 45px; display: inline-block; text-align: center; }
    .bd-zero { color: #cbd5e1; background: #f8fafc; }
    .bd-plus { background: #dcfce7; color: #15803d; }
    .bd-minus { background: #fee2e2; color: #b91c1c; }

    /* BUTTONS */
    .btn-action { height: 42px; padding: 0 20px; border-radius: 10px; font-weight: 600; font-size: 13px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none !important; transition: 0.2s; }
    .btn-blue { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(37,99,235,0.25); }
    .btn-blue:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-green { background: var(--success); color: white; box-shadow: 0 4px 12px rgba(16,185,129,0.25); }
    .btn-green:hover { background: #059669; transform: translateY(-1px); }
    .btn-white { background: #fff; border: 1px solid #cbd5e1; color: #475569; }
    .btn-white:hover { background: #f8fafc; border-color: #94a3b8; color: #1e293b; }
    .btn-disabled { opacity: 0.6; cursor: not-allowed; pointer-events: none; filter: grayscale(100%); }

    /* --- CUSTOM OVERLAY MODAL COMPACT (NO BOOTSTRAP DEPENDENCY) --- */
    .custom-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6); z-index: 99990;
        display: none; align-items: center; justify-content: center;
        backdrop-filter: blur(4px);
    }
    .custom-modal-box {
        background: #fff; width: 100%; max-width: 380px; /* FIX: LEBIH KECIL (COMPACT) */
        border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden; animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        transform: scale(0.95); opacity: 0;
    }
    .custom-modal-box.show { transform: scale(1); opacity: 1; }
    
    @keyframes modalPop {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .cm-header { padding: 24px 24px 8px; text-align: center; } /* Compact Padding */
    .cm-icon { 
        width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 28px; margin: 0 auto 12px; /* Smaller Icon */
    }
    .cm-icon.warn { background: #fff7ed; color: #ea580c; border: 4px solid #ffedd5; }
    .cm-icon.info { background: #eff6ff; color: #2563eb; border: 4px solid #dbeafe; }
    .cm-icon.error { background: #fef2f2; color: #ef4444; border: 4px solid #fee2e2; }

    .cm-title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; } /* Smaller Title */
    .cm-body { padding: 0 32px 24px; text-align: center; font-size: 14px; line-height: 1.5; color: #475569; } /* Smaller Text */
    .cm-footer { background: #f8fafc; padding: 16px 24px; display: flex; gap: 12px; justify-content: center; border-top: 1px solid #f1f5f9; }
    
    .cm-btn { padding: 8px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; border: 1px solid transparent; } /* Compact Button */
    .cm-btn-cancel { background: #fff; border-color: #cbd5e1; color: #475569; }
    .cm-btn-cancel:hover { background: #f1f5f9; }
    .cm-btn-confirm { background: #2563eb; color: #fff; border: none; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
    .cm-btn-confirm:hover { background: #1d4ed8; }

    /* LOADING */
    .loading-overlay { display: none; padding: 80px 20px; text-align: center; color: #64748b; }
</style>

<div class="page-container">
    {{-- HEADER --}}
    <div class="header-wrap">
        <div class="header-left">
            <div class="header-icon-box"><i class="fa fa-plus-square"></i></div>
            <div class="header-title">
                <h1>Stock Opname Baru</h1>
                <p>Mulai sesi perhitungan stok fisik baru.</p>
            </div>
        </div>
        <a href="{{ route('stock-opnames.index') }}" class="btn-action btn-white"><i class="fa fa-arrow-left"></i> Kembali</a>
    </div>

    <form method="POST" action="{{ route('stock-opnames.store') }}" id="soForm">
        {{ csrf_field() }}
        <input type="hidden" name="items_json" id="items_json">

        {{-- CONFIG --}}
        <div class="card-modern">
            <div class="card-hd"><i class="fa fa-sliders text-primary"></i> Konfigurasi Periode</div>
            <div class="card-bd">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Opname</label>
                        <input type="text" name="opname_date" id="opname_date" class="form-control-mod datepicker-flat" 
                               value="{{ date('Y-m-d') }}" placeholder="dd/mm/yyyy" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Catatan / Referensi</label>
                        <input type="text" name="notes" class="form-control-mod" placeholder="Contoh: SO Rutin Januari 2025" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="visibility: hidden;">Spacer</label>
                        <div class="helper-container">
                            <span><i class="fa fa-info-circle"></i> *Tanggal menentukan periode filter "Item Aktif"</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- WORKSPACE --}}
        <div class="card-modern" style="min-height: 500px; display: flex; flex-direction: column;">
            <div class="filter-bar">
                <div style="flex: 1; display:flex; gap:12px; flex-wrap: wrap;">
                    <select id="modeFilter" class="select-mod primary" style="width: 240px;">
                        <option value="active">Mode: Barang Aktif</option>
                        <option value="all">Mode: Semua Barang</option>
                    </select>
                    <select id="categoryFilter" class="select-mod" style="width: 260px;">
                        <option value="all">-- Semua Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn-action btn-green" id="btnLoadData">
                        <i class="fa fa-refresh"></i> Load Data
                    </button>
                </div>
                <div>
                    <input type="text" id="tableSearch" class="form-control-mod" style="width:280px;" placeholder="Cari barang di tabel...">
                </div>
            </div>

            <div style="flex: 1; position: relative;">
                {{-- LOADING --}}
                <div id="loadingState" class="loading-overlay">
                    <i class="fa fa-circle-o-notch fa-spin fa-3x" style="color:#2563eb; margin-bottom:24px;"></i>
                    <div style="font-weight:700; font-size:16px; color:#1e293b; margin-bottom:8px;">Sedang Menganalisis Transaksi...</div>
                    <p style="font-size:13px;">Mohon tunggu, sistem sedang mencari item yang relevan dengan periode ini.</p>
                </div>

                {{-- TABLE --}}
                <div class="table-wrap">
                    <table class="table-perf">
                        <thead>
                            <tr>
                                <th width="50" class="text-center">No</th>
                                <th width="120" class="text-center">Kode</th>
                                <th>Nama Barang</th>
                                <th width="150">Lokasi</th>
                                <th width="80" class="text-center">Sat</th>
                                <th width="100" class="text-center">Sistem</th>
                                <th width="140" class="text-center" style="color:#2563eb; background:#eff6ff; border-bottom:2px solid #60a5fa;">Input Fisik</th>
                                <th width="100" class="text-center">Selisih</th>
                                <th width="200">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="tbody">
                            <tr>
                                <td colspan="9" class="text-center" style="padding:80px 20px; color:#94a3b8;">
                                    <div style="margin-bottom:16px; opacity:0.5;">
                                        <i class="fa fa-arrow-up" style="font-size:40px;"></i>
                                    </div>
                                    <h4 style="font-size:16px; font-weight:700; margin-bottom:6px;">Belum Ada Data</h4>
                                    <p style="font-size:13px;">Pilih filter kategori di atas lalu klik tombol <strong>Load Data</strong> untuk memulai.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="padding: 24px; background: #f8fafc; border-top: 1px solid var(--border); text-align: right;">
                <button type="button" class="btn-action btn-blue" id="btnPreSubmit"><i class="fa fa-save"></i> Simpan Draft SO</button>
            </div>
        </div>
    </form>
</div>

{{-- === CUSTOM MODALS COMPACT (MANUAL HTML/CSS/JS) === --}}

{{-- 1. Modal Confirm Load --}}
<div id="cm-confirm-load" class="custom-modal-overlay">
    <div class="custom-modal-box">
        <div class="cm-header">
            <div class="cm-icon warn"><i class="fa fa-exclamation-triangle"></i></div>
            <h3 class="cm-title">Load Data Baru?</h3>
        </div>
        <div class="cm-body">
            Tindakan ini akan <strong>mereset tabel</strong>. Input fisik yang belum disimpan akan <strong>hilang permanen</strong>.<br><br>Lanjutkan memuat data baru?
        </div>
        <div class="cm-footer">
            <button type="button" class="cm-btn cm-btn-cancel" onclick="closeCustomModal('cm-confirm-load')">Batal</button>
            <button type="button" class="cm-btn cm-btn-confirm" id="cm-btn-yes-load">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

{{-- 2. Modal Alert --}}
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

    function showCustomAlert(title, msg, type) {
        $('#cm-alert-title').text(title);
        $('#cm-alert-msg').html(msg);
        
        var $icon = $('#cm-alert-icon');
        $icon.removeClass('info warn error').html('');
        
        if(type === 'error') {
            $icon.addClass('error').html('<i class="fa fa-times"></i>');
        } else if (type === 'warn') {
            $icon.addClass('warn').html('<i class="fa fa-exclamation-triangle"></i>');
        } else {
            $icon.addClass('info').html('<i class="fa fa-info"></i>');
        }
        openCustomModal('cm-alert');
    }

    jQuery(function($){
        // 1. Init Flatpickr
        flatpickr(".datepicker-flat", {
            altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", locale: "id", allowInput: true
        });

        // 2. Logic Load Data
        $('#btnLoadData').click(function(e){
            e.preventDefault();
            // Cek apakah ada data di tabel? (cek row item)
            var hasData = $('#tbody tr.item-row').length > 0;
            
            if(hasData) {
                openCustomModal('cm-confirm-load');
            } else {
                executeLoadData();
            }
        });

        // Confirm Load YES
        $('#cm-btn-yes-load').click(function(){
            closeCustomModal('cm-confirm-load');
            setTimeout(executeLoadData, 300);
        });

        // Main Load Function
        function executeLoadData() {
            var catId = $('#categoryFilter').val();
            var mode  = $('#modeFilter').val();
            var date  = $('#opname_date').val();
            
            var $btn = $('#btnLoadData');
            var $body = $('#tbody');
            var $load = $('#loadingState');
            var $wrap = $('.table-wrap');

            // Set UI Loading
            $btn.addClass('btn-disabled').prop('disabled', true).html('<i class="fa fa-spin fa-circle-o-notch"></i> Loading...');
            $wrap.hide();
            $body.empty();
            $load.show();

            $.ajax({
                url: '{{ route("api.so.items_by_category") }}',
                method: 'GET',
                data: { category_id: catId, mode: mode, date: date },
                dataType: 'json', // Expect JSON
                success: function(data) {
                    var rows = [];
                    if(!data || data.length === 0) {
                        rows.push(`<tr><td colspan="9" class="text-center" style="padding:80px 20px; color:#94a3b8;"><i class="fa fa-search" style="font-size:32px; margin-bottom:10px; display:block; opacity:0.5;"></i><span style="font-weight:600;">Tidak ditemukan item.</span><br>Coba ubah kategori atau tanggal periode.</td></tr>`);
                    } else {
                        $.each(data, function(i, item){
                            // FIX: FLOAT CAST
                            var sys = parseFloat(item.current_stock);
                            var loc = item.location ? item.location : '-';
                            rows.push(`
                            <tr class="item-row" data-id="${item.id}" data-sys="${sys}">
                                <td class="text-center" style="color:#94a3b8;">${i + 1}</td>
                                <td class="text-center" style="font-weight:700; color:#475569;">${item.code}</td>
                                <td><div style="font-weight:600; color:#1e293b;">${item.name}</div></td>
                                <td style="color:#64748b; font-size:12px;"><i class="fa fa-map-marker"></i> ${loc}</td>
                                <td class="text-center text-muted">${item.unit || '-'}</td>
                                <td class="text-center" style="font-weight:700; color:#64748b;">${sys}</td>
                                <td style="background:#eff6ff; padding:8px;">
                                    {{-- FIX: Input step 0.01 --}}
                                    <input type="number" class="inp-fisik" value="${sys}" min="0" step="0.01">
                                </td>
                                <td class="text-center"><span class="badge-diff bd-zero">0</span></td>
                                <td><input type="text" class="inp-notes" placeholder="..."></td>
                            </tr>`);
                        });
                    }
                    $body.html(rows.join(''));
                    if(data && data.length > 0) setTimeout(function(){ $('.inp-fisik').first().focus(); }, 100);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    var msg = "Gagal memuat data. ";
                    if(xhr.status == 500) msg += "Terjadi kesalahan internal server (500).";
                    else if(xhr.status == 404) msg += "Route API tidak ditemukan (404).";
                    else msg += "Periksa koneksi internet Anda.";
                    
                    showCustomAlert('Error', msg, 'error');
                    $body.html(`<tr><td colspan="9" class="text-center" style="padding:40px; color:#ef4444;"><i class="fa fa-exclamation-circle"></i> ${msg}</td></tr>`);
                },
                complete: function() {
                    $load.hide();
                    $wrap.fadeIn();
                    $btn.removeClass('btn-disabled').prop('disabled', false).html('<i class="fa fa-refresh"></i> Load Data');
                }
            });
        }

        // 3. Realtime Calculation
        $('#tbody').on('input keyup', '.inp-fisik', function(){
            var $row = $(this).closest('tr');
            // FIX: Parse Float
            var sys = parseFloat($row.data('sys')) || 0;
            var val = $(this).val();
            
            var $badge = $row.find('.badge-diff');
            $badge.removeClass('bd-zero bd-plus bd-minus');

            if (val === '') {
                $badge.text('-').addClass('bd-zero');
                return;
            }

            // FIX: Parse Float
            var fis = parseFloat(val);
            var diff = fis - sys;
            
            // Format 2 desimal jika ada koma
            var diffDisplay = (diff % 1 === 0) ? diff : diff.toFixed(2);
            var sign = diff > 0 ? '+' : '';
            
            $badge.text(sign + diffDisplay);
            if(diff === 0) $badge.addClass('bd-zero');
            else if(diff > 0) $badge.addClass('bd-plus');
            else $badge.addClass('bd-minus');
        });

        // 4. Keyboard Navigation
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

        // 5. Search
        $('#tableSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $("#tbody tr.item-row").filter(function() {
                var text = $(this).find('td').eq(1).text().toLowerCase() + ' ' + $(this).find('td').eq(2).text().toLowerCase();
                $(this).toggle(text.indexOf(value) > -1)
            });
        });

        // 6. Pre-Submit Validation
        $('#btnPreSubmit').click(function(e) {
            e.preventDefault();
            
            var data = [];
            var hasInput = false;

            $('.item-row').each(function() {
                var row = $(this);
                var val = row.find('.inp-fisik').val();
                
                // Simpan jika TIDAK KOSONG. '0' dianggap input valid.
                if (val !== '' && val !== null) {
                    hasInput = true;
                    data.push({
                        item_id: row.data('id'),
                        system: row.data('sys'),
                        physical: val,
                        notes: row.find('.inp-notes').val()
                    });
                }
            });

            if (!hasInput) {
                showCustomAlert('Data Kosong', 'Belum ada data fisik yang diinput!<br>Mohon isi minimal satu item (meskipun 0) sebelum menyimpan.', 'warn');
                return false;
            }

            $('#items_json').val(JSON.stringify(data));
            $('#soForm').submit();
        });

        // Prevent Enter Submit
        $(window).keydown(function(event){
            if(event.keyCode == 13 && event.target.nodeName != 'TEXTAREA') {
                event.preventDefault();
                return false;
            }
        });
    });
</script>
@endsection
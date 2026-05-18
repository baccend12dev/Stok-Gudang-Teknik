@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL HALAMAN REKAPITULASI (PROCUREMENT PLAN) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-calculator"></i>
        <strong>FUNGSI UTAMA:</strong> Halaman ini merekap total permintaan dari seluruh departemen untuk menghitung kebutuhan pembelian barang (PR) secara akurat.
    </div>

    <h4 class="help-h"><i class="fa fa-shopping-cart text-primary"></i> Membaca Kolom "Saran Beli"</h4>
    <p class="help-p">
        Sistem menghitung otomatis defisit stok dengan rumus:
        <br>
        <code>(Total Diminta + Buffer Stock) - Stok Gudang Saat Ini = <strong>Saran Beli</strong></code>
    </p>
    <ul class="help-list">
        <li>
            <strong style="color:#DC2626;">ANGKA MERAH (+100):</strong> Stok kurang. Anda disarankan membeli sejumlah angka tersebut agar stok aman.
        </li>
        <li>
            <strong style="color:#16A34A;">OK (Hijau):</strong> Stok gudang masih mencukupi untuk menutupi semua permintaan periode ini plus cadangan buffer. Tidak perlu beli.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-calendar text-primary"></i> Filter Periode (Wajib Diperhatikan)</h4>
    <ul class="help-list">
        <li>
            <strong>Bulan Ini (Current):</strong> 
            Menampilkan akumulasi permintaan yang sedang masuk saat ini. Data inilah yang digunakan sebagai dasar pembuatan <strong>PR (Purchase Requisition)</strong> ke Purchasing.
        </li>
        <li>
            <strong>Bulan Lalu (History):</strong> 
            Menampilkan data permintaan yang sudah lewat. Gunakan hanya untuk keperluan audit atau evaluasi tren konsumsi departemen.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-list-alt text-primary"></i> Rincian Distribusi</h4>
    <p class="help-p">
        Klik tombol <strong>"Rincian"</strong> di sebelah kanan untuk melihat departemen mana saja yang meminta barang tersebut. Ini berguna untuk analisa jika terjadi lonjakan permintaan yang tidak wajar.
    </p>
@endsection

@section('content')
<style>
    /* --- SYSTEM STYLE --- */
    :root {
        --primary: #4F46E5; --primary-hover: #4338CA;
        --secondary: #64748B; --border: #E2E8F0;
        --surface: #FFFFFF; --background: #F8FAFC;
        --table-head: #F1F5F9;
        --font-main: 'Inter', system-ui, sans-serif;
        --radius: 12px;
        --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        --danger-bg: #FEF2F2; --danger-text: #B91C1C;
        --success-bg: #F0FDF4; --success-text: #15803D;
        --warn-bg: #FFFBEB; --warn-text: #B45309; --warn-border: #FCD34D;
    }

    .app-container { padding: 25px 30px; font-family: var(--font-main); }

    /* --- PAGE HEADER --- */
    .pg-header { 
        display: flex; justify-content: space-between; align-items: center; 
        margin-bottom: 25px; border-bottom: 1px solid var(--border); padding-bottom: 20px;
    }
    .pg-title { display: flex; align-items: center; gap: 16px; }
    .pg-icon-box {
        width: 52px; height: 52px; background: #EEF2FF; color: var(--primary);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 26px; border: 1px solid #E0E7FF;
        box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    }
    .pg-text h1 { font-size: 24px; font-weight: 800; color: #0F172A; margin: 0; letter-spacing: -0.5px; }
    .pg-subtitle { font-size: 13px; color: var(--secondary); margin-top: 4px; font-weight: 500; }

    .btn-nav {
        text-decoration: none !important; padding: 10px 20px; border-radius: 10px;
        font-weight: 600; font-size: 13px; color: #475569; background: white;
        border: 1px solid var(--border); transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05); cursor: pointer;
    }
    .btn-nav:hover { background: #F8FAFC; color: #0F172A; border-color: #CBD5E1; transform: translateY(-1px); }
    .btn-excel { 
        background: #10B981; color: white !important; border: none; border-bottom: 2px solid #059669;
        text-shadow: 0 1px 1px rgba(0,0,0,0.1);
    }
    .btn-excel:hover { background: #059669; color: white !important; border-bottom-color: #047857; }

    /* --- FILTER CARD (BOX KOTAK) --- */
    .filter-card {
        background: white; border: 1px solid var(--border); border-radius: var(--radius);
        padding: 20px; margin-bottom: 25px; box-shadow: var(--shadow);
    }
    .filter-header {
        font-size: 13px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;
        margin-bottom: 15px; display: flex; align-items: center; gap: 8px;
    }
    .filter-grid {
        display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;
    }
    .f-group label { display: block; font-size: 12px; font-weight: 600; color: var(--secondary); margin-bottom: 6px; }
    .f-input {
        width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;
        font-size: 13px; font-weight: 600; color: #1E293B; background: #fff; height: 42px; /* Fixed Height */
    }
    .f-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    
    .btn-filter {
        background: var(--primary); color: white; border: none; padding: 0 24px; border-radius: 8px;
        font-weight: 700; font-size: 13px; height: 42px; cursor: pointer; display: flex; align-items: center; gap: 8px;
    }
    .btn-filter:hover { background: var(--primary-hover); }

    /* --- HISTORICAL ALERT (PERFECTIONIST STYLE) --- */
    .alert-historical {
        background: #FFFBEB; 
        border-left: 5px solid #F59E0B; 
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 16px 20px;
        margin-bottom: 25px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        animation: fadeIn 0.5s ease-in-out;
    }
    .alert-icon-wrap {
        background: #FEF3C7;
        color: #D97706;
        width: 40px; height: 40px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .alert-content h4 {
        margin: 0 0 6px 0;
        font-size: 15px;
        font-weight: 800;
        color: #92400E;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .alert-content p {
        margin: 0;
        font-size: 13px;
        color: #B45309;
        line-height: 1.5;
        font-weight: 500;
    }
    .alert-content strong { color: #78350F; font-weight: 700; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* --- QUICK ACTIONS & INFO BAR (SEJAJAR) --- */
    .filter-footer {
        margin-top: 20px; padding-top: 15px; border-top: 1px dashed var(--border);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
    }
    
    .quick-actions { display: flex; align-items: center; gap: 10px; }
    .qa-label { font-size: 12px; font-weight: 700; color: #64748B; margin-right: 5px; }
    
    .btn-quick {
        padding: 0 14px; height: 34px; border-radius: 6px; font-size: 12px; font-weight: 600;
        background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; cursor: pointer; transition: 0.2s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-quick:hover { background: #E2E8F0; color: #1E293B; border-color: #CBD5E1; }

    .info-metrics {
        display: flex; align-items: center; gap: 20px; font-size: 12px; color: #334155;
        background: #F8FAFC; padding: 6px 12px; border-radius: 8px; border: 1px solid #E2E8F0;
    }
    .info-metrics strong { color: var(--primary); }
    .info-sep { width: 1px; height: 16px; background: #CBD5E1; }

    /* --- SEARCH & TABS --- */
    .controls-row { display: flex; gap: 15px; margin-bottom: 15px; align-items: center; }
    .search-wrap { flex: 1; position: relative; max-width: 400px; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8; }
    .search-input { 
        width: 100%; padding: 10px 10px 10px 36px; border: 1px solid var(--border); border-radius: 10px;
        font-size: 13px; outline: none; transition: border 0.2s;
    }
    .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }

    .cat-tabs { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 5px; flex: 1; }
    .cat-tab { 
        padding: 8px 16px; border-radius: 20px; background: white; border: 1px solid var(--border);
        font-size: 12px; font-weight: 600; color: #64748B; cursor: pointer; white-space: nowrap;
    }
    .cat-tab.active { background: var(--primary); color: white; border-color: var(--primary); }

    /* --- TABLE --- */
    .table-card { 
        background: white; border-radius: var(--radius); border: 1px solid var(--border); 
        box-shadow: var(--shadow); overflow: hidden;
    }
    .table-wrap { max-height: 60vh; overflow: auto; }
    
    .recap-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
    .recap-table th {
        background: var(--table-head); padding: 16px 12px;
        font-weight: 800; color: #475569; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;
        border-bottom: 1px solid #CBD5E1; position: sticky; top: 0; z-index: 10;
        box-shadow: 0 2px 2px -1px rgba(0,0,0,0.05);
    }
    .text-center { text-align: center !important; }
    
    .recap-table td { padding: 12px 12px; border-bottom: 1px solid #F1F5F9; color: #1E293B; vertical-align: middle; }
    .recap-table tr:last-child td { border-bottom: none; }
    .recap-table tr:hover { background-color: #F8FAFC; }

    .row-deficit { background-color: #FEF2F2 !important; }
    .text-deficit { color: #DC2626; font-weight: 800; }
    .row-safe { background-color: #F0FDF4 !important; }
    .text-safe { color: #16A34A; font-weight: 700; }

    .item-main { font-weight: 700; font-size: 14px; margin-bottom: 4px; color: #0F172A; }
    .item-sub { font-size: 11px; color: var(--secondary); display: flex; align-items: center; gap: 6px; }
    .badge-code { font-family: monospace; background: #F1F5F9; padding: 2px 6px; border-radius: 4px; color: #475569; letter-spacing: -0.3px; }
    .badge-cat { background: #EEF2FF; color: var(--primary); padding: 4px 10px; border-radius: 99px; font-weight: 700; font-size: 10px; text-transform: uppercase; }
    
    .badge { display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; min-width: 40px; }
    .b-stock { background: #F3F4F6; color: #374151; border: 1px solid #E5E7EB; }
    .b-req { background: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE; }
    .b-buy { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
    .b-urgent { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
    .b-buffer { background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A; }

    /* Modal List */
    .modal-detail-list { list-style: none; padding: 0; margin: 0; }
    .modal-detail-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px dashed #E2E8F0; }
    .modal-detail-item:last-child { border-bottom: none; }
    .dept-name { font-weight: 600; color: #334155; }
    .dept-qty { font-weight: 700; color: var(--primary); background: #EEF2FF; padding: 2px 10px; border-radius: 6px; font-size: 13px; }
    .dept-status { font-size: 10px; text-transform: uppercase; font-weight: 700; color: #64748B; margin-left: 8px; }

    /* SweetAlert Override */
    .swal2-popup.modern-popup { border-radius: 16px !important; padding: 25px !important; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1) !important; }
    .swal2-title { font-family: var(--font-main) !important; color: #1E293B !important; font-size: 18px !important; padding: 0 0 10px 0 !important; }
    .swal2-confirm { background: var(--primary) !important; border-radius: 8px !important; font-weight: 700 !important; padding: 10px 24px !important; }
</style>

<div class="app-container">
    {{-- HEADER --}}
    <div class="pg-header">
        <div class="pg-title">
            <div class="pg-icon-box"><i class="fa fa-shopping-cart"></i></div>
            <div class="pg-text">
                <h1>Dashboard Pengadaan Barang</h1>
                <div class="pg-subtitle">
                    Analisis kebutuhan vs stok gudang untuk keputusan pembelian yang cerdas.
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            {{-- TOMBOL EXCEL --}}
            <a href="{{ route('requests.recap.export', ['from' => $startDate, 'to' => $endDate]) }}" class="btn-nav btn-excel">
                <i class="fa fa-file-excel-o"></i> Download Plan
            </a>
            <a href="{{ route('requests.index') }}" class="btn-nav">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    {{-- KOTAK FILTER PERIODE --}}
    <div class="filter-card">
        <div class="filter-header">
            <i class="fa fa-calendar-check-o"></i> Filter Periode Procurement (Wajib)
        </div>
        
        <form action="{{ route('requests.recap') }}" method="GET" id="filterForm">
            <div class="filter-grid">
                <div class="f-group">
                    <label>Dari Tanggal:</label>
                    <input type="text" name="from" id="dateFrom" class="f-input js-flatpickr" value="{{ $startDate }}" placeholder="Pilih Tanggal...">
                </div>
                <div class="f-group">
                    <label>Sampai Tanggal:</label>
                    <input type="text" name="to" id="dateTo" class="f-input js-flatpickr" value="{{ $endDate }}" placeholder="Pilih Tanggal...">
                </div>
                <div class="f-group">
                    <button type="submit" class="btn-filter">
                        <i class="fa fa-search"></i> Tampilkan
                    </button>
                </div>
            </div>
        </form>

        {{-- QUICK ACCESS & INFO BAR --}}
        <div class="filter-footer">
            <div class="quick-actions">
                <span class="qa-label">Quick Access:</span>
                <button type="button" class="btn-quick" onclick="setPeriod('this_month')">📅 Bulan Ini</button>
                <button type="button" class="btn-quick" onclick="setPeriod('last_month')">📅 Bulan Lalu</button>
                <button type="button" class="btn-quick" onclick="location.reload()"><i class="fa fa-refresh"></i> Reset</button>
            </div>

            <div class="info-metrics">
                <div>
                    <i class="fa fa-info-circle"></i> Periode: 
                    <strong>{{ date('d/m/Y', strtotime($startDate)) }}</strong> - <strong>{{ date('d/m/Y', strtotime($endDate)) }}</strong>
                </div>
                <div class="info-sep"></div>
                <div>
                    📊 Total: <strong>{{ $totalDocs }}</strong> Dokumen
                </div>
            </div>
        </div>
    </div>

    {{-- ALERT HISTORICAL MODE --}}
    @php
        $isHistorical = (strtotime($endDate) < strtotime(date('Y-m-d')));
    @endphp
    
    @if($isHistorical)
        <div class="alert-historical">
            <div class="alert-icon-wrap">
                <i class="fa fa-history"></i>
            </div>
            <div class="alert-content">
                <h4>MODE ARSIP / DATA HISTORIS</h4>
                <p>
                    Anda sedang melihat rekapitulasi periode lampau. Angka di kolom <strong>"Saran Beli"</strong> adalah kalkulasi matematis mentah berdasarkan total permintaan saat itu dikurangi stok saat ini. 
                    <br>Mohon pastikan untuk mengecek ulang <strong>History PR (Purchase Request)</strong> agar tidak terjadi duplikasi pengadaan barang.
                </p>
            </div>
        </div>
    @endif

    {{-- KONTEN TABEL --}}
    @if($recapItems->count() > 0)
        
        {{-- SEARCH & CATEGORY TABS --}}
        <div class="controls-row">
            <div class="search-wrap">
                <i class="fa fa-search search-icon"></i>
                <input type="text" id="filterInput" class="search-input" placeholder="Cari nama barang atau kode...">
            </div>
            <div class="cat-tabs">
                <button class="cat-tab active" onclick="filterCat('all', this)">SEMUA</button>
                @foreach($categories as $id => $name)
                    <button class="cat-tab" onclick="filterCat('{{ $id }}', this)">{{ $name }}</button>
                @endforeach
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrap">
                <table class="recap-table" id="recapTable">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Item Barang</th>
                            <th style="width: 80px;" class="text-center">Satuan</th>
                            <th style="width: 100px;" class="text-center">Buffer (Min)</th>
                            <th style="width: 100px;" class="text-center">Stok Gudang</th>
                            <th style="width: 100px;" class="text-center">Total Diminta</th>
                            <th style="width: 120px;" class="text-center">Saran Beli (Defisit)</th>
                            <th style="width: 100px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recapItems as $item)
                            @php
                                // FIX: Float
                                $stock   = (float)$item->current_stock;
                                // USE TOTAL HISTORY (ASLI)
                                $demandDisplay = (float)$item->total_requested_raw;
                                
                                // RAW CALCULATION FOR SARAN BELI (Sama seperti Total Diminta untuk konsistensi di mata Admin)
                                // Logic: (Total Diminta + Buffer) - Stok Sekarang
                                $buffer  = (float)$item->buffer_min; 
                                $totalNeed = $demandDisplay + $buffer;
                                $deficit   = max(0, $totalNeed - $stock);
                                
                                $isSafe    = ($deficit == 0);
                                $rowClass  = $isSafe ? 'row-safe' : 'row-deficit';
                            @endphp
                            <tr class="item-row {{ $rowClass }}" data-cat="{{ $item->category_id }}">
                                <td>
                                    <div class="item-main">{{ $item->item_name }}</div>
                                    <div class="item-sub">
                                        <span class="badge-code">{{ $item->item_code }}</span>
                                        <span class="badge-cat" style="font-size:9px;">{{ $item->category_name }}</span>
                                    </div>
                                </td>
                                <td class="text-center" style="font-weight: 500;">{{ $item->item_unit }}</td>
                                <td class="text-center"><span class="badge b-buffer">{{ (float)$buffer }}</span></td>
                                <td class="text-center"><span class="badge b-stock">{{ (float)$stock }}</span></td>
                                
                                {{-- KOLOM TOTAL DIMINTA (ANGKA ASLI) --}}
                                <td class="text-center"><span class="badge b-req">{{ (float)$demandDisplay }}</span></td>
                                
                                {{-- KOLOM SARAN BELI (RAW CALCULATION) --}}
                                <td class="text-center">
                                    @if(!$isSafe)
                                        <span class="badge b-urgent">+{{ (float)$deficit }}</span>
                                    @else
                                        <span class="badge b-buy" style="background:#F1F5F9; color:#94A3B8; border-color:#E2E8F0;">OK</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn-nav" style="padding: 6px 12px; font-size: 11px;" 
                                            onclick="showDetail({{ $item->item_id }}, '{{ addslashes(htmlspecialchars($item->item_name)) }}')">
                                            <i class="fa fa-list-ul"></i> Rincian
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- EMPTY STATE --}}
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 10px;">
            <img src="https://img.icons8.com/fluency/96/calendar.png" style="opacity: 0.6; margin-bottom: 20px; width: 80px;">
            <h3 style="margin:0; font-weight: 800; color: #1E293B;">Tidak Ada Request di Periode Ini</h3>
            <p style="color: #64748B; margin-top: 8px; max-width: 400px; margin-left: auto; margin-right: auto;">
                Coba ubah filter tanggal di atas untuk melihat data periode lain.
            </p>
        </div>
    @endif
</div>

{{-- DATA INJECTION FOR JS --}}
<script>
    var detailData = {!! json_encode($detailMap) !!};
</script>

{{-- LOAD LIBRARIES --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // 1. INIT FLATPICKR (KALENDER)
    flatpickr(".js-flatpickr", {
        altInput: true, altFormat: "d M Y", dateFormat: "Y-m-d",
        locale: "id", allowInput: true
    });

    // 2. QUICK PERIOD FUNCTION
    function setPeriod(type) {
        var today = new Date();
        var y = today.getFullYear(), m = today.getMonth();
        var firstDay, lastDay;

        if (type === 'this_month') {
            firstDay = new Date(y, m, 1);
            lastDay = new Date(y, m + 1, 0);
        } else if (type === 'last_month') {
            firstDay = new Date(y, m - 1, 1);
            lastDay = new Date(y, m, 0);
        }

        // Format YYYY-MM-DD manually to avoid timezone issues
        var f = (d) => {
            return d.getFullYear() + "-" + ("0"+(d.getMonth()+1)).slice(-2) + "-" + ("0"+d.getDate()).slice(-2);
        };

        // Update value & trigger form submit
        document.getElementById('dateFrom')._flatpickr.setDate(firstDay);
        document.getElementById('dateTo')._flatpickr.setDate(lastDay);
        
        // Opsional: Langsung submit form biar user gak perlu klik "Tampilkan" lagi
        document.getElementById('filterForm').submit();
    }

    // 3. FILTER TABLE FUNCTION
    const filterInput = document.getElementById('filterInput');
    const tableRows = document.querySelectorAll('.item-row');
    let activeCat = 'all';

    function filterTable() {
        const term = filterInput ? filterInput.value.toLowerCase() : '';
        if(tableRows.length > 0) {
            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const cat = row.getAttribute('data-cat');
                const matchSearch = text.includes(term);
                const matchCat = (activeCat === 'all' || cat === activeCat);
                row.style.display = (matchSearch && matchCat) ? '' : 'none';
            });
        }
    }

    if(filterInput){ filterInput.addEventListener('keyup', filterTable); }

    window.filterCat = function(catId, btn) {
        activeCat = catId;
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterTable();
    }

    // 4. SHOW DETAIL MODAL
    window.showDetail = function(itemId, itemName) {
        var data = detailData[itemId];
        var listHtml = '<ul class="modal-detail-list">';
        
        if (data && data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                var d = data[i];
                listHtml += 
                    '<li class="modal-detail-item">' +
                        '<div>' +
                            '<span class="dept-name">' + d.dept + '</span>' +
                            '<span class="dept-status">' + d.status + '</span>' +
                        '</div>' +
                        // FIX: Float
                        '<span class="dept-qty">' + parseFloat(d.qty) + '</span>' +
                    '</li>';
            }
        } else {
            listHtml += '<li class="modal-detail-item" style="justify-content:center; color:#94a3b8;">Tidak ada data detail.</li>';
        }
        listHtml += '</ul>';

        Swal.fire({
            title: itemName,
            html: 
                '<div style="text-align: left; margin-bottom: 15px; font-size: 13px; color: #64748B;">' +
                    'Rincian permintaan asli (Total Requested):' +
                '</div>' + listHtml,
            confirmButtonText: 'Tutup',
            width: 400,
            customClass: {
                popup: 'modern-popup',
                title: 'swal2-title',
                htmlContainer: 'swal2-html-container',
                confirmButton: 'swal2-confirm'
            }
        });
    }
</script>
@endsection
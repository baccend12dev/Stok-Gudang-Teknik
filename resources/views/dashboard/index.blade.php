@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL DASHBOARD (ADMIN & SUPER ADMIN) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i> 
        <strong>INFO:</strong> Data yang ditampilkan mengikuti <strong>Filter Tanggal</strong> di pojok kanan atas. Default adalah bulan ini.
    </div>

    <h4 class="help-h"><i class="fa fa-tachometer text-primary"></i> Membaca Indikator Utama (KPI)</h4>
    <p class="help-p">Dashboard ini berfungsi sebagai "Menara Kontrol". Kotak-kotak di atas menunjukkan status kesehatan inventori saat ini:</p>
    
    <ul class="help-list">
        <li>
            <strong>Permintaan Baru (Kuning):</strong> Jumlah request dari user yang statusnya masih <em>OPEN</em>. 
            <br><span style="font-size:11px; color:#64748b;">Tindakan: Klik untuk segera melakukan Approval.</span>
        </li>
        <li>
            <strong>Total Keluar & Barang Masuk:</strong> Akumulasi pergerakan barang dalam periode tanggal yang dipilih.
            <br><span style="font-size:11px; color:#2563eb;">Fitur: <strong>KLIK KARTU INI</strong> untuk melihat rincian detail item apa saja yang bergerak tanpa perlu membuka menu Laporan.</span>
        </li>
        <li>
            <strong>Stok Kritis (Merah):</strong> Barang yang jumlah fisiknya sudah di bawah batas aman (Buffer Stock). Segera lakukan pemesanan ulang (RO).
        </li>
        <li>
            <strong>Stok Habis (Hitam):</strong> Barang yang fisiknya 0. Ini prioritas tertinggi untuk di-restock agar operasional tidak terganggu.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-line-chart text-primary"></i> Analisa Grafik</h4>
    <ul class="help-list">
        <li><strong>Tren Pergerakan:</strong> Membandingkan volume barang masuk vs keluar selama 6 bulan terakhir. Berguna untuk melihat pola musiman.</li>
        <li><strong>Top 5 Dept Boros:</strong> Menunjukkan departemen mana yang paling sering meminta barang (berdasarkan frekuensi item).</li>
    </ul>

    <h4 class="help-h"><i class="fa fa-bolt text-primary"></i> Quick Actions & Prioritas</h4>
    <p class="help-p">
        Gunakan tombol <strong>"Quick Actions"</strong> (di tengah) untuk jalan pintas input data.
        Pantau panel <strong>"Prioritas Tugas"</strong> (di kanan) untuk melihat pekerjaan yang tertunda (Draft Stock Opname atau BON Pending).
    </p>
@endsection

@section('content')
{{-- Load Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
{{-- Load Flatpickr CSS (Format d/m/Y) --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    /* GLOBAL DASHBOARD STYLES */
    
    /* ===== HEADER + FILTER (FIX LAYOUT) ===== */
    .dashboard-header{
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:18px;
        flex-wrap:wrap;
        margin-bottom: 18px; /* Jarak aman ke elemen berikutnya */
    }
    .dashboard-header-left{ min-width: 280px; }
    .dashboard-title{ margin:0; }
    .dashboard-subtitle{ margin:6px 0 0 0; }

    .dash-header-filter{
        display:flex;
        gap:12px;
        align-items:flex-end;
        flex-wrap:wrap;
        justify-content:flex-end;
    }
    .dash-filter-group{
        display:flex;
        flex-direction:column;
        gap:6px;
        min-width: 170px;
    }
    .dash-filter-label{
        font-size: 11px;
        font-weight: 800;
        color:#64748b;
        text-transform: uppercase;
        letter-spacing: .6px;
        display:flex;
        align-items:center;
        gap:6px;
        margin:0;
        line-height:1;
        white-space:nowrap;
    }
    .dash-filter-input{
        height: 40px;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background:#fff;
        color:#0f172a;
        font-size: 13px;
        outline: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        min-width: 170px;
    }
    /* FIX: Input Flatpickr agar background putih */
    .dash-filter-input.flatpickr-input { background-color: #fff !important; }

    .dash-filter-actions{
        display:flex;
        gap:10px;
        align-items:flex-end;
        padding-bottom: 0;
    }
    .dash-btn-apply{
        height:40px;
        padding: 0 16px;
        border-radius: 10px;
        border: 1px solid #2563eb;
        background: #3b82f6;
        color:#fff;
        font-weight:700;
        font-size: 12px;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:8px;
        box-shadow: 0 6px 14px rgba(59,130,246,0.18);
        transition: all .2s ease;
        white-space:nowrap;
    }
    .dash-btn-apply:hover{ background:#2563eb; border-color:#2563eb; }
    .dash-btn-reset{
        height:40px;
        padding: 0 14px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color:#0f172a;
        font-weight:700;
        font-size: 12px;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        transition: all .2s ease;
        white-space:nowrap;
    }
    .dash-btn-reset:hover{ background:#f8fafc; }

    /* Jarak bawah filter ke KPI biar lega */
    .dash-header-spacer{ margin-bottom: 10px; }

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

    /* 1. KPI CARDS (HORIZONTAL LAYOUT - UPGRADED) */
    /* FIX: Gunakan auto-fit agar jumlah kolom dinamis (bisa 5 kolom) */
    .dash-grid { 
        display: grid; 
        /* FIX: Perlebar min-width card jadi 220px biar konten gak desak-desakan */
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
        gap: 20px; 
        margin-bottom: 28px; 
    }

    .kpi-card {
        background: #fff; border-radius: 16px; padding: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        display: flex; 
        align-items: center; 
        gap: 16px; /* Kurangi gap dikit biar muat */
        height: 100%; transition: all 0.3s ease;
        position: relative; overflow: hidden;
        text-decoration: none !important;
        cursor: pointer; color: inherit;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.06); border-color: #bfdbfe; }
    
    .kpi-icon-wrapper {
        width: 56px; height: 56px; border-radius: 14px; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 24px; flex-shrink: 0;
    }

    .kpi-content { flex-grow: 1; display: flex; flex-direction: column; justify-content: center; min-width: 0; /* Prevent overflow */ }

    .kpi-title { 
        font-size: 11px; font-weight: 700; color: #64748b; 
        text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; 
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* FIX: TAMPILAN TANGGAL SATU BARIS */
    .kpi-period {
        font-size: 11px; /* Perkecil dikit */
        font-weight: 600;
        color:#334155;
        display:flex;
        align-items:center;
        gap:5px;
        margin-bottom: 4px;
        white-space: nowrap; /* Mencegah turun baris */
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .kpi-period i{ color:#94a3b8; font-size: 11px; }

    .kpi-value-row { display: flex; align-items: baseline; gap: 4px; }
    .kpi-value { font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1; }
    .kpi-unit  { font-size: 12px; font-weight: 600; color: #64748b; }
    
    .kpi-trend { font-size: 11px; font-weight: 600; margin-top: 4px; }

    /* Color Themes (Sama) */
    .sc-blue .kpi-icon-wrapper { background: #eff6ff; color: #2563eb; }
    .sc-green .kpi-icon-wrapper { background: #f0fdf4; color: #16a34a; }
    .sc-red .kpi-icon-wrapper { background: #fef2f2; color: #dc2626; }
    .sc-orange .kpi-icon-wrapper { background: #fff7ed; color: #ea580c; }
    .sc-dark .kpi-icon-wrapper { background: #f1f5f9; color: #334155; }
    .sc-yellow .kpi-icon-wrapper { background: #fffbeb; color: #f59e0b; }
    
    .text-up { color: #16a34a; } 
    .text-down { color: #dc2626; }
    .text-muted-trend { color: #94a3b8; font-weight: 500; }

    /* 2. QUICK ACTIONS (TILES STYLE) */
    .quick-actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
    .btn-quick {
        background: #fff; border: 1px solid #e2e8f0; padding: 20px; border-radius: 16px;
        text-decoration: none; color: #475467; 
        display: flex; align-items: center; gap: 15px; transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .btn-quick-icon {
        width: 50px; height: 50px; border-radius: 12px; background: #f8fafc; 
        display: flex; align-items: center; justify-content: center; font-size: 22px; color: #3b82f6;
        transition: all 0.3s ease;
    }
    .btn-quick-info h5 { margin: 0 0 2px 0; font-size: 14px; font-weight: 700; color: #1e293b; }
    .btn-quick-info p { margin: 0; font-size: 11px; color: #94a3b8; }
    
    .btn-quick:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: #bfdbfe; }
    .btn-quick:hover .btn-quick-icon { background: #3b82f6; color: #fff; }
    
    /* 3. CONTENT LAYOUT */
    .row-content { display: flex; gap: 24px; margin-bottom: 28px; flex-wrap: wrap; }
    .col-main { flex: 2; min-width: 300px; }
    .col-side { flex: 1; min-width: 300px; }
    
    .content-card { 
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; 
        overflow: hidden; height: 100%; display: flex; flex-direction: column; 
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }
    .card-hd { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .card-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px; }
    .card-bd { padding: 24px; flex-grow: 1; position: relative; }

    /* 4. ACTION LIST */
    .action-list { list-style: none; padding: 0; margin: 0; }
    .action-item { display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px dashed #e2e8f0; }
    .action-item:last-child { border-bottom: none; padding-bottom: 0; }
    .action-left { display: flex; align-items: center; gap: 14px; }
    .action-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    
    .btn-action { 
        padding: 6px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; 
        text-decoration: none; background: #f8fafc; color: #475467; border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    .btn-action:hover { background: #3b82f6; color: #fff; border-color: #3b82f6; }

    /* 5. TABLE RECENT */
    .table-recent { width: 100%; font-size: 13px; border-collapse: separate; border-spacing: 0; }
    .table-recent td { padding: 14px 0; border-bottom: 1px solid #f1f5f9; color: #475467; vertical-align: middle; }
    .table-recent tr:last-child td { border-bottom: none; }
    
    .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-pill.success { background: #f0fdf4; color: #15803d; }
    .status-pill.warning { background: #fefce8; color: #b45309; }
    .status-pill.danger  { background: #fef2f2; color: #b91c1c; }
    
    .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .dot-success { background: #22c55e; }
    .dot-warning { background: #f59e0b; }
    .dot-danger  { background: #ef4444; }
</style>

@php
    // === FIX LOGIC TANGGAL ===
    $valStart = isset($kpiStartStr) ? $kpiStartStr : date('Y-m-01');
    $valEnd   = isset($kpiEndStr)   ? $kpiEndStr   : date('Y-m-d');

    // Label Periode untuk KPI Card
    $kpiPeriodLabel = '';
    if (strtotime($valStart) && strtotime($valEnd)) {
        $kpiPeriodLabel = date('d M Y', strtotime($valStart)) . ' - ' . date('d M Y', strtotime($valEnd));
    }
@endphp

<div class="page-container">
    {{-- HEADER + FILTER (SEJAJAR DENGAN JUDUL) --}}
    <div class="dashboard-header">
        <div class="dashboard-header-left">
            <h1 class="dashboard-title">Executive Dashboard</h1>
            <p class="dashboard-subtitle">Ringkasan aktivitas inventori dan status operasional per hari ini.</p>
        </div>

        <form action="{{ route('dashboard') }}" method="GET" class="dash-header-filter">
            <div class="dash-filter-group">
                <label class="dash-filter-label"><i class="fa fa-calendar"></i> Dari Tanggal</label>
                <input type="text" name="start_date" class="dash-filter-input datepicker-flat" 
                       value="{{ $valStart }}" placeholder="dd/mm/yyyy">
            </div>

            <div class="dash-filter-group">
                <label class="dash-filter-label"><i class="fa fa-calendar"></i> Sampai Tanggal</label>
                <input type="text" name="end_date" class="dash-filter-input datepicker-flat" 
                       value="{{ $valEnd }}" placeholder="dd/mm/yyyy">
            </div>

            <div class="dash-filter-actions">
                <button type="submit" class="dash-btn-apply">
                    <i class="fa fa-filter"></i> Terapkan
                </button>
                <a href="{{ route('dashboard') }}" class="dash-btn-reset">Reset</a>
            </div>
        </form>
    </div>

    <div class="dash-header-spacer"></div>

    {{-- 1. KPI CARDS (HORIZONTAL LAYOUT) --}}
    <div class="dash-grid">
        
        {{-- WIDGET REQUEST BARU (KUNING) --}}
        <a href="{{ route('requests.index', ['status' => 'OPEN']) }}" class="kpi-card sc-yellow">
            <div class="kpi-icon-wrapper"><i class="fa fa-inbox"></i></div>
            <div class="kpi-content">
                <div class="kpi-title" style="color: #b45309;">Permintaan Baru</div>
                <div class="kpi-value-row">
                    <div class="kpi-value" style="color: #b45309;">
                        {{ isset($pendingRequestCount) ? $pendingRequestCount : 0 }}
                    </div>
                    <div class="kpi-unit" style="color: #d97706;">Request</div>
                </div>
                <div class="kpi-trend" style="color: #d97706; font-weight: 500;">
                    Menunggu Approval
                </div>
            </div>
        </a>

        {{-- Total Keluar (LINKED TO DETAIL) --}}
        <a href="{{ route('dashboard.detail.out', ['start_date' => $valStart, 'end_date' => $valEnd]) }}" class="kpi-card sc-orange">
            <div class="kpi-icon-wrapper"><i class="fa fa-upload"></i></div>
            <div class="kpi-content">
                <div class="kpi-title">Total Keluar</div>

                @if(!empty($kpiPeriodLabel))
                    <div class="kpi-period"><i class="fa fa-calendar"></i> {{ $kpiPeriodLabel }}</div>
                @endif

                <div class="kpi-value-row">
                    <div class="kpi-value">{{ number_format($bonThisMonth, 0, ',', '.') }}</div>
                    <div class="kpi-unit">Item</div>
                </div>
            </div>
        </a>

        {{-- Barang Masuk (LINKED TO DETAIL) --}}
        <a href="{{ route('dashboard.detail.in', ['start_date' => $valStart, 'end_date' => $valEnd]) }}" class="kpi-card sc-blue">
            <div class="kpi-icon-wrapper"><i class="fa fa-download"></i></div>
            <div class="kpi-content">
                <div class="kpi-title">Barang Masuk</div>

                @if(!empty($kpiPeriodLabel))
                    <div class="kpi-period"><i class="fa fa-calendar"></i> {{ $kpiPeriodLabel }}</div>
                @endif

                <div class="kpi-value-row">
                    <div class="kpi-value">{{ number_format($lpbThisMonth, 0, ',', '.') }}</div>
                    <div class="kpi-unit">Item</div>
                </div>
            </div>
        </a>

        {{-- Stok Kritis (Link to Critical) --}}
        {{-- FIX: Navigasi ke stok kritis (< Min) --}}
        <a href="{{ route('buffer-alerts.index', ['buffer_level'=>'critical']) }}" class="kpi-card sc-red">
            <div class="kpi-icon-wrapper"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="kpi-content">
                <div class="kpi-title text-down">Stok Kritis</div>
                <div class="kpi-value-row">
                    <div class="kpi-value text-down">{{ $criticalCount }}</div>
                    <div class="kpi-unit text-down">Item</div>
                </div>
                <div class="kpi-trend text-down">
                    Di bawah Buffer
                </div>
            </div>
        </a>

        {{-- Stok Habis (Link to Empty) --}}
        {{-- FIX: Navigasi ke stok habis (0) menggunakan 'empty' agar sesuai Controller --}}
        <a href="{{ route('buffer-alerts.index', ['buffer_level'=>'empty', 'per_page' => 100]) }}" class="kpi-card sc-dark">
            <div class="kpi-icon-wrapper"><i class="fa fa-ban"></i></div>
            <div class="kpi-content">
                <div class="kpi-title">Stok Habis (0)</div>
                <div class="kpi-value-row">
                    <div class="kpi-value">{{ $emptyStock }}</div>
                    <div class="kpi-unit">Item</div>
                </div>
                <div class="kpi-trend text-muted-trend">
                    Segera Order
                </div>
            </div>
        </a>
    </div>

    {{-- 2. QUICK ACTIONS --}}
    <div class="quick-actions">
        <a href="{{ route('lpbs.create') }}" class="btn-quick">
            <div class="btn-quick-icon"><i class="fa fa-plus-circle"></i></div>
            <div class="btn-quick-info">
                <h5>Terima Barang</h5>
                <p>Input data dari Supplier</p>
            </div>
        </a>
        <a href="{{ route('bons.create') }}" class="btn-quick">
            <div class="btn-quick-icon"><i class="fa fa-paper-plane"></i></div>
            <div class="btn-quick-info">
                <h5>Minta Barang</h5>
                <p>Buat BON untuk Dept.</p>
            </div>
        </a>
        <a href="{{ route('stock-opnames.create') }}" class="btn-quick">
            <div class="btn-quick-icon"><i class="fa fa-balance-scale"></i></div>
            <div class="btn-quick-info">
                <h5>Stock Opname</h5>
                <p>Cek fisik vs sistem</p>
            </div>
        </a>
        <a href="{{ route('buffer-alerts.index') }}" class="btn-quick">
            <div class="btn-quick-icon"><i class="fa fa-search"></i></div>
            <div class="btn-quick-info">
                <h5>Cek Stok</h5>
                <p>Monitoring barang</p>
            </div>
        </a>
    </div>

    <div class="row-content">
        {{-- 3. CHART TREN --}}
        <div class="col-main">
            <div class="content-card">
                <div class="card-hd">
                    <h4 class="card-title"><i class="fa fa-line-chart text-primary"></i> Tren Pergerakan (6 Bulan)</h4>
                </div>
                <div class="card-bd" style="height: 320px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        {{-- 4. ACTION REQUIRED --}}
        <div class="col-side">
            <div class="content-card">
                <div class="card-hd">
                    <h4 class="card-title"><i class="fa fa-tasks" style="color:#f59e0b"></i> Prioritas Tugas</h4>
                </div>
                <div class="card-bd" style="padding-top:0;">
                    <ul class="action-list">
                        <li class="action-item">
                            <div class="action-left">
                                <div class="action-icon bg-orange-soft" style="color:#ea580c"><i class="fa fa-file-text-o"></i></div>
                                <div>
                                    <h5 style="font-weight:700">{{ $pendingBon }} BON Baru</h5>
                                    <p style="font-size:11px; color:#64748b">Menunggu persetujuan</p>
                                </div>
                            </div>
                            <a href="{{ route('bons.index', ['status' => 'PENDING']) }}" class="btn-action">Proses</a>
                        </li>
                        <li class="action-item">
                            <div class="action-left">
                                <div class="action-icon bg-blue-soft" style="color:#2563eb"><i class="fa fa-edit"></i></div>
                                <div>
                                    <h5 style="font-weight:700">{{ $draftSo }} Draft SO</h5>
                                    <p style="font-size:11px; color:#64748b">Belum diposting</p>
                                </div>
                            </div>
                            <a href="{{ route('stock-opnames.index') }}" class="btn-action">Lanjut</a>
                        </li>
                        <li class="action-item">
                            <div class="action-left">
                                <div class="action-icon bg-red-soft" style="color:#dc2626"><i class="fa fa-shopping-cart"></i></div>
                                <div>
                                    <h5 style="font-weight:700">{{ $totalRestockNeed }} Item Restock</h5>
                                    <p style="font-size:11px; color:#64748b">Kritis & Habis</p>
                                </div>
                            </div>
                            <a href="{{ route('buffer-alerts.index') }}" class="btn-action">Lihat</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row-content">
        {{-- 5. PIE CHART --}}
        <div class="col-side">
            <div class="content-card">
                <div class="card-hd">
                    <h4 class="card-title"><i class="fa fa-pie-chart" style="color:#8b5cf6"></i> Top 5 Dept. Boros</h4>
                </div>
                <div class="card-bd" style="height: 280px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>

        {{-- 6. RECENT TABLE --}}
        <div class="col-main">
            <div class="content-card">
                <div class="card-hd">
                    <h4 class="card-title"><i class="fa fa-clock-o" style="color:#64748b"></i> Aktivitas Terbaru</h4>
                    <a href="{{ route('bons.index') }}" style="font-size:12px; font-weight:600; text-decoration:none; color:#2563eb;">Lihat Semua</a>
                </div>
                <div class="card-bd" style="padding-top:0;">
                    <table class="table-recent">
                        @foreach($recentBon as $bon)
                        <tr>
                            <td width="100" style="font-weight:600; color:#334155">{{ $bon->date->format('d M Y') }}</td>
                            <td>
                                <span style="font-weight:700; color:#2563eb">{{ $bon->bon_number }}</span>
                                <span style="color:#cbd5e1; margin:0 6px;">|</span>
                                {{ $bon->department ? $bon->department->name : 'Umum' }}
                            </td>
                            <td class="text-right">
                                @if($bon->status == 'ISSUED')
                                    <span class="status-pill success"><span class="dot dot-success"></span> Selesai</span>
                                @elseif($bon->status == 'APPROVED')
                                    <span class="status-pill warning"><span class="dot dot-warning"></span> Disetujui</span>
                                @else
                                    <span class="status-pill warning" style="background:#fff7ed; color:#c2410c"><span class="dot dot-warning" style="background:#f97316"></span> Pending</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Script Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    // FIX: Inisialisasi Flatpickr
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr(".datepicker-flat", {
            altInput: true,      
            altFormat: "d/m/Y",  // FORMAT YANG DIMINTA: DAY/MONTH/YEAR
            dateFormat: "Y-m-d", 
            locale: "id",        
            allowInput: true     
        });
    });

    // CHART JS CONFIGURATION
    var ctxTrend = document.getElementById('trendChart').getContext('2d');
    var gradientIn = ctxTrend.createLinearGradient(0, 0, 0, 300);
    gradientIn.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
    gradientIn.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    var gradientOut = ctxTrend.createLinearGradient(0, 0, 0, 300);
    gradientOut.addColorStop(0, 'rgba(239, 68, 68, 0.2)');
    gradientOut.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthsLabels) !!},
            datasets: [
                { label: 'Barang Keluar (Item)', data: {!! json_encode($trendDataOut) !!}, borderColor: '#ef4444', backgroundColor: gradientOut, borderWidth: 2, pointRadius: 3, pointHoverRadius: 6, tension: 0.4, fill: true },
                { label: 'Barang Masuk (Item)', data: {!! json_encode($trendDataIn) !!}, borderColor: '#10b981', backgroundColor: gradientIn, borderWidth: 2, pointRadius: 3, pointHoverRadius: 6, tension: 0.4, fill: true }
            ]
        },
        options: { 
            responsive: true, maintainAspectRatio: false, 
            plugins: { legend: { position: 'bottom' } }, 
            scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#f1f5f9' } }, x: { grid: { display: false } } },
            interaction: { mode: 'index', intersect: false }
        }
    });

    var ctxPie = document.getElementById('pieChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($pieLabels) !!},
            datasets: [{
                data: {!! json_encode($pieData) !!},
                backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#8b5cf6'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: { 
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { boxWidth: 12, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var val = context && context.parsed !== undefined ? context.parsed : 0;
                            return val + ' item';
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
</script>
@endsection

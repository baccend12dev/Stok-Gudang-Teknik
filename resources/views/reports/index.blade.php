@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL PUSAT LAPORAN (REPORT HUB) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-bar-chart"></i>
        <strong>PUSAT DATA:</strong> Halaman ini adalah gerbang utama untuk mengakses seluruh arsip dan analisa pergerakan barang.
    </div>

    <h4 class="help-h"><i class="fa fa-question-circle text-primary"></i> Kapan Menggunakan Laporan Ini?</h4>
    <ul class="help-list">
        <li>
            <strong>Kartu Stok:</strong>
            Gunakan ini jika ada selisih stok saat Opname. Anda bisa melacak kronologis keluar-masuk satu barang spesifik untuk mencari tahu di transaksi mana selisih terjadi.
        </li>
        <li>
            <strong>Pemakaian Departemen:</strong>
            Gunakan ini untuk evaluasi budget. Anda bisa melihat departemen mana yang paling boros menggunakan barang tertentu dalam periode ini.
        </li>
        <li>
            <strong>Laporan Bulanan:</strong>
            Gunakan ini untuk <strong>Closing Bulanan</strong>. 
            <br><em>Tips: Tombol "Export Excel" di dalam laporan ini sudah diformat khusus (termasuk kolom Valuasi/Rupiah) untuk kebutuhan pelaporan ke Departemen Keuangan/Accounting.</em>
        </li>
    </ul>
@endsection

@section('content')
<style>
    /* Layout */
    body { background-color: #f5f5f5 !important; }
    .page-container { max-width: 100%; margin: 0 auto; padding: 24px 32px; box-sizing: border-box; }
    
    .dashboard-header { margin-bottom: 30px; }
    .dashboard-title { font-size: 24px; font-weight: 800; margin: 0; color: #111827; letter-spacing: -0.5px; }
    .dashboard-subtitle { font-size: 14px; color: #64748b; margin-top: 6px; }

    /* Report Grid */
    .report-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
    }

    /* Report Card Style */
    .report-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 28px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: #cbd5e1;
    }

    /* Icon Wrapper */
    .icon-wrapper {
        width: 64px;
        height: 64px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }
    
    .report-card:hover .icon-wrapper { transform: scale(1.1) rotate(5deg); }

    /* Typography */
    .report-title { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 10px; letter-spacing: -0.02em; }
    .report-desc { font-size: 14px; color: #64748b; line-height: 1.6; flex-grow: 1; }
    
    /* Arrow Action */
    .action-arrow {
        margin-top: 24px;
        font-size: 13px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: gap 0.2s;
    }
    
    .report-card:hover .action-arrow { gap: 12px; }
    
    /* Color Themes */
    /* 1. Stock Card (Blue) */
    .theme-blue .icon-wrapper { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
    .theme-blue .action-arrow { color: #2563eb; }
    .theme-blue:hover { border-top: 4px solid #2563eb; }
    
    /* 2. Dept Usage (Purple) */
    .theme-purple .icon-wrapper { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
    .theme-purple .action-arrow { color: #7c3aed; }
    .theme-purple:hover { border-top: 4px solid #7c3aed; }

    /* 3. Monthly Report (Green) */
    .theme-green .icon-wrapper { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .theme-green .action-arrow { color: #16a34a; }
    .theme-green:hover { border-top: 4px solid #16a34a; }

    /* 4. Movement Report (Orange) */
    .theme-orange .icon-wrapper { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
    .theme-orange .action-arrow { color: #ea580c; }
    .theme-orange:hover { border-top: 4px solid #ea580c; }

    /* 5. Trend Report (Indigo) */
    .theme-indigo .icon-wrapper { background: #eef2ff; color: #4f46e5; border: 1px solid #e0e7ff; }
    .theme-indigo .action-arrow { color: #4f46e5; }
    .theme-indigo:hover { border-top: 4px solid #4f46e5; }

</style>

<div class="page-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Pusat Laporan (Report Hub)</h1>
        <p class="dashboard-subtitle">Akses seluruh data riwayat, analisa pemakaian, dan rekapitulasi stok dalam satu tempat terintegrasi.</p>
    </div>

    <div class="report-grid">
        
        {{-- 1. KARTU STOK (AUDIT TRAIL) --}}
        <a href="{{ route('reports.stock-card') }}" class="report-card theme-blue">
            <div class="icon-wrapper"><i class="fa fa-history"></i></div>
            <div class="report-title">Kartu Stok</div>
            <div class="report-desc">
                Lihat detail kronologis pergerakan (Masuk & Keluar) per item barang. Digunakan untuk investigasi selisih stok dan audit transaksi harian.
            </div>
            <div class="action-arrow">Buka Laporan <i class="fa fa-arrow-right"></i></div>
        </a>

        {{-- 2. PEMAKAIAN DEPARTEMEN (ANALISA) --}}
        <a href="{{ route('reports.department-usage') }}" class="report-card theme-purple">
            <div class="icon-wrapper"><i class="fa fa-building-o"></i></div>
            <div class="report-title">Pemakaian Departemen</div>
            <div class="report-desc">
                Analisa pengeluaran barang berdasarkan departemen peminta. Ketahui pola konsumsi dan barang apa saja yang paling banyak digunakan oleh divisi tertentu.
            </div>
            <div class="action-arrow">Buka Laporan <i class="fa fa-arrow-right"></i></div>
        </a>

        {{-- 3. LAPORAN BULANAN (SHEET ALL) --}}
        <a href="{{ route('reports.inventory_monthly.index') }}" class="report-card theme-green">
            <div class="icon-wrapper"><i class="fa fa-table"></i></div>
            <div class="report-title">Laporan Bulanan (All)</div>
            <div class="report-desc">
                Rekapitulasi total stok Awal, Masuk, Keluar, dan Akhir seluruh barang dalam satu periode. Mendukung export Excel format khusus (Valuasi Rp) untuk Accounting.
            </div>
            <div class="action-arrow">Buka Laporan <i class="fa fa-arrow-right"></i></div>
        </a>

        {{-- 4. LAPORAN PERGERAKAN BARANG (FAST/SLOW) --}}
        <a href="{{ route('reports.movement') }}" class="report-card theme-orange">
            <div class="icon-wrapper"><i class="fa fa-sliders"></i></div>
            <div class="report-title">Laporan Pergerakan Barang</div>
            <div class="report-desc">
                Analisa klasifikasi perputaran barang (Fast, Slow, atau Normal Moving) berdasarkan volume pengeluaran barang dan batas threshold per barang.
            </div>
            <div class="action-arrow">Buka Laporan <i class="fa fa-arrow-right"></i></div>
        </a>

        {{-- 5. LAPORAN TREND PEMAKAIAN --}}
        <a href="{{ route('reports.usage-trend') }}" class="report-card theme-indigo">
            <div class="icon-wrapper"><i class="fa fa-line-chart"></i></div>
            <div class="report-title">Laporan Trend Pemakaian</div>
            <div class="report-desc">
                Analisa grafik tren pemakaian barang dari bulan ke bulan. Berguna untuk memantau fluktuasi konsumsi bulanan dan perencanaan kebutuhan stok (Procurement).
            </div>
            <div class="action-arrow">Buka Laporan <i class="fa fa-arrow-right"></i></div>
        </a>

    </div>
</div>
@endsection
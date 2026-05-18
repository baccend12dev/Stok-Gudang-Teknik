@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL MONITOR STOK (BUFFER ALERT) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-heartbeat"></i>
        <strong>FILOSOFI HALAMAN INI:</strong> Fitur ini berfungsi sebagai <em>Early Warning System</em> untuk menjaga <strong>Safety Stock (Stok Pengaman)</strong> di gudang, berbeda dengan halaman Rekap Kebutuhan di Request yang berfokus pada pemenuhan kebutuhan user.
    </div>

    <h4 class="help-h"><i class="fa fa-sort-amount-desc text-primary"></i> Logika 4 Zona Status</h4>
    <p class="help-p">Sistem mengkategorikan kesehatan stok berdasarkan <strong>Buffer Minimum</strong> yang Anda atur di Master Barang. Contoh simulasi jika Buffer Min = <strong>25</strong>:</p>
    
    <ul class="help-list">
        <li>
            <span class="badge" style="background:#1f2937; color:white;">KOSONG (Hitam)</span> : 
            <strong>Stok = 0</strong>. Barang habis total. Prioritas utama pengadaan.
        </li>
        <li>
            <span class="badge" style="background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;">KRITIS (Merah)</span> : 
            <strong>Stok 1 s/d 24</strong>. Stok ada, tapi sudah di bawah batas aman. Risiko <em>stockout</em> tinggi.
        </li>
        <li>
            <span class="badge" style="background:#fffbeb; color:#b45309; border:1px solid #fcd34d;">MENIPIS (Kuning)</span> : 
            <strong>Stok 25 s/d 30</strong>. Stok berada di area "pas-pasan" (Range: Buffer Min s/d Buffer Min + 5). Perlu waspada.
        </li>
        <li>
            <span class="badge" style="background:#ecfdf5; color:#15803d; border:1px solid #86efac;">AMAN (Hijau)</span> : 
            <strong>Stok 31 ke atas</strong>. Stok melimpah jauh di atas buffer. Tidak perlu tindakan.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-calculator text-primary"></i> Rumus Saran Order</h4>
    <p class="help-p">
        Angka di kolom "Saran Order" adalah jumlah <strong>MINIMAL</strong> yang harus dibeli untuk sekedar mengembalikan stok ke titik aman, bukan jumlah mutlak pembelian.
        <br><em>Tips: Jika item memiliki Buffer = 0, sistem akan menganggapnya selalu Aman kecuali stoknya benar-benar 0 (Kosong).</em>
    </p>

    <h4 class="help-h"><i class="fa fa-filter text-primary"></i> Navigasi Cepat</h4>
    <p class="help-p">
        Klik kartu warna-warni di atas tabel (Total/Kosong/Kritis/dll) untuk memfilter tabel secara instan agar Anda bisa fokus pada item yang bermasalah saja.
    </p>
@endsection

@section('content')
<style>
    /* Global Layout */
    body { background-color: #f5f5f5 !important; }
    .page-container { max-width: 100%; margin: 0 auto; padding: 0 24px 24px; box-sizing: border-box; }
    
    /* Header */
    .dashboard-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .dashboard-header-main { display: flex; align-items: center; gap: 12px; }
    
    /* Icon Lonceng Fix Center */
    .dashboard-title-icon { 
        width: 40px; height: 40px; 
        border-radius: 999px; 
        background: rgba(220, 38, 38, 0.1); 
        display: flex; align-items: center; justify-content: center; 
        color: #dc2626; 
        font-size: 18px; 
    }
    
    .dashboard-title { font-size: 20px; font-weight: 700; margin: 0; color: #111827; line-height: 1.2; }
    .dashboard-subtitle { font-size: 13px; color: #6b7280; margin-top: 2px; }

    /* KPI SUMMARY CARDS (5 Kolom) */
    .stat-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
        gap: 16px; 
        margin-bottom: 24px; 
    }
    .stat-card { 
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; 
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; 
        transition: all 0.2s ease; cursor: pointer; position: relative; overflow: hidden;
    }
    .stat-card:hover { transform: translateY(-2px); border-color: #2563eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    
    /* Active State */
    .stat-card.active { border-width: 2px; background: #fafafa; }
    .stat-card.active.sc-blue { border-color: #3b82f6; background: #eff6ff; }
    .stat-card.active.sc-dark { border-color: #374151; background: #f3f4f6; }
    .stat-card.active.sc-red { border-color: #ef4444; background: #fef2f2; }
    .stat-card.active.sc-yellow { border-color: #f59e0b; background: #fffbeb; }
    .stat-card.active.sc-green { border-color: #10b981; background: #ecfdf5; }
    
    .stat-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
    .stat-value { font-size: 26px; color: #0f172a; font-weight: 800; line-height: 1; }
    .stat-desc { font-size: 12px; color: #9ca3af; margin-top: 6px; font-weight: 500; }

    /* Card Left Border Colors & Special Fonts */
    .sc-blue { border-left: 4px solid #3b82f6; }
    .sc-blue .stat-value { color: #2563eb; } /* FIX: Angka Total jadi Biru */

    .sc-dark { border-left: 4px solid #374151; }
    .sc-dark .stat-label { color: #1f2937 !important; } /* FIX: Label Kosong jadi Hitam */
    .sc-dark .stat-value { color: #111827 !important; } /* FIX: Angka Kosong jadi Hitam */

    .sc-red { border-left: 4px solid #ef4444; }
    .sc-yellow { border-left: 4px solid #f59e0b; }
    .sc-green { border-left: 4px solid #10b981; }

    /* Filter Section */
    .section { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); }
    .section-bd { padding: 20px; }
    
    .filter-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 16px; }
    .filter-group { display: flex; flex-direction: column; gap: 6px; }
    .form-label { font-size: 12px; color: #4b5563; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 0.3px; }
    .form-control { height: 42px; border: 1px solid #d1d5db; border-radius: 8px; padding: 4px 12px; font-size: 14px; transition: border-color 0.15s; }
    .form-control:focus { border-color: #2563eb; outline: none; }
    
    /* Buttons */
    .btn-primary { background: #2563eb; color: #fff; border: 1px solid #2563eb; border-radius: 8px; padding: 0 24px; height: 42px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s; }
    .btn-primary:hover { background: #1d4ed8; }
    
    .btn-light { background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 24px; height: 42px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; text-decoration: none !important; cursor: pointer; transition: background 0.2s; }
    .btn-light:hover { background: #f9fafb; border-color: #9ca3af; text-decoration: none !important; }
    
    .btn-success { background: #10b981; color: #fff; border: 1px solid #10b981; border-radius: 8px; padding: 0 20px; height: 38px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; text-decoration: none !important; transition: background 0.2s; }
    .btn-success:hover { background: #059669; text-decoration: none !important; }

    /* Table */
    .table-wrapper { border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; table-layout: fixed; }
    
    .table thead th { 
        background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700; 
        padding: 16px 16px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; 
        white-space: nowrap; vertical-align: middle; position: sticky; top: 0; z-index: 10; letter-spacing: 0.5px;
    }
    .table tbody td { 
        padding: 14px 16px; font-size: 14px; color: #1e293b; 
        border-bottom: 1px solid #f1f5f9; vertical-align: middle; 
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .table tbody tr:hover { background: #f8fafc; }
    .table tbody tr:last-child td { border-bottom: none; }
    
    /* Column Widths */
    .col-code { width: 120px; text-align: center; }
    .col-name { width: auto; white-space: normal !important; line-height: 1.4; }
    .col-loc  { width: 200px; }
    .col-unit { width: 150px; text-align: center; }
    .col-buf  { width: 150px; text-align: center; }
    .col-stok { width: 150px; text-align: center; }
    .col-stat { width: 150px; text-align: center; }
    .col-saran{ width: 150px; text-align: center; }

    /* Badges & Highlights */
    .badge { padding: 5px 12px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; letter-spacing: 0.5px; }
    
    /* FIX BADGE KOSONG: Abu Transparan Soft */
    .badge-dark-soft { 
        background: #e2e8f0; /* Slate 200 */
        color: #475569;      /* Slate 600 */
        border: 1px solid #cbd5e1; 
    } 
    
    .badge-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
    .badge-warning { background: #fffbeb; color: #b45309; border: 1px solid #fcd34d; }
    .badge-success { background: #ecfdf5; color: #15803d; border: 1px solid #86efac; }
    
    .suggestion-box { background: #f0fdf4; color: #15803d; font-weight: 700; padding: 4px 12px; border-radius: 99px; display: inline-block; border: 1px solid #bbf7d0; font-size: 12px; }
    
    /* Row Highlights */
    .row-critical { background-color: #fff1f2; } 
    .text-red { color: #dc2626 !important; }

    /* Pagination */
    .pagination-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #64748b; }
    .pagination { display: inline-flex; margin: 0; gap: 4px; }
    .pagination > li > a, .pagination > li > span { border-radius: 8px; border: 1px solid #e2e8f0; color: #475467; background: #fff; padding: 8px 14px; text-decoration: none; }
    .pagination > .active > span { background-color: #2563eb; border-color: #2563eb; color: #fff; }
</style>

<div class="page-container">

    {{-- Header --}}
    <div class="dashboard-header">
        <div class="dashboard-header-main">
            <div class="dashboard-title-icon"><i class="fa fa-bell"></i></div>
            <div>
                <h1 class="dashboard-title">Monitor Stok & Peringatan</h1>
                <p class="dashboard-subtitle">Dashboard kesehatan stok real-time.</p>
            </div>
        </div>
        @if($items->count() > 0)
        {{-- FIX: Teks Tombol Dinamis berdasarkan Filter --}}
        @php
            $btnText = 'Download Rencana Pembelian';
            if ($level === 'empty') $btnText .= ' (Stok Kosong)';
            elseif ($level === 'critical') $btnText .= ' (Stok Kritis)';
            elseif ($level === 'warning') $btnText .= ' (Stok Menipis)';
            elseif ($level === 'safe') $btnText .= ' (Stok Aman)';
        @endphp
        <a href="{{ route('buffer-alerts.export.excel', ['buffer_level' => $level, 'search' => $search]) }}" class="btn-success">
            <i class="fa fa-file-excel-o"></i> {{ $btnText }}
        </a>
        @endif
    </div>

    {{-- KPI CARDS (5 KOLOM) --}}
    <div class="stat-grid">
        <div class="stat-card sc-blue {{ $level == 'all' ? 'active' : '' }}" onclick="applyFilter('all')">
            <div class="stat-label">Total Item Aktif</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-desc">Seluruh barang</div>
        </div>
        
        {{-- Card Kosong: Label & Value Hitam --}}
        <div class="stat-card sc-dark {{ $level == 'empty' ? 'active' : '' }}" onclick="applyFilter('empty')">
            <div class="stat-label">Stok Kosong (0)</div>
            <div class="stat-value">{{ $stats['empty'] }}</div>
            <div class="stat-desc">Barang habis total</div>
        </div>

        {{-- Card Merah: Kritis --}}
        <div class="stat-card sc-red {{ $level == 'critical' ? 'active' : '' }}" onclick="applyFilter('critical')">
            <div class="stat-label text-red" style="color:#dc2626">Stok Kritis</div>
            <div class="stat-value text-red" style="color:#dc2626">{{ $stats['critical'] }}</div>
            <div class="stat-desc">Di bawah buffer</div>
        </div>
        
        <div class="stat-card sc-yellow {{ $level == 'warning' ? 'active' : '' }}" onclick="applyFilter('warning')">
            <div class="stat-label" style="color:#d97706">Stok Menipis</div>
            <div class="stat-value" style="color:#d97706">{{ $stats['warning'] }}</div>
            <div class="stat-desc">Mendekati batas</div>
        </div>
        
        <div class="stat-card sc-green {{ $level == 'safe' ? 'active' : '' }}" onclick="applyFilter('safe')">
            <div class="stat-label" style="color:#16a34a">Stok Aman</div>
            <div class="stat-value" style="color:#16a34a">{{ $stats['safe'] }}</div>
            <div class="stat-desc">Tersedia</div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="section">
        <div class="section-bd">
            <form id="filterForm" method="GET" action="{{ route('buffer-alerts.index') }}">
                <input type="hidden" name="buffer_level" id="buffer_level" value="{{ $level }}">

                <div class="filter-row">
                    <div class="filter-group" style="flex-grow:1;">
                        <label class="form-label">Cari Barang</label>
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari Kode atau Nama Barang...">
                    </div>
                    <div class="filter-group" style="width:100px;">
                        <label class="form-label">Tampil</label>
                        <select name="per_page" class="form-control">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">&nbsp;</label>
                        <div style="display:flex; gap:8px;">
                            <button type="submit" class="btn-primary"><i class="fa fa-search"></i> Cari</button>
                            <a href="{{ route('buffer-alerts.index') }}" class="btn-light">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th class="col-code">Kode</th>
                    <th class="col-name">Nama Barang</th>
                    <th class="col-loc">Lokasi</th>
                    <th class="col-unit">Satuan</th>
                    <th class="col-buf">Buffer Min</th>
                    <th class="col-stok">Stok Fisik</th>
                    <th class="col-stat">Status</th>
                    <th class="col-saran" style="background:#f0fdf4; color:#15803d; border-bottom:2px solid #16a34a;">Saran Order</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $it)
                <tr class="{{ $it->status_label == 'KOSONG' ? 'row-critical' : ($it->status_label == 'KRITIS' ? 'row-critical' : '') }}">
                    <td style="text-align:center; font-weight:600; color:#4b5563;">{{ $it->code }}</td>
                    <td style="font-weight:500;">{{ $it->name }}</td>
                    <td><i class="fa fa-map-marker text-muted" style="font-size:10px; margin-right:4px;"></i>{{ $it->location ?: '-' }}</td>
                    <td style="text-align:center; color:#64748b;">{{ $it->unit }}</td>
                    {{-- FIX: Tampilkan desimal --}}
                    <td style="text-align:center; color:#64748b;">{{ (float)$it->buffer_min }}</td>
                    
                    <td style="text-align:center; font-weight:800; font-size:14px; {{ $it->current_stock <= $it->buffer_min ? 'color:#dc2626;' : 'color:#059669;' }}">
                        {{-- FIX: Tampilkan desimal --}}
                        {{ (float)$it->current_stock }}
                    </td>

                    <td style="text-align:center;">
                        <span class="badge {{ $it->status_class }}">{{ $it->status_label }}</span>
                    </td>

                    <td style="text-align:center; background:rgba(240, 253, 244, 0.5);">
                        @if($it->suggested_qty > 0)
                            {{-- FIX: Tampilkan desimal --}}
                            <span class="suggestion-box">+ {{ (float)$it->suggested_qty }}</span>
                        @else
                            <span class="text-muted" style="font-size:10px;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:40px; color:#6b7280;">
                        <i class="fa fa-inbox" style="font-size:32px; color:#cbd5e1; margin-bottom:10px; display:block;"></i>
                        <div><b>Data Tidak Ditemukan</b></div>
                        <div style="font-size:12px;">Tidak ada item yang sesuai dengan kriteria filter ini.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper">
        <div>
            Menampilkan <strong>{{ $items->firstItem() }}</strong> sampai <strong>{{ $items->lastItem() }}</strong> dari <strong>{{ $items->total() }}</strong> data
        </div>
        <div>{{ $items->links() }}</div>
    </div>

</div>

<script>
    // Script untuk menjadikan Card sebagai tombol filter
    function applyFilter(type) {
        document.getElementById('buffer_level').value = type;
        document.getElementById('filterForm').submit();
    }
</script>
@endsection
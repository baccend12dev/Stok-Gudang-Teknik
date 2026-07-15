@extends('layouts.app')

@section('help-content')
    <div class="help-alert">
        <i class="fa fa-line-chart"></i>
        <strong>LAPORAN TREN PEMAKAIAN:</strong> Halaman ini membantu menganalisa volume pengeluaran barang (Pemakaian/Outflow) dari bulan ke bulan dalam satu tahun.
    </div>

    <h4 class="help-h"><i class="fa fa-info-circle text-primary"></i> Indikator & Grafik</h4>
    <ul class="help-list">
        <li><strong>Grafik Tren:</strong> Menunjukkan fluktuasi pemakaian bulanan secara visual. Garis tebal ungu mewakili total pemakaian keseluruhan, sementara garis tipis mewakili top 5 item paling boros.</li>
        <li><strong>Rata-rata/Bulan:</strong> Digunakan sebagai acuan perencanaan pembelian (Procurement Planning) agar menghindari penumpukan stok berlebih (Overstock) maupun kehabisan stok (Stockout).</li>
    </ul>
@endsection

@section('content')

{{-- Load Select2 CSS --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<style>
    body { background-color: #f5f5f5 !important; }
    .page-container { max-width: 100%; margin: 0 auto; padding: 0 24px 24px; box-sizing: border-box; }
    .dashboard-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .dashboard-title { font-size: 20px; font-weight: 700; margin: 0; color: #111827; }

    .section { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); padding: 16px; }
    
    .filter-box { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 150px; }
    .filter-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0; }
    
    .form-control-sm { height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; width: 100%; }

    .select2-container { width: 100% !important; display: block; }
    .select2-container .select2-selection--single { 
        height: 38px !important; 
        border: 1px solid #d1d5db !important; 
        border-radius: 8px !important; 
        padding: 4px 0; 
        background-color: #fff;
        position: relative; 
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered { 
        line-height: 28px !important; 
        padding-left: 12px; 
        padding-right: 35px; 
        font-size: 13px; 
        color: #374151; 
    }
    
    .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        top: 50% !important;
        right: 30px !important;
        transform: translateY(-50%);
        width: auto !important;
        height: auto !important;
        line-height: 1 !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        text-align: center;
        color: #9ca3af !important;
        font-size: 18px !important;
        font-weight: bold;
        cursor: pointer;
        z-index: 10; 
    }
    .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #dc2626 !important; 
        background: transparent !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow { 
        position: absolute !important;
        height: 36px !important; 
        right: 8px !important; 
        top: 1px !important;
    }

    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }

    .btn-filter { 
        height: 38px; padding: 0 20px; background: #2563eb; color: #fff; border: none; border-radius: 8px; 
        font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; 
        width: auto; flex: none;
    }
    .btn-filter:hover { background: #1d4ed8; }
    
    .btn-export { 
        height: 38px; padding: 0 20px; background: #10b981; color: #fff; border: none; border-radius: 8px; 
        font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; 
    }
    .btn-export:hover { background: #059669; }

    .btn-back {
        height: 38px; padding: 0 16px; background: #fff; border: 1px solid #d1d5db; 
        border-radius: 8px; color: #374151; font-size: 13px; font-weight: 600; 
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-back:hover { background: #f3f4f6; color: #111827; }

    /* Summary Cards */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px; }
    .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
    .stat-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
    .stat-value { font-size: 18px; color: #0f172a; font-weight: 800; margin-top: auto; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .sc-indigo { border-left: 4px solid #6366f1; }
    .sc-emerald { border-left: 4px solid #10b981; }
    .sc-amber { border-left: 4px solid #f59e0b; }

    .table-container { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; overflow-x: auto; margin-top: 16px; }
    .table { width: 100%; border-collapse: collapse; font-size: 12px; min-width: 1000px; }
    .table th { background: #f9fafb; padding: 12px 10px; text-align: left; font-weight: 700; color: #4b5563; border-bottom: 1px solid #e5e7eb; }
    .table td { padding: 10px; border-bottom: 1px solid #e5e7eb; color: #374151; vertical-align: middle; }
    .table tr:last-child td { border-bottom: none; }
    .table tr:hover td { background-color: #f8fafc; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }

    /* Chart Card */
    .chart-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
    .chart-container { position: relative; height: 320px; width: 100%; }
</style>

<div class="page-container">
    
    {{-- HEADER --}}
    <div class="dashboard-header">
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="{{ route('reports.index') }}" class="btn-back" title="Kembali ke Pusat Laporan">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
            <h1 class="dashboard-title">Laporan Trend Pemakaian</h1>
        </div>
        <div>
            <a href="{{ route('reports.usage-trend.export', ['year' => $year, 'item_id' => $itemId, 'category_id' => $categoryId, 'department_id' => $deptId]) }}" class="btn-export">
                <i class="fa fa-file-excel-o"></i> Export Excel
            </a>
        </div>
    </div>

    {{-- FILTER BOX --}}
    <div class="section">
        <form action="{{ route('reports.usage-trend') }}" method="GET" class="filter-box">
            
            <div class="filter-item" style="max-width: 100px; min-width: 80px;">
                <label class="filter-label">Tahun</label>
                <select name="year" class="form-control-sm" style="padding: 0 8px;">
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="filter-item">
                <label class="filter-label">Kategori</label>
                <select name="category_id" class="form-control-sm select2-filter" data-placeholder="-- Semua Kategori --">
                    <option value=""></option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-item">
                <label class="filter-label">Departemen Pemakai</label>
                <select name="department_id" class="form-control-sm select2-filter" data-placeholder="-- Semua Departemen --">
                    <option value=""></option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-item" style="flex: 1.5; min-width: 200px;">
                <label class="filter-label">Cari Barang</label>
                <select name="item_id" class="form-control-sm select2-filter" data-placeholder="-- Semua Barang --">
                    <option value=""></option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}" {{ $itemId == $it->id ? 'selected' : '' }}>
                            [{{ $it->code }}] {{ $it->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="dash-filter-actions">
                <button type="submit" class="btn-filter">
                    <i class="fa fa-filter"></i> Terapkan
                </button>
                <a href="{{ route('reports.usage-trend') }}" class="btn-back" style="height:38px;">
                    Reset
                </a>
            </div>

        </form>
    </div>

    {{-- STATS CARDS --}}
    <div class="stat-grid">
        <div class="stat-card sc-indigo">
            <span class="stat-label">Total Pengambilan ({{ $year }})</span>
            <span class="stat-value">{{ number_format($totalAnnualQty, 2) }} unit</span>
        </div>
        <div class="stat-card sc-emerald">
            <span class="stat-label">Barang Paling Banyak Dipakai</span>
            <span class="stat-value" title="{{ $topItemName }}">{{ $topItemName }}</span>
        </div>
        <div class="stat-card sc-amber">
            <span class="stat-label">Bulan Konsumsi Tertinggi</span>
            <span class="stat-value">{{ $peakMonth }}</span>
        </div>
    </div>

    {{-- CHART CARD --}}
    <div class="chart-card">
        <div style="font-weight: 700; font-size: 14px; margin-bottom: 12px; color: #1f2937; display:flex; align-items:center; gap:8px;">
            <i class="fa fa-line-chart text-indigo"></i> Grafik Tren Pengeluaran Bulanan ({{ $year }})
        </div>
        <div class="chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    {{-- DATA TABLE CARD --}}
    <div class="section" style="padding:0; overflow:hidden;">
        <div style="padding: 16px 20px; font-weight:700; font-size:14px; border-bottom:1px solid #e5e7eb; color:#1f2937; display:flex; justify-content:space-between; align-items:center;">
            <span>Matriks Pemakaian Barang Bulanan</span>
            <span style="font-weight:400; font-size:12px; color:#6b7280;">Diurutkan dari pemakaian tertinggi</span>
        </div>
        <div class="table-container" style="margin-top:0; border:none; border-radius:0;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th style="width: 120px;">Kode</th>
                        <th style="min-width: 200px;">Nama Barang</th>
                        <th style="width: 80px;">Satuan</th>
                        @php
                            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                        @endphp
                        @foreach($months as $m)
                            <th class="text-right" style="width: 55px;">{{ $m }}</th>
                        @endforeach
                        <th class="text-right" style="width: 80px; background:#f0fdf4; font-weight:800; color:#15803d;">Total</th>
                        <th class="text-right" style="width: 80px; background:#f8fafc; font-weight:800; color:#475569;">Rerata/Bln</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trendData as $idx => $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td style="font-family:monospace; font-weight:600;">{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['unit'] }}</td>
                            @foreach($row['months'] as $mIdx => $val)
                                <td class="text-right" style="color: {{ $val > 0 ? '#0f172a' : '#9ca3af' }}; font-weight: {{ $val > 0 ? '500' : '400' }};">
                                    {{ $val > 0 ? number_format($val, 2) : '-' }}
                                </td>
                            @endforeach
                            <td class="text-right" style="background:#f0fdf4; font-weight:700; color:#16a34a;">
                                {{ number_format($row['total'], 2) }}
                            </td>
                            <td class="text-right" style="background:#f8fafc; font-weight:600; color:#475569;">
                                {{ number_format($row['total'] / 12, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="text-center" style="padding: 40px; color:#6b7280;">
                                <i class="fa fa-info-circle" style="font-size:24px; margin-bottom:8px; display:block;"></i>
                                Tidak ada data pergerakan keluar (pemakaian) pada periode filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 filter
        $('.select2-filter').select2({
            allowClear: true,
            width: '100%'
        });

        // Initialize Chart
        const ctx = document.getElementById('trendChart').getContext('2d');
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        
        const datasets = {!! json_encode($chartDatasets) !!};
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11, family: 'Inter, sans-serif' }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, family: 'Inter, sans-serif' } }
                    },
                    y: {
                        grid: { color: '#f3f4f6' },
                        ticks: { font: { size: 11, family: 'Inter, sans-serif' } },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endsection

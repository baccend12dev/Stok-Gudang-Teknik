@extends('layouts.app')

@section('content')
<style>
    body { background-color: #f5f5f5 !important; }
    .page-container { max-width: 100%; margin: 0 auto; padding: 0 24px 24px; box-sizing: border-box; }
    .dashboard-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .dashboard-title { font-size: 20px; font-weight: 700; margin: 0; color: #111827; }

    /* Summary Stats Cards */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
    .stat-card { 
        background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; 
        transition: transform 0.2s; cursor: pointer;
    }
    .stat-card:hover { transform: translateY(-2px); border-color: #2563eb; }
    .stat-card.active { border: 2px solid #2563eb; background: #eff6ff; }

    .stat-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
    .stat-value { font-size: 24px; color: #0f172a; font-weight: 800; }
    .stat-desc { font-size: 12px; color: #9ca3af; margin-top: 4px; }

    .sc-blue { border-left: 4px solid #3b82f6; }
    .sc-red { border-left: 4px solid #ef4444; }
    .sc-yellow { border-left: 4px solid #f59e0b; }
    .sc-green { border-left: 4px solid #10b981; }

    /* Section & Toolbar */
    .section-hd { 
        background: #fff; border: 1px solid #e5e7eb; border-radius: 10px 10px 0 0; padding: 12px 16px; 
        display: flex; justify-content: space-between; align-items: center;
    }
    .search-box { width: 250px; height: 36px; border: 1px solid #d1d5db; border-radius: 6px; padding: 0 12px; font-size: 13px; }

    /* Table Styling */
    .table-container { border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px; background: #fff; overflow: hidden; }
    .table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .table th { background: #f8fafc; padding: 12px 16px; text-align: left; font-weight: 700; color: #475467; border-bottom: 2px solid #e2e8f0; position: sticky; top: 0; z-index: 10; }
    .table td { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }

    /* Row Highlighting */
    .row-critical { background-color: #fef2f2; }
    .row-critical td { color: #991b1b; }
    .row-warning { background-color: #fffbeb; }
    
    /* Badges */
    .badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; display: inline-block; }
    .bg-red { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .bg-yellow { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .bg-green { background: #dcfce7; color: #166534; border: 1px solid #86efac; }

    .text-right { text-align: right; }
    .text-center { text-align: center; }
    
    .btn-export { height: 38px; padding: 0 20px; background: #10b981; color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-export:hover { background: #059669; }
    
    .btn-detail { padding: 4px 10px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; color: #374151; font-size: 11px; text-decoration: none; font-weight: 600; transition: all 0.2s; }
    .btn-detail:hover { background: #f3f4f6; border-color: #2563eb; color: #2563eb; }
</style>

<div class="page-container">
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Monitor Stok (Real-time)</h1>
            <p style="font-size:12px; color:#64748b; margin-top:4px;">Status stok barang per hari ini: <strong>{{ date('d M Y') }}</strong></p>
        </div>
        <div>
             <a href="{{ route('reports.saldo.export') }}" class="btn-export">
                <i class="fa fa-file-excel-o"></i> Export Excel
             </a>
        </div>
    </div>

    {{-- HEAD-UP DISPLAY (SUMMARY CARDS) --}}
    <div class="stat-grid">
        <div class="stat-card sc-blue active filter-trigger" data-filter="all">
            <div class="stat-label">Total Item</div>
            <div class="stat-value">{{ $totalItems }}</div>
            <div class="stat-desc">Semua barang aktif</div>
        </div>
        <div class="stat-card sc-red filter-trigger" data-filter="critical">
            <div class="stat-label text-red">Stok Kritis (Habis)</div>
            <div class="stat-value text-red">{{ $critical }}</div>
            <div class="stat-desc">Perlu restock segera!</div>
        </div>
        <div class="stat-card sc-yellow filter-trigger" data-filter="warning">
            <div class="stat-label" style="color:#d97706">Stok Menipis</div>
            <div class="stat-value" style="color:#d97706">{{ $warning }}</div>
            <div class="stat-desc">Mendekati buffer limit</div>
        </div>
        <div class="stat-card sc-green filter-trigger" data-filter="safe">
            <div class="stat-label text-green">Stok Aman</div>
            <div class="stat-value text-green">{{ $safe }}</div>
            <div class="stat-desc">Ketersediaan terjaga</div>
        </div>
    </div>

    {{-- TABLE SECTION --}}
    <div class="section-hd">
        <div style="font-weight:700; font-size:14px; color:#374151;">
            <i class="fa fa-list"></i> Daftar Persediaan
        </div>
        <input type="text" id="tableSearch" class="search-box" placeholder="Cari nama / kode barang...">
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th width="50" class="text-center">No</th>
                    <th width="100">Kode</th>
                    <th>Nama Barang</th>
                    <th width="150">Lokasi</th>
                    <th width="80" class="text-center">Sat</th>
                    <th width="100" class="text-center">Buffer</th>
                    <th width="100" class="text-right">Stok Fisik</th>
                    <th width="120" class="text-center">Status</th>
                    <th width="120" class="text-center">Saran Restock</th>
                    <th width="80" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="stockBody">
                @foreach($items as $index => $it)
                @php
                    $rowClass = '';
                    if($it->status_label == 'CRITICAL') $rowClass = 'row-critical';
                    elseif($it->status_label == 'WARNING') $rowClass = 'row-warning';
                @endphp
                <tr class="item-row {{ $rowClass }}" data-status="{{ strtolower($it->status_label) }}">
                    <td class="text-center" style="color:#64748b">{{ $index + 1 }}</td>
                    <td style="font-weight:700;">{{ $it->code }}</td>
                    <td>{{ $it->name }}</td>
                    <td><i class="fa fa-map-marker" style="color:#9ca3af"></i> {{ $it->location ?: '-' }}</td>
                    <td class="text-center text-muted">{{ $it->unit }}</td>
                    <td class="text-center text-muted">{{ (int)$it->buffer_min }}</td>
                    
                    <td class="text-right" style="font-weight:800; font-size:14px;">
                        {{ (int)$it->current_stock }}
                    </td>

                    <td class="text-center">
                        @if($it->status_label == 'CRITICAL') 
                            <span class="badge bg-red">KRITIS</span>
                        @elseif($it->status_label == 'WARNING') 
                            <span class="badge bg-yellow">MENIPIS</span>
                        @else 
                            <span class="badge bg-green">AMAN</span>
                        @endif
                    </td>

                    <td class="text-center">
                        @if($it->restock_qty > 0)
                            <span style="color:#dc2626; font-weight:700;">+{{ (int)$it->restock_qty }}</span>
                        @else
                            <span style="color:#cbd5e1">-</span>
                        @endif
                    </td>

                    <td class="text-center">
                        <a href="{{ route('reports.stock-card', ['item_id' => $it->id]) }}" class="btn-detail" title="Lihat Kartu Stok">
                            <i class="fa fa-history"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script>
    jQuery(document).ready(function($) {
        // Smart Filter via Summary Cards
        $('.filter-trigger').click(function() {
            // Visual Active State
            $('.filter-trigger').removeClass('active');
            $(this).addClass('active');

            var filter = $(this).data('filter');
            var rows = $('#stockBody tr.item-row');

            if (filter === 'all') {
                rows.show();
            } else {
                rows.hide();
                rows.filter('[data-status="' + filter + '"]').show();
            }
        });

        // Search Filter
        $('#tableSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $("#stockBody tr.item-row").filter(function() {
                var text = $(this).find('td:eq(1)').text().toLowerCase() + ' ' + $(this).find('td:eq(2)').text().toLowerCase();
                $(this).toggle(text.indexOf(value) > -1)
            });
        });
    });
</script>
@endsection
@extends('layouts.app')

@section('content')
<style>
    body { background-color: #f5f5f5 !important; }
    .page-container { max-width: 100%; margin: 0 auto; padding: 0 24px 24px; box-sizing: border-box; }
    .dashboard-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .dashboard-title { font-size: 20px; font-weight: 700; margin: 0; color: #111827; }

    /* Filter Card */
    .filter-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group label {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
    }
    .form-control {
        height: 38px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 0 12px;
        font-size: 13px;
        color: #1e293b;
        background-color: #fff;
        min-width: 180px;
    }
    .form-control:focus {
        border-color: #3b82f6;
        outline: none;
    }
    
    .btn-submit {
        height: 38px;
        padding: 0 20px;
        background: #3b82f6;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
    }
    .btn-submit:hover {
        background: #2563eb;
    }

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

    /* Badges */
    .badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; display: inline-block; text-align: center; }
    .bg-red { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .bg-yellow { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .bg-green { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .bg-blue { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

    .text-right { text-align: right; }
    .text-center { text-align: center; }
    
    .btn-export { height: 38px; padding: 0 20px; background: #10b981; color: #fff; border: none; border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-export:hover { background: #059669; }
</style>

<div class="page-container">
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Laporan Pergerakan Barang (Fast / Slow)</h1>
            <p style="font-size:12px; color:#64748b; margin-top:4px;">Analisa keaktifan barang berdasarkan threshold Fast/Slow Moving</p>
        </div>
        <div>
             <a href="{{ route('reports.movement.export', ['start_date' => $start, 'end_date' => $end, 'classification' => $selectedClass]) }}" class="btn-export">
                <i class="fa fa-file-excel-o"></i> Export Excel
             </a>
        </div>
    </div>

    {{-- FILTER FORM --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('reports.movement') }}" class="filter-form">
            <div class="form-group">
                <label>Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $start }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Tanggal Selesai</label>
                <input type="date" name="end_date" value="{{ $end }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Klasifikasi</label>
                <select name="classification" class="form-control">
                    <option value="ALL" {{ $selectedClass == 'ALL' ? 'selected' : '' }}>Semua Klasifikasi</option>
                    <option value="FAST" {{ $selectedClass == 'FAST' ? 'selected' : '' }}>FAST MOVING</option>
                    <option value="SLOW" {{ $selectedClass == 'SLOW' ? 'selected' : '' }}>SLOW MOVING</option>
                    <option value="NORMAL" {{ $selectedClass == 'NORMAL' ? 'selected' : '' }}>NORMAL MOVING</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa fa-filter"></i> Filter
            </button>
        </form>
    </div>

    {{-- TABLE SECTION --}}
    <div class="section-hd">
        <div style="font-weight:700; font-size:14px; color:#374151;">
            <i class="fa fa-list"></i> Hasil Klasifikasi Pergerakan Barang
        </div>
        <input type="text" id="tableSearch" class="search-box" placeholder="Cari nama / kode barang...">
    </div>

    <div class="table-container">
        <table class="table" id="movementTable">
            <thead>
                <tr>
                    <th width="50" class="text-center">No</th>
                    <th width="120">Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th width="80" class="text-center">Satuan</th>
                    <th width="100" class="text-center">Batas Fast</th>
                    <th width="100" class="text-center">Batas Slow</th>
                    <th width="130" class="text-right">Total Keluar (Periode)</th>
                    <th width="150" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filteredItems as $index => $it)
                <tr class="item-row">
                    <td class="text-center" style="color:#64748b">{{ $index + 1 }}</td>
                    <td style="font-weight:700;">{{ $it->code }}</td>
                    <td>{{ $it->name }}</td>
                    <td>{{ $it->category ? $it->category->name : '-' }}</td>
                    <td class="text-center text-muted">{{ $it->unit }}</td>
                    <td class="text-center text-muted">{{ (int)$it->batas_fast_moving }}</td>
                    <td class="text-center text-muted">{{ (int)$it->batas_slow_moving }}</td>
                    <td class="text-right" style="font-weight:700;">{{ (float)$it->total_out }}</td>
                    <td class="text-center">
                        @if($it->classification == 'FAST')
                            <span class="badge bg-green">FAST MOVING</span>
                        @elseif($it->classification == 'SLOW')
                            <span class="badge bg-red">SLOW MOVING</span>
                        @else
                            <span class="badge bg-blue">NORMAL MOVING</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted" style="padding: 24px;">Tidak ada data barang untuk kriteria filter ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('tableSearch').addEventListener('keyup', function() {
        var filter = this.value.toLowerCase();
        var rows = document.querySelectorAll('#movementTable tbody tr.item-row');

        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
@endsection

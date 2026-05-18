@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL KATALOG BARANG === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>REFERENSI BARANG:</strong> Halaman ini menampilkan daftar seluruh barang yang tersedia di sistem secara <em>real-time</em>. Gunakan fitur pencarian untuk mencari kode atau nama barang yang Anda butuhkan sebelum membuat Request.
    </div>

    <h4 class="help-h"><i class="fa fa-search text-primary"></i> Cara Menggunakan</h4>
    <ul class="help-list">
        <li>
            <strong>Kolom Kode:</strong> Kode identifikasi barang internal perusahaan.
        </li>
        <li>
            <strong>Kolom Nama Barang:</strong> Deskripsi lengkap nama barang.
        </li>
        <li>
            <strong>Kolom Unit:</strong> Satuan barang (PCS, PAK, RIM, dll).
        </li>
        <li>
            <strong>Tips:</strong> Gunakan kolom pencarian di atas tabel untuk menemukan barang dengan cepat. Anda bisa mencari berdasarkan Kode maupun Nama.
        </li>
    </ul>
@endsection

@section('content')
<style>
    /* ====== CATALOG PAGE STYLING ====== */
    .cat-container { max-width: 100%; margin: 0 auto; padding: 0 0 24px; box-sizing: border-box; }

    /* Header */
    .cat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .cat-header-main { display: flex; align-items: center; gap: 12px; }
    .cat-header-icon {
        width: 44px; height: 44px; border-radius: 12px;
        background: #eff6ff; display: flex; align-items: center; justify-content: center;
        color: #2563eb; font-size: 18px; border: 1px solid #dbeafe;
    }
    .cat-title { font-size: 20px; font-weight: 700; margin: 0; color: #111827; line-height: 1.2; }
    .cat-subtitle { font-size: 13px; color: #6b7280; margin-top: 2px; }

    /* KPI Card */
    .cat-kpi {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 14px 20px; display: flex; align-items: center; gap: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .cat-kpi-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: #eff6ff; display: flex; align-items: center; justify-content: center;
        color: #2563eb; font-size: 16px;
    }
    .cat-kpi-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .cat-kpi-value { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1; margin-top: 3px; }

    /* Search Section */
    .cat-section { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); }
    .cat-section-bd { padding: 20px; }

    .cat-filter-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; }
    .cat-filter-group { display: flex; flex-direction: column; gap: 6px; }
    .cat-form-label { font-size: 12px; color: #4b5563; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 0.3px; }
    .cat-form-control {
        height: 42px; border: 1px solid #d1d5db; border-radius: 8px;
        padding: 4px 12px; font-size: 14px; transition: border-color 0.15s;
    }
    .cat-form-control:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

    select.cat-form-control {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding-right: 36px;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 10px center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
        cursor: pointer;
    }

    .cat-btn-primary {
        background: #2563eb; color: #fff; border: 1px solid #2563eb;
        border-radius: 8px; padding: 0 24px; height: 42px;
        font-size: 14px; font-weight: 600; display: flex; align-items: center;
        gap: 8px; cursor: pointer; transition: background 0.2s;
    }
    .cat-btn-primary:hover { background: #1d4ed8; }

    .cat-btn-light {
        background: #fff; color: #374151; border: 1px solid #d1d5db;
        border-radius: 8px; padding: 0 18px; height: 42px;
        font-size: 14px; font-weight: 600; display: flex; align-items: center;
        gap: 8px; text-decoration: none !important; cursor: pointer; transition: background 0.2s;
    }
    .cat-btn-light:hover { background: #f9fafb; border-color: #9ca3af; text-decoration: none !important; }

    /* Table */
    .cat-table-wrapper {
        border-radius: 12px; overflow: hidden;
        border: 1px solid #e5e7eb; background: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .cat-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; table-layout: fixed; }

    .cat-table thead th {
        background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700;
        padding: 14px 16px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase;
        white-space: nowrap; vertical-align: middle; position: sticky; top: 0; z-index: 10;
        letter-spacing: 0.5px;
    }
    .cat-table tbody td {
        padding: 13px 16px; font-size: 14px; color: #1e293b;
        border-bottom: 1px solid #f1f5f9; vertical-align: middle;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cat-table tbody tr:hover { background: #f8fafc; }
    .cat-table tbody tr:last-child td { border-bottom: none; }

    /* Column Widths */
    .cat-col-no   { width: 60px; text-align: center; }
    .cat-col-code { width: 130px; text-align: center; }
    .cat-col-name { width: auto; white-space: normal !important; line-height: 1.4; }
    .cat-col-unit { width: 130px; text-align: center; }

    /* Code Badge */
    .cat-codepill {
        display: inline-flex; align-items: center;
        padding: 4px 10px; border-radius: 999px;
        border: 1px solid #bfdbfe; background: #eff6ff;
        color: #1d4ed8; font-size: 12px; font-weight: 700;
    }

    /* Pagination */
    .cat-pagination-wrapper {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 20px; padding: 16px 20px 0;
        border-top: 1px solid #e5e7eb; font-size: 13px; color: #64748b;
    }
    .pagination { display: inline-flex; margin: 0; gap: 4px; }
    .pagination > li > a,
    .pagination > li > span {
        border-radius: 8px; border: 1px solid #e2e8f0;
        color: #475467; background: #fff; padding: 8px 14px;
        text-decoration: none; font-size: 13px; font-weight: 600;
    }
    .pagination > li > a:hover { background: #eff6ff; border-color: #2563eb; color: #2563eb; }
    .pagination > .active > span { background-color: #2563eb; border-color: #2563eb; color: #fff; }
    .pagination > .disabled > span { background: #f8fafc; color: #94a3b8; border-color: #e2e8f0; }

    @media (max-width: 767px) {
        .cat-header { flex-direction: column; align-items: flex-start; gap: 12px; }
        .cat-filter-row { flex-direction: column; }
        .cat-filter-group { width: 100%; }
        .cat-pagination-wrapper { flex-direction: column; gap: 12px; text-align: center; }
    }
</style>

<div class="cat-container">
    {{-- Header --}}
    <div class="cat-header">
        <div class="cat-header-main">
            <div class="cat-header-icon"><i class="fa fa-book"></i></div>
            <div>
                <h1 class="cat-title">Katalog Barang</h1>
                <p class="cat-subtitle">Referensi daftar barang yang tersedia di sistem (real-time).</p>
            </div>
        </div>

        <div class="cat-kpi">
            <div class="cat-kpi-icon"><i class="fa fa-cubes"></i></div>
            <div>
                <div class="cat-kpi-label">Total Barang Aktif</div>
                <div class="cat-kpi-value">{{ $items->total() }}</div>
            </div>
        </div>
    </div>

    {{-- Search Section --}}
    <div class="cat-section">
        <div class="cat-section-bd">
            <form method="GET" action="{{ route('catalog.index') }}">
                <div class="cat-filter-row">
                    <div class="cat-filter-group" style="flex-grow:1;">
                        <label class="cat-form-label">Cari Barang</label>
                        <input type="text" name="search" value="{{ $search }}" class="cat-form-control"
                               placeholder="Ketik Kode atau Nama Barang...">
                    </div>
                    <div class="cat-filter-group" style="width:100px;">
                        <label class="cat-form-label">Tampil</label>
                        <select name="per_page" class="cat-form-control">
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div class="cat-filter-group">
                        <label class="cat-form-label">&nbsp;</label>
                        <div style="display:flex; gap:8px;">
                            <button type="submit" class="cat-btn-primary"><i class="fa fa-search"></i> Cari</button>
                            <a href="{{ route('catalog.index') }}" class="cat-btn-light"><i class="fa fa-refresh"></i></a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="cat-table-wrapper">
        <table class="cat-table">
            <thead>
                <tr>
                    <th class="cat-col-no">No</th>
                    <th class="cat-col-code">Kode</th>
                    <th class="cat-col-name">Nama Barang</th>
                    <th class="cat-col-unit">Unit</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $i => $it)
                <tr>
                    <td style="text-align:center; color:#94a3b8; font-weight:600;">
                        {{ ($items->currentPage() - 1) * $items->perPage() + $i + 1 }}
                    </td>
                    <td style="text-align:center;">
                        <span class="cat-codepill">{{ $it->code }}</span>
                    </td>
                    <td style="font-weight:600; text-transform:uppercase;">{{ $it->name }}</td>
                    <td style="text-align:center; color:#64748b; font-weight:500;">{{ $it->unit }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; padding:50px; color:#94a3b8;">
                        <i class="fa fa-search" style="font-size:36px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                        <div style="font-size:15px; font-weight:600; color:#64748b;">Tidak ada barang ditemukan</div>
                        <div style="font-size:12px; margin-top:4px;">Coba ubah kata kunci pencarian Anda.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($items->total() > 0)
    <div class="cat-pagination-wrapper">
        <div>
            Menampilkan <strong>{{ $items->firstItem() }}</strong> sampai <strong>{{ $items->lastItem() }}</strong>
            dari <strong>{{ $items->total() }}</strong> barang
        </div>
        <div>{{ $items->links() }}</div>
    </div>
    @endif
</div>
@endsection

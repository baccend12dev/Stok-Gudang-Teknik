@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL HALAMAN STOCK OPNAME (INDEX) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-balance-scale"></i>
        <strong>KONSEP DASAR:</strong> Stock Opname (SO) adalah proses audit fisik untuk mencocokkan stok di sistem dengan kenyataan di gudang. Hasil SO akan menjadi acuan stok terbaru.
    </div>

    <h4 class="help-h"><i class="fa fa-info-circle text-primary"></i> Status Dokumen</h4>
    <ul class="help-list">
        <li>
            <span class="status-pill st-draft" style="font-size:10px;"><span class="dot" style="background:#9ca3af"></span> DRAFT</span> : 
            Data fisik sudah diinput tapi belum dieksekusi. Stok gudang <strong>BELUM BERUBAH</strong>. Masih bisa diedit bebas.
        </li>
        <li>
            <span class="status-pill st-processed" style="font-size:10px;"><span class="dot" style="background:#16a34a"></span> PROCESSED</span> : 
            <strong>FINAL.</strong> Selisih stok sudah dihitung dan sistem sudah melakukan penyesuaian (Adjustment) otomatis. Stok gudang sudah berubah mengikuti inputan fisik Anda.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-exclamation-triangle text-danger"></i> Aturan Hapus (Rollback)</h4>
    <p class="help-p">
        Hati-hati saat menghapus SO yang berstatus <strong>PROCESSED</strong>! 
        <br>Menghapus dokumen ini berarti <strong>MEMBATALKAN PENYESUAIAN STOK</strong>. Sistem akan mengembalikan jumlah stok ke posisi semula sebelum SO dilakukan.
    </p>

    <h4 class="help-h"><i class="fa fa-users text-primary"></i> Pembagian Tugas (Scope)</h4>
    <p class="help-p">
        Admin General hanya melihat SO General, dan Admin Apparel hanya melihat SO Apparel. 
        <br><strong>Sangat Disarankan:</strong> Jangan menggunakan akun Super Admin untuk membuat SO karena akan memuat semua item sekaligus (Rawan Error/Lag). Gunakan akun admin spesifik.
    </p>
@endsection

@section('content')
{{-- Load Flatpickr CSS --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    /* --- MODERN RESET & VARIABLES --- */
    :root {
        --primary: #2563eb; --primary-hover: #1d4ed8;
        --bg-soft: #f8fafc; --border-color: #e2e8f0;
        --text-main: #334155; --text-muted: #64748b;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    body { background-color: #f1f5f9 !important; color: var(--text-main); font-family: 'Inter', sans-serif; }
    .page-container { width: 100%; padding: 24px 32px; box-sizing: border-box; }

    /* FIX: Outline Hitam & Underline */
    *:focus { outline: none !important; }
    a { text-decoration: none !important; }

    /* HEADER */
    .header-wrapper { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { display: flex; align-items: center; gap: 16px; }
    
    /* FIX: Icon Box Color Matching Primary Button */
    .icon-box {
        width: 48px; height: 48px; 
        background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%); /* Soft Blue Gradient */
        border: 1px solid #dbeafe; /* Soft Blue Border */
        color: var(--primary); /* MATCHING BLUE COLOR */
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 20px; box-shadow: var(--shadow-sm);
    }
    
    .title-text h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: -0.5px; }
    .title-text p { font-size: 13px; color: var(--text-muted); margin: 4px 0 0 0; }

    /* BUTTONS */
    .btn-modern {
        height: 42px; padding: 0 20px; border-radius: 8px; font-weight: 600; font-size: 13px;
        display: inline-flex; align-items: center; gap: 8px; border: 1px solid transparent;
        transition: all 0.2s; cursor: pointer;
    }
    
    /* FIX: Remove Pale Blue Focus Ring */
    .btn-primary { 
        background: var(--primary); color: #fff; 
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.25); 
    }
    .btn-primary:hover { 
        background: var(--primary-hover); 
        transform: translateY(-1px); 
    }
    /* Override Bootstrap/Browser Default Focus Color */
    .btn-primary:active, .btn-primary:focus {
        background-color: var(--primary-hover) !important;
        border-color: transparent !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4) !important; /* Solid shadow, no pale ring */
        color: #fff !important;
    }

    .btn-light { background: #fff; border-color: #cbd5e1; color: #475569; }
    .btn-light:hover { background: #f8fafc; border-color: #94a3b8; color: #1e293b; }
    .btn-light:focus, .btn-light:active {
        background: #f1f5f9 !important;
        border-color: #94a3b8 !important;
        box-shadow: none !important;
    }
    
    /* CARD & FILTERS */
    .modern-card { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px; }
    .filter-container { padding: 20px 24px; border-bottom: 1px solid var(--border-color); background: #fff; }
    .filter-row { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; }
    
    .form-group-modern { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 160px; }
    .form-group-modern label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control-modern {
        border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 13px; height: 42px; width: 100%; transition: all 0.2s;
    }
    .form-control-modern:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
    .form-control-modern.flatpickr-input { background-color: #fff !important; }

    /* TABLE */
    .table-responsive { overflow-x: auto; }
    .table-modern { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-modern th {
        background: #f8fafc; color: #475569; font-weight: 600; font-size: 11px; text-transform: uppercase;
        letter-spacing: 0.5px; padding: 14px 20px; border-bottom: 1px solid var(--border-color); white-space: nowrap;
    }
    .table-modern td { padding: 16px 20px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
    .table-modern tr:hover td { background: #f8fafc; }

    /* STATUS PILLS */
    .status-pill { padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; }
    .st-draft { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
    .st-processed { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .dot { width: 6px; height: 6px; border-radius: 50%; }

    /* ACTION BUTTONS (CHIPS) */
    .action-chips { display: flex; gap: 6px; justify-content: center; }
    .btn-icon {
        width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
        border: 1px solid transparent; transition: all 0.2s; cursor: pointer; color: #64748b; background: transparent;
    }
    .btn-icon:hover { background: #f1f5f9; color: var(--primary); transform: translateY(-2px); }
    .btn-icon.delete:hover { background: #fef2f2; color: #ef4444; }

    /* MODAL CLEAN */
    .modal-clean .modal-content { border-radius: 16px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    .modal-clean .modal-header { border-bottom: 1px solid #f1f5f9; padding: 20px 24px; }
    .modal-clean .modal-footer { background: #f8fafc; padding: 16px 24px; border-top: 1px solid #f1f5f9; }

    /* FLATPICKR TWEAK */
    .flatpickr-calendar { font-size: 12px !important; width: 310px !important; }
    .flatpickr-day { height: 32px !important; line-height: 32px !important; max-width: 42px !important; }
</style>

<div class="page-container">
    {{-- Header --}}
    <div class="header-wrapper">
        <div class="page-title">
            {{-- Icon Box sudah diupdate warnanya --}}
            <div class="icon-box"><i class="fa fa-balance-scale"></i></div>
            <div class="title-text">
                <h1>Stock Opname</h1>
                <p>Monitor stok fisik vs sistem dan sesuaikan selisih barang.</p>
            </div>
        </div>
        <a href="{{ route('stock-opnames.create') }}" class="btn-modern btn-primary">
            <i class="fa fa-plus-circle"></i> Buat SO Baru
        </a>
    </div>

    {{-- Main Card --}}
    <div class="modern-card">
        {{-- Filter --}}
        <div class="filter-container">
            <form method="GET" action="{{ route('stock-opnames.index') }}">
                <div class="filter-row">
                    <div class="form-group-modern" style="flex: 2;">
                        <label>Cari Catatan / ID</label>
                        <input type="text" name="q" value="{{ isset($q) ? $q : '' }}" class="form-control-modern" placeholder="Cari nomor ID atau catatan...">
                    </div>
                    <div class="form-group-modern">
                        <label>Dari Tanggal</label>
                        <input type="text" name="date_from" value="{{ isset($dfrom) ? $dfrom : '' }}" class="form-control-modern datepicker-flat" placeholder="dd/mm/yyyy">
                    </div>
                    <div class="form-group-modern">
                        <label>Sampai Tanggal</label>
                        <input type="text" name="date_to" value="{{ isset($dto) ? $dto : '' }}" class="form-control-modern datepicker-flat" placeholder="dd/mm/yyyy">
                    </div>
                    <div class="form-group-modern" style="flex: 0 0 100px;">
                        <label>Baris</label>
                        <select name="per_page" class="form-control-modern">
                            @foreach([10,25,50] as $pp) 
                                <option value="{{ $pp }}" {{ (isset($perPage) && $perPage == $pp) ? 'selected' : '' }}>{{ $pp }}</option> 
                            @endforeach
                        </select>
                    </div>
                    <div style="display:flex; gap:10px; padding-bottom:1px;">
                        <button type="submit" class="btn-modern btn-primary"><i class="fa fa-filter"></i> Filter</button>
                        <a href="{{ route('stock-opnames.index') }}" class="btn-modern btn-light">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th width="100" class="text-center">No</th>
                        <th width="150">Tanggal</th>
                        <th width="150">ID Dokumen</th>
                        <th width="180" class="text-center">Jumlah Item</th>
                        <th width="180" class="text-center">Total Selisih</th>
                        <th>Catatan</th>
                        <th width="200" class="text-center">Status</th>
                        <th width="200" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opnameHeaders as $i => $h)
                    <tr>
                        <td class="text-center" style="color:#94a3b8;">{{ $opnameHeaders->firstItem() + $i }}</td>
                        <td>
                            <div style="font-weight:600; color:#334155;">{{ $h->opname_date->format('d M Y') }}</div>
                            <div style="font-size:11px; color:#64748b;">{{ $h->created_at->format('H:i') }} WIB</div>
                        </td>
                        <td><span style="font-family:monospace; font-weight:700; color:#2563eb; background:#eff6ff; padding:2px 6px; border-radius:4px;">#{{ $h->id }}</span></td>
                        <td class="text-center">{{ $h->details_count }}</td>
                        <td class="text-center">
                            @php $td = (float)$h->total_difference; @endphp
                            <span style="font-weight:700; color: {{ $td > 0 ? '#16a34a' : ($td < 0 ? '#dc2626' : '#94a3b8') }}">
                                {{ $td > 0 ? '+' : '' }}{{ $td }}
                            </span>
                        </td>
                        <td style="color:#475569;">{{ \Illuminate\Support\Str::limit($h->notes, 40) ?: '-' }}</td>
                        <td class="text-center">
                            @if($h->status === 'DRAFT') 
                                <span class="status-pill st-draft"><span class="dot" style="background:#9ca3af"></span> Draft</span>
                            @else 
                                <span class="status-pill st-processed"><span class="dot" style="background:#16a34a"></span> Processed</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="action-chips">
                                <a href="{{ route('stock-opnames.show', $h->id) }}" class="btn-icon" title="Lihat Detail">
                                    <i class="fa fa-eye"></i>
                                </a>
                                @if($h->status === 'DRAFT')
                                    <a href="{{ route('stock-opnames.edit', $h->id) }}" class="btn-icon" title="Edit Data">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                                <button type="button" class="btn-icon delete" 
                                    onclick="confirmDelete('{{ route('stock-opnames.destroy', $h->id) }}', '{{ $h->id }}', '{{ $h->status }}')"
                                    title="Hapus">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center" style="padding:40px; color:#94a3b8;">Belum ada data Stock Opname.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div style="padding: 20px 24px; border-top: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
             <div style="font-size:13px; color:#64748b;">Menampilkan <strong>{{ $opnameHeaders->count() }}</strong> dari <strong>{{ $opnameHeaders->total() }}</strong> data</div>
             <div>{{ $opnameHeaders->links() }}</div>
        </div>
    </div>
</div>

{{-- MODAL DELETE --}}
<div class="modal fade modal-clean" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 400px;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="margin:0; font-weight:700; color:#ef4444;"><i class="fa fa-trash"></i> Hapus Stock Opname</h4>
            </div>
            <div class="modal-body">
                <p style="font-size:15px; color:#334155; line-height:1.5;">
                    Anda yakin ingin menghapus dokumen SO <strong>#<span id="del_so_id"></span></strong>?
                </p>
                <div id="processedWarning" style="display:none; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px; margin-top:10px;">
                    <p style="margin:0; font-size:12px; color:#991b1b; font-weight:600;">
                        <i class="fa fa-exclamation-triangle"></i> PERHATIAN (Processed):
                    </p>
                    <p style="margin:4px 0 0; font-size:12px; color:#b91c1c;">
                        Data ini sudah diproses. Menghapusnya akan <strong>MEMBATALKAN (ROLLBACK)</strong> stok di sistem.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <form id="deleteForm" action="" method="POST">
                    {{ csrf_field() }} {{ method_field('DELETE') }}
                    <button type="button" class="btn-modern btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-modern" style="background:#ef4444; color:#fff; border:none;">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr(".datepicker-flat", {
            altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", locale: "id", allowInput: true
        });
    });

    function confirmDelete(url, id, status) {
        if (window.jQuery) {
            $('#deleteForm').attr('action', url);
            $('#del_so_id').text(id);
            if(status === 'PROCESSED') $('#processedWarning').show();
            else $('#processedWarning').hide();
            $('#deleteModal').modal('show');
        }
    }
</script>
@endsection
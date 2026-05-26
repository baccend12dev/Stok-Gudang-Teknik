@extends('layouts.app')
@section('title', 'Master Kategori')

{{-- === PANDUAN KONTEKSTUAL KATEGORI === --}}
@section('help-content')
    <div class="help-alert" style="background:#eff6ff; border-left-color:#2563eb; color:#1e40af;">
        <i class="fa fa-tags"></i>
        <strong>MASTER KATEGORI:</strong> Halaman ini mengelola pengelompokan barang (kategori), seperti ATK, Seragam, SBN, atau Kebutuhan Teknis lainnya.
    </div>

    <h4 class="help-h"><i class="fa fa-info-circle text-primary"></i> Hubungan & Alur Data</h4>
    <ul class="help-list">
        <li>
            <strong>Kode Unik</strong>: Kode kategori digunakan dalam sistem penomoran dan pengelompokan barang. Contoh: <code>ATK</code>, <code>AK</code> (Alat Kerja), <code>PK</code>.
        </li>
        <li>
            <strong>Inventory Scope</strong>: Kategori dikelompokkan ke dalam scope wewenang admin (General vs Apparel) untuk mempermudah operasional staff gudang.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-warning text-danger"></i> Aturan Penghapusan</h4>
    <p class="help-p">
        Kategori <strong>tidak dapat dihapus</strong> jika masih memiliki produk/barang di dalamnya. Silakan pindahkan atau hapus barang terkait terlebih dahulu.
    </p>
@endsection

@section('content')
<style>
    .wrap { padding: 20px; }
    
    /* Topbar Konsisten */
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
    .tleft { display: flex; align-items: center; gap: 12px; }
    .iconbox {
        width: 44px; height: 44px; border-radius: 12px;
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        display: flex; align-items: center; justify-content: center;
        color: #4338ca; font-size: 18px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.45);
    }
    .ttl h1 { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2; }
    .ttl span { font-size: 13px; color: #64748b; font-weight: 500; }

    /* Toolbar & Search Form */
    .toolbar-card { 
        background: #fff; 
        border-radius: 12px; 
        border: 1px solid #e2e8f0; 
        padding: 16px; 
        margin-bottom: 20px; 
        box-shadow: 0 1px 3px rgba(0,0,0,.04); 
    }
    .search-form { display: flex; gap: 10px; align-items: center; }
    .search-input { 
        flex: 1; 
        min-width: 200px;
        padding: 10px 14px; 
        border: 1px solid #cbd5e1; 
        border-radius: 8px; 
        font-size: 14px; 
        color: #0f172a; 
        outline: none; 
        transition: all 0.15s ease;
    }
    .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }

    /* Button Styling */
    .btnx { 
        padding: 10px 18px; 
        border-radius: 10px; 
        font-weight: 700; 
        font-size: 13px; 
        text-decoration: none !important; 
        display: inline-flex; 
        align-items: center; 
        gap: 8px; 
        border: 1px solid transparent; 
        cursor: pointer; 
        transition: all 0.2s ease; 
    }
    .btn-add { background: #2563eb; color: #fff !important; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
    .btn-add:hover { background: #1d4ed8; color: #fff !important; transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(37, 99, 235, 0.3); }
    
    .btn-submit { background: #0f172a; color: #fff; }
    .btn-submit:hover { background: #1e293b; }
    
    .btn-reset { background: #f1f5f9; color: #475569 !important; border: 1px solid #cbd5e1; }
    .btn-reset:hover { background: #e2e8f0; }

    /* Table styling */
    .cardx { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f8fafc; padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #0f172a; vertical-align: middle; }
    tr:hover td { background-color: #f8fafc; }
    
    .code-tag { background: #f1f5f9; color: #334155; font-family: monospace; font-weight: 700; padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; }

    /* Action Buttons */
    .act-btn { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; color: #64748b; background: #fff; margin-right: 4px; transition: .15s; cursor: pointer; text-decoration: none !important; }
    .act-btn:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
    .act-btn.del:hover { background: #fef2f2; color: #ef4444; border-color: #fecaca; }

    /* Modal Clean */
    .modal-clean .modal-content { border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .modal-body-clean { padding: 30px; text-align: center; }
    .icon-box-modal { width: 60px; height: 60px; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .icon-box-modal.danger { background: #fee2e2; color: #ef4444; }
    .m-title { font-size: 18px; font-weight: 800; margin-bottom: 8px; color: #0f172a; }
    .m-desc { font-size: 14px; color: #64748b; margin-bottom: 0; line-height: 1.5; }
    .m-foot { background: #f8fafc; padding: 16px 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; border-top: 1px solid #e2e8f0; }
    .btn-m { padding: 10px; border-radius: 8px; font-weight: 700; border: 1px solid #e2e8f0; background: #fff; color: #64748b; text-align: center; cursor: pointer;}
    .btn-m:hover { background: #f1f5f9; }
    .btn-m.confirm-del { background: #ef4444; color: #fff; border: none; }
    .btn-m.confirm-del:hover { background: #dc2626; }
</style>

<div class="wrap">
    {{-- Header Topbar --}}
    <div class="topbar">
        <div class="tleft">
            <div class="iconbox"><i class="fa fa-tags"></i></div>
            <div class="ttl">
                <h1>Master Kategori</h1>
                <span>Kelola kategori barang untuk membagi scope barang inventori.</span>
            </div>
        </div>
        @if($has)
            <a href="{{ route('category.create') }}" class="btnx btn-add"><i class="fa fa-plus"></i> Tambah Kategori</a>
        @endif
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div style="background:#f0fdf4; border-left:4px solid #16a34a; padding:15px; border-radius:8px; margin-bottom:20px; color:#14532d; font-size:14px; font-weight: 600;">
            <i class="fa fa-check-circle" style="margin-right:8px; color:#16a34a;"></i>
            {!! session('success') !!}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fef2f2; border-left:4px solid #dc2626; padding:15px; border-radius:8px; margin-bottom:20px; color:#7f1d1d; font-size:14px; font-weight: 600;">
            <i class="fa fa-exclamation-circle" style="margin-right:8px; color:#dc2626;"></i>
            {!! session('error') !!}
        </div>
    @endif

    {{-- Toolbar Filter & Pencarian --}}
    @if($has)
        <div class="toolbar-card">
            <form method="get" class="search-form">
                <input type="text" name="q" class="search-input" value="{{ $q }}" placeholder="Cari kode atau nama kategori...">
                <button type="submit" class="btnx btn-submit"><i class="fa fa-search"></i> Cari</button>
                @if($q !== '')
                    <a class="btnx btn-reset" href="{{ route('category.index') }}"><i class="fa fa-refresh"></i> Reset</a>
                @endif
            </form>
        </div>
    @endif

    @if(!$has)
        <div class="cardx" style="padding:40px; text-align:center;">
            <div class="icon-box-modal danger" style="margin-bottom:20px;"><i class="fa fa-database"></i></div>
            <h3 class="m-title" style="font-size:22px;">Tabel Database Belum Tersedia</h3>
            <p class="m-desc" style="font-size:15px; max-width:500px; margin: 0 auto 20px;">
                Tabel <code>categories</code> tidak ditemukan pada database. Silakan jalankan migrasi, seeder, atau eksekusi perintah SQL di bawah ini:
            </p>
            <pre style="background:#0f172a; color:#f8fafc; padding:20px; border-radius:10px; font-family:monospace; text-align:left; max-width:600px; margin:0 auto; overflow-x:auto;">
CREATE TABLE categories (
    id serial PRIMARY KEY,
    code varchar(10) UNIQUE NOT NULL,
    name varchar(255) NOT NULL,
    created_at timestamp DEFAULT now(),
    updated_at timestamp DEFAULT now()
);</pre>
        </div>
    @else
        {{-- Card Tabel Kategori --}}
        <div class="cardx">
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:60px; text-align:center;">No</th>
                            <th style="width:150px;">Kode</th>
                            <th>Nama Kategori</th>
                            <th style="width:120px; text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $idx => $r)
                        <tr>
                            <td style="text-align:center; color:#64748b; font-weight:600;">{{ $rows->firstItem() + $idx }}</td>
                            <td><span class="code-tag">{{ $r->code }}</span></td>
                            <td style="font-weight:700; color:#0f172a;">{{ $r->name }}</td>
                            <td style="text-align:center;">
                                <a href="{{ route('category.edit', $r->id) }}" class="act-btn" title="Edit Kategori"><i class="fa fa-pencil"></i></a>
                                <button type="button" class="act-btn del" title="Hapus Kategori" onclick="openDeleteModal('{{ $r->id }}', '{{ $r->code }} - {{ $r->name }}')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="muted" style="text-align:center; padding:40px; color:#64748b;">
                                <div style="font-size:32px; margin-bottom:10px; color:#cbd5e1;"><i class="fa fa-folder-open-o"></i></div>
                                Belum ada data kategori.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->total() > 0)
                <div style="padding:15px; border-top:1px solid #e2e8f0;">{{ $rows->links() }}</div>
            @endif
        </div>
    @endif
</div>

{{-- MODAL DELETE CLEAN --}}
<div class="modal fade modal-clean" id="modalDelete" tabindex="-1">
    <div class="modal-dialog modal-sm" style="margin-top:10%;">
        <div class="modal-content">
            <div class="modal-body-clean">
                <div class="icon-box-modal danger"><i class="fa fa-trash"></i></div>
                <h3 class="m-title">Hapus Kategori?</h3>
                <p class="m-desc">Kategori <strong id="del-name">...</strong> akan dihapus permanen. Tindakan ini tidak bisa dibatalkan jika sudah terikat dengan produk lain.</p>
            </div>
            <div class="m-foot">
                <button type="button" class="btn-m" data-dismiss="modal">Batal</button>
                <form id="form-delete" action="" method="POST" style="display:contents;">
                    {{ csrf_field() }} 
                    {{ method_field('DELETE') }}
                    <button type="submit" class="btn-m confirm-del">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openDeleteModal(id, name) {
        $('#del-name').text(name);
        $('#form-delete').attr('action', '/category/' + id);
        $('#modalDelete').modal('show');
    }
</script>
@endsection

@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL DAFTAR BARANG === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>INFO MASTER DATA:</strong> Halaman ini adalah "Kamus" seluruh barang yang terdaftar di sistem.
    </div>

    <h4 class="help-h"><i class="fa fa-list text-primary"></i> Membaca Tabel Barang</h4>
    <ul class="help-list">
        <li>
            <strong>Kode Barang:</strong> Kode identifikasi barang. 
            <br><span style="font-size:11px; color:#d97706;">
                <i class="fa fa-exclamation-circle"></i> <strong>Catatan:</strong> Kode barang di sini <strong>TIDAK WAJIB UNIK</strong>. Satu kode boleh digunakan untuk beberapa varian item jika memang aturan internal perusahaan demikian.
            </span>
        </li>
        <li>
            <strong>Buffer (Batas Aman):</strong> Jumlah minimum stok yang harus ada di gudang. Jika stok fisik turun di bawah angka ini, sistem akan memberikan peringatan (Warna Merah di Dashboard).
        </li>
        <li>
            <strong>Status:</strong>
            <ul style="margin-top:4px; margin-bottom:0;">
                <li><span class="badge badge-ok">ACTIVE</span> : Barang bisa direquest oleh user.</li>
                <li><span class="badge badge-low">NONACTIVE</span> : Barang diarsipkan (tidak muncul di pencarian user).</li>
            </ul>
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-search text-primary"></i> Tips Pencarian</h4>
    <p class="help-p">
        Gunakan kolom <strong>"Cari Data"</strong> untuk mencari berdasarkan Kode, Nama Barang, atau Lokasi Rak. 
        Gunakan <strong>"Filter Status"</strong> untuk menyembunyikan barang yang sudah tidak aktif.
    </p>
@endsection

@section('content')
{{-- IMPORT LIBRARY ALERT MODERN (SweetAlert2) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ====== FRAME PANEL ====== */
.section { background: #fff; border: 1px solid #e7e7f0; border-radius: 12px; margin-bottom: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
.section .section-hd { display: flex; align-items: center; gap: 8px; padding: 14px 18px; border-bottom: 1px solid #eef2f7; background: #f8fafc; border-radius: 12px 12px 0 0; }
.section .section-hd > i { color: #2563eb; font-size: 16px; }
.section .section-hd > strong { font-size: 15px; color: #1e293b; }
.section .section-bd { padding: 20px; }

/* ====== FORM/FILTER ====== */
.form-label { display: block; font-size: 13px; color: #64748b; margin-bottom: 6px; font-weight: 600; }

/* FIX: Custom Select Dropdown Modern */
.form-control { 
    height: 40px; 
    border: 1px solid #d0d5dd; 
    border-radius: 8px; 
    padding: 6px 12px; 
    font-size: 14px; 
    color: #1e293b; 
    transition: all 0.2s; 
    width: 100%;
}
select.form-control {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    padding-right: 36px; /* Space for arrow */
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 10px center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    cursor: pointer;
}

.form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); outline: none; }

.filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; }
.filters .form-group { display: flex; flex-direction: column; margin: 0; }
.filters .form-actions { display: flex; align-items: flex-end; gap: 10px; }
.filters .form-actions .fake-label { visibility: hidden; height: 0; margin: 0; font-size: 13px; line-height: 1; }

/* ====== BUTTONS ====== */
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; border: 1px solid transparent; padding: 0 16px; font-weight: 600; cursor: pointer; height: 40px; text-decoration: none; font-size: 14px; transition: all 0.2s; outline: none; }
.btn i { font-size: 15px; }
.btn:active { transform: translateY(1px); }
.btn:focus { outline: none; box-shadow: none; }

.btn-primary { background: #2563eb; color: #fff; border-color: #2563eb; }
.btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }

.btn-light { background: #fff; border-color: #d0d5dd; color: #344054; }
.btn-light:hover { background: #f8fafc; border-color: #cbd5e1; }

.btn-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.btn-danger:hover { background: #fecaca; color: #7f1d1d; }
.btn-danger:focus, .btn-danger:active { background: #fee2e2; color: #991b1b; border-color: #fecaca; }

.btn-compact { padding: 0 12px; height: 34px; font-size: 13px; }

/* ====== TABLE STYLING ====== */
.table-wrapper { border: 1px solid #eef2f7; border-radius: 10px; overflow: hidden; margin-top: 16px; }
.table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; table-layout: fixed; }

.table thead th {
    background: #f8fafc;
    color: #475467;
    font-weight: 700;
    font-size: 12px; 
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 12px;
    border-bottom: 1px solid #eef2f7;
    white-space: nowrap;
    vertical-align: middle;
}

.table tbody td {
    padding: 12px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: 14px; 
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.table tbody tr:last-child td { border-bottom: none; }
.table tbody tr:hover { background: #f8fafc; }

/* === PROPORSIONAL KOLOM === */
.col-code { width: 100px; text-align: center; }
.col-name { width: 420px; white-space: normal !important; line-height: 1.5; } 
.col-unit { width: 90px; text-align: center; } 
.col-loc  { width: 110px; text-align: center; } 
.col-buf  { width: 90px; text-align: center; }
.col-stok { width: 90px; text-align: center; }
.col-stat { width: 110px; text-align: center; }
.col-act  { width: 130px; text-align: center; }

/* Utilities */
.t-center { text-align: center !important; }
.t-right { text-align: right !important; }
.t-left { text-align: left !important; }

.badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.badge-ok { background: #dcfce7; color: #166534; }
.badge-low { background: #fee2e2; color: #991b1b; }

/* ====== MODERN PAGINATION ====== */
.pagination-wrapper { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-top: 20px; 
    padding-top: 20px;
    border-top: 1px solid #eef2f7;
    font-size: 14px; 
    color: #64748b; 
}
.pagination-wrapper .pagination { display: inline-flex; margin: 0; padding: 0; gap: 6px; }
.pagination-wrapper .pagination > li { display: inline; }
.pagination-wrapper .pagination > li > a,
.pagination-wrapper .pagination > li > span {
    border-radius: 6px !important;
    border: 1px solid #e2e8f0;
    color: #475467;
    background: #fff;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.pagination-wrapper .pagination > li > a:hover { background: #eff6ff; border-color: #2563eb; color: #2563eb; }
.pagination-wrapper .pagination > .active > span { background: #2563eb; border-color: #2563eb; color: #fff; }
.pagination-wrapper .pagination > .disabled > span { background: #f8fafc; color: #94a3b8; border-color: #e2e8f0; cursor: not-allowed; }

/* === TWEAK SWEETALERT AGAR MATCHING === */
.swal2-popup { font-family: inherit !important; border-radius: 16px !important; }
.swal2-title { font-size: 20px !important; color: #1e293b !important; }
.swal2-html-container { font-size: 15px !important; color: #64748b !important; }
.swal2-actions { gap: 12px; }
.swal2-styled.swal2-confirm { background-color: #dc2626 !important; border-radius: 8px !important; font-weight: 600; padding: 12px 28px !important; font-size: 14px !important; }
.swal2-styled.swal2-cancel { background-color: #fff !important; color: #374151 !important; border: 1px solid #d1d5db !important; border-radius: 8px !important; font-weight: 600; padding: 12px 28px !important; font-size: 14px !important; }
.swal2-styled.swal2-cancel:hover { background-color: #f3f4f6 !important; }
</style>

<div class="section">
  <div class="section-hd">
    <i class="fa fa-cube"></i> <strong>Daftar Item Master</strong>
  </div>
  <div class="section-bd">
    
    {{-- FORM PENCARIAN & FILTER --}}
    <form method="GET" action="{{ route('items.index') }}">
      <div class="filters">
        <div class="form-group" style="flex:1;min-width:220px">
          <label class="form-label">Cari Data</label>
          <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Ketik kode, nama, atau lokasi...">
        </div>
        <div class="form-group" style="width:160px">
          <label class="form-label">Filter Status</label>
          <select name="status" class="form-control">
            <option value="all">Semua</option>
            <option value="ACTIVE" {{ $status=='ACTIVE'?'selected':'' }}>AKTIF</option>
            <option value="NONACTIVE" {{ $status=='NONACTIVE'?'selected':'' }}>NONAKTIF</option>
          </select>
        </div>
        <div class="form-group" style="width:100px">
          <label class="form-label">Limit</label>
          <select name="per_page" class="form-control">
            <option value="10" {{ $perPage==10?'selected':'' }}>10</option>
            <option value="25" {{ $perPage==25?'selected':'' }}>25</option>
            <option value="50" {{ $perPage==50?'selected':'' }}>50</option>
            <option value="100" {{ $perPage==100?'selected':'' }}>100</option>
          </select>
        </div>
        <div class="form-actions">
            <label class="fake-label">_</label>
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Cari</button>
            <a href="{{ route('items.index') }}" class="btn btn-light" title="Reset Filter"><i class="fa fa-refresh"></i></a>
        </div>
        <div class="form-actions" style="margin-left:auto;">
            <label class="fake-label">_</label>
            <a href="{{ route('items.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Item</a>
        </div>
      </div>
    </form>

    {{-- TABEL DATA --}}
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th class="col-code">Kode</th>
            <th class="col-name">Nama Barang</th>
            <th class="col-unit">Unit</th>
            <th class="col-loc">Lokasi</th>
            <th class="col-buf">Buffer</th>
            <th class="col-stok">Stok</th>
            <th class="col-stat">Status</th>
            <th class="col-act">Aksi</th>
          </tr>
        </thead>
        <tbody>
        @forelse($items as $it)
        @php 
          // FIX: Gunakan (float) agar 0.50 tidak dibulatkan jadi 0
          $currStock = (float)$it->current_stock;
          $bufMin = (float)$it->buffer_min;
          $levelLow = $currStock <= ($bufMin + 5);
        @endphp
        <tr>
          <td class="t-center" style="font-weight:700; color:#374151;">{{ $it->code }}</td>
          <td class="t-left" title="{{ $it->name }}">
              <div style="font-weight:500; text-transform: uppercase;">{{ $it->name }}</div>
          </td>
          <td class="t-center text-muted">{{ $it->unit }}</td>
          <td class="t-center">
            @if($it->location && $it->location !== '-')
                <span style="color:#4b5563;"><i class="fa fa-map-marker" style="color:#94a3b8;margin-right:3px"></i> {{ $it->location }}</span>
            @else
                <span style="color:#d1d5db;">-</span>
            @endif
          </td>
          <td class="t-center text-muted">{{ $bufMin }}</td>
          <td class="t-center">
             @if($levelLow)
               <strong style="color:#dc2626">{{ $currStock }}</strong>
             @else
               <span style="font-weight:600; color:#059669;">{{ $currStock }}</span>
             @endif
          </td>
          <td class="t-center">
            @if($levelLow)
              <span class="badge badge-low">LOW</span>
            @else
              <span class="badge badge-ok">OK</span>
            @endif
          </td>
          <td class="t-center">
            <div style="display:inline-flex; gap:8px; justify-content:center;">
                <a href="{{ route('items.edit',$it->id) }}" class="btn btn-primary btn-compact" title="Edit Data">
                    <i class="fa fa-pencil"></i>
                </a>
                
                {{-- TOMBOL HAPUS DENGAN TRIGGER JS --}}
                <form action="{{ route('items.destroy',$it->id) }}" method="POST" style="display:inline" class="form-delete">
                  {{ csrf_field() }} {{ method_field('DELETE') }}
                  <button type="button" class="btn btn-danger btn-compact btn-delete-trigger" 
                          data-name="{{ $it->name }}"
                          data-code="{{ $it->code }}"
                          title="Hapus Data">
                    <i class="fa fa-trash"></i>
                  </button>
                </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
            <td colspan="8" class="t-center" style="padding:40px;color:#94a3b8;">
                <i class="fa fa-folder-open-o" style="font-size:36px;margin-bottom:12px;opacity:0.5;"></i><br>
                <span style="font-size:15px;font-weight:500;">Tidak ada data barang ditemukan.</span>
            </td>
        </tr>
      @endforelse
        </tbody>
      </table>
    </div>

    {{-- PAGINATION --}}
    <div class="pagination-wrapper">
      <div>
        Menampilkan <strong>{{ $items->firstItem() }}</strong> - <strong>{{ $items->lastItem() }}</strong> dari <strong>{{ $items->total() }}</strong> data
      </div>
      <div>{{ $items->links() }}</div>
    </div>

  </div>
</div>

<script>
// Logic Konfirmasi Hapus Modern
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-trigger');
    
    if (btn) {
        e.preventDefault(); 
        btn.blur();

        const form = btn.closest('form');
        const itemName = btn.getAttribute('data-name');
        const itemCode = btn.getAttribute('data-code');

        Swal.fire({
            title: 'Hapus Item?',
            html: `Anda akan menghapus <b>${itemCode}</b><br><span style="color:#64748b;font-size:14px; text-transform:uppercase;">${itemName}</span><br><br>Data yang dihapus tidak bisa dikembalikan!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true, 
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
});
</script>
@endsection
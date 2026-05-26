@extends('layouts.app')
@section('title', 'Edit Kategori')

{{-- === PANDUAN KONTEKSTUAL EDIT KATEGORI === --}}
@section('help-content')
    <div class="help-alert" style="background:#eff6ff; border-left-color:#2563eb; color:#1e40af;">
        <i class="fa fa-info-circle"></i>
        <strong>BACA SAJA (READ-ONLY):</strong> Kode kategori bersifat permanen dan tidak dapat diubah setelah dibuat untuk menjaga keterkaitan data dengan barang inventori yang sudah terdaftar.
    </div>

    <h4 class="help-h"><i class="fa fa-pencil text-primary"></i> Data Yang Dapat Diubah</h4>
    <ul class="help-list">
        <li>
            <strong>Nama Kategori</strong>: Anda dapat mengubah nama kategori agar lebih tepat menggambarkan kelompok barang yang berada di bawahnya.
        </li>
    </ul>
@endsection

@section('content')
<style>
    .wrap-form { padding: 20px; max-width: 600px; margin: 0 auto; }
    .cardx { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .cardx-head { background: #f8fafc; padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
    .cardx-head h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; }
    .cardx-body { padding: 24px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control { 
        height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: none; padding: 8px 12px; font-size: 14px; color: #0f172a; width: 100%; transition: all .2s; 
    }
    .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); outline: none; }
    
    .form-control[readonly] { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; border-color: #cbd5e1; }
    
    .text-muted { font-size: 12px; color: #94a3b8; margin-top: 4px; display: block; }
    
    /* Button Styling */
    .btnx { display: block; width: 100%; padding: 12px; border-radius: 8px; font-weight: 700; font-size: 14px; text-align: center; cursor: pointer; text-decoration: none; margin-bottom: 10px; border: 1px solid transparent; transition: .15s; }
    .btn-save { background: #2563eb; color: #fff; border: none; }
    .btn-save:hover { background: #1d4ed8; }
    .btn-cancel { background: #fff; border-color: #e2e8f0; color: #64748b; }
    .btn-cancel:hover { background: #f1f5f9; color: #0f172a; }
</style>

<div class="wrap-form">
    <div class="cardx">
        <div class="cardx-head">
            <h2><i class="fa fa-pencil" style="color:#2563eb;"></i> Edit Kategori</h2>
        </div>
        
        <div class="cardx-body">
            {{-- Alert error --}}
            @if($errors->any())
                <div style="background:#fef2f2; border-left:4px solid #dc2626; padding:15px; border-radius:8px; margin-bottom:20px; color:#7f1d1d; font-size:14px; font-weight: 600;">
                    <i class="fa fa-exclamation-circle" style="margin-right:8px; color:#dc2626;"></i>
                    Harap perbaiki kesalahan berikut:
                    <ul style="margin: 5px 0 0 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('category.update', $category->id) }}" method="POST" autocomplete="off">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                
                <div class="form-group">
                    <label>Kode Kategori (Tidak Dapat Diubah)</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $category->code) }}" readonly>
                    <span class="text-muted"><i class="fa fa-lock"></i> Kode kategori dikunci demi integritas data relasi barang.</span>
                </div>
                
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="name" class="form-control" required placeholder="Contoh: Alat Tulis Kantor, Kebutuhan Kebersihan" value="{{ old('name', $category->name) }}">
                </div>

                <hr style="margin:25px 0; border-top:1px solid #f1f5f9;">
                
                <button type="submit" class="btnx btn-save"><i class="fa fa-save"></i> Perbarui Kategori</button>
                <a href="{{ route('category.index') }}" class="btnx btn-cancel">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Tambah Departemen')

{{-- === PANDUAN KONTEKSTUAL TAMBAH DEPARTEMEN === --}}
@section('help-content')
    <div class="help-alert" style="background:#eff6ff; border-left-color:#2563eb; color:#1e40af;">
        <i class="fa fa-info-circle"></i>
        <strong>KODE & NAMA:</strong> Pastikan kode departemen diisi singkat (maksimal 10 karakter) dan unik untuk mempermudah identifikasi transaksi gudang.
    </div>

    <h4 class="help-h"><i class="fa fa-lightbulb-o text-warning"></i> Tips Penulisan</h4>
    <ul class="help-list">
        <li>
            <strong>Kode</strong>: Gunakan singkatan huruf besar, contoh: <code>PROD</code> untuk Produksi, <code>TEK</code> untuk Teknik, atau <code>LOG</code> untuk Logistik.
        </li>
        <li>
            <strong>Nama</strong>: Tulis nama lengkap departemen dengan jelas agar mudah dimengerti saat pemetaan user atau persetujuan item.
        </li>
        <li>
            <strong>Deskripsi</strong>: Tulis keterangan opsional tentang cakupan atau lokasi departemen tersebut.
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
    
    textarea.form-control { height: auto; min-height: 100px; resize: vertical; }
    
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
            <h2><i class="fa fa-building-o" style="color:#2563eb;"></i> Tambah Departemen</h2>
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

            <form action="{{ route('departments.store') }}" method="POST" autocomplete="off">
                {{ csrf_field() }}
                
                <div class="form-group">
                    <label>Kode Departemen</label>
                    <input type="text" name="code" class="form-control" maxlength="10" required placeholder="Contoh: PROD, TEK, LOG" value="{{ old('code') }}">
                    <span class="text-muted"><i class="fa fa-info-circle"></i> Maksimal 10 karakter, harus unik.</span>
                </div>
                
                <div class="form-group">
                    <label>Nama Departemen</label>
                    <input type="text" name="name" class="form-control" required placeholder="Contoh: Produksi, Teknik, Logistik" value="{{ old('name') }}">
                </div>

                <div class="form-group">
                    <label>Deskripsi / Keterangan</label>
                    <textarea name="description" class="form-control" placeholder="Keterangan opsional mengenai departemen ini...">{{ old('description') }}</textarea>
                </div>

                <hr style="margin:25px 0; border-top:1px solid #f1f5f9;">
                
                <button type="submit" class="btnx btn-save"><i class="fa fa-save"></i> Simpan Departemen</button>
                <a href="{{ route('departments.index') }}" class="btnx btn-cancel">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection

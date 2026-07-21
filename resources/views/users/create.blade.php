@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL BUAT USER (REGISTRATION) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-user-plus"></i>
        <strong>SISTEM TERPUSAT:</strong> Tidak ada halaman Register publik. Semua akun harus dibuat manual melalui halaman ini oleh Super Admin.
    </div>

    <h4 class="help-h"><i class="fa fa-cogs text-primary"></i> Poin Penting Setting Akun</h4>
    <ul class="help-list">
        <li>
            <strong>Email:</strong> Gunakan email perusahaan yang aktif. Ini akan menjadi username login.
        </li>
        <li>
            <strong>Password Default:</strong> Sistem otomatis mengisi <code>123456</code>. Mohon informasikan kepada user baru untuk segera mengganti password setelah login pertama kali.
        </li>
        <li>
            <strong>Inventory Scope:</strong>
            Hanya muncul jika Role = <strong>ADMIN</strong>. 
            <br>Wajib dipilih sesuai tanggung jawabnya: 
            <br>- <strong>GENERAL</strong> (Bu Ani) = ATK, Kebersihan, dll.
            <br>- <strong>APPAREL</strong> (Bu Shinta) = Seragam & Sepatu.
        </li>
    </ul>
@endsection

@section('content')

<style>
    .wrap-form { padding:20px; max-width:600px; margin:0 auto; }
    .cardx { background:#fff; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); }
    .cardx-head { background:#f8fafc; padding:20px 24px; border-bottom:1px solid #e2e8f0; }
    .cardx-head h2 { margin:0; font-size:18px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:10px; }
    .cardx-body { padding:24px; }
    
    .form-group label { display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; }
    .form-control { 
        height:42px; border-radius:8px; border:1px solid #cbd5e1; box-shadow:none; padding:8px 12px; font-size:14px; color:#0f172a; width:100%; transition:all .2s; 
    }
    .form-control:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1); outline:none; }
    
    .scope-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:15px; margin-top:10px; }
    .text-muted { font-size:12px; color:#94a3b8; margin-top:4px; display:block; }
    
    .btnx { display:block; width:100%; padding:12px; border-radius:8px; font-weight:700; font-size:14px; text-align:center; cursor:pointer; text-decoration:none; margin-bottom:10px; border:1px solid transparent; transition:.15s; }
    .btn-save { background:#2563eb; color:#fff; }
    .btn-save:hover { background:#1d4ed8; }
    .btn-cancel { background:#fff; border-color:#e2e8f0; color:#64748b; }
    .btn-cancel:hover { background:#f1f5f9; color:#0f172a; }
</style>

<div class="wrap-form">
    <div class="cardx">
        <div class="cardx-head">
            <h2><i class="fa fa-user-plus" style="color:#2563eb;"></i> Tambah User Baru</h2>
        </div>
        
        <div class="cardx-body">
            <form action="{{ route('users.store') }}" method="POST" autocomplete="off">
                {{ csrf_field() }}
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" required placeholder="Contoh: Budi Santoso">
                </div>
                
                <div class="form-group">
                    <label>Email (Username Login)</label>
                    <input type="email" name="email" class="form-control" required placeholder="user@otto.com">
                </div>

                <div class="form-group">
                    <label>Password Awal</label>
                    <input type="text" name="password" class="form-control" value="123456" required>
                    <span class="text-muted"><i class="fa fa-info-circle"></i> Default password adalah 123456.</span>
                </div>

                <div class="form-group">
                    <label>Role / Peran</label>
                    <select name="role" id="roleSelect" class="form-control" required onchange="toggleScope()">
                        <option value="USER">USER (Staff Department)</option>
                        <option value="APPROVAL">APPROVAL</option>
                        <option value="ADMIN">ADMIN (Inventory Staff)</option>
                        <option value="SUPER_ADMIN">SUPER ADMIN (Manager)</option>
                    </select>
                </div>

                <div class="form-group" id="deptGroup">
                    <label>Departemen</label>
                    <select name="department_id" class="form-control">
                        <option value="">-- Pilih Departemen --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- SCOPE HANYA MUNCUL JIKA ROLE ADMIN --}}
                <div class="form-group scope-box" id="scopeGroup" style="display:none;">
                    <label style="color:#1e40af;">Inventory Scope (Khusus Admin)</label>
                    <select name="inventory_scope" class="form-control">
                        <option value="ALL">Semua Kategori</option>
                        <option value="GENERAL">GENERAL (Bu Ani - ATK, SBN, dll)</option>
                        <option value="APPAREL">APPAREL (Bu Shinta - AK, PK)</option>
                    </select>
                    <span class="text-muted" style="color:#60a5fa;">*Menentukan barang apa saja yang bisa dikelola admin ini.</span>
                </div>

                <hr style="margin:25px 0; border-top:1px solid #f1f5f9;">
                
                <button type="submit" class="btnx btn-save">Simpan User</button>
                <a href="{{ route('users.index') }}" class="btnx btn-cancel">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleScope() {
        var role = document.getElementById('roleSelect').value;
        var scope = document.getElementById('scopeGroup');
        if(role === 'ADMIN') {
            scope.style.display = 'block';
        } else {
            scope.style.display = 'none';
        }
    }
</script>
@endsection
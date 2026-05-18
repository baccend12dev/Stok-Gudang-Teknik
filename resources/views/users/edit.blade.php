@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL EDIT USER === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-pencil"></i>
        <strong>MODE EDIT PROFIL:</strong> Anda sedang mengubah data master pengguna.
    </div>

    <h4 class="help-h"><i class="fa fa-lock text-primary"></i> Ganti Password?</h4>
    <p class="help-p">
        Untuk alasan keamanan, perubahan password <strong>TIDAK DILAKUKAN DI SINI</strong>. 
        <br>Gunakan tombol <strong>Reset Password</strong> (ikon kunci) di halaman daftar user jika Anda ingin mereset password pengguna ini ke default (123456).
    </p>

    <h4 class="help-h"><i class="fa fa-exclamation-triangle text-warning"></i> Hati-hati Mengubah Role</h4>
    <p class="help-p">
        Jika Anda mengubah Role dari <strong>ADMIN</strong> menjadi <strong>USER</strong>, pastikan untuk menghapus Inventory Scope-nya agar tidak terjadi konflik hak akses di kemudian hari.
    </p>
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
    
    .alert-info-soft { background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1; padding:12px; border-radius:8px; font-size:13px; display:flex; gap:10px; align-items:center; margin-top:20px; }

    .btnx { display:block; width:100%; padding:12px; border-radius:8px; font-weight:700; font-size:14px; text-align:center; cursor:pointer; text-decoration:none; margin-bottom:10px; border:1px solid transparent; transition:.15s; }
    .btn-save { background:#2563eb; color:#fff; }
    .btn-save:hover { background:#1d4ed8; }
    .btn-cancel { background:#fff; border-color:#e2e8f0; color:#64748b; }
    .btn-cancel:hover { background:#f1f5f9; color:#0f172a; }
</style>

<div class="wrap-form">
    <div class="cardx">
        <div class="cardx-head">
            <h2><i class="fa fa-pencil-square-o" style="color:#2563eb;"></i> Edit User</h2>
        </div>
        
        <div class="cardx-body">
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                {{ csrf_field() }} {{ method_field('PUT') }}
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                </div>
                
                <div class="form-group">
                    <label>Email (Username Login)</label>
                    <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="roleSelect" class="form-control" required onchange="toggleScope()">
                        <option value="USER" {{ $user->role == 'USER' ? 'selected' : '' }}>USER (Dept)</option>
                        <option value="ADMIN" {{ $user->role == 'ADMIN' ? 'selected' : '' }}>ADMIN (Inventory)</option>
                        <option value="SUPER_ADMIN" {{ $user->role == 'SUPER_ADMIN' ? 'selected' : '' }}>SUPER ADMIN</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Departemen</label>
                    <select name="department_id" class="form-control">
                        <option value="">-- Pilih Departemen --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ $user->department_id == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group scope-box" id="scopeGroup" style="display:{{ $user->role == 'ADMIN' ? 'block' : 'none' }};">
                    <label style="color:#1e40af;">Inventory Scope (Khusus Admin)</label>
                    <select name="inventory_scope" class="form-control">
                        <option value="ALL" {{ $user->inventory_scope == 'ALL' ? 'selected' : '' }}>Semua Kategori</option>
                        <option value="GENERAL" {{ $user->inventory_scope == 'GENERAL' ? 'selected' : '' }}>GENERAL (Bu Ani - ATK, SBN, dll)</option>
                        <option value="APPAREL" {{ $user->inventory_scope == 'APPAREL' ? 'selected' : '' }}>APPAREL (Bu Shinta - AK, PK)</option>
                    </select>
                </div>

                <div class="alert-info-soft">
                    <i class="fa fa-info-circle" style="font-size:18px;"></i> 
                    <span>Password tidak dapat diubah disini. Gunakan tombol <strong>Reset Password</strong> di halaman utama jika user lupa password.</span>
                </div>

                <hr style="margin:25px 0; border-top:1px solid #f1f5f9;">

                <button type="submit" class="btnx btn-save">Simpan Perubahan</button>
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
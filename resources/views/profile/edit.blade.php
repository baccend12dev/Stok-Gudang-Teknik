@extends('layouts.app')

@section('content')
<style>
    /* Styling Khusus Halaman Ini */
    .wrap-profile { padding:30px 20px; max-width:550px; margin:0 auto; }
    
    .cardx { 
        background:#fff; border-radius:16px; border:1px solid #e2e8f0; 
        overflow:hidden; box-shadow:0 10px 25px -5px rgba(0,0,0,0.05); 
    }
    
    .cardx-head { 
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); 
        padding: 25px 30px; border-bottom:1px solid #e2e8f0; 
        text-align: center;
    }
    .cardx-head h2 { margin:0; font-size:20px; font-weight:800; color:#0f172a; }
    .cardx-head p { margin:5px 0 0; font-size:13px; color:#64748b; }
    
    .cardx-icon {
        width: 60px; height: 60px; border-radius: 50%; 
        background: #eff6ff; color: #3b82f6; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 24px; margin: 0 auto 15px; border: 1px solid #dbeafe;
    }

    .cardx-body { padding:30px; }

    /* Modern Input Group */
    .form-group { margin-bottom: 20px; position: relative; }
    .form-group label { 
        display:block; font-size:12px; font-weight:700; color:#64748b; 
        margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px; 
    }
    
    .input-icon-wrap { position: relative; }
    .input-icon-wrap i {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        color: #94a3b8; font-size: 16px;
    }
    .form-control { 
        height:46px; border-radius:10px; border:1px solid #cbd5e1; 
        padding-left: 42px; /* Space for icon */
        padding-right: 15px;
        font-size:14px; color:#0f172a; width:100%; transition:all .2s; 
    }
    .form-control:focus { 
        border-color:#3b82f6; box-shadow:0 0 0 4px rgba(59, 130, 246, 0.1); outline:none; 
    }

    .help-block { font-size: 12px; color: #94a3b8; margin-top: 6px; display: block; }

    .btnx { 
        display:block; width:100%; padding:14px; border-radius:10px; 
        font-weight:700; font-size:14px; text-align:center; cursor:pointer; 
        border:1px solid transparent; transition:all .2s; 
        background:#3b82f6; color:#fff; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
    }
    .btnx:hover { background:#2563eb; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3); }
    
    hr { margin: 25px 0; border-color: #f1f5f9; }
</style>

<div class="wrap-profile">
    <div class="cardx">
        <div class="cardx-head">
            <div class="cardx-icon">
                <i class="fa fa-lock"></i>
            </div>
            <h2>Ganti Password</h2>
            <p>Amankan akun Anda dengan password yang kuat.</p>
        </div>
        
        <div class="cardx-body">
            {{-- Form Start --}}
            <form action="{{ route('profile.password.update') }}" method="POST" autocomplete="off">
                {{ csrf_field() }}
                
                <div class="form-group">
                    <label>Password Lama</label>
                    <div class="input-icon-wrap">
                        <i class="fa fa-key"></i>
                        <input type="password" name="current_password" class="form-control" required placeholder="Masukkan password saat ini">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="input-icon-wrap">
                        <i class="fa fa-unlock-alt"></i>
                        <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="Buat password baru">
                    </div>
                    <span class="help-block">Minimal 6 karakter kombinasi huruf & angka.</span>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <div class="input-icon-wrap">
                        <i class="fa fa-check-circle-o"></i>
                        <input type="password" name="new_password_confirmation" class="form-control" required minlength="6" placeholder="Ulangi password baru">
                    </div>
                </div>

                <hr>
                <button type="submit" class="btnx">Update Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
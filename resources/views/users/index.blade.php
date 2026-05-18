@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL USER MANAGEMENT (INDEX) === --}}
@section('help-content')
    <div class="help-alert" style="background:#eff6ff; border-left-color:#2563eb; color:#1e40af;">
        <i class="fa fa-users"></i>
        <strong>PUSAT KENDALI AKSES:</strong> Halaman ini adalah gerbang keamanan sistem. Hanya Super Admin yang dapat menambah, mengubah, atau menghapus pengguna.
    </div>

    <h4 class="help-h"><i class="fa fa-sitemap text-primary"></i> Matriks Hak Akses (Role)</h4>
    <ul class="help-list">
        <li>
            <span class="badge b-super">SUPER ADMIN</span> : 
            Akses Penuh (God Mode). Bisa masuk ke semua menu, termasuk User Management ini. Tidak butuh setting <em>Inventory Scope</em>.
        </li>
        <li>
            <span class="badge b-admin">ADMIN</span> : 
            Staff Gudang. Punya wewenang eksekusi (Approve Request, Buat BON, SO). Wajib diset <em>Inventory Scope</em>-nya (General/Apparel).
        </li>
        <li>
            <span class="badge b-user">USER DEPT</span> : 
            End-user biasa. Hanya bisa membuat Request Barang. Terikat pada satu Departemen tertentu.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-key text-warning"></i> Prosedur Reset Password</h4>
    <p class="help-p">
        Jika ada user yang lupa password, klik tombol kunci kuning <i class="fa fa-key" style="color:#d97706"></i>. 
        <br>Password akan otomatis direset menjadi default: <strong>123456</strong>.
    </p>
@endsection

@section('content')
<style>
    .wrap{ padding:20px; }
    /* Topbar Konsisten dengan Halaman Lain */
    .topbar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .tleft{ display:flex; align-items:center; gap:12px; }
    .iconbox{
        width:44px; height:44px; border-radius:12px;
        background:linear-gradient(135deg,#e0e7ff,#c7d2fe);
        display:flex; align-items:center; justify-content:center;
        color:#4338ca; font-size:18px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.45);
    }
    .ttl h1{ font-size:24px; font-weight:800; color:#0f172a; margin:0; line-height:1.2; }
    .ttl span{ font-size:13px; color:#64748b; font-weight:500; }

    .cardx{ background:#fff; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    table{ width:100%; border-collapse:collapse; }
    th{ background:#f8fafc; padding:12px 16px; text-align:left; font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; border-bottom:1px solid #e2e8f0; }
    td{ padding:12px 16px; border-bottom:1px solid #f1f5f9; font-size:14px; color:#0f172a; vertical-align:middle; }
    
    .badge{ padding:4px 10px; border-radius:99px; font-size:11px; font-weight:800; border:1px solid transparent; }
    .b-super{ background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
    .b-admin{ background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
    .b-user{ background:#f1f5f9; color:#475569; border-color:#cbd5e1; }

    /* --- BUTTON STYLING FIX --- */
    .btnx { 
        padding:9px 16px; 
        border-radius:10px; 
        font-weight:700; 
        font-size:13px; 
        text-decoration:none !important; /* Force no underline */
        display:inline-flex; 
        align-items:center; 
        gap:8px; 
        border:1px solid transparent; 
        cursor:pointer; 
        transition:all 0.2s ease; /* Smooth transition */
    }
    
    .btn-add { 
        background:#2563eb; 
        color:#fff !important; /* Force white text */
        box-shadow:0 4px 6px -1px rgba(37, 99, 235, 0.2); 
    }
    
    .btn-add:hover { 
        background:#1d4ed8; 
        color:#fff !important; 
        transform:translateY(-1px); 
        box-shadow:0 6px 8px -1px rgba(37, 99, 235, 0.3);
        text-decoration: none;
    }
    /* -------------------------- */
    
    .act-btn{ width:34px; height:34px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e2e8f0; color:#64748b; background:#fff; margin-right:4px; transition:.15s; cursor:pointer; text-decoration:none !important; }
    .act-btn:hover{ background:#f8fafc; color:#0f172a; border-color:#cbd5e1; }
    .act-btn.reset:hover{ background:#fffbeb; color:#d97706; border-color:#fcd34d; }
    .act-btn.del:hover{ background:#fef2f2; color:#ef4444; border-color:#fecaca; }
    
    /* Modal Styles */
    .modal-clean .modal-content{ border-radius:16px; border:none; overflow:hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .modal-body-clean{ padding:30px; text-align:center; }
    .icon-box{ width:60px; height:60px; border-radius:50%; margin:0 auto 16px; display:flex; align-items:center; justify-content:center; font-size:24px; }
    .icon-box.warn{ background:#fffbeb; color:#d97706; }
    .icon-box.danger{ background:#fee2e2; color:#ef4444; }
    .m-title{ font-size:18px; font-weight:800; margin-bottom:8px; color:#0f172a; }
    .m-desc{ font-size:14px; color:#64748b; margin-bottom:0; line-height:1.5; }
    .m-foot{ background:#f8fafc; padding:16px 24px; display:grid; grid-template-columns:1fr 1fr; gap:12px; border-top:1px solid #e2e8f0; }
    .btn-m{ padding:10px; border-radius:8px; font-weight:700; border:1px solid #e2e8f0; background:#fff; color:#64748b; text-align:center; cursor:pointer;}
    .btn-m:hover{ background:#f1f5f9; }
    .btn-m.confirm-reset{ background:#f59e0b; color:#fff; border:none; }
    .btn-m.confirm-reset:hover{ background:#d97706; }
    .btn-m.confirm-del{ background:#ef4444; color:#fff; border:none; }
    .btn-m.confirm-del:hover{ background:#dc2626; }
</style>

<div class="wrap">
    <div class="topbar">
        <div class="tleft">
            {{-- ICON BOX --}}
            <div class="iconbox"><i class="fa fa-users"></i></div>
            <div class="ttl">
                <h1>User Management</h1>
                <span>Kelola {{ $users->total() }} akun pengguna terdaftar.</span>
            </div>
        </div>
        <a href="{{ route('users.create') }}" class="btnx btn-add"><i class="fa fa-plus"></i> Tambah User</a>
    </div>

    {{-- ALERT DIHAPUS DARI SINI AGAR TIDAK DOUBLE --}}

    <div class="cardx">
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Nama User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Departemen</th>
                        <th>Scope (Admin)</th>
                        <th style="width:200px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $idx => $u)
                    <tr>
                        <td>{{ $users->firstItem() + $idx }}</td>
                        <td style="font-weight:700;">{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>
                            @if($u->role == 'SUPER_ADMIN') <span class="badge b-super">Super Admin</span>
                            @elseif($u->role == 'ADMIN') <span class="badge b-admin">Admin</span>
                            @else <span class="badge b-user">User Dept</span>
                            @endif
                        </td>
                        <td>{{ $u->department ? $u->department->name : '-' }}</td>
                        <td>
                            @if($u->role == 'ADMIN')
                                <span style="font-size:11px; color:#64748b;">{{ $u->inventory_scope_label }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('users.edit', $u->id) }}" class="act-btn" title="Edit"><i class="fa fa-pencil"></i></a>
                            
                            <button type="button" class="act-btn reset" title="Reset Password" 
                                    onclick="openResetModal('{{ $u->id }}', '{{ $u->name }}')">
                                <i class="fa fa-key"></i>
                            </button>

                            @if(auth()->id() !== $u->id)
                                <button type="button" class="act-btn del" title="Hapus User"
                                        onclick="openDeleteModal('{{ $u->id }}', '{{ $u->name }}')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:15px;">{{ $users->links() }}</div>
    </div>
</div>

{{-- MODAL RESET PASSWORD --}}
<div class="modal fade modal-clean" id="modalReset" tabindex="-1">
    <div class="modal-dialog modal-sm" style="margin-top:10%;">
        <div class="modal-content">
            <div class="modal-body-clean">
                <div class="icon-box warn"><i class="fa fa-lock"></i></div>
                <h3 class="m-title">Reset Password?</h3>
                <p class="m-desc">Password user <strong id="reset-name">...</strong> akan diubah menjadi default: <strong>123456</strong></p>
            </div>
            <div class="m-foot">
                <button type="button" class="btn-m" data-dismiss="modal">Batal</button>
                <form id="form-reset" action="" method="POST" style="display:contents;">
                    {{ csrf_field() }}
                    <button type="submit" class="btn-m confirm-reset">Ya, Reset</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DELETE --}}
<div class="modal fade modal-clean" id="modalDelete" tabindex="-1">
    <div class="modal-dialog modal-sm" style="margin-top:10%;">
        <div class="modal-content">
            <div class="modal-body-clean">
                <div class="icon-box danger"><i class="fa fa-trash"></i></div>
                <h3 class="m-title">Hapus User?</h3>
                <p class="m-desc">Akun <strong id="del-name">...</strong> akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.</p>
            </div>
            <div class="m-foot">
                <button type="button" class="btn-m" data-dismiss="modal">Batal</button>
                <form id="form-delete" action="" method="POST" style="display:contents;">
                    {{ csrf_field() }} {{ method_field('DELETE') }}
                    <button type="submit" class="btn-m confirm-del">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openResetModal(id, name) {
        $('#reset-name').text(name);
        $('#form-reset').attr('action', '/users/' + id + '/reset-password');
        $('#modalReset').modal('show');
    }
    function openDeleteModal(id, name) {
        $('#del-name').text(name);
        $('#form-delete').attr('action', '/users/' + id);
        $('#modalDelete').modal('show');
    }
</script>
@endsection
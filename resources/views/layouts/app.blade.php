<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Inventori Teknik - PT. OTTO PHARMACEUTICAL</title>

    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
    
    {{-- Google Font (Roboto Flex & JetBrains Mono) --}}
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,300;8..144,400;8..144,500;8..144,600;8..144,700;8..144,800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #2d3436;       /* Deep Charcoal */
            --primary-dark: #181f21;  /* Primary dark */
            --sidebar-from: #2d3436;  /* Gradasi Atas */
            --sidebar-to: #181f21;    /* Gradasi Bawah */
            --text-muted: #747879;    /* Outline / muted */
            --bg-body: #fcf9f8;       /* Surface Background */
            --warning: #ff9f43;       /* Industrial Orange */
            --success: #27ae60;       /* Forest Green */
        }

        body {
            font-family: 'Roboto Flex', sans-serif;
            background-color: var(--bg-body);
            overflow-x: hidden;
            margin: 0; padding: 0;
            font-size: 14px;
        }

        /* === WRAPPER LAYOUT === */
        #wrapper {
            padding-left: 0;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            width: 100%;
        }

        /* === SIDEBAR (PREMIUM LOOK) === */
        #sidebar-wrapper {
            width: 260px;
            background: var(--sidebar-from);
            background: linear-gradient(180deg, var(--sidebar-from) 0%, var(--sidebar-to) 100%);
            position: fixed;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            left: 260px;
            margin-left: -260px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
        }

        /* Custom Scrollbar Sidebar */
        #sidebar-wrapper::-webkit-scrollbar { width: 5px; }
        #sidebar-wrapper::-webkit-scrollbar-track { background: transparent; }
        #sidebar-wrapper::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        #sidebar-wrapper::-webkit-scrollbar-thumb:hover { background: #475569; }

        /* Sidebar Brand */
        .sidebar-brand {
            height: 70px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            background: rgba(0,0,0,0.2);
            color: #fff;
            font-weight: 800;
            font-size: 16px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar-brand i { 
            color: var(--warning); 
            margin-right: 12px; 
            font-size: 22px;
            text-shadow: 0 0 15px rgba(255, 159, 67, 0.4);
        }

        /* Sidebar Menu */
        .sidebar-nav { list-style: none; padding: 15px 0; margin: 0; }
        
        /* Menu Header (Label) - Compact */
        .sidebar-header {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 800;
            padding: 10px 24px 5px 24px;
            letter-spacing: 1.2px;
            margin-top: 0px;
        }

        .sidebar-nav li a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-muted);
            padding: 13px 24px;
            font-size: 14px;
            font-weight: 500;
            border-left: 4px solid transparent;
            transition: all 0.2s ease-in-out;
        }
        
        .sidebar-nav li a i { 
            width: 24px; 
            text-align: center; 
            margin-right: 12px; 
            font-size: 16px; 
            transition: transform 0.2s;
        }

        /* Hover Effect */
        .sidebar-nav li a:hover {
            background: rgba(255,255,255,0.03);
            color: #f8fafc;
            padding-left: 28px;
        }
        .sidebar-nav li a:hover i {
            transform: scale(1.15);
            color: var(--warning);
        }

        /* Active State */
        .sidebar-nav li.active a {
            background: linear-gradient(90deg, rgba(255, 159, 67, 0.15) 0%, transparent 100%);
            color: #fff;
            border-left-color: var(--warning);
            font-weight: 600;
        }
        .sidebar-nav li.active a i { color: var(--warning); }

        /* Logic Toggle */
        #wrapper.toggled #sidebar-wrapper { width: 260px; }
        @media(min-width: 768px) {
            #wrapper { padding-left: 260px; }
            #sidebar-wrapper { width: 260px; left: 260px; }
            #wrapper.toggled { padding-left: 0; }
            #wrapper.toggled #sidebar-wrapper { width: 0; }
        }

        /* === TOP NAVIGATION (ATAS) === */
        #page-content-wrapper { width: 100%; min-width: 0; position: relative; transition: all 0.3s ease; }
        
        .top-navbar {
            background: #fff;
            height: 65px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .btn-toggle-menu {
            background: transparent; border: none; font-size: 20px; color: #475569; cursor: pointer; padding: 5px; transition: color 0.2s;
        }
        .btn-toggle-menu:hover { color: var(--primary); }

        /* === HELP BUTTON STYLING (FIXED HOVER/FOCUS STATE) === */
        .btn-help-nav {
            display: flex; align-items: center; gap: 8px;
            background: #f1f3f4; color: var(--primary); /* Default: Gray */
            font-weight: 700; font-size: 13px;
            padding: 8px 16px; border-radius: 4px;
            text-decoration: none !important;
            cursor: pointer;
            transition: all 0.2s ease; margin-right: 20px;
            border: 1px solid #dfe6e9;
            outline: none; /* Hapus outline browser */
        }

        /* HANYA berubah jadi Gelap saat HOVER atau saat di-KLIK (Active) */
        .btn-help-nav:hover, 
        .btn-help-nav:active { 
            background: var(--primary); 
            color: #fff; 
            border-color: var(--primary); 
            transform: translateY(-1px); 
            box-shadow: 0 4px 12px rgba(45, 52, 54, 0.25);
            text-decoration: none !important;
        }

        /* FIX: Saat Focus (lepas klik/modal close), KEMBALI ke style DEFAULT */
        .btn-help-nav:focus {
            background: #f1f3f4;
            color: var(--primary);
            border-color: #dfe6e9;
            text-decoration: none !important;
            outline: none;
            box-shadow: none; 
            transform: none;
        }

        /* Exception: Jika user masih Hover sambil Focus */
        .btn-help-nav:focus:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(45, 52, 54, 0.25);
        }

        .user-info {
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1.2;
        }

        .user-name {
            font-size: 13px;
            font-weight: 600;
        }

        .user-scope {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 18px;
        }

        /* === MAIN CONTENT === */
        .main-content { padding: 25px; min-height: calc(100vh - 65px); }

        /* Global Styles Override */
        .panel { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-radius: 10px; }
        .panel-heading { background: #fff !important; border-bottom: 1px solid #f1f5f9; padding: 15px 20px; border-radius: 10px 10px 0 0; font-weight: 700; }
        
        /* SweetAlert Custom Font */
        .swal2-popup { font-family: 'Roboto Flex', sans-serif; border-radius: 4px; }

        /* === HELP MODAL STYLING (OVERKILL UI) === */
        .modal-help-content { border-radius: 4px; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.2); font-family: 'Roboto Flex', sans-serif; }
        .modal-help-header { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); padding: 25px 30px; border: none; position: relative; }
        .modal-help-title { color: #fff; font-weight: 800; font-size: 20px; margin: 0; display: flex; align-items: center; gap: 10px; }
        .modal-help-close { position: absolute; top: 20px; right: 20px; color: rgba(255,255,255,0.6); font-size: 24px; cursor: pointer; transition: 0.2s; }
        .modal-help-close:hover { color: #fff; transform: rotate(90deg); }
        .help-body { padding: 30px; min-height: 300px; max-height: 70vh; overflow-y: auto; }
        
        /* Typography Content */
        .help-h { font-size: 16px; font-weight: 800; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
        .help-p { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 16px; }
        .help-list { padding-left: 0; list-style: none; margin-bottom: 20px; }
        .help-list li { position: relative; padding-left: 24px; margin-bottom: 10px; color: #334155; font-size: 13px; font-weight: 500; line-height: 1.5; }
        .help-list li:before { content: "\f058"; font-family: FontAwesome; position: absolute; left: 0; top: 1px; color: #10b981; font-size: 14px; }
        .help-alert { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 6px; color: #92400e; font-size: 13px; margin-bottom: 20px; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Mobile */
        @media(max-width:767px) {
            #wrapper { padding-left: 0; }
            #sidebar-wrapper { width: 0; }
            #wrapper.toggled #sidebar-wrapper { width: 260px; margin-left: 0; }
            .main-content { padding: 15px; }
            .top-navbar { padding: 0 15px; }
            .btn-help-nav span { display: none; } /* Icon only di mobile */
        }
    </style>
</head>
<body>

    <div id="wrapper">
        <nav id="sidebar-wrapper">
            <div class="sidebar-brand">
                <i class="fa fa-cubes"></i> INVENTORY TEKNIK
            </div>
            <ul class="sidebar-nav">
                
                @php
                    $authUser = Auth::user();
                    $isUser   = ($authUser && $authUser->role === 'USER');
                @endphp

                @if(!$isUser)
                    <li class="sidebar-header">UTAMA</li>
                    <li class="{{ Request::is('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}">
                            <i class="fa fa-tachometer"></i> <span>Dashboard</span>
                        </a>
                    </li>

                    <li class="sidebar-header">MASTER DATA</li>
                    <li class="{{ Request::is('items*') ? 'active' : '' }}">
                        <a href="{{ route('items.index') }}">
                            <i class="fa fa-archive"></i> <span>Daftar Barang</span>
                        </a>
                    </li>

                    <!-- <li class="{{ Request::is('budgets*') ? 'active' : '' }}">
                        <a href="{{ route('budgets.index') }}">
                            <i class="fa fa-sliders"></i> 
                            <span>Atur Plafon Dept.</span>
                        </a>
                    </li> -->
                    <li class="{{ Request::is('thresholds*') ? 'active' : '' }}">
                        <a href="{{ route('thresholds.index') }}">
                            <i class="fa fa-sliders"></i> 
                            <span>Atur TrashHold.</span>
                        </a>
                    </li>

                    <li class="{{ Request::is('departments*') ? 'active' : '' }}">
                        <a href="{{ route('departments.index') }}">
                            <i class="fa fa-building-o"></i>
                            <span>Master Departemen</span>
                        </a>
                    </li>

                    <li class="{{ Request::is('category*') ? 'active' : '' }}">
                        <a href="{{ route('category.index') }}">
                            <i class="fa fa-tags"></i>
                            <span>Master Kategori</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-header">INVENTORY CONTROL</li>
                    <li class="{{ Request::is('lpbs*') ? 'active' : '' }}">
                        <a href="{{ route('lpbs.index') }}">
                            <i class="fa fa-download"></i> <span>LPB (Masuk)</span>
                        </a>
                    </li>
                    <li class="{{ Request::is('bons*') ? 'active' : '' }}">
                        <a href="{{ route('bons.index') }}">
                            <i class="fa fa-upload"></i> <span>BON (Keluar)</span>
                        </a>
                    </li>
                    
                    <!-- di nonaktifkan untuk teknik karena langsung masuk ke bon permintaan -->
                    <!-- <li class="{{ Request::is('requests*') ? 'active' : '' }}">
                        <a href="{{ route('requests.index') }}" style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center;">
                                <i class="fa fa-inbox"></i> <span>Permintaan Barang</span>
                            </div>
                            
                            @if(isset($globalPendingRequestCount) && $globalPendingRequestCount > 0)
                                <span style="background: #ef4444; color: white; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 99px; box-shadow: 0 2px 4px rgba(239,68,68,0.3); line-height: 1.2;">
                                    {{ $globalPendingRequestCount }}
                                </span>
                            @endif
                        </a>
                    </li> -->

                    <li class="{{ Request::is('stock-opnames*') ? 'active' : '' }}">
                        <a href="{{ route('stock-opnames.index') }}">
                            <i class="fa fa-balance-scale"></i> <span>Stock Opname</span>
                        </a>
                    </li>
                    
                    <li class="{{ Request::is('buffer-alerts*') ? 'active' : '' }}">
                        <a href="{{ route('buffer-alerts.index') }}">
                            <i class="fa fa-bell"></i> <span>Monitor Stok</span>
                        </a>
                    </li>

                    <li class="{{ Request::is('purchase-orders*') ? 'active' : '' }}">
                        <a href="{{ route('purchase-orders.index') }}">
                            <i class="fa fa-shopping-bag"></i> <span>Purchase Order</span>
                        </a>
                    </li>

                    <li class="sidebar-header">LAPORAN</li>
                    <li class="{{ Request::is('reports*') ? 'active' : '' }}">
                        <a href="{{ route('reports.index') }}">
                            <i class="fa fa-file-text-o"></i> <span>Pusat Laporan</span>
                        </a>
                    </li>
                @endif

                @if($isUser)
                    <li class="sidebar-header">MENU UTAMA</li>
                    <li class="{{ Request::is('requests*') ? 'active' : '' }}">
                        <a href="{{ route('requests.index') }}">
                            <i class="fa fa-shopping-cart"></i> <span>Buat Permintaan</span>
                        </a>
                    </li>
                    <li class="{{ Request::is('catalog*') ? 'active' : '' }}">
                        <a href="{{ route('catalog.index') }}">
                            <i class="fa fa-book"></i> <span>Katalog Barang</span>
                        </a>
                    </li>
                @endif

                <li class="sidebar-header">SYSTEM</li>

                @if(auth()->check() && auth()->user()->role === 'SUPER_ADMIN')
                    <li class="{{ Request::is('users*') ? 'active' : '' }}">
                        <a href="{{ route('users.index') }}">
                            <i class="fa fa-users"></i> <span>User Management</span>
                        </a>
                    </li>
                @endif

                <li class="{{ Request::is('profile*') ? 'active' : '' }}">
                    <a href="{{ route('profile.edit') }}">
                        <i class="fa fa-user-circle"></i> <span>Profil Saya</span>
                    </a>
                </li>

                <li>
                    <a href="{{ url('/logout') }}">
                        <i class="fa fa-sign-out"></i> <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div id="page-content-wrapper">
            <nav class="top-navbar">
                <button class="btn-toggle-menu" id="menu-toggle">
                    <i class="fa fa-bars"></i>
                </button>

                <div style="flex:1;"></div>

                {{-- === TOMBOL BANTUAN DISINI === --}}
                <a href="#" class="btn-help-nav" data-toggle="modal" data-target="#contextHelpModal">
                    <i class="fa fa-question-circle"></i> <span>Bantuan & Panduan</span>
                </a>

                <div class="user-info">
                    @if ($authUser)
                        <div class="user-meta">
                            <div class="user-name">{{ $authUser->name }}</div>
                            <div class="user-scope">
                                @if ($authUser->role === 'USER')
                                    Department Staff
                                @elseif ($authUser->inventory_scope === \App\User::SCOPE_GENERAL)
                                    Admin General (ATK/Kebersihan)
                                @elseif ($authUser->inventory_scope === \App\User::SCOPE_APPAREL)
                                    Admin Apparel (Seragam/Sepatu)
                                @else
                                    Super Admin
                                @endif
                            </div>
                        </div>
                        <div class="user-avatar"><i class="fa fa-user"></i></div>
                    @else
                        <span>Inventori HR</span>
                        <div class="user-avatar"><i class="fa fa-user"></i></div>
                    @endif
                </div>
            </nav>

            <div class="main-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible" role="alert" style="border-radius:8px; border:none; background:#dcfce7; color:#166534; box-shadow:0 1px 2px rgba(0,0,0,0.05); position: relative;">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); opacity: 0.5;"><span aria-hidden="true">&times;</span></button>
                        <i class="fa fa-check-circle"></i> <strong>Berhasil!</strong> {!! session('success') !!}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible" role="alert" style="border-radius:8px; border:none; background:#fee2e2; color:#991b1b; box-shadow:0 1px 2px rgba(0,0,0,0.05); position: relative;">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); opacity: 0.5;"><span aria-hidden="true">&times;</span></button>
                        <i class="fa fa-exclamation-triangle"></i> <strong>Error!</strong> {!! session('error') !!}
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    {{-- === CONTEXTUAL HELP MODAL (GLOBAL WRAPPER) === --}}
    <div class="modal fade" id="contextHelpModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content modal-help-content">
                <div class="modal-help-header">
                    <div class="modal-help-title">
                        <i class="fa fa-book"></i> Pusat Bantuan
                    </div>
                    <div class="modal-help-close" data-dismiss="modal">
                        <i class="fa fa-times"></i>
                    </div>
                </div>

                <div class="help-body">
                    @hasSection('help-content')
                        {{-- Jika View Anak punya section 'help-content', tampilkan disini --}}
                        @yield('help-content')
                    @else
                        {{-- Fallback jika tidak ada panduan khusus --}}
                        <div style="text-align:center; padding:40px 0;">
                            <img src="https://img.icons8.com/color/96/000000/info--v1.png" style="margin-bottom:15px; opacity:0.8;">
                            <h4 class="help-h" style="justify-content:center;">Selamat Datang di Inventory HR</h4>
                            <p class="help-p">
                                Silakan gunakan menu di samping kiri untuk navigasi.<br>
                                Jika Anda bingung, hubungi Admin HR untuk bantuan lebih lanjut.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>


        $(document).ready(function() {
            $("#menu-toggle").click(function(e) {
                e.preventDefault();
                $("#wrapper").toggleClass("toggled");
            });

            $.extend( true, $.fn.dataTable.defaults, {
                "language": { "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json" },
                "autoWidth": false,
                "responsive": true
            });
            
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true, orientation: "bottom auto"
            });

            $('body').on('click', '.delete-confirm', function(e) {
                e.preventDefault();
                var form = $(this).closest('form');
                var itemName = $(this).data('name') || 'data ini';
                
                Swal.fire({
                    title: 'Hapus Data?',
                    text: "Anda akan menghapus " + itemName + ". Tindakan ini tidak bisa dibatalkan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) { form.submit(); }
                });
            });

            // Auto-hide Flash Messages
            setTimeout(function() {
                $(".alert:not(.alert-important)").fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        });
    </script>

    @yield('scripts')
</body>
</html>
<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fa fa-cubes"></i> <span class="hidden-xs">Inventori Umum</span>
            </a>
        </div>
        <div class="collapse navbar-collapse" id="navbar-collapse">
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                        <i class="fa fa-bell"></i> 
                        <span class="badge">{{ $criticalItemsCount ?? 0 }}</span> 
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu notifications-menu">
                        <li class="dropdown-header">Notification</li>
                        @if(isset($criticalItems) && count($criticalItems) > 0)
                            @foreach($criticalItems as $item)
                                <li>
                                    <a href="{{ route('items.show', $item->id) }}">
                                        <div class="notification-icon bg-danger">
                                            <i class="fa fa-exclamation"></i>
                                        </div>
                                        <div class="notification-content">
                                            <p class="notification-title">{{ $item->name }}</p>
                                            <p class="notification-text">Stok: {{ $item->current_stock }} (Buffer: {{ $item->buffer_min }})</p>
                                            <small class="text-{{ $item->buffer_level == 'critical' ? 'danger' : 'warning' }}">
                                                {{ $item->buffer_level == 'critical' ? 'Stok Kritis' : 'Stok Menipis' }}
                                            </small>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li><a href="#" class="text-center">Tidak ada notifikasi</a></li>
                        @endif
                        <li class="divider"></li>
                        <li><a href="{{ route('buffer-alerts.index') }}" class="text-center">Lihat Semua Notifikasi</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                        <i class="fa fa-user"></i> {{ Auth::check() ? Auth::user()->name : 'Guest' }} <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="#"><i class="fa fa-user"></i> Profil</a></li>
                        <li><a href="#"><i class="fa fa-cog"></i> Pengaturan</a></li>
                        <li role="separator" class="divider"></li>
                        <li>
                            @if(Auth::check())
                                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fa fa-sign-out"></i> Keluar
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    {{ csrf_field() }}
                                </form>
                            @else
                                <a href="{{ route('login') }}">
                                    <i class="fa fa-sign-in"></i> Login
                                </a>
                            @endif
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
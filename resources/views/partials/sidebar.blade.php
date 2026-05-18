<ul class="nav nav-sidebar">
    <li class="{{ Request::is('dashboard') ? 'active' : '' }}">
        <a href="{{ route('dashboard') }}">
            <i class="fa fa-dashboard"></i> Dashboard
        </a>
    </li>
    <li class="nav-divider"></li>
    <li class="{{ Request::is('items*') ? 'active' : '' }}">
        <a href="{{ route('items.index') }}">
            <i class="fa fa-cube"></i> Daftar Item
        </a>
    </li>
    <li class="{{ Request::is('lpbs*') ? 'active' : '' }}">
        <a href="{{ route('lpbs.index') }}">
            <i class="fa fa-arrow-circle-down"></i> LPB Masuk
        </a>
    </li>
    <li class="{{ Request::is('bons*') ? 'active' : '' }}">
        <a href="{{ route('bons.index') }}">
            <i class="fa fa-arrow-circle-up"></i> BON Keluar
        </a>
    </li>
    <li class="{{ Request::is('stock-opnames*') ? 'active' : '' }}">
        <a href="{{ route('stock-opnames.index') }}">
            <i class="fa fa-balance-scale"></i> Stock Opname
        </a>
    </li>
    <li class="nav-divider"></li>
    <li class="{{ Request::is('buffer-alerts*') ? 'active' : '' }}">
        <a href="{{ route('buffer-alerts.index') }}">
            <i class="fa fa-bell"></i> Peringatan Stok
        </a>
    </li>
    <li class="{{ Request::is('reports*') ? 'active' : '' }}">
        <a href="{{ route('reports.index') }}">
            <i class="fa fa-file-text"></i> Laporan
        </a>
    </li>
    <li class="nav-divider"></li>
    <li>
        <a href="{{ route('import-excel') }}" class="text-muted">
            <i class="fa fa-download"></i> Import Data
        </a>
    </li>
    <li>
        <a href="#" class="text-muted">
            <i class="fa fa-question-circle"></i> Bantuan
        </a>
    </li>
</ul>
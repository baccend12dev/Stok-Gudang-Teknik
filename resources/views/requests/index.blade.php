@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL HALAMAN REQUEST (DAFTAR PERMINTAAN) === --}}
@section('help-content')
    @php
        $role = auth()->user()->role;
        $isUser = ($role === 'USER');
    @endphp

    <div class="help-alert">
        <i class="fa fa-clock-o"></i>
        <strong>TIMELINE PENGADAAN:</strong> Permintaan yang Anda buat bulan ini akan direkap untuk proses PR (Purchase Requisition) dan dipenuhi saat barang datang di <strong>Bulan Depan</strong>.
    </div>

    @if($isUser)
        <h4 class="help-h"><i class="fa fa-user-circle-o text-primary"></i> Panduan Singkat Pemohon</h4>
        <ul class="help-list">
            <li>
                <strong>Langkah 1:</strong> Klik tombol biru <strong>"Buat Request"</strong> di pojok kanan atas.
            </li>
            <li>
                <strong>Langkah 2:</strong> Isi daftar barang kebutuhan departemen Anda untuk 1 bulan ke depan.
                <br><em style="font-size:11px; color:#64748b;">(Hanya bisa diisi tanggal 1-7 setiap bulannya).</em>
            </li>
            <li>
                <strong>Langkah 3:</strong> Pantau status di tabel. Jika status <strong>APPROVED</strong>, berarti permintaan sudah masuk antrian pengadaan. Tunggu info pengambilan barang dari Admin bulan depan.
            </li>
        </ul>
    @endif

    <h4 class="help-h"><i class="fa fa-flag text-primary"></i> Arti Status Request</h4>
    <ul class="help-list">
        <li>
            <span class="badge b-open">OPEN</span> : 
            Permintaan baru dibuat. Belum divalidasi oleh Admin.
        </li>
        <li>
            <span class="badge b-approved">APPROVED</span> : 
            Permintaan sudah disetujui Admin dan masuk dalam rekap PR (Pre-Order). Menunggu barang datang.
        </li>
        <li>
            <span class="badge b-partial">PARTIAL</span> : 
            Barang sudah datang sebagian dan sudah dibuatkan BON. Sebagian lagi masih menunggu (Backorder).
        </li>
        <li>
            <span class="badge b-closed">CLOSED</span> : 
            <strong>Selesai.</strong> Barang sudah diterima penuh (Full) ATAU sisa barang dianggap hangus karena sudah ganti periode (Tutup Buku).
        </li>
    </ul>

    @if(!$isUser)
        <h4 class="help-h"><i class="fa fa-cogs text-danger"></i> Area Admin (Procurement & Closing)</h4>
        <ul class="help-list">
            <li>
                <strong>Rekap Kebutuhan:</strong> Gunakan tombol "Rekap Kebutuhan" untuk menarik data Excel sebagai dasar pembuatan PR ke Purchasing.
            </li>
            <li>
                <strong>Tutup Periode (Closing):</strong> 
                Tombol merah ini berfungsi untuk membersihkan sisa data lama (Cut-Off). Anda memiliki <strong>Otoritas Penuh</strong> untuk menentukan kapan penutupan dilakukan sesuai kebijakan internal.
                <br>
                <div style="margin-top:6px; background:#fff1f2; border:1px solid #fecaca; padding:8px; border-radius:6px; font-size:12px; color:#991b1b;">
                    <strong>Cara Kerja Sistem:</strong><br>
                    Saat tombol ditekan, sistem HANYA akan menutup request dari <strong>BULAN LALU</strong> (Backlog). Request di bulan berjalan (Current Month) <strong>TIDAK AKAN TERTUTUP</strong> dan tetap aman.<br>
                    <em>Contoh: Klik di akhir Januari akan menutup sisa data Desember. Data Januari tetap aktif.</em>
                </div>
            </li>
        </ul>
    @endif
@endsection

@section('content')
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    /* --- CORE VARIABLES & RESET --- */
    :root{
        --bg:#f1f5f9; --card:#fff; --border:#e2e8f0;
        --text:#0f172a; --muted:#64748b;
        --primary:#2563eb; --radius:12px; --shadow:0 1px 3px rgba(0,0,0,.06);
        --danger: #ef4444;
    }
    body{ background:var(--bg); color:var(--text); }
    .wrap{ padding:14px 22px 30px; }

    /* --- TOPBAR --- */
    .topbar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap: wrap; gap: 10px; }
    .tleft{ display:flex; align-items:center; gap:12px; }
    .iconbox{
        width:44px; height:44px; border-radius:12px;
        background:linear-gradient(135deg,#e0e7ff,#c7d2fe);
        display:flex; align-items:center; justify-content:center;
        color:#4338ca; font-size:18px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.45);
    }
    .tt h1{ margin:0; font-size:20px; font-weight:800; letter-spacing:-.2px; }
    .tt p{ margin:2px 0 0; font-size:12px; color:var(--muted); }

    /* --- BUTTONS --- */
    .btnx{
        background:#fff; border:1px solid var(--border); color:var(--text);
        padding:9px 14px; border-radius:10px; font-weight:800; font-size:13px;
        display:inline-flex; align-items:center; gap:8px;
        text-decoration:none !important; box-shadow:var(--shadow);
        transition:.15s; cursor: pointer;
    }
    .btnx:hover{ background:#f8fafc; transform:translateY(-1px); }
    
    /* PRIMARY STYLE (BIRU TERANG - Untuk Tombol Cari & Buat Request) */
    .btnx.primary{
        background:#2563eb; border-color:#2563eb; color:#fff;
        box-shadow:0 8px 16px rgba(37,99,235,.18);
    }
    .btnx.primary:hover{ background:#1d4ed8; }
    
    /* RECAP BUTTON STYLE */
    .btnx.recap {
        background: #2563eb; border-color: #2563eb; color: #fff;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    }
    .btnx.recap:hover { background: #1d4ed8; }

    /* --- CARD & FILTERS --- */
    .cardx{
        background:var(--card); border:1px solid var(--border);
        border-radius:var(--radius); box-shadow:var(--shadow);
        overflow:hidden; margin-bottom:12px;
    }
    .cardx-h{
        padding:14px 18px; border-bottom:1px solid var(--border);
        display:flex; justify-content:space-between; align-items:center;
    }
    .cardx-h .ttl{ margin:0; font-size:14px; font-weight:900; color:#0f172a; }
    .cardx-b{ padding:14px 18px; }

    .lbl{
        display:block; font-size:11px; font-weight:900; color:var(--muted);
        text-transform:uppercase; letter-spacing:.35px; margin-bottom:6px;
    }
    .ctl{
        width:100%; height:40px; border:1px solid #cbd5e1; border-radius:10px;
        padding:8px 10px; background:#fff;
    }
    .ctl:focus{ outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.12); }

    .filter-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
    .filter-item { flex: 1 1 150px; min-width: 140px; }
    .filter-search { flex: 2 1 300px; } 
    .filter-actions { display: flex; gap: 8px; }

    /* --- TABLE --- */
    table{ width:100%; border-collapse:collapse; table-layout: fixed; }
    thead th{
        background:#f8fafc; border-bottom:1px solid var(--border);
        padding:12px 12px; font-size:11px; font-weight:900; color:var(--muted);
        text-transform:uppercase; letter-spacing:.35px; white-space: nowrap;
    }
    tbody td{
        padding:12px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle;
        font-size:13px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    tbody tr.master-row:hover td{ background:#fbfdff; }

    .text-center { text-align: center !important; }
    .text-right { text-align: right !important; }

    .badge{
        display:inline-flex; align-items:center; gap:6px;
        padding:5px 10px; border-radius:999px; font-size:11px; font-weight:900;
        border:1px solid transparent;
    }
    .b-open{ background:#fffbeb; color:#92400e; border-color:#fde68a; }
    .b-approved{ background:#ecfdf5; color:#047857; border-color:#d1fae5; }
    .b-partial{ background:#eff6ff; color:#1d4ed8; border-color:#dbeafe; }
    .b-closed{ background:#f1f5f9; color:#0f172a; border-color:#e2e8f0; }
    .b-rejected{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
    .b-cancel{ background:#f1f5f9; color:#334155; border-color:#e2e8f0; }

    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    .muted{ color:#64748b; font-size:12px; }
    .user-email { display: block; font-size: 11px; color: var(--muted); margin-top: 2px; font-weight: 500; }

    .actions{ display:flex; gap:6px; justify-content:center; } 
    .a-btn{
        padding:6px 10px; border-radius:8px; border:1px solid var(--border);
        background:#fff; font-weight:900; font-size:12px; color: #475569;
        display:inline-flex; align-items:center; gap:6px;
        text-decoration:none !important; transition: .15s;
        cursor: pointer;
    }
    .a-btn:hover{ background:#f8fafc; color: #0f172a; border-color: #cbd5e1; }
    .a-btn.edit:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
    .a-btn.del:hover { background: #fef2f2; color: #ef4444; border-color: #fecaca; }

    .td-notes { white-space: normal !important; line-height: 1.4; font-size: 12px; color: #64748b; }

    /* --- EXPANDABLE ROW STYLES --- */
    .btn-expand { 
        background: none; border: none; cursor: pointer; color: var(--primary); font-size: 16px; 
        padding: 5px; outline: none; display: flex; align-items: center; justify-content: center;
        width: 24px; height: 24px; border-radius: 50%;
        transition: all 0.2s ease;
    }
    .btn-expand:hover { background: #eff6ff; }
    .btn-expand i { line-height: 1; display: block; }
    .btn-expand.open { color: var(--danger); background: #fef2f2; }
    
    .detail-row { display: none; background-color: #f8fafc; }
    .detail-box { 
        padding: 15px 20px; margin: 10px 20px 10px 60px;
        background: white; border-radius: 8px; border: 1px solid #e2e8f0; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.02); 
    }
    .detail-table { width: 100%; font-size: 12px; border-collapse: collapse; }
    .detail-table th { background: #f1f5f9; color: #64748b; padding: 8px 12px; font-weight: 700; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
    .detail-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .detail-table tr:last-child td { border-bottom: none; }

    /* --- MODERN MODAL --- */
    .modal-clean { border-radius: 16px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; }
    .modal-head-clean { padding: 24px 24px 0; background: white; border: none; text-align: center; }
    .modal-icon-box {
        width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        margin: 0 auto 10px; font-size: 28px;
    }
    .modal-icon-box.danger { background: #fee2e2; color: #ef4444; }
    .modal-body-clean { padding: 0 30px 24px; text-align: center; }
    .modal-title-clean { font-size: 18px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
    .modal-desc-clean { font-size: 14px; color: var(--muted); line-height: 1.5; }
    .modal-desc-clean strong { color: var(--text); font-weight: 700; }
    .modal-foot-clean {
        background: #f8fafc; padding: 16px 24px; border-top: 1px solid var(--border);
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    }
    .btn-modal { 
        padding: 10px; border-radius: 10px; font-weight: 700; font-size: 14px; 
        border: 1px solid var(--border); cursor: pointer; text-align: center;
    }
    .btn-modal.cancel { background: #fff; color: var(--muted); }
    .btn-modal.cancel:hover { background: #f1f5f9; color: var(--text); }
    .btn-modal.confirm { background: #ef4444; color: #fff; border-color: #dc2626; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25); border: none;}
    .btn-modal.confirm:hover { background: #dc2626; transform: translateY(-1px); }
    .modal-dialog { margin-top: 10vh; }
</style>

<div class="wrap">

    <div class="topbar">
        <div class="tleft">
            <div class="iconbox"><i class="fa fa-inbox"></i></div>
            <div class="tt">
                <h1>Daftar Request</h1>
                <p>Gunakan filter untuk mempercepat pencarian request.</p>
            </div>
        </div>

        <div style="display: flex; gap: 10px;">
            @if(auth()->user()->role !== 'USER')
                {{-- TOMBOL TUTUP PERIODE (The Purge) --}}
                <form id="form-close-period" action="{{ route('requests.closePeriod') }}" method="POST" style="display:inline-block;">
                    {{ csrf_field() }}
                    <button type="button" class="btnx" onclick="openClosePeriodModal()" 
                            style="background:#ef4444; border-color:#ef4444; color:#fff; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2);">
                        <i class="fa fa-gavel"></i> Tutup Periode
                    </button>
                </form>

                <a href="{{ route('requests.recap') }}" class="btnx recap">
                    <i class="fa fa-shopping-basket"></i> Rekap Kebutuhan
                </a>
            @endif

            {{-- FIX: SUPER_ADMIN JUGA BISA CREATE REQUEST (EMERGENCY MODE) --}}
            @if(auth()->user()->role === 'USER' || auth()->user()->role === 'SUPER_ADMIN')
                <a href="{{ route('requests.create') }}" class="btnx primary">
                    <i class="fa fa-plus"></i> Buat Request
                </a>
            @endif
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="cardx">
        <div class="cardx-h">
            <h3 class="ttl"><i class="fa fa-filter" style="color:#2563eb;"></i> Filter</h3>
        </div>
        <div class="cardx-b">
            <form method="GET" action="{{ route('requests.index') }}">
                <div class="filter-row">
                    @php
                        // LOGIC TANGGAL DEFAULT (DINAMIS)
                        $defaultFrom = date('Y-m-01');
                        $defaultTo   = date('Y-m-d'); // Hari ini
                        
                        $valFrom = request('from', $defaultFrom);
                        $valTo   = request('to', $defaultTo);
                    @endphp

                    <div class="filter-item">
                        <label class="lbl">Dari</label>
                        <input type="text" name="from" class="ctl js-date"
                               value="{{ $valFrom }}" placeholder="dd/mm/yyyy">
                    </div>
                    <div class="filter-item">
                        <label class="lbl">Sampai</label>
                        <input type="text" name="to" class="ctl js-date"
                               value="{{ $valTo }}" placeholder="dd/mm/yyyy">
                    </div>
                    <div class="filter-item">
                        <label class="lbl">Status</label>
                        <select name="status" class="ctl">
                            @php $st = strtoupper(request('status','ALL')); @endphp
                            <option value="ALL" {{ $st=='ALL'?'selected':'' }}>ALL</option>
                            <option value="OPEN" {{ $st=='OPEN'?'selected':'' }}>OPEN</option>
                            <option value="APPROVED" {{ $st=='APPROVED'?'selected':'' }}>APPROVED</option>
                            <option value="PARTIAL" {{ $st=='PARTIAL'?'selected':'' }}>PARTIAL</option>
                            <option value="CLOSED" {{ $st=='CLOSED'?'selected':'' }}>CLOSED</option>
                            <option value="REJECTED" {{ $st=='REJECTED'?'selected':'' }}>REJECTED</option>
                            <option value="CANCELLED" {{ $st=='CANCELLED'?'selected':'' }}>CANCELLED</option>
                        </select>
                    </div>
                    <div class="filter-item filter-search">
                        <label class="lbl">Cari No. Request</label>
                        <input type="text" name="search" class="ctl"
                               value="{{ request('search','') }}" placeholder="REQ/202512/0001">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btnx primary" style="height:40px;">
                            <i class="fa fa-search"></i> Cari
                        </button>
                        <a href="{{ route('requests.index') }}" class="btnx" style="height:40px;">
                            <i class="fa fa-refresh"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="cardx">
        <div class="cardx-h">
            <h3 class="ttl"><i class="fa fa-list" style="color:#2563eb;"></i> Data</h3>
            <div class="muted">
                Total halaman: {{ $requests->lastPage() }} • Total data: {{ $requests->total() }}
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width:30px; text-align:center;"><i class="fa fa-list-ul"></i></th>
                        <th style="width:110px;">No Request</th>
                        <th style="width:90px;" class="text-center">Tanggal</th>
                        <th style="width:100px;" class="text-center">Departemen</th>
                        <th style="width:140px;">Pembuat</th>
                        <th style="width:200px;">Catatan</th>
                        <th style="width:90px;" class="text-center">Status</th>
                        <th style="width:70px;" class="text-center">Items</th>
                        <th style="width:80px;" class="text-center">Total Qty</th>
                        <th style="width:90px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $r)
                        @php
                            $badge = 'b-closed';
                            $icon  = 'fa-circle';
                            if($r->status == 'OPEN'){ $badge='b-open'; $icon='fa-clock-o'; }
                            elseif($r->status == 'APPROVED'){ $badge='b-approved'; $icon='fa-check'; }
                            elseif($r->status == 'PARTIAL'){ $badge='b-partial'; $icon='fa-adjust'; }
                            elseif($r->status == 'CLOSED'){ $badge='b-closed'; $icon='fa-lock'; }
                            elseif($r->status == 'REJECTED'){ $badge='b-rejected'; $icon='fa-times'; }
                            elseif($r->status == 'CANCELLED'){ $badge='b-cancel'; $icon='fa-ban'; }

                            $itemsCount = $r->details ? $r->details->count() : 0;
                            $totalQty = 0;
                            
                            $detailsArr = [];
                            if($r->details){
                                foreach($r->details as $d){ 
                                    // FIX: Float
                                    $totalQty += (float)$d->quantity;
                                    
                                    $iName = ($d->item && $d->item->name) ? $d->item->name : '-';
                                    $iCode = ($d->item && $d->item->code) ? $d->item->code : '-';
                                    $iUnit = ($d->item && $d->item->unit) ? $d->item->unit : '';
                                    
                                    $detailsArr[] = [
                                        'item_name' => $iName,
                                        'item_code' => $iCode,
                                        // FIX: Float
                                        'qty' => (float)$d->quantity,
                                        'processed' => (float)$d->processed_qty,
                                        'unit' => $iUnit
                                    ];
                                }
                            }
                            $jsonDetails = json_encode($detailsArr);
                        @endphp
                        
                        <tr class="master-row">
                            <td class="text-center">
                                {{-- FIX: PASSING PARENT STATUS ($r->status) KE JS FUNCTION --}}
                                <button type="button" class="btn-expand" onclick='toggleDetail(this, {!! $jsonDetails !!}, "{{ $r->status }}")'>
                                    <i class="fa fa-plus-circle"></i>
                                </button>
                            </td>
                            <td class="mono" style="font-weight:900;">
                                {{ $r->request_number }}
                                @if($r->bon)
                                    <div style="font-size:9px; color:#64748b; margin-top:2px;">Ref: {{ $r->bon->bon_number }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $r->date ? date('d/m/Y', strtotime($r->date)) : '-' }}</td>
                            <td class="text-center">{{ $r->department ? $r->department->name : '-' }}</td>
                            <td>
                                <div style="font-weight:900; color:#0f172a;">{{ $r->user ? $r->user->name : '-' }}</div>
                                <span class="user-email">{{ $r->user ? $r->user->email : '' }}</span>
                            </td>
                            <td class="td-notes">
                                {{ $r->notes ?: '-' }}
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $badge }}">
                                    <i class="fa {{ $icon }}"></i> {{ $r->status }}
                                </span>
                            </td>
                            <td class="text-center" style="font-weight:900;">{{ number_format($itemsCount) }}</td>
                            {{-- FIX: Float Display --}}
                            <td class="text-center" style="font-weight:900;">{{ (float)$totalQty }}</td>
                            <td class="text-center">
                                <div class="actions">
                                    <a href="{{ route('requests.show', $r->id) }}" class="a-btn" title="Lihat Detail">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    
                                    @if($r->status === 'OPEN')
                                        @if(auth()->user()->role !== 'USER' || auth()->user()->id === $r->user_id)
                                            <a href="{{ route('requests.edit', $r->id) }}" class="a-btn edit" title="Edit Request">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                            
                                            <form id="form-delete-{{ $r->id }}" action="{{ route('requests.destroy', $r->id) }}" method="POST" style="display:none;">
                                                {{ csrf_field() }}
                                                {{ method_field('DELETE') }}
                                            </form>
                                            
                                            <button type="button" class="a-btn del" title="Hapus Request"
                                                    onclick="openDeleteModal('form-delete-{{ $r->id }}', '{{ $r->request_number }}')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <tr class="detail-row">
                            <td colspan="10" style="padding:0; border:none;">
                                <div class="detail-box">
                                    <h5 style="margin:0 0 10px; font-weight:700; color:#475569; font-size:12px; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
                                        <i class="fa fa-list-ul"></i> Rincian Barang
                                    </h5>
                                    <table class="detail-table">
                                        <thead>
                                            <tr>
                                                <th style="width:120px;">Kode Barang</th>
                                                <th>Nama Barang</th>
                                                <th style="width:150px; text-align:center;">Qty Minta</th>
                                                <th style="width:150px; text-align:center;">Sudah Diproses</th>
                                                <th style="width:150px; text-align:center;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="detail-body"></tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="padding:30px; text-align:center; color:#94a3b8;">
                                <i class="fa fa-inbox" style="font-size: 24px; margin-bottom: 8px; display: block;"></i> 
                                Tidak ada data request.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:12px 18px;">
            {!! $requests->appends(request()->except('page'))->links() !!}
        </div>
    </div>

</div>

{{-- MODAL DELETE --}}
<div class="modal fade" id="modalDelete" tabindex="-1" role="dialog" style="z-index: 10000;">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content modal-clean">
            <div class="modal-head-clean">
                <div class="modal-icon-box danger">
                    <i class="fa fa-trash"></i>
                </div>
            </div>
            <div class="modal-body-clean">
                <h3 class="modal-title-clean">Hapus Request?</h3>
                <p class="modal-desc-clean">
                    Anda akan menghapus data request <strong id="del-req-num">...</strong> secara permanen.
                    Tindakan ini tidak bisa dibatalkan.
                </p>
            </div>
            <div class="modal-foot-clean">
                <button type="button" class="btn-modal cancel" data-dismiss="modal">Batal</button>
                <button type="button" class="btn-modal confirm" id="btn-confirm-delete">Ya, Hapus</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TUTUP PERIODE --}}
<div class="modal fade" id="modalClosePeriod" tabindex="-1" role="dialog" style="z-index: 10000;">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content modal-clean">
            <div class="modal-head-clean">
                <div class="modal-icon-box danger">
                    <i class="fa fa-gavel"></i>
                </div>
            </div>
            <div class="modal-body-clean">
                <h3 class="modal-title-clean">Tutup Periode?</h3>
                <p class="modal-desc-clean">
                    Anda akan menutup semua permintaan <strong>BULAN LALU</strong> yang masih menggantung.<br><br>
                    Permintaan yang ditutup tidak bisa diproses lagi <strong>(Sisa Jatah Hangus)</strong>.
                </p>
            </div>
            <div class="modal-foot-clean">
                <button type="button" class="btn-modal cancel" data-dismiss="modal">Batal</button>
                <button type="button" class="btn-modal confirm" onclick="$('#form-close-period').submit()">Ya, Tutup Periode</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
<script>
    // Flatpickr
    (function(){
        var els = document.querySelectorAll('.js-date');
        for(var i=0;i<els.length;i++){
            flatpickr(els[i],{
                altInput:true, altFormat:"d/m/Y", dateFormat:"Y-m-d", locale:"id", allowInput:true
            });
        }
    })();

    // EXPAND ROW LOGIC (FIX: Dengan Status Parent)
    function toggleDetail(btn, details, parentStatus) {
        var $btn = $(btn);
        var $tr = $btn.closest('tr');
        var $detailRow = $tr.next('.detail-row');
        
        $btn.toggleClass('open');
        var icon = $btn.find('i');
        
        if ($btn.hasClass('open')) {
            icon.removeClass('fa-plus-circle').addClass('fa-minus-circle');
        } else {
            icon.removeClass('fa-minus-circle').addClass('fa-plus-circle');
        }

        if ($detailRow.is(':hidden')) {
            var $tbody = $detailRow.find('.detail-body');
            $tbody.empty();
            
            if (details && details.length > 0) {
                $.each(details, function(i, d) {
                    var statusHtml = '';

                    // LOGIC STATUS (FIXED)
                    // 1. Jika Item sudah full processed = Selesai (Apapun status headernya)
                    if (d.processed >= d.qty) {
                        statusHtml = '<span style="color:#16a34a; font-weight:700;">Selesai</span>';
                    }
                    // 2. Jika Header CLOSED dan belum full = Closed (Hangus)
                    else if (parentStatus === 'CLOSED') {
                        statusHtml = '<span style="color:#0f172a; font-weight:700; background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:11px;">Closed</span>';
                    }
                    // 3. Jika belum full tapi sudah ada yang diambil = Partial
                    else if (d.processed > 0) {
                        statusHtml = '<span style="color:#ca8a04; font-weight:700;">Partial</span>';
                    }
                    // 4. Sisanya = Pending
                    else {
                        statusHtml = '<span style="color:#94a3b8;">Pending</span>';
                    }

                    var rowHtml = '<tr>' +
                        '<td><span style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-family:monospace;">' + d.item_code + '</span></td>' +
                        '<td style="font-weight:600;">' + d.item_name + '</td>' +
                        '<td class="text-center" style="font-weight:700;">' + d.qty + ' ' + d.unit + '</td>' +
                        '<td class="text-center" style="font-weight:700;">' + d.processed + ' ' + d.unit + '</td>' +
                        '<td class="text-center">' + statusHtml + '</td>' +
                    '</tr>';
                    $tbody.append(rowHtml);
                });
            } else {
                $tbody.append('<tr><td colspan="5" class="text-center text-muted">Tidak ada rincian items.</td></tr>');
            }
            
            $detailRow.show();
        } else {
            $detailRow.hide();
        }
    }

    // Modal Delete
    function openDeleteModal(formId, reqNum) {
        $('#del-req-num').text(reqNum);
        $('#btn-confirm-delete').off('click').on('click', function(){
            $('#' + formId).submit();
        });
        $('#modalDelete').modal('show');
    }

    // Modal Close Period
    function openClosePeriodModal() {
        $('#modalClosePeriod').modal('show');
    }
</script>
@endsection
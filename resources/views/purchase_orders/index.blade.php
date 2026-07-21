@extends('layouts.app')

@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>SISTEM PEMESANAN (PO):</strong> Halaman ini digunakan untuk mengelola Rencana Pembelian (Draft PO) dan Purchase Order resmi.
    </div>
    <h4 class="help-h"><i class="fa fa-list text-primary"></i> Penjelasan Status PO</h4>
    <ul class="help-list">
        <li>
            <span class="badge" style="background:#e2e8f0; color:#475569;">DRAFT</span> : 
            Rencana pembelian barang. Belum dipesan ke supplier. Masih bisa diubah/dihapus.
        </li>
        <li>
            <span class="badge badge-primary" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">ORDERED</span> : 
            Barang sudah dipesan ke supplier (Status: **Dalam Pemesanan**). Kuantitas akan tampil di kolom "Sedang Dipesan".
        </li>
        <li>
            <span class="badge badge-warning" style="background:#fffbeb; color:#d97706; border:1px solid #fde68a;">PARTIAL</span> : 
            Sebagian barang pesanan sudah diterima di gudang (melalui LPB).
        </li>
        <li>
            <span class="badge badge-success" style="background:#ecfdf5; color:#16a34a; border:1px solid #a7f3d0;">RECEIVED</span> : 
            Seluruh barang dalam PO telah diterima di gudang. Proses PO selesai.
        </li>
        <li>
            <span class="badge badge-danger" style="background:#fef2f2; color:#dc2626; border:1px solid #fca5a5;">CANCELLED</span> : 
            Pemesanan dibatalkan. Kuantitas pesanan tidak dihitung lagi.
        </li>
    </ul>
@endsection

@section('content')
<style>
.section { background: #fff; border: 1px solid #e7e7f0; border-radius: 12px; margin-bottom: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
.section .section-hd { display: flex; align-items: center; gap: 8px; padding: 14px 18px; border-bottom: 1px solid #eef2f7; background: #f8fafc; border-radius: 12px 12px 0 0; }
.section .section-hd > i { color: #2563eb; font-size: 16px; }
.section .section-hd > strong { font-size: 15px; color: #1e293b; }
.section .section-bd { padding: 20px; }

.form-label { display: block; font-size: 13px; color: #64748b; margin-bottom: 6px; font-weight: 600; }
.form-control { 
    height: 40px; border: 1px solid #d0d5dd; border-radius: 8px; padding: 6px 12px; font-size: 14px; color: #1e293b; transition: all 0.2s; width: 100%;
}
select.form-control {
    -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 36px;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 10px center; background-repeat: no-repeat; background-size: 1.5em 1.5em; cursor: pointer;
}
.form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); outline: none; }

.filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; }
.filters .form-group { display: flex; flex-direction: column; margin: 0; }
.filters .form-actions { display: flex; align-items: flex-end; gap: 10px; }
.filters .form-actions .fake-label { visibility: hidden; height: 0; margin: 0; }

.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; border: 1px solid transparent; padding: 0 16px; font-weight: 600; cursor: pointer; height: 40px; text-decoration: none; font-size: 14px; transition: all 0.2s; }
.btn-primary { background: #2563eb; color: #fff; border-color: #2563eb; }
.btn-primary:hover { background: #1d4ed8; }
.btn-light { background: #fff; border-color: #d0d5dd; color: #344054; }
.btn-light:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-success { background: #10b981; color: #fff; border-color: #10b981; }
.btn-success:hover { background: #059669; }

.table-wrapper { border: 1px solid #eef2f7; border-radius: 10px; overflow-x: auto; margin-top: 16px; }
.table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; table-layout: fixed; }
.table thead th { background: #f8fafc; color: #475467; font-weight: 700; font-size: 11px; text-transform: uppercase; padding: 8px 10px; border-bottom: 1px solid #eef2f7; }
.table tbody td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.table tbody tr:hover { background: #f8fafc; }

.badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.badge-draft { background: #e2e8f0; color: #475569; border: 1px solid #cbd5e1; }
.badge-ordered { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.badge-partial { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.badge-received { background: #ecfdf5; color: #16a34a; border: 1px solid #a7f3d0; }
.badge-cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }

.col-expand { width: 40px; text-align: center; }
.col-no { width: 140px; text-align: center; }
.col-date { width: 100px; text-align: center; }
.col-supplier { width: 220px; }
.col-items { width: 130px; text-align: center; }
.col-status { width: 110px; text-align: center; }
.col-act { width: 110px; text-align: center; }

.pagination-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eef2f7; color: #64748b; }
.pagination { display: inline-flex; margin: 0; gap: 4px; }
.pagination > li > a, .pagination > li > span { border-radius: 6px; border: 1px solid #e2e8f0; color: #475467; background: #fff; padding: 8px 14px; font-size: 13px; text-decoration: none; }
.pagination > .active > span { background: #2563eb; border-color: #2563eb; color: #fff; }

/* EXPANDABLE ROW STYLES */
.table tbody tr.po-details-row:hover {
    background: #f8fafc !important;
}
.table-po-details {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
    table-layout: auto !important;
}
.table-po-details th {
    background: #f8fafc;
    color: #64748b;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    padding: 8px 12px;
    border-bottom: 1px solid #e2e8f0;
}
.table-po-details td {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
    color: #1e293b;
    white-space: normal !important;
}
.table-po-details tr:last-child td {
    border-bottom: none;
}
.btn-toggle-po {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    font-size: 11px;
    color: #2563eb;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 50%;
    transition: all 0.2s ease;
    outline: none;
    padding: 0;
}
.btn-toggle-po:hover, .btn-toggle-po:focus {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
    text-decoration: none;
    outline: none;
}
</style>

<div class="section">
    <div class="section-hd">
        <i class="fa fa-shopping-bag"></i> <strong>Sistem Purchase Requisition (PR)</strong>
    </div>
    <div class="section-bd">
        <form method="GET" action="{{ route('purchase-orders.index') }}">
            <div class="filters">
                <div class="form-group" style="flex:1; min-width:220px;">
                    <label class="form-label">Cari Code</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Ketik nomor PR, supplier, atau catatan...">
                </div>
                <div class="form-group" style="width:180px;">
                    <label class="form-label">Filter Status</label>
                    <select name="status" class="form-control">
                        <option value="all">Semua Status</option>
                        <option value="DRAFT" {{ $status == 'DRAFT' ? 'selected' : '' }}>DRAFT (Rencana)</option>
                        <option value="ORDERED" {{ $status == 'ORDERED' ? 'selected' : '' }}>ORDERED (Dipesan)</option>
                        <option value="PARTIALLY_RECEIVED" {{ $status == 'PARTIALLY_RECEIVED' ? 'selected' : '' }}>PARTIAL (Diterima Sebagian)</option>
                        <option value="RECEIVED" {{ $status == 'RECEIVED' ? 'selected' : '' }}>RECEIVED (Selesai)</option>
                        <option value="CANCELLED" {{ $status == 'CANCELLED' ? 'selected' : '' }}>CANCELLED (Batal)</option>
                    </select>
                </div>
                <div class="form-group" style="width:90px;">
                    <label class="form-label">Limit</label>
                    <select name="per_page" class="form-control">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Cari</button>
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-light" title="Reset"><i class="fa fa-refresh"></i></a>
                </div>
                <div class="form-actions" style="margin-left:auto; display:flex; gap:8px;">
                    <a href="{{ route('buffer-alerts.index') }}" class="btn btn-light"><i class="fa fa-bell text-warning"></i> Monitor Stok</a>
                    <a href="{{ route('purchase-orders.create') }}" class="btn btn-success"><i class="fa fa-plus"></i> Tambah PR Baru</a>
                </div>
            </div>
        </form>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-expand"></th>
                        <th class="col-no">No. PR</th>
                        <th class="col-date">Tanggal</th>
                        <th class="col-supplier">Pemasok / Supplier</th>
                        <th class="col-items">Total Barang</th>
                        <th class="col-status">Status</th>
                        <th class="col-act">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pos as $po)
                    <tr>
                        <td style="text-align:center;">
                            <button type="button" class="btn-toggle-po" data-id="{{ $po->id }}" title="Klik untuk lihat detail item">
                                <i class="fa fa-chevron-down"></i>
                            </button>
                        </td>
                        <td style="font-weight:700; color:#374151; text-align:center;">
                            <a href="{{ route('purchase-orders.show', $po->id) }}" style="color:#2563eb; text-decoration:none;">{{ $po->po_number }}</a>
                        </td>
                        <td style="text-align:center;">{{ date('d/m/Y', strtotime($po->date)) }}</td>
                        <td>{{ $po->supplier_name ?: '-' }}</td>
                        <td style="text-align:center; font-weight:500; color:#475569;">
                            {{ $po->details->count() }} Item
                        </td>
                        <td style="text-align:center;">
                            @if($po->status === 'DRAFT')
                                <span class="badge badge-draft">Draft</span>
                            @elseif($po->status === 'ORDERED')
                                <span class="badge badge-ordered">Ordered</span>
                            @elseif($po->status === 'PARTIALLY_RECEIVED')
                                <span class="badge badge-partial">Partial</span>
                            @elseif($po->status === 'RECEIVED')
                                <span class="badge badge-received">Received</span>
                            @elseif($po->status === 'CANCELLED')
                                <span class="badge badge-cancelled">Cancelled</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div style="display:inline-flex; gap:8px;">
                                <a href="{{ route('purchase-orders.show', $po->id) }}" class="btn btn-light" style="padding:0 10px; height:30px; font-size:12px;" title="Lihat Detail">
                                    <i class="fa fa-eye"></i>
                                </a>
                                @if(in_array($po->status, ['DRAFT', 'ORDERED', 'PARTIALLY_RECEIVED']))
                                    <a href="{{ route('purchase-orders.edit', $po->id) }}" class="btn btn-primary" style="padding:0 10px; height:30px; font-size:12px;" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    
                    {{-- Expandable PO Details Row --}}
                    <tr id="po-details-row-{{ $po->id }}" class="po-details-row" style="display: none; background: #f8fafc;">
                        <td colspan="7" style="padding: 10px 12px; border-bottom: 1px solid #eef2f7; white-space: normal; overflow: visible;">
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.01);">
                                <table class="table-po-details">
                                    <thead>
                                        <tr>
                                            <th style="width: 150px; text-align: left;">Kode Barang</th>
                                            <th style="text-align: left;">Nama Barang</th>
                                            <th style="width: 80px; text-align: center;">Satuan</th>
                                            <th style="width: 110px; text-align: center;">Qty Dipesan</th>
                                            <th style="width: 110px; text-align: center;">Qty Diterima</th>
                                            <th style="width: 120px; text-align: center;">Sedang Dipesan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($po->details as $detail)
                                        <tr>
                                            <td style="font-weight: 600; color: #475569; text-align: left;">{{ $detail->item->code }}</td>
                                            <td style="font-weight: 500; text-align: left; white-space: normal;">{{ $detail->item->name }}</td>
                                            <td style="text-align: center; color: #64748b;">{{ $detail->item->unit }}</td>
                                            <td style="text-align: center; font-weight: 700; color: #0f172a;">{{ (float)$detail->quantity }}</td>
                                            <td style="text-align: center; font-weight: 700; color: #16a34a;">{{ (float)$detail->received_qty }}</td>
                                            <td style="text-align: center; font-weight: 700; color: #dc2626;">{{ (float)$detail->remaining_qty }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">
                            <i class="fa fa-shopping-bag" style="font-size:36px; margin-bottom:12px; opacity:0.5;"></i><br>
                            <span style="font-size:15px; font-weight:500;">Tidak ada data Purchase Order ditemukan.</span>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            <div>
                Menampilkan <strong>{{ $pos->firstItem() ?: 0 }}</strong> - <strong>{{ $pos->lastItem() ?: 0 }}</strong> dari <strong>{{ $pos->total() }}</strong> data
            </div>
            <div>{{ $pos->links() }}</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function($) {
        'use strict';
        $(document).ready(function() {
            $('.btn-toggle-po').on('click', function(e) {
                e.preventDefault();
                var poId = $(this).data('id');
                var targetRow = $('#po-details-row-' + poId);
                var icon = $(this).find('i');
                
                targetRow.toggle();
                
                if (targetRow.is(':visible')) {
                    icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    $(this).css({
                        'background': '#2563eb',
                        'color': '#fff',
                        'border-color': '#2563eb'
                    });
                } else {
                    icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    $(this).css({
                        'background': '#eff6ff',
                        'color': '#2563eb',
                        'border-color': '#bfdbfe'
                    });
                }
            });
        });
    })(jQuery);
</script>
@endsection

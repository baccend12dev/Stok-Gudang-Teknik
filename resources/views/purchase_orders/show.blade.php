@extends('layouts.app')

@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i>
        <strong>DETAIL PURCHASE ORDER:</strong> Di sini Anda dapat melihat rincian pemesanan, perkembangan barang yang sudah diterima (parsial), serta riwayat LPB terkait.
    </div>
@endsection

@section('content')
<style>
    .page-container { width: 100%; padding: 8px 16px; box-sizing: border-box; }
    .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .header-title-wrapper { display: flex; align-items: center; gap: 12px; }
    .header-visual-icon {
        width: 42px; height: 42px; background: linear-gradient(135deg, #e0e7ff 0%, #dbeafe 100%);
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        color: #2563eb; font-size: 20px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.6);
    }
    .header-content h1 { font-size: 20px; font-weight: 700; margin: 0; line-height: 1.2; }
    .header-content p { font-size: 12px; color: #64748b; margin-top: 1px; margin-bottom: 0; }
    
    .btn-back {
        background: white; color: #0f172a; padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 600;
        text-decoration: none; box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05); border: 1px solid white;
        transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; height: 34px;
    }
    .btn-back:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08); color: #2563eb; }

    .card-section {
        background: #fff; border-radius: 8px; border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); margin-bottom: 12px; overflow: hidden;
    }
    .card-header { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px; background: #fff; }
    .card-title { font-size: 13px; font-weight: 600; margin: 0; }
    .card-body { padding: 12px; }

    .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-draft { background: #e2e8f0; color: #475569; border: 1px solid #cbd5e1; }
    .badge-ordered { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .badge-partial { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .badge-received { background: #ecfdf5; color: #16a34a; border: 1px solid #a7f3d0; }
    .badge-cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }

    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 4px; }
    .info-label { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-size: 13px; font-weight: 600; color: #1e293b; margin-top: 1px; }

    .table-container { border: 1px solid #e2e8f0; border-radius: 6px; overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
    .table thead { background-color: #f8fafc; }
    .table thead th { padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; }
    .table tbody td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: middle !important; font-size: 12px; color: #1e293b; }
    .table tbody tr:last-child td { border-bottom: none; }

    .action-btn-group { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border-radius: 50px; border: 1px solid transparent; padding: 0 16px; font-weight: 600; cursor: pointer; height: 34px; text-decoration: none; font-size: 12px; transition: all 0.2s; }
    .btn-action-primary { background: linear-gradient(145deg, #3b82f6, #1d4ed8); color: white; box-shadow: 0 4px 10px -2px rgba(37, 99, 235, 0.4); }
    .btn-action-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.5); }
    .btn-action-success { background: linear-gradient(145deg, #10b981, #059669); color: white; box-shadow: 0 4px 10px -2px rgba(16, 185, 129, 0.4); }
    .btn-action-success:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.5); }
    .btn-action-danger { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
    .btn-action-danger:hover { background: #ef4444; color: white; transform: translateY(-2px); }
    .btn-action-light { background: white; color: #334155; border: 1px solid #cbd5e1; }
    .btn-action-light:hover { background: #f8fafc; border-color: #94a3b8; transform: translateY(-2px); }

    .text-success { color: #16a34a !important; font-weight: 700; }
    .text-warning { color: #d97706 !important; font-weight: 700; }
    .text-danger { color: #dc2626 !important; font-weight: 700; }
</style>

<div class="page-container">
    <div class="dashboard-header">
        <div class="header-title-wrapper">
            <div class="header-visual-icon"><i class="fa fa-shopping-bag"></i></div>
            <div class="header-content">
                <h1>Detail Purchase Order (PR)</h1>
                <p>Informasi status pemesanan barang dan penerimaan parsial.</p>
            </div>
        </div>
        <div>
            <a href="{{ route('purchase-orders.index') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> <span>Kembali</span>
            </a>
        </div>
    </div>

    {{-- HEADER CARD --}}
    <div class="card-section">
        <div class="card-header" style="justify-content: space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="header-icon-wrapper-small"><i class="fa fa-file-text-o"></i></div>
                <h3 class="card-title">PR #{{ $po->po_number }}</h3>
            </div>
            <div>
                @if($po->status === 'DRAFT')
                    <span class="badge badge-draft">Draft (Rencana)</span>
                @elseif($po->status === 'ORDERED')
                    <span class="badge badge-ordered">Ordered (Dalam Pemesanan)</span>
                @elseif($po->status === 'PARTIALLY_RECEIVED')
                    <span class="badge badge-partial">Diterima Sebagian</span>
                @elseif($po->status === 'RECEIVED')
                    <span class="badge badge-received">Diterima Lengkap</span>
                @elseif($po->status === 'CANCELLED')
                    <span class="badge badge-cancelled">Dibatalkan</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div>
                    <div class="info-label">Tanggal Pemesanan</div>
                    <div class="info-value">{{ date('d F Y', strtotime($po->date)) }}</div>
                </div>
                <div>
                    <div class="info-label">Supplier / Pemasok</div>
                    <div class="info-value">{{ $po->supplier_name ?: '-' }}</div>
                </div>
                <div>
                    <div class="info-label">Dibuat Pada</div>
                    <div class="info-value">{{ $po->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
            @if($po->notes)
                <div style="border-top:1px solid #f1f5f9; padding-top:8px; margin-top:8px;">
                    <div class="info-label">Catatan Tambahan</div>
                    <div style="font-size:13px; color:#475569; margin-top:2px; line-height:1.5;">{{ $po->notes }}</div>
                </div>
            @endif

            {{-- TOMBOL AKSI BERDASARKAN STATUS --}}
            <div style="border-top:1px solid #f1f5f9; padding-top:10px; margin-top:10px;" class="action-btn-group">
                @if($po->status === 'DRAFT')
                    <form action="{{ route('purchase-orders.ordered', $po->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Kirim pemesanan? Status barang akan berubah menjadi sedang dipesan.')">
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-action-primary"><i class="fa fa-send"></i> Pesan ke Supplier</button>
                    </form>
                    <a href="{{ route('purchase-orders.edit', $po->id) }}" class="btn btn-action-light"><i class="fa fa-pencil"></i> Edit PR</a>
                    <form action="{{ route('purchase-orders.destroy', $po->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus rencana pembelian ini?')">
                        {{ csrf_field() }} {{ method_field('DELETE') }}
                        <button type="submit" class="btn btn-action-danger"><i class="fa fa-trash"></i> Hapus Rencana</button>
                    </form>
                @endif

                @if(in_array($po->status, ['ORDERED', 'PARTIALLY_RECEIVED']))
                    <!-- <a href="{{ route('lpbs.create', ['po_id' => $po->id]) }}" class="btn btn-action-success"><i class="fa fa-download"></i> Terima Barang (Buat LPB)</a> -->
                    <a href="{{ route('purchase-orders.edit', $po->id) }}" class="btn btn-action-light"><i class="fa fa-pencil"></i> Edit PR</a>
                    <form action="{{ route('purchase-orders.cancel', $po->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Batalkan pemesanan PO ini?')">
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-action-danger"><i class="fa fa-ban"></i> Batalkan Pemesanan</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- DETAIL BARANG CARD --}}
    <div class="card-section">
        <div class="card-header">
            <div class="header-icon-wrapper-small" style="background:rgba(16, 185, 129, 0.1); color:#10b981;"><i class="fa fa-cubes"></i></div>
            <h3 class="card-title">Daftar Barang & Status Penerimaan</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-container" style="border:none; border-radius:0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">No</th>
                            <th style="width: 120px; text-align: center;">Kode</th>
                            <th>Nama Barang</th>
                            <th style="width: 100px; text-align: center;">Satuan</th>
                            <th style="width: 150px; text-align: center;">Qty Dipesan</th>
                            <th style="width: 150px; text-align: center;">Qty Diterima</th>
                            <th style="width: 150px; text-align: center;">Sisa Qty</th>
                            <th>Keterangan Item</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($po->details as $idx => $detail)
                        <tr>
                            <td style="text-align: center; color:#94a3b8; font-weight:600;">{{ $idx + 1 }}</td>
                            <td style="text-align: center; font-weight:600; color:#475569;">{{ $detail->item->code }}</td>
                            <td style="font-weight:500;">{{ $detail->item->name }}</td>
                            <td style="text-align: center; color:#64748b;">{{ $detail->item->unit }}</td>
                            <td style="text-align: center; font-weight:700;">{{ (float)$detail->quantity }}</td>
                            <td style="text-align: center; font-weight:700;" class="{{ $detail->received_qty >= $detail->quantity ? 'text-success' : ($detail->received_qty > 0 ? 'text-warning' : '') }}">
                                {{ (float)$detail->received_qty }}
                            </td>
                            <td style="text-align: center; font-weight:700;" class="{{ $detail->remaining_qty > 0 ? 'text-danger' : 'text-success' }}">
                                {{ (float)$detail->remaining_qty }}
                            </td>
                            <td style="color:#64748b;">{{ $detail->notes ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PENERIMAAN (LPB) CARD --}}
    @if($po->lpbs->count() > 0)
        <div class="card-section">
            <div class="card-header">
                <div class="header-icon-wrapper-small" style="background:rgba(37, 99, 235, 0.1); color:#2563eb;"><i class="fa fa-history"></i></div>
                <h3 class="card-title">Riwayat Penerimaan Gudang (LPB)</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-container" style="border:none; border-radius:0;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 60px; text-align: center;">No</th>
                                <th style="width: 200px; text-align: center;">No. LPB</th>
                                <th style="width: 150px; text-align: center;">Tanggal Terima</th>
                                <th>Catatan / Keterangan</th>
                                <th style="width: 150px; text-align: center;">Total Kuantitas</th>
                                <th style="width: 120px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($po->lpbs as $idx => $lpb)
                            <tr>
                                <td style="text-align: center; color:#94a3b8; font-weight:600;">{{ $idx + 1 }}</td>
                                <td style="text-align: center; font-weight:700; color:#374151;">{{ $lpb->lpb_number }}</td>
                                <td style="text-align: center;">{{ date('d/m/Y', strtotime($lpb->date)) }}</td>
                                <td>{{ $lpb->notes ?: '-' }}</td>
                                <td style="text-align: center; font-weight:700; color:#2563eb;">{{ (float) $lpb->details()->sum('quantity') }}</td>
                                <td style="text-align: center;">
                                    <a href="{{ route('lpbs.edit', $lpb->id) }}" class="btn btn-action-light" style="padding:0 10px; height:30px; font-size:12px;" title="Lihat/Edit LPB">
                                        <i class="fa fa-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

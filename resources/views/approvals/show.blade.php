@extends('layouts.app')

@section('content')
<style>
    .appr-container {
        max-width: 1000px;
        margin: 0 auto;
        padding-bottom: 40px;
    }
    .appr-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .appr-card-header {
        padding: 18px 24px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .appr-card-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .appr-card-body {
        padding: 24px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }
    .info-item {
        background: #f8fafc;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
    }
    .info-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }
    .appr-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }
    .appr-table th {
        background: #f1f5f9;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }
    .appr-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 14px;
    }
    .appr-table tr:last-child td {
        border-bottom: none;
    }
    .badge-status {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .bg-pending { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .bg-approved { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .bg-rejected { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    
    .action-box {
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        margin-top: 24px;
    }
    .btn-action {
        padding: 12px 28px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    .btn-approve {
        background: #16a34a;
        color: white;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
    }
    .btn-approve:hover {
        background: #15803d;
        transform: translateY(-1px);
    }
    .btn-reject {
        background: #dc2626;
        color: white;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
    }
    .btn-reject:hover {
        background: #b91c1c;
        transform: translateY(-1px);
    }
    .btn-back {
        background: #e2e8f0;
        color: #475569;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
    }
    .btn-back:hover {
        background: #cbd5e1;
        color: #1e293b;
        text-decoration: none;
    }
</style>

<div class="appr-container">
    {{-- Top Bar --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <a href="{{ route('requests.index') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> Kembali ke Daftar Permintaan
            </a>
        </div>
        <div>
            @if($hdr->status === 'PENDING_APPROVAL')
                <span class="badge-status bg-pending">
                    <i class="fa fa-clock-o"></i> Menunggu Persetujuan Anda
                </span>
            @elseif($hdr->status === 'REJECTED')
                <span class="badge-status bg-rejected">
                    <i class="fa fa-times-circle"></i> Permintaan Ditolak
                </span>
            @else
                <span class="badge-status bg-approved">
                    <i class="fa fa-check-circle"></i> Permintaan Disetujui
                </span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius: 8px; margin-bottom: 20px;">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" style="border-radius: 8px; margin-bottom: 20px;">
            <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Request Info Card --}}
    <div class="appr-card">
        <div class="appr-card-header">
            <h3 class="appr-card-title">
                <i class="fa fa-file-text-o text-primary"></i> 
                Permintaan Barang #{{ $hdr->request_number }}
            </h3>
            <span style="font-size: 13px; color: #64748b;">
                Tanggal: <strong>{{ \Carbon\Carbon::parse($hdr->date)->format('d F Y') }}</strong>
            </span>
        </div>
        <div class="appr-card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Pemohon</div>
                    <div class="info-value">{{ $hdr->user ? $hdr->user->name : '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Departemen / Divisi</div>
                    <div class="info-value">
                        {{ $hdr->department ? $hdr->department->name : '-' }} 
                        @if($hdr->division_name) ({{ $hdr->division_name }}) @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Approval Atasan</div>
                    <div class="info-value">{{ Auth::user()->name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status Permintaan</div>
                    <div class="info-value">
                        @if($hdr->status === 'PENDING_APPROVAL')
                            <span style="color: #c2410c;">PENDING APPROVAL</span>
                        @elseif($hdr->status === 'REJECTED')
                            <span style="color: #b91c1c;">DITOLAK</span>
                        @else
                            <span style="color: #15803d;">DISETUJUI</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($hdr->notes)
                <div style="margin-top: 16px; background: #fffbebfb; border: 1px solid #fef3c7; padding: 12px 16px; border-radius: 8px;">
                    <div class="info-label" style="color: #b45309;">Catatan / Keperluan Utama:</div>
                    <div style="font-size: 13px; color: #78350f; font-weight: 500;">{{ $hdr->notes }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Items Table Card --}}
    <div class="appr-card">
        <div class="appr-card-header">
            <h3 class="appr-card-title">
                <i class="fa fa-list text-primary"></i> 
                Rincian Barang yang Diminta
            </h3>
            <span style="font-size: 12px; color: #64748b;">Total {{ count($hdr->details) }} Item</span>
        </div>
        <div class="appr-card-body" style="padding: 0;">
            <table class="appr-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th style="width: 140px;">Kode Barang</th>
                        <th>Nama Barang</th>
                        <th style="width: 140px;">Kategori</th>
                        <th style="width: 110px; text-align: right;">Jumlah Qty</th>
                        <th style="width: 90px;">Satuan</th>
                        <th>Keperluan / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hdr->details as $idx => $dtl)
                        <tr>
                            <td style="text-align: center; font-weight: 600; color: #94a3b8;">{{ $idx + 1 }}</td>
                            <td>
                                <span style="font-family: monospace; font-weight: 700; color: #2563eb;">
                                    {{ $dtl->item ? $dtl->item->code : '-' }}
                                </span>
                            </td>
                            <td style="font-weight: 600; color: #0f172a;">
                                {{ $dtl->item ? $dtl->item->name : '-' }}
                            </td>
                            <td>
                                <span style="font-size: 12px; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; color: #475569;">
                                    {{ $dtl->item && $dtl->item->category ? $dtl->item->category->name : '-' }}
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-size: 15px; color: #0f172a;">
                                {{ (float)$dtl->quantity }}
                            </td>
                            <td style="color: #64748b;">
                                {{ $dtl->item ? $dtl->item->unit : '-' }}
                            </td>
                            <td>
                                @if($dtl->remarks)
                                    <span style="font-size: 13px; color: #475569;">{{ $dtl->remarks }}</span>
                                @else
                                    <span style="color: #cbd5e1; font-style: italic; font-size: 12px;">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Action Decision Card --}}
    @if($hdr->status === 'PENDING_APPROVAL' && (Auth::user()->id == $hdr->approver_id || Auth::user()->role === 'SUPER_ADMIN'))
        <div class="action-box">
            <h4 style="margin: 0 0 8px 0; font-weight: 700; color: #1e293b;">Persetujuan Atasan</h4>
            <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px;">
                Silakan periksa kebutuhan barang di atas. Pilih untuk menyetujui atau menolak permintaan ini.
            </p>

            <div style="display: flex; justify-content: center; gap: 16px;">
                {{-- Form Approve --}}
                <form action="{{ route('requests.approverApprove', $hdr->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin MENYETUJUI permintaan barang ini?');">
                   {{csrf_field()}}
                    <button type="submit" class="btn-action btn-approve">
                        <i class="fa fa-check-circle" style="font-size: 18px;"></i> Setuju / Disetujui
                    </button>
                </form>

                {{-- Form Reject --}}
                <form action="{{ route('requests.approverReject', $hdr->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin MENOLAK permintaan barang ini?');">
                   {{csrf_field()}}
                    <button type="submit" class="btn-action btn-reject">
                        <i class="fa fa-times-circle" style="font-size: 18px;"></i> Tidak Setuju / Tolak
                    </button>
                </form>
            </div>
        </div>
    @elseif($hdr->status !== 'PENDING_APPROVAL')
        <div style="background: #f8fafc; border-radius: 8px; padding: 16px; text-align: center; color: #64748b; font-size: 13px;">
            <i class="fa fa-info-circle"></i> Permintaan ini telah selesai diproses dengan status <strong>{{ $hdr->status }}</strong>.
        </div>
    @endif
</div>
@endsection

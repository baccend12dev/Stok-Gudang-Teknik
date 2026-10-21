<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Persetujuan Permintaan Barang - {{ $hdr->request_number }}</title>
    
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #16a34a;
            --success-hover: #15803d;
            --danger: #dc2626;
            --danger-hover: #b91c1c;
            --warning: #d97706;
            --dark: #0f172a;
            --slate: #475569;
            --light-slate: #64748b;
            --border: #e2e8f0;
            --bg: #f8fafc;
            --card-bg: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg);
            color: #1e293b;
            line-height: 1.5;
            padding: 20px 12px 60px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* Top Bar Branding */
        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .brand-subtitle {
            font-size: 13px;
            color: var(--light-slate);
            margin-top: 2px;
        }

        /* Alert notifications */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .alert-success {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* Main Card */
        .card {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .card-title-group h2 {
            font-size: 18px;
            font-weight: 800;
            color: var(--dark);
            margin: 0;
        }
        .card-title-group .doc-date {
            font-size: 13px;
            color: var(--light-slate);
            margin-top: 2px;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .badge-pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }
        .badge-approved {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-rejected {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            padding: 20px 24px;
            background: #fafafa;
            border-bottom: 1px solid var(--border);
        }
        .info-item {
            background: #ffffff;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        .info-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--light-slate);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
            word-break: break-word;
        }

        /* Notes box */
        .notes-box {
            margin: 16px 24px 0 24px;
            background: #fefce8;
            border: 1px solid #fef08a;
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 13px;
            color: #854d0e;
        }
        .notes-box strong {
            display: block;
            margin-bottom: 4px;
            color: #713f12;
            font-size: 12px;
            text-transform: uppercase;
        }

        /* Items Section */
        .section-header {
            padding: 20px 24px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .item-table th {
            background: #f8fafc;
            color: var(--slate);
            padding: 12px 16px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .item-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            color: #334155;
            vertical-align: middle;
        }
        .item-table tr:last-child td {
            border-bottom: none;
        }
        .item-code {
            font-family: monospace;
            font-weight: 700;
            color: var(--primary);
            font-size: 12px;
        }
        .item-name {
            font-weight: 600;
            color: var(--dark);
        }
        .item-qty {
            font-weight: 800;
            font-size: 15px;
            color: var(--dark);
        }
        .category-pill {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            background: #f1f5f9;
            color: var(--slate);
        }

        /* Action Decision Box */
        .decision-card {
            background: #ffffff;
            border-radius: 14px;
            border: 2px dashed #cbd5e1;
            padding: 28px 24px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
            margin-bottom: 24px;
        }
        .decision-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .decision-desc {
            font-size: 13px;
            color: var(--light-slate);
            max-width: 560px;
            margin: 0 auto 24px auto;
        }
        .decision-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s ease;
            min-width: 180px;
        }
        .btn-approve {
            background: var(--success);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);
        }
        .btn-approve:hover {
            background: var(--success-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.45);
        }
        .btn-reject {
            background: #ffffff;
            color: var(--danger);
            border: 2px solid var(--danger);
        }
        .btn-reject:hover {
            background: #fef2f2;
            transform: translateY(-2px);
        }

        /* Status banner when already handled */
        .status-handled-box {
            padding: 24px;
            border-radius: 14px;
            text-align: center;
            margin-bottom: 24px;
        }
        .status-handled-approved {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
        }
        .status-handled-rejected {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: #ffffff;
            border-radius: 16px;
            max-width: 480px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            animation: modalFadeIn 0.2s ease-out;
        }
        @keyframes modalFadeIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-header {
            padding: 20px 24px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }
        .modal-body {
            padding: 24px;
        }
        .modal-body textarea {
            width: 100%;
            height: 100px;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-family: inherit;
            font-size: 13px;
            resize: vertical;
        }
        .modal-body textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .modal-footer {
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .btn-secondary {
            padding: 10px 20px;
            border-radius: 8px;
            background: #e2e8f0;
            color: #475569;
            border: none;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
        }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: var(--light-slate);
            margin-top: 30px;
        }
    </style>
</head>
<body>

<div class="container">
    {{-- Header Branding --}}
    <div class="brand-header">
        <div class="brand-title">
            <i class="fa fa-cubes text-primary" style="color: var(--primary);"></i>
            SISTEM STOK GUDANG TEKNIK
        </div>
        <div class="brand-subtitle">Portal Persetujuan Permintaan Barang (Online Approval)</div>
    </div>

    {{-- Notification Alerts --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="fa fa-check-circle" style="font-size: 20px;"></i>
            <div>
                <strong>Berhasil!</strong>
                <div>{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            <i class="fa fa-exclamation-circle" style="font-size: 20px;"></i>
            <div>
                <strong>Perhatian:</strong>
                <div>{{ session('error') }}</div>
            </div>
        </div>
    @endif

    {{-- Main Document Card --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title-group">
                <h2>No. Request: {{ $hdr->request_number }}</h2>
                <div class="doc-date">
                    <i class="fa fa-calendar-o"></i> Tanggal Pengajuan: <strong>{{ \Carbon\Carbon::parse($hdr->date)->format('d F Y') }}</strong>
                </div>
            </div>
            <div>
                @if($hdr->status === 'PENDING_APPROVAL')
                    <span class="badge badge-pending">
                        <i class="fa fa-clock-o"></i> MENUNGGU PERSETUJUAN
                    </span>
                @elseif($hdr->status === 'REJECTED')
                    <span class="badge badge-rejected">
                        <i class="fa fa-times-circle"></i> DITOLAK
                    </span>
                @else
                    <span class="badge badge-approved">
                        <i class="fa fa-check-circle"></i> TELAH DISETUJUI ({{ $hdr->status }})
                    </span>
                @endif
            </div>
        </div>

        {{-- Info Grid --}}
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
                <div class="info-label">Atasan / Approver Ditunjuk</div>
                <div class="info-value" style="color: var(--primary);">
                    {{ $hdr->approver ? $hdr->approver->name : '-' }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Waktu Disetujui</div>
                <div class="info-value">
                    @if($hdr->approved_by_approver_at)
                        {{ \Carbon\Carbon::parse($hdr->approved_by_approver_at)->format('d-m-Y H:i') }}
                    @else
                        <span style="color: var(--light-slate); font-weight: normal;">-</span>
                    @endif
                </div>
            </div>
        </div>

        @if($hdr->notes)
            <div class="notes-box">
                <strong>Catatan / Keperluan Pengajuan:</strong>
                {{ $hdr->notes }}
            </div>
        @endif

        {{-- Table Items --}}
        <div class="section-header">
            <h3><i class="fa fa-list-ul" style="color: var(--primary);"></i> Rincian Barang Yang Diminta</h3>
            <span style="font-size: 12px; color: var(--light-slate); font-weight: 600;">
                Total: {{ count($hdr->details) }} Item
            </span>
        </div>

        <div class="table-responsive">
            <table class="item-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No</th>
                        <th style="width: 140px;">Kode Barang</th>
                        <th>Nama Barang</th>
                        <th style="width: 130px;">Kategori</th>
                        <th style="width: 110px; text-align: right;">Jumlah Qty</th>
                        <th style="width: 80px;">Satuan</th>
                        <th>Keperluan / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hdr->details as $idx => $dtl)
                        <tr>
                            <td style="text-align: center; color: var(--light-slate); font-weight: 600;">{{ $idx + 1 }}</td>
                            <td>
                                <span class="item-code">{{ $dtl->item ? $dtl->item->code : '-' }}</span>
                            </td>
                            <td>
                                <div class="item-name">{{ $dtl->item ? $dtl->item->name : '-' }}</div>
                            </td>
                            <td>
                                <span class="category-pill">
                                    {{ $dtl->item && $dtl->item->category ? $dtl->item->category->name : '-' }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="item-qty">{{ (float)$dtl->quantity }}</span>
                            </td>
                            <td style="color: var(--light-slate); font-weight: 500;">
                                {{ $dtl->item ? $dtl->item->unit : '-' }}
                            </td>
                            <td>
                                @if($dtl->remarks)
                                    <span style="color: #334155;">{{ $dtl->remarks }}</span>
                                @else
                                    <span style="color: #cbd5e1; font-style: italic;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--light-slate); padding: 30px;">
                                Tidak ada rincian barang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Decision Actions --}}
    @if($hdr->status === 'PENDING_APPROVAL')
        <div class="decision-card">
            <div class="decision-title">Keputusan Persetujuan Atasan</div>
            <div class="decision-desc">
                Halo, <strong>{{ $hdr->approver ? $hdr->approver->name : 'Bapak/Ibu Atasan' }}</strong>. Silakan periksa rincian kebutuhan barang di atas. Klik salah satu tombol di bawah untuk menyetujui atau menolak permohonan ini:
            </div>

            <div class="decision-buttons">
                {{-- Form Approve --}}
                <form action="{{ route('approvals.public.approve', $token) }}" method="POST" id="formApprove">
                    {{ csrf_field() }}
                    <button type="button" class="btn-action btn-approve" onclick="confirmApprove()">
                        <i class="fa fa-check-circle" style="font-size: 18px;"></i> Setujui Permintaan
                    </button>
                </form>

                {{-- Trigger Reject Modal --}}
                <button type="button" class="btn-action btn-reject" onclick="openRejectModal()">
                    <i class="fa fa-times-circle" style="font-size: 18px;"></i> Tolak Permintaan
                </button>
            </div>
        </div>
    @elseif($hdr->status === 'REJECTED')
        <div class="status-handled-box status-handled-rejected">
            <i class="fa fa-times-circle" style="font-size: 36px; margin-bottom: 8px;"></i>
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 4px;">Permintaan Ini Telah Ditolak</h3>
            <p style="font-size: 13px; opacity: 0.9;">Keputusan penolakan telah tercatat di sistem gudang.</p>
        </div>
    @else
        <div class="status-handled-box status-handled-approved">
            <i class="fa fa-check-circle" style="font-size: 36px; margin-bottom: 8px;"></i>
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 4px;">Permintaan Ini Telah Disetujui</h3>
            <p style="font-size: 13px; opacity: 0.9;">
                Disetujui pada: <strong>{{ $hdr->approved_by_approver_at ? \Carbon\Carbon::parse($hdr->approved_by_approver_at)->format('d F Y H:i') : '-' }}</strong>. Dokumen BON telah otomatis diterbitkan untuk diproses Admin Gudang.
            </p>
        </div>
    @endif

    <div class="footer-note">
        &copy; {{ date('Y') }} Sistem Stok Gudang Teknik &bull; Halaman persetujuan resmi aman berbasis token.
    </div>
</div>

{{-- Modal Reject Form --}}
<div class="modal-overlay" id="rejectModal">
    <div class="modal-box">
        <form action="{{ route('approvals.public.reject', $token) }}" method="POST" id="formReject">
            {{ csrf_field() }}
            <div class="modal-header">
                <h4><i class="fa fa-exclamation-triangle text-danger" style="color: var(--danger);"></i> Konfirmasi Tolak Permintaan</h4>
                <button type="button" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #94a3b8;" onclick="closeRejectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 13px; color: var(--slate); margin-bottom: 12px;">
                    Apakah Anda yakin ingin menolak permohonan <strong>#{{ $hdr->request_number }}</strong>? Masukkan alasan atau catatan penolakan untuk pemohon:
                </p>
                <textarea name="reason" placeholder="Tuliskan alasan penolakan di sini (opsional)..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeRejectModal()">Batal</button>
                <button type="submit" class="btn-action btn-reject" style="padding: 10px 20px; min-width: auto;">
                    <i class="fa fa-times-circle"></i> Ya, Tolak Permintaan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmApprove() {
        if (confirm('Apakah Anda yakin ingin MENYETUJUI permohonan barang #{{ $hdr->request_number }} ini?')) {
            document.getElementById('formApprove').submit();
        }
    }

    function openRejectModal() {
        document.getElementById('rejectModal').classList.add('active');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('active');
    }

    // Close modal if user clicks outside
    window.addEventListener('click', function(e) {
        var modal = document.getElementById('rejectModal');
        if (e.target === modal) {
            closeRejectModal();
        }
    });
</script>

</body>
</html>

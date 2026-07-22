<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Permintaan Approval Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            color: #333333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .header {
            background-color: #2563eb;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 20px;
        }
        .content {
            padding: 24px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 8px 0;
            vertical-align: top;
            font-size: 14px;
        }
        .info-label {
            font-weight: bold;
            color: #64748b;
            width: 140px;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .item-table th, .item-table td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            font-size: 13px;
        }
        .item-table th {
            background-color: #f8fafc;
            text-align: left;
            color: #475569;
        }
        .btn-container {
            text-align: center;
            margin: 30px 0 10px 0;
        }
        .btn {
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 6px;
            display: inline-block;
        }
        .footer {
            background-color: #f8fafc;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Persetujuan Permintaan Barang</h2>
        </div>
        <div class="content">
            <p>Halo, <strong>{{ $approverName }}</strong></p>
            <p>Anda menerima pengajuan permintaan barang baru yang memerlukan persetujuan Anda dengan rincian sebagai berikut:</p>

            <table class="info-table">
                <tr>
                    <td class="info-label">No. Request</td>
                    <td>: <strong>{{ $hdr->request_number }}</strong></td>
                </tr>
                <tr>
                    <td class="info-label">Pemohon</td>
                    <td>: {{ $hdr->user ? $hdr->user->name : '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Departemen / Divisi</td>
                    <td>: {{ $hdr->department ? $hdr->department->name : '-' }} / {{ $hdr->division_name }}</td>
                </tr>
                <tr>
                    <td class="info-label">Tanggal</td>
                    <td>: {{ date('d-m-Y', strtotime($hdr->date)) }}</td>
                </tr>
                @if($hdr->notes)
                <tr>
                    <td class="info-label">Catatan</td>
                    <td>: {{ $hdr->notes }}</td>
                </tr>
                @endif
            </table>

            <h4>Daftar Barang Yang Diminta:</h4>
            <table class="item-table">
                <thead>
                    <tr>
                        <th style="width: 30px; text-align: center;">No</th>
                        <th>Nama Barang</th>
                        <th style="width: 70px; text-align: right;">Qty</th>
                        <th>Keperluan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hdr->details as $idx => $dtl)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $dtl->item ? $dtl->item->name : '-' }}</strong>
                            <br><small style="color: #64748b;">{{ $dtl->item ? $dtl->item->code : '' }}</small>
                        </td>
                        <td style="text-align: right;">{{ (float)$dtl->quantity }} {{ $dtl->item ? $dtl->item->unit : '' }}</td>
                        <td>{{ $dtl->remarks ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="btn-container">
                <a href="{{ route('requests.show', $hdr->id) }}" class="btn" target="_blank">Tinjau & Disetujui / Tolak</a>
            </div>
            <p style="font-size: 12px; color: #64748b; text-align: center;">
                Jika tombol di atas tidak berfungsi, salin dan buka tautan berikut di browser Anda:<br>
                <a href="{{ route('requests.show', $hdr->id) }}">{{ route('requests.show', $hdr->id) }}</a>
            </p>
        </div>
        <div class="footer">
            Email ini dikirim otomatis oleh Sistem Stok Gudang Teknik. Harap tidak membalas email ini.
        </div>
    </div>
</body>
</html>

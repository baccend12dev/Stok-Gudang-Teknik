<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tanda Terima - {{ $bon->bon_number }}</title>
    
    {{-- Import Font Oswald untuk Logo OTTO --}}
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500&display=swap" rel="stylesheet">

    <style>
        /* RESET & BASIC */
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            color: #000;
            margin: 0;
            padding: 20px;
            background: #e2e2e2;
        }

        /* PAPER SETTING - LANDSCAPE A4 */
        .page {
            width: 297mm;
            height: auto;
            padding: 10mm 15mm;
            margin: 0 auto;
            background: white;
            border: 1px solid #d3d3d3;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            position: relative;
            box-sizing: border-box;
        }

        /* HEADER */
        .header {
            border-bottom: 3px double #000;
            padding-bottom: 5px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
        }

        .brand-section {
            text-align: left;
            padding-bottom: 2px;
        }
        
        /* LOGO OTTO */
        .brand-name {
            font-family: 'Oswald', sans-serif;
            font-size: 36pt;
            font-weight: 500;
            margin: 0;
            line-height: 1;
            text-transform: uppercase;
            font-style: normal;
            letter-spacing: 2px;
            color: #000;
            transform: scaleY(1.1); 
            transform-origin: left bottom;
            display: inline-block;
        }

        .company-name {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 8px;
            letter-spacing: 0.5px;
            font-family: "Times New Roman", Times, serif;
        }

        .doc-title {
            text-align: right;
            margin-bottom: 2px;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 16pt;
            text-decoration: underline;
            text-transform: uppercase;
            font-weight: bold;
        }
        .doc-title span {
            display: block;
            font-size: 11pt;
            font-weight: bold;
            margin-top: 4px;
        }

        /* INFO TABLE */
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 11pt;
        }
        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .label {
            width: 110px;
            font-weight: bold;
        }
        .separator {
            width: 15px;
            text-align: center;
        }

        /* ITEMS TABLE */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 11pt;
            vertical-align: middle;
        }
        .data-table th {
            background-color: #f0f0f0;
            text-transform: uppercase;
            text-align: center;
            font-weight: bold;
            padding: 8px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* SIGNATURE AREA */
        .signature-wrapper {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            page-break-inside: avoid;
            padding: 0 50px;
        }
        .sign-box {
            width: 25%;
            text-align: center;
        }
        .sign-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 60px; /* Ruang TTD */
        }
        .sign-name {
            font-weight: bold;
            font-size: 11pt;
        }

        /* UTILITY */
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-family: sans-serif;
            font-size: 14px;
            cursor: pointer;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .btn-print { background: #3b82f6; color: white; border: none; }
        .btn-back { background: #64748b; color: white; border: none; margin-right: 10px; }

        /* PRINT CONFIG */
        @media print {
            @page {
                size: landscape;
                margin: 0mm; 
            }
            body {
                background: none;
                padding: 0;
                margin: 0;
            }
            .page {
                width: 100%;
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 10mm 15mm; 
                height: auto;
            }
            .no-print {
                display: none !important;
            }
            .data-table th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="{{ route('bons.index') }}" class="btn btn-back">&larr; Kembali</a>
        <button onclick="window.print()" class="btn btn-print">Cetak Tanda Terima</button>
    </div>

    <div class="page">
        <div class="header">
            <div class="brand-section">
                <h1 class="brand-name">OTTO</h1>
                <div class="company-name">PT Otto Pharmaceutical Industries</div>
            </div>
            <div class="doc-title">
                <h2>TANDA TERIMA BARANG</h2>
                <span>NO: {{ $bon->bon_number }}</span>
            </div>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Tanggal</td>
                <td class="separator">:</td>
                <td>{{ date('d F Y', strtotime($bon->date)) }}</td>
                
                <td class="separator"></td>
                <td class="label">Divisi</td>
                <td class="separator">:</td>
                <td>{{ $bon->division_name ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Departemen</td>
                <td class="separator">:</td>
                <td>{{ $bon->department ? $bon->department->name : '-' }}</td>

                <td class="separator"></td>
                <td class="label">Keterangan</td>
                <td class="separator">:</td>
                <td>{{ $bon->notes ?: '-' }}</td>
            </tr>
        </table>

        <table class="data-table">
            <col style="width: 5%">  
            <col style="width: 15%"> 
            <col style="width: 40%"> 
            <col style="width: 10%"> 
            <col style="width: 10%"> 
            <col style="width: 20%"> 
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Satuan</th>
                    <th>Qty</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php $counter = 1; @endphp
                @foreach($bon->details as $detail)
                @php
                    // Default ambil quantity (permintaan)
                    $displayQty = $detail->quantity;

                    // TAPI, jika issued_quantity TIDAK NULL, maka pakai issued_quantity
                    // Ini akan otomatis menghandle status apapun, selama admin sudah input angka issued
                    if (!is_null($detail->issued_quantity)) {
                        $displayQty = $detail->issued_quantity;
                    } elseif (!is_null($detail->approved_quantity)) {
                        $displayQty = $detail->approved_quantity;
                    }
                @endphp
                @if((float)$displayQty <= 0)
                    @continue
                @endif
                <tr>
                    <td class="text-center">{{ $counter++ }}</td>
                    <td class="text-center">{{ $detail->item ? $detail->item->code : '-' }}</td>
                    <td style="font-weight:bold;">{{ $detail->item ? $detail->item->name : 'Item #'.$detail->item_id }}</td>
                    <td class="text-center">{{ $detail->item ? $detail->item->unit : '' }}</td>
                    
                    {{-- FIX LOGIC QTY: PRIORITAS ISSUED QUANTITY --}}
                    <td class="text-center" style="font-weight:bold; font-size:12pt;">
                        {{ (float)$displayQty }}
                    </td>
                    
                    <td>{{ $detail->notes }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signature-wrapper">
            <div class="sign-box">
                <div class="sign-title">Disetujui</div>
                <div class="sign-name">( ........................... )</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">Pemberi</div>
                <div class="sign-name">( ........................... )</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">Penerima</div>
                <div class="sign-name">( ........................... )</div>
            </div>
        </div>
    </div>

    <script>
        // window.print();
    </script>
</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cetak LPB {{ $lpb->lpb_number }}</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <style>
        body{font-size:11px;color:#111;padding:20px;}
        h1{font-size:16px;font-weight:600;margin-bottom:4px}
        h2{font-size:13px;font-weight:600;margin-top:0}
        .header-table td{padding:2px 4px;vertical-align:top}
        .table>thead>tr>th,
        .table>tbody>tr>td{padding:4px 6px;font-size:11px;}
        .text-right{text-align:right;}
        .small{font-size:10px;color:#6b7280}
        @media print{
            body{padding:0;margin:0;}
            .no-print{display:none;}
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;">
        <button onclick="window.print()" class="btn btn-default btn-xs">
            <i class="fa fa-print"></i> Cetak
        </button>
    </div>

    <h1>PT OTTO PHARMACEUTICAL INDUSTRIES</h1>
    <h2>LPB Persediaan Umum</h2>
    <hr style="margin:6px 0 10px 0;">

    <table class="header-table" style="width:100%;margin-bottom:8px;">
        <tr>
            <td style="width:18%;">No LPB</td>
            <td style="width:2%;">:</td>
            <td style="width:30%;"><strong>{{ $lpb->lpb_number }}</strong></td>
            <td style="width:18%;">Tanggal</td>
            <td style="width:2%;">:</td>
            <td>{{ $lpb->date ? $lpb->date->format('d/m/Y') : '-' }}</td>
        </tr>
        <tr>
            <td>Pemasok / Ref</td>
            <td>:</td>
            <td>{{ $lpb->vendor ?: '-' }}</td>
            <td>Catatan</td>
            <td>:</td>
            <td>{{ $lpb->notes }}</td>
        </tr>
    </table>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th>Item</th>
            <th style="width:70px;">Satuan</th>
            <th style="width:90px;" class="text-right">Qty</th>
            <th style="width:100px;" class="text-right">Harga</th>
            <th style="width:120px;" class="text-right">Total</th>
        </tr>
        </thead>
        <tbody>
        @php $grandTotal = 0; @endphp
        @foreach($details as $idx => $detail)
            @php
                $item  = $detail->item;
                $total = $detail->quantity * $detail->price;
                $grandTotal += $total;
            @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>
                    @if($item)
                        [{{ $item->code }}] {{ $item->name }}
                    @else
                        Item #{{ $detail->item_id }}
                    @endif
                </td>
                <td>{{ $detail->unit ?: '-' }}</td>
                <td class="text-right">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th colspan="5" class="text-right">Total</th>
            <th class="text-right">{{ number_format($grandTotal, 0, ',', '.') }}</th>
        </tr>
        </tfoot>
    </table>

    <p class="small">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}.
    </p>
</body>
</html>

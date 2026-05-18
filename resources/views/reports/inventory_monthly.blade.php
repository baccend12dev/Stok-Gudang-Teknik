@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL LAPORAN BULANAN (THE ACCOUNTANT) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-calculator"></i>
        <strong>LOGIKA DASAR:</strong> Laporan ini menggunakan metode <em>Flow Calculation</em>.
        <br><code>Saldo Awal + Total Masuk (LPB) - Total Keluar (BON) = <strong>Saldo Akhir</strong></code>
    </div>

    <h4 class="help-h"><i class="fa fa-mouse-pointer text-primary"></i> Fitur Drill Down (Klik Angka)</h4>
    <p class="help-p">
        Angka yang berwarna <span style="color:#2563eb; text-decoration:underline;">Biru</span> pada kolom <strong>MASUK</strong> dan <strong>KELUAR</strong> dapat diklik.
        <br>Sistem akan menampilkan rincian dokumen (Nomor BON/LPB) yang membentuk angka tersebut untuk keperluan audit/tracking.
    </p>

    <h4 class="help-h"><i class="fa fa-file-excel-o text-success"></i> Format Export Excel</h4>
    <p class="help-p">
        Tombol <strong>"Export Excel"</strong> di atas akan mengunduh file dengan format standar Akunting.
        <br><em>Note: Kolom Harga/Rupiah di Excel disediakan (kosong/0) agar Departemen Keuangan dapat langsung mengisi nilai valuasi tanpa perlu mengubah format laporan.</em>
    </p>
@endsection

@section('content')
{{-- Load Flatpickr CSS (Format d/m/Y) --}}
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

<style>
    body { background-color: #f5f5f5 !important; }
    .page-container { max-width: 100%; margin: 0 auto; padding: 0 24px 24px; box-sizing: border-box; }
    .dashboard-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .dashboard-title { font-size: 20px; font-weight: 700; margin: 0; color: #111827; }
    
    /* FIX: PERKECIL UKURAN FLATPICKR */
    .flatpickr-calendar { font-size: 12px !important; width: 310px !important; }
    .flatpickr-rContainer, .flatpickr-days, .dayContainer { width: 310px !important; }
    .flatpickr-day { height: 32px !important; line-height: 32px !important; max-width: 42px !important; }
    .flatpickr-current-month { font-size: 110% !important; padding-top: 10px !important; }

    .section { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); padding: 16px; }

    /* Filter Bar */
    .filter-box { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; }
    .filter-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0; }
    .form-control-sm { height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; min-width: 160px; }
    /* FIX: Input Flatpickr background putih */
    .form-control-sm.flatpickr-input { background-color: #fff !important; }
    
    .btn-filter { height: 38px; padding: 0 20px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-filter:hover { background: #1d4ed8; }

    /* Tombol Kembali */
    .btn-back {
        height: 38px; padding: 0 16px; background: #fff; border: 1px solid #d1d5db; 
        border-radius: 8px; color: #374151; font-size: 13px; font-weight: 600; 
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-back:hover { background: #f3f4f6; color: #111827; }

    /* Checkbox */
    .toggle-wrapper { display: flex; align-items: center; gap: 8px; height: 38px; padding-left: 16px; border-left: 1px solid #e5e7eb; margin-left: auto; }
    .custom-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #2563eb; margin: 0; }
    .checkbox-label { font-size: 13px; font-weight: 500; color: #374151; cursor: pointer; user-select: none; margin: 0; line-height: 1; }

    /* Table Compact */
    .table-container { width: 100%; overflow-x: hidden; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; position: relative; }
    .table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .table th, .table td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
    
    .table thead th { background: #f8fafc; font-weight: 700; text-align: center; color: #4b5563; text-transform: uppercase; position: sticky; top: 0; z-index: 5; }
    
    /* Expand Button */
    .toggle-btn { cursor: pointer; color: #2563eb; font-size: 14px; transition: transform 0.2s; width: 20px; text-align: center; display: inline-block; }
    .toggle-btn:hover { color: #1d4ed8; }
    
    /* Expanded Detail Row */
    .tr-detail { background-color: #f8fafc; display: none; }
    .detail-box { padding: 15px; border-left: 3px solid #2563eb; margin: 5px 10px 15px 30px; background: #fff; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
    .dept-item { display: flex; justify-content: space-between; padding: 6px 10px; border-radius: 4px; background: #f1f5f9; font-size: 11px; color: #475467; border: 1px solid #e2e8f0; }
    .dept-item strong { color: #0f172a; }
    .dept-val { cursor: pointer; text-decoration: underline; color: #2563eb; font-weight: 700; }

    /* Row Styling */
    .tr-category td { background-color: #fffbeb; font-weight: 800; color: #92400e; text-transform: uppercase; border-top: 2px solid #fcd34d; padding: 12px 15px; }
    .tr-total td { background-color: #f1f5f9; font-weight: 800; color: #334155; border-top: 2px solid #cbd5e1; }
    .tr-grand-total td { background-color: #e2e8f0; color: #1e293b; font-weight: 800; border-top: 2px solid #94a3b8; }

    .bg-awal { background-color: #eff6ff; }
    .bg-masuk { background-color: #f0fdf4; color: #16a34a; font-weight: bold; }
    .bg-keluar { background-color: #fff7ed; color: #c2410c; font-weight: bold; }
    .bg-akhir { background-color: #f8fafc; font-weight: bold; }

    .clickable { cursor: pointer; text-decoration: underline; color: #2563eb; }
    .clickable:hover { color: #1d4ed8; background-color: rgba(37, 99, 235, 0.1); }
    
    .text-red { color: #dc2626; }
    
    /* Text Helpers */
    .text-center { text-align: center; } .text-right { text-align: right; } .text-left { text-align: left; }
    
    /* Modal */
    .modal-header { background: #f8fafc; border-bottom: 1px solid #e5e7eb; padding: 15px 20px; border-radius: 10px 10px 0 0; }
    .drill-table th { background: #f9fafb; padding: 10px; font-weight: 700; border-bottom: 2px solid #eee; }
    .drill-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
</style>

<div class="page-container">
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Laporan Persediaan Bulanan</h1>
            <p style="font-size:12px; color:#64748b; margin-top:4px;">
                <i class="fa fa-calendar"></i> Periode: {{ date('d M Y', strtotime($startDate)) }} — {{ date('d M Y', strtotime($endDate)) }}
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('reports.index') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> Kembali ke Laporan
            </a>
             <a href="{{ route('reports.inventory_monthly.export', request()->all()) }}" class="btn btn-success" style="padding:8px 16px; border-radius:8px; font-weight:600; text-decoration:none; color:#fff; background:#10b981; border:none; display:inline-flex; align-items:center; gap:6px;">
                <i class="fa fa-file-excel-o"></i> Export Excel
             </a>
        </div>
    </div>

    <div class="section">
        <form method="GET" action="{{ route('reports.inventory_monthly.index') }}" class="filter-box">
            <div class="filter-item">
                <label class="filter-label">Dari Tanggal</label>
                {{-- FIX: Flatpickr d/m/Y --}}
                <input type="text" name="start_date" 
                       value="{{ $startDate }}" 
                       class="form-control-sm datepicker-flat" placeholder="dd/mm/yyyy">
            </div>
            <div class="filter-item">
                <label class="filter-label">Sampai Tanggal</label>
                {{-- FIX: Flatpickr d/m/Y --}}
                <input type="text" name="end_date" 
                       value="{{ $endDate }}" 
                       class="form-control-sm datepicker-flat" placeholder="dd/mm/yyyy">
            </div>
            
            <div class="filter-item">
                <label class="filter-label">&nbsp;</label>
                <button type="submit" class="btn-filter"><i class="fa fa-filter"></i> Tampilkan Data</button>
            </div>

            <div class="toggle-wrapper">
                <input type="checkbox" id="toggleZero" class="custom-checkbox" checked>
                <label for="toggleZero" class="checkbox-label">Sembunyikan Item Tanpa Transaksi</label>
            </div>
        </form>
    </div>

    <div class="table-container" id="tableContainer">
        <table class="table" id="reportTable">
            <thead>
                <tr>
                    <th style="width:30px;"></th> <th style="width:40px;">NO</th>
                    <th style="width:100px;">KODE</th>
                    <th style="text-align:left;">NAMA BARANG</th>
                    <th style="width:100px;">SAT</th>
                    <th style="width:150px;" class="bg-awal">SALDO AWAL</th>
                    <th style="width:150px;" class="bg-masuk">MASUK</th>
                    <th style="width:150px;" class="bg-keluar">TOTAL KELUAR</th>
                    <th style="width:150px;" class="bg-akhir">SALDO AKHIR</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $globalCounter = 1; 
                    $grandTotal = ['awal' => 0, 'masuk' => 0, 'keluar' => 0, 'akhir' => 0];
                @endphp

                @foreach($groupedData as $category => $items)
                
                {{-- Logic Hide Category --}}
                @php
                    $hasActiveItem = false;
                    foreach($items as $row) {
                        if($row->masuk > 0 || $row->keluar_total > 0 || $row->saldo_awal != $row->saldo_akhir) {
                            $hasActiveItem = true; break;
                        }
                    }
                    $catRowClass = $hasActiveItem ? 'cat-has-data' : 'cat-empty';
                @endphp

                <tr class="tr-category {{ $catRowClass }}">
                    <td colspan="9">{{ $category }}</td>
                </tr>

                @php
                    $subAwal = 0; $subMasuk = 0; $subKeluar = 0; $subAkhir = 0;
                @endphp

                @foreach($items as $row)
                @php 
                    $hasMovement = ($row->masuk > 0 || $row->keluar_total > 0 || $row->saldo_awal != $row->saldo_akhir);
                    $rowClass = $hasMovement ? 'row-move' : 'row-zero';
                    $isCritical = $row->saldo_akhir <= $row->buffer_min;
                    $awalStyle = $row->saldo_awal == 0 ? 'color:#cbd5e1;' : 'font-weight:600;';
                    
                    // FIX: Rounding accumulators
                    $subAwal   = round($subAwal + (float)$row->saldo_awal, 2); 
                    $subMasuk  = round($subMasuk + (float)$row->masuk, 2); 
                    $subKeluar = round($subKeluar + (float)$row->keluar_total, 2); 
                    $subAkhir  = round($subAkhir + (float)$row->saldo_akhir, 2);
                    
                    // ESCAPE ITEM NAME UNTUK JS
                    $safeName = addslashes($row->name);
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="text-center">
                        @if($row->keluar_total > 0)
                            <i class="fa fa-plus-square toggle-btn" data-target="#detail-{{ $row->id }}"></i>
                        @endif
                    </td>
                    <td class="text-center" style="color:#64748b">{{ $globalCounter++ }}</td>
                    <td class="text-center" style="font-weight:600; color:#475467;">{{ $row->code }}</td>
                    <td title="{{ $row->name }}">{{ $row->name }}</td>
                    <td class="text-center">{{ $row->unit }}</td>
                    
                    <td class="text-center bg-awal" style="{{ $awalStyle }}">
                        {{ (float)$row->saldo_awal == 0 ? '' : (float)$row->saldo_awal }}
                    </td>
                    
                    {{-- FIX: CLICKABLE LINK (DIPERBAIKI DENGAN LOGIC BARU) --}}
                    <td class="text-center bg-masuk {{ $row->masuk > 0 ? 'clickable' : '' }}" 
                        @if($row->masuk > 0)
                            onclick="showDetails('in', '{{ $row->id }}', null, '{{ $safeName }}')"
                        @endif
                    >
                        {{ $row->masuk > 0 ? '+'.(float)$row->masuk : '' }}
                    </td>

                    {{-- FIX: CLICKABLE LINK (DIPERBAIKI DENGAN LOGIC BARU) --}}
                    <td class="text-center bg-keluar {{ $row->keluar_total > 0 ? 'clickable' : '' }}"
                        @if($row->keluar_total > 0)
                            onclick="showDetails('out', '{{ $row->id }}', null, '{{ $safeName }} (Total)')"
                        @endif
                    >
                         {{ $row->keluar_total == 0 ? '' : (float)$row->keluar_total }}
                    </td>

                    <td class="text-center bg-akhir {{ $isCritical ? 'text-red' : '' }}">
                        {{ (float)$row->saldo_akhir == 0 ? '' : (float)$row->saldo_akhir }}
                    </td>
                </tr>

                {{-- DETAIL ROW (ACCORDION) --}}
                @if($row->keluar_total > 0)
                <tr class="tr-detail" id="detail-{{ $row->id }}">
                    <td colspan="9" style="padding:0; border:none;">
                        <div class="detail-box">
                            <div style="margin-bottom:8px; font-weight:700; font-size:11px; color:#64748b; text-transform:uppercase;">Rincian Penggunaan per Departemen:</div>
                            <div class="dept-grid">
                                @foreach($departments as $dept)
                                    @if(isset($row->dept_usage[$dept->id]) && $row->dept_usage[$dept->id] > 0)
                                        <div class="dept-item">
                                            <span>{{ $dept->name }}</span>
                                            {{-- FIX: CLICKABLE LINK ACCORDION --}}
                                            <span class="dept-val" onclick="showDetails('out', '{{ $row->id }}', '{{ $dept->id }}', '{{ $safeName }} - {{ addslashes($dept->name) }}')">
                                                {{ (float)$row->dept_usage[$dept->id] }}
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </td>
                </tr>
                @endif

                @endforeach

                @php
                    // FIX: Rounding Grand Total
                    $grandTotal['awal']   = round($grandTotal['awal'] + $subAwal, 2); 
                    $grandTotal['masuk']  = round($grandTotal['masuk'] + $subMasuk, 2); 
                    $grandTotal['keluar'] = round($grandTotal['keluar'] + $subKeluar, 2); 
                    $grandTotal['akhir']  = round($grandTotal['akhir'] + $subAkhir, 2);
                @endphp

                <tr class="tr-total {{ $catRowClass }}">
                    <td colspan="5" class="text-right" style="padding-right:20px;">TOTAL {{ $category }}</td>
                    <td class="text-center">{{ $subAwal==0?'':(float)$subAwal }}</td>
                    <td class="text-center">{{ $subMasuk==0?'':(float)$subMasuk }}</td>
                    <td class="text-center">{{ $subKeluar==0?'':(float)$subKeluar }}</td>
                    <td class="text-center">{{ $subAkhir==0?'':(float)$subAkhir }}</td>
                </tr>

                @endforeach

                <tr class="tr-grand-total">
                    <td colspan="5" class="text-right" style="padding-right:20px;">GRAND TOTAL</td>
                    <td class="text-center">{{ $grandTotal['awal']==0?'':(float)$grandTotal['awal'] }}</td>
                    <td class="text-center">{{ $grandTotal['masuk']==0?'':(float)$grandTotal['masuk'] }}</td>
                    <td class="text-center">{{ $grandTotal['keluar']==0?'':(float)$grandTotal['keluar'] }}</td>
                    <td class="text-center">{{ $grandTotal['akhir']==0?'':(float)$grandTotal['akhir'] }}</td>
                </tr>

            </tbody>
        </table>
    </div>
</div>

{{-- MODAL & SCRIPT --}}
<div class="modal fade" id="drillModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="border-radius:10px;">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="drillTitle" style="font-weight:700;">Detail Transaksi</h4>
      </div>
      <div class="modal-body">
         <div id="drillLoading" style="text-align:center; padding:20px; color:#64748b;">
            <i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...
         </div>
         <div class="table-responsive">
             <table class="drill-table" id="drillContent" style="display:none; width:100%">
                 <thead><tr><th>Tanggal</th><th>No Dokumen</th><th>Sumber/Tujuan</th><th style="text-align:right">Qty</th><th>Ket</th></tr></thead>
                 <tbody id="drillBody"></tbody>
             </table>
         </div>
         <div id="drillEmpty" style="display:none; text-align:center; padding:20px; color:#94a3b8;">Tidak ada detail transaksi.</div>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
{{-- Script Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    // 1. Definisikan fungsi secara global (di luar jQuery ready)
    //    agar bisa dipanggil oleh onclick attribute di HTML
    window.showDetails = function(type, itemId, deptId, titleName) {
        $('#drillModal').modal('show');
        $('#drillTitle').text('Detail: ' + titleName);
        $('#drillLoading').show(); 
        $('#drillContent').hide(); 
        $('#drillEmpty').hide(); 
        $('#drillBody').empty();

        // FIX: GUNAKAN LOGIC LINK URL DENGAN QUERY PARAMS
        var baseUrl = '{{ url("/") }}';
        var dateFrom = '{{ $startDate }}';
        var dateTo = '{{ $endDate }}';
        
        // URL Controller untuk ambil JSON data
        var ajaxUrl = '{{ route("reports.inventory_monthly.drill_down") }}';
        
        $.ajax({
            url: ajaxUrl,
            data: { 
                type: type, 
                item_id: itemId, 
                dept_id: deptId, 
                start_date: dateFrom, 
                end_date: dateTo 
            },
            success: function(data) {
                $('#drillLoading').hide();
                if(data.length > 0) {
                    var html = '';
                    data.forEach(function(row){
                        var d = new Date(row.date);
                        var dateStr = d.getDate() + '/' + (d.getMonth()+1) + '/' + d.getFullYear();
                        var linkUrl = '#';
                        
                        // FIX: Logic Link URL ke Index Page dengan Filter
                        // LPB -> Index LPB dengan filter nomor dokumen
                        if(row.doc_type === 'LPB') {
                            linkUrl = baseUrl + '/lpbs?q=' + row.doc_number + '&from=' + dateFrom + '&to=' + dateTo;
                        } 
                        // BON -> Show BON (Karena BON Show page sudah detail)
                        else if(row.doc_type === 'BON') {
                            linkUrl = baseUrl + '/bons/' + row.doc_id;
                        }
                        // SO -> Show SO
                        else if(row.doc_type === 'SO') {
                            linkUrl = baseUrl + '/stock-opnames/' + row.doc_id;
                        }

                        // FIX: Tampilkan float quantity
                        var qtyDisplay = parseFloat(row.quantity);

                        html += '<tr>' +
                                '<td>'+ dateStr +'</td>' +
                                '<td><a href="'+linkUrl+'" target="_blank" style="font-weight:700; color:#2563eb; text-decoration:underline;">'+ row.doc_number +'</a></td>' +
                                '<td>'+ (row.source || '-') +'</td>' +
                                '<td style="text-align:right; font-weight:bold">'+ qtyDisplay +'</td>' +
                                '<td style="color:#64748b; font-size:11px;">'+ (row.notes || '-') +'</td>' +
                                '</tr>';
                    });
                    $('#drillBody').html(html); 
                    $('#drillContent').show();
                } else { 
                    $('#drillEmpty').show(); 
                }
            },
            error: function() { 
                $('#drillLoading').hide(); 
                $('#drillEmpty').text('Gagal memuat data.').show(); 
            }
        });
    };

    jQuery(document).ready(function($) {
        // FIX: Inisialisasi Flatpickr
        $(".datepicker-flat").flatpickr({
            altInput: true,      
            altFormat: "d/m/Y",  // Format visual: DAY/MONTH/YEAR
            dateFormat: "Y-m-d", // Format data: YYYY-MM-DD
            locale: "id",        
            allowInput: true     
        });

        // Toggle Zero Logic
        function applyFilter() {
            if($('#toggleZero').is(':checked')) {
                $('.row-zero').hide();
                $('.tr-detail.row-zero').hide(); 
                $('.tr-category.cat-empty').hide(); 
                $('.tr-total.cat-empty').hide();
            } else {
                $('.row-zero').show();
                $('.tr-category').show();
                $('.tr-total').show();
                $('.tr-detail').hide(); 
                $('.toggle-btn i').removeClass('fa-minus-square').addClass('fa-plus-square');
            }
        }
        $('#toggleZero').change(applyFilter);
        applyFilter();

        // Accordion Logic
        $(document).on('click', '.toggle-btn', function(){
            var $btn = $(this);
            var $tr = $btn.closest('tr');
            var $detail = $tr.next('.tr-detail');
            
            if($detail.is(':visible')) {
                $detail.hide();
                $btn.removeClass('fa-minus-square').addClass('fa-plus-square');
            } else {
                $detail.show();
                $btn.removeClass('fa-plus-square').addClass('fa-minus-square');
            }
        });
    });
</script>
@endsection
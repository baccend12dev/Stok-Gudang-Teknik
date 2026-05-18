@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL DETAIL STOCK OPNAME (AUDIT RESULT) === --}}
@section('help-content')
    <div class="help-alert" style="background:#eff6ff; border-left-color:#2563eb; color:#1e40af;">
        <i class="fa fa-balance-scale"></i>
        <strong>STATUS: {{ $opname->status }}</strong>. 
        @if($opname->status == 'DRAFT')
            Data ini masih berupa "Kertas Kerja". Stok gudang belum berubah.
        @else
            Stok gudang sudah disesuaikan (Adjustment) mengikuti hasil fisik di bawah ini.
        @endif
    </div>

    <h4 class="help-h"><i class="fa fa-search text-primary"></i> Cara Analisa Cepat</h4>
    <ul class="help-list">
        <li>
            <strong>Filter "Selisih Only":</strong> 
            Klik kotak merah/summary di bagian KPI untuk menyaring tabel agar hanya menampilkan barang yang bermasalah (Fisik ≠ Sistem).
        </li>
        <li>
            <strong>Arti Angka Selisih:</strong>
            <ul style="margin-top:4px; font-size:11px;">
                <li><span style="color:#16a34a; font-weight:800;">(+) Hijau</span> : Barang fisik LEBIH BANYAK dari sistem (Surplus/Barang Temuan).</li>
                <li><span style="color:#dc2626; font-weight:800;">(-) Merah</span> : Barang fisik KURANG dari sistem (Barang Hilang/Rusak/Admin Lupa Catat Keluar).</li>
            </ul>
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-bolt text-danger"></i> Tindakan Eksekusi</h4>
    <ul class="help-list">
        <li>
            <strong>Proses & Update Stok:</strong> Menjadikan data fisik sebagai stok master yang baru. Selisih akan dicatat sebagai riwayat adjustment.
        </li>
        <li>
            <strong>Rollback (Batalkan):</strong> Mengembalikan stok ke kondisi sebelum SO dilakukan. Gunakan ini jika Anda tidak sengaja memproses data yang salah.
        </li>
    </ul>
@endsection

@section('content')
<style>
    /* --- SYSTEM STYLES (Konsisten dengan Create/Edit) --- */
    :root {
        --primary: #2563eb; --primary-hover: #1d4ed8; --primary-light: #eff6ff;
        --success: #10b981; --success-hover: #059669; /* Hover lebih gelap */
        --warning: #f59e0b; --warning-hover: #d97706;
        --danger: #ef4444; --danger-hover: #dc2626;
        --border: #e2e8f0; --text: #334155; --muted: #64748b;
        --bg-body: #f1f5f9;
    }
    
    body { background-color: var(--bg-body) !important; color: var(--text); font-family: 'Inter', sans-serif; }
    .page-container { width: 100%; padding: 24px 32px; box-sizing: border-box; }
    
    /* UTILS */
    *:focus { outline: none !important; }
    a { text-decoration: none !important; }

    /* HEADER WRAP */
    .header-wrap { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .header-left { display: flex; align-items: center; gap: 16px; }
    
    /* Icon Box (Supaya serasi sama Create/Edit) */
    .header-icon-box {
        width: 48px; height: 48px; 
        background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
        border: 1px solid #dbeafe; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: var(--primary); font-size: 20px;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1);
    }
    .header-title h1 { font-size: 24px; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; letter-spacing: -0.5px; }
    .header-title p { font-size: 13px; color: var(--muted); margin: 2px 0 0; font-weight: 500; }

    /* BUTTONS MODERN (FIX HOVER PUCAT) */
    .btn-action { 
        height: 40px; padding: 0 18px; border-radius: 8px; font-weight: 600; font-size: 13px; 
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; 
        border: 1px solid transparent; cursor: pointer; transition: all 0.2s ease;
        text-decoration: none !important;
    }
    
    /* Green Button Fix */
    .btn-green { background: var(--success); color: #fff !important; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2); }
    .btn-green:hover { background: var(--success-hover); transform: translateY(-1px); box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3); color: #fff; }
    
    /* Orange Button */
    .btn-orange { background: var(--warning); color: #fff !important; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2); }
    .btn-orange:hover { background: var(--warning-hover); transform: translateY(-1px); color: #fff; }
    
    /* Red Button */
    .btn-red { background: var(--danger); color: #fff; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2); }
    .btn-red:hover { background: var(--danger-hover); transform: translateY(-1px); color: #fff; }
    
    /* White/Default Button */
    .btn-white { background: #fff; border: 1px solid #cbd5e1; color: #475569; }
    .btn-white:hover { background: #f8fafc; border-color: #94a3b8; color: #1e293b; }
    
    /* Locked Button */
    .btn-locked { background: #f1f5f9; border: 1px solid #e2e8f0; color: #94a3b8; cursor: default; }

    /* INFO SECTION & CARDS */
    .card-modern { 
        background: #fff; border-radius: 12px; border: 1px solid var(--border); 
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 24px; overflow: hidden;
    }
    .card-pd { padding: 20px 24px; }

    /* INFO GRID SYSTEM (PENGGANTI TABLE YANG BERANTAKAN) */
    .info-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px; align-items: start;
    }
    .info-item { display: flex; flex-direction: column; gap: 4px; }
    .info-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
    .info-value.notes { color: #64748b; font-style: italic; line-height: 1.4; }

    /* BADGES STATUS */
    .badge-pill { padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; }
    .bg-draft { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
    .bg-processed { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .dot { width: 6px; height: 6px; border-radius: 50%; }

    /* KPI CARDS */
    .kpi-container { display: flex; gap: 16px; margin-top: 24px; flex-wrap: wrap; border-top: 1px dashed #e2e8f0; padding-top: 24px; }
    .summary-card { 
        flex: 1; min-width: 160px;
        display: flex; align-items: center; gap: 14px; 
        padding: 16px; background: #fff; 
        border: 1px solid #e2e8f0; border-radius: 12px; 
        cursor: pointer; transition: all 0.2s; 
    }
    .summary-card:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .summary-card.active { border-color: var(--primary); background: #eff6ff; box-shadow: 0 0 0 1px var(--primary); }
    
    .sc-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .sc-blue .sc-icon { background: #eff6ff; color: #2563eb; }
    .sc-red .sc-icon { background: #fef2f2; color: #dc2626; }
    .sc-green .sc-icon { background: #f0fdf4; color: #16a34a; }
    
    .sc-info div { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
    .sc-info strong { font-size: 22px; color: #0f172a; font-weight: 800; line-height: 1; }

    /* TABLE PERFECT (MATCHING OTHER PAGES) */
    .table-container { border-radius: 0 0 12px 12px; overflow: hidden; border-top: 1px solid #e2e8f0; }
    .table-responsive { max-height: 600px; overflow-y: auto; }
    .table-perf { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-perf th { 
        background: #f8fafc; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; 
        color: #475569; font-weight: 800; font-size: 11px; text-transform: uppercase; 
        position: sticky; top: 0; z-index: 10; white-space: nowrap;
    }
    .table-perf td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle; }
    .table-perf tr:hover td { background: #f8fafc; }
    .table-perf tr:last-child td { border-bottom: none; }

    /* SEARCH BAR */
    .section-toolbar { 
        padding: 12px 24px; background: #fff; border-bottom: 1px solid #f1f5f9; 
        display: flex; justify-content: space-between; align-items: center;
    }
    .search-input {
        width: 250px; height: 36px; padding: 0 12px; border: 1px solid #cbd5e1; 
        border-radius: 8px; font-size: 13px; color: #1e293b; transition: 0.2s;
    }
    .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

    /* MODAL MODERN */
    .modal-content.modern-modal { border-radius:16px; border:none; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
    .modern-modal .modal-header { background:#fff; padding:24px 24px 10px; border:none; }
    .modern-modal .modal-title { font-weight:800; font-size:18px; color:#1e293b; display: flex; align-items: center; gap: 10px; }
    .modern-modal .modal-body { padding:10px 24px 24px; color:#64748b; font-size:14px; line-height:1.6; }
    .modern-modal .modal-footer { background:#f8fafc; padding:16px 24px; border-top:1px solid #e2e8f0; }
    
    .alert-box { padding: 12px; border-radius: 8px; font-size: 13px; margin-top: 10px; display: flex; gap: 10px; align-items: flex-start; }
    .alert-warn { background: #fffbeb; color: #b45309; border: 1px solid #fcd34d; }
    .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
</style>

<div class="page-container">
    {{-- HEADER --}}
    <div class="header-wrap">
        <div class="header-left">
            <div class="header-icon-box">
                @if($opname->status == 'DRAFT') <i class="fa fa-file-text-o"></i>
                @else <i class="fa fa-check-square-o"></i> @endif
            </div>
            <div class="header-title">
                <h1>Detail Stock Opname</h1>
                <p>Informasi detail hasil perhitungan stok fisik.</p>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
             {{-- EXPORT BUTTON --}}
             <a href="{{ route('stock-opnames.export', $opname->id) }}" class="btn-action btn-green" target="_blank">
                <i class="fa fa-file-excel-o"></i> Export Excel
             </a>

             @if($opname->status === 'DRAFT')
                {{-- TOMBOL PROSES --}}
                <form id="form-process" action="{{ route('stock-opnames.process', $opname->id) }}" method="POST" style="display:inline;">
                    {{ csrf_field() }}
                    <button type="button" class="btn-action btn-green" onclick="confirmProcess()">
                        <i class="fa fa-check-circle"></i> Proses & Update Stok
                    </button>
                </form>
                
                <a href="{{ route('stock-opnames.edit', $opname->id) }}" class="btn-action btn-orange">
                    <i class="fa fa-pencil"></i> Edit Draft
                </a>
             @else
                {{-- TOMBOL ROLLBACK --}}
                <form id="form-rollback" action="{{ route('stock-opnames.rollback', $opname->id) }}" method="POST" style="display:inline;">
                    {{ csrf_field() }}
                    <button type="button" class="btn-action btn-red" onclick="confirmRollback()">
                        <i class="fa fa-undo"></i> Batalkan / Revisi
                    </button>
                </form>

                <div class="btn-action btn-locked">
                    <i class="fa fa-lock"></i> Terkunci (Processed)
                </div>
             @endif

             {{-- DELETE --}}
             <button type="button" class="btn-action btn-red" onclick="confirmDelete()">
                <i class="fa fa-trash"></i>
             </button>

             <a href="{{ route('stock-opnames.index') }}" class="btn-action btn-white">
                <i class="fa fa-arrow-left"></i> Kembali
             </a>
        </div>
    </div>

    {{-- INFO CARD & KPI --}}
    <div class="card-modern">
        <div class="card-pd">
            {{-- GRID INFO (RAPI) --}}
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Referensi Dokumen</span>
                    <span class="info-value" style="font-family:monospace; color:var(--primary); background:#eff6ff; padding:2px 6px; border-radius:4px; align-self:start;">
                        #{{ $opname->id }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Opname</span>
                    <span class="info-value">{{ $opname->opname_date->format('d F Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <div>
                        @if($opname->status === 'DRAFT') 
                            <span class="badge-pill bg-draft"><span class="dot" style="background:#9ca3af"></span> Draft</span>
                        @else 
                            <span class="badge-pill bg-processed"><span class="dot" style="background:#16a34a"></span> Processed</span> 
                        @endif
                    </div>
                </div>
                <div class="info-item" style="flex: 2;">
                    <span class="info-label">Catatan</span>
                    <span class="info-value notes">{{ $opname->notes ?: '-' }}</span>
                </div>
            </div>

            {{-- KPI STATS --}}
            @php
                $countTotal = $opname->details->count();
                // FIX: Gunakan float comparison agar akurat
                $countDiff = $opname->details->filter(function($d){ return (float)$d->difference != 0; })->count();
                $countMatch = $countTotal - $countDiff;
            @endphp

            <div class="kpi-container">
                <div class="summary-card sc-blue active" id="btnShowAll">
                    <div class="sc-icon"><i class="fa fa-list"></i></div>
                    <div class="sc-info">
                        <div>Total Item</div>
                        <strong>{{ $countTotal }}</strong>
                    </div>
                </div>
                <div class="summary-card sc-red" id="btnShowDiff">
                    <div class="sc-icon"><i class="fa fa-exclamation-triangle"></i></div>
                    <div class="sc-info">
                        <div>Selisih (Diff)</div>
                        <strong>{{ $countDiff }}</strong>
                    </div>
                </div>
                <div class="summary-card sc-green" id="btnShowMatch">
                    <div class="sc-icon"><i class="fa fa-check"></i></div>
                    <div class="sc-info">
                        <div>Sesuai (Match)</div>
                        <strong>{{ $countMatch }}</strong>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- TABLE SECTION --}}
        <div class="section-toolbar">
            <div style="font-size:13px; font-weight:700; color:#475569; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
                <i class="fa fa-table text-primary"></i> Rincian Barang
            </div>
            <input type="text" id="tableSearch" class="search-input" placeholder="Cari kode atau nama barang...">
        </div>
        
        <div class="table-container">
            <div class="table-responsive">
                <table class="table-perf">
                    <thead>
                        <tr>
                            <th class="text-center" width="50">No</th>
                            <th class="text-center" width="100">Kode</th>
                            <th>Nama Barang</th>
                            <th class="text-center" width="80">Sat</th>
                            <th class="text-center" width="100">Stok Sistem</th>
                            <th class="text-center" width="100">Stok Fisik</th>
                            <th class="text-center" width="100">Selisih</th>
                            <th width="200">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="showTbody">
                        @foreach($opname->details as $index => $d)
                        @php
                            // FIX: Cast ke Float untuk tampilan desimal
                            $sys = (float)$d->system_quantity;
                            $fis = (float)$d->physical_quantity;
                            $dif = (float)$d->difference;

                            $isDiff = $dif != 0;
                            $rowClass = $isDiff ? 'row-diff' : 'row-match';
                            $bgStyle = $isDiff ? 'background:#fff7ed;' : ''; // Soft orange row if diff
                            $diffColor = $dif > 0 ? '#16a34a' : ($dif < 0 ? '#dc2626' : '#9ca3af');
                            $sign = $dif > 0 ? '+' : '';
                        @endphp
                        <tr class="item-row {{ $rowClass }}" style="{{ $bgStyle }}">
                            <td class="text-center" style="color:#94a3b8;">{{ $index + 1 }}</td>
                            <td class="text-center" style="font-weight:700; color:#475569;">{{ $d->item->code }}</td>
                            <td class="item-name" style="font-weight:600; color:#1e293b;">{{ $d->item->name }}</td>
                            <td class="text-center" style="color:#64748b;">{{ $d->item->unit }}</td>
                            
                            {{-- FIX: Tampilan Desimal --}}
                            <td class="text-center" style="color:#64748b;">{{ $sys }}</td>
                            <td class="text-center" style="font-weight:700; color:#1e293b;">{{ $fis }}</td>
                            
                            <td class="text-center" style="font-weight:800; font-size:13px; color:{{ $diffColor }}">
                                {{ $sign }}{{ $dif }}
                            </td>
                            <td style="color:#475569; font-style:italic;">{{ $d->notes ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI PROSES --}}
<div class="modal fade" id="processModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fa fa-check-circle text-success"></i> Proses Stock Opname?</h4>
            </div>
            <div class="modal-body">
                <p>Anda yakin ingin memproses dokumen ini? Stok master akan diperbarui sesuai hasil fisik.</p>
                <div class="alert-box alert-warn">
                    <i class="fa fa-exclamation-triangle" style="font-size:16px;"></i>
                    <div>
                        <strong>Perhatian:</strong><br>
                        Tindakan ini akan mengupdate stok secara otomatis. Pastikan data sudah benar.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-white" data-dismiss="modal">Batal</button>
                <button type="button" class="btn-action btn-green" onclick="submitProcess()">Ya, Proses</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI ROLLBACK --}}
<div class="modal fade" id="rollbackModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fa fa-undo text-danger"></i> Batalkan Proses?</h4>
            </div>
            <div class="modal-body">
                <p>Anda yakin ingin mengembalikan status dokumen menjadi DRAFT?</p>
                <div class="alert-box alert-danger">
                    <i class="fa fa-info-circle" style="font-size:16px;"></i>
                    <div>
                        <strong>Efek Rollback:</strong><br>
                        Stok master akan dikembalikan ke posisi sebelum SO ini diproses.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-white" data-dismiss="modal">Batal</button>
                <button type="button" class="btn-action btn-red" onclick="submitRollback()">Ya, Batalkan</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI DELETE --}}
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h4 class="modal-title" style="color:#ef4444;"><i class="fa fa-trash"></i> Hapus Dokumen</h4>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus dokumen SO <strong>#{{ $opname->id }}</strong> secara permanen?</p>
                @if($opname->status === 'PROCESSED')
                <div class="alert-box alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    <div>
                        <strong>Warning (Processed):</strong><br>
                        Sistem akan melakukan ROLLBACK stok terlebih dahulu sebelum menghapus data.
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <form action="{{ route('stock-opnames.destroy', $opname->id) }}" method="POST">
                    {{ csrf_field() }} {{ method_field('DELETE') }}
                    <button type="button" class="btn-action btn-white" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-action btn-red">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script>
jQuery(function($){
    // FILTER BUTTONS LOGIC
    $('.summary-card').click(function(){
        $('.summary-card').removeClass('active');
        $(this).addClass('active');
        
        var id = $(this).attr('id');
        $('#showTbody tr').hide();
        
        if(id === 'btnShowAll') $('#showTbody tr').show();
        else if(id === 'btnShowDiff') $('#showTbody tr.row-diff').show();
        else if(id === 'btnShowMatch') $('#showTbody tr.row-match').show();
    });

    // SEARCH LOGIC
    $('#tableSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#showTbody tr").filter(function() {
            var text = $(this).find('.item-name').text().toLowerCase() + ' ' + $(this).find('td:eq(1)').text().toLowerCase();
            $(this).toggle(text.indexOf(value) > -1)
        });
    });
});

// GLOBAL FUNCTIONS FOR MODALS
function confirmProcess() {
    if (window.jQuery) jQuery('#processModal').modal('show');
}
function submitProcess() {
    document.getElementById('form-process').submit();
}

function confirmRollback() {
    if (window.jQuery) jQuery('#rollbackModal').modal('show');
}
function submitRollback() {
    document.getElementById('form-rollback').submit();
}

function confirmDelete() {
    if (window.jQuery) jQuery('#deleteModal').modal('show');
}
</script>
@endsection
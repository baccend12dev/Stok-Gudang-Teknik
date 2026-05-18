@extends('layouts.app')

@section('content')
@php
  // LOGIC VIEW: Menyiapkan Data
  $mode = request()->has('view') ? request('view') : 'item';

  // 1. Hitung Total Qty LPB
  $totalLPBQty = 0;
  foreach ($lpb->details as $d) { $totalLPBQty += (int)$d->quantity; }

  // 2. Siapkan Mapping Data Existing untuk Lookup Cepat
  $existingMap = [];
  if (!empty($existing)) {
      foreach($existing as $detailId => $quotas) {
          foreach($quotas as $q) {
              $existingMap[$detailId][$q->department_id] = (int)$q->quota_quantity;
          }
      }
  }

  // 3. Ringkasan per Departemen (Untuk Mode View 'dept')
  $deptSummary = [];
  foreach ($departments as $dept) {
      $deptSummary[$dept->id] = ['qty' => 0, 'items' => 0];
  }
  
  if (!empty($existingMap)) {
      foreach ($lpb->details as $d) {
          if (isset($existingMap[$d->id])) {
              foreach ($existingMap[$d->id] as $deptId => $qty) {
                  if ($qty > 0 && isset($deptSummary[$deptId])) {
                      $deptSummary[$deptId]['qty'] += $qty;
                      $deptSummary[$deptId]['items'] += 1;
                  }
              }
          }
      }
  }

  // 4. Urutkan Departemen A-Z (Visual Consistency)
  $sortedDepartments = $departments->sortBy('name');
@endphp

{{-- =======================  STYLE CSS MODERN  ======================= --}}
<style>
    :root {
        --primary: #2563eb;
        --primary-soft: #eff6ff; /* Highlight Background */
        --primary-border: #bfdbfe;
        --primary-text: #1e40af;
        
        --success: #10b981;
        --success-soft: #ecfdf5;
        
        --danger: #ef4444;
        --danger-soft: #fef2f2;
        --danger-border: #fca5a5;
        
        --text-main: #0f172a;
        --text-sub: #64748b;
        --text-muted: #94a3b8; /* Untuk Ghosting Effect */
        
        --border: #e2e8f0;
        --bg-card: #ffffff;
        --bg-body: #f8fafc;
        
        --radius: 12px;
    }

    body { background-color: var(--bg-body) !important; color: var(--text-main); font-family: 'Inter', sans-serif; }
    .main-content { background: transparent !important; }
    .page-container { width: 100%; padding: 20px 40px 100px; box-sizing: border-box; } /* Padding bawah ditambah utk footer */

    /* --- HEADER --- */
    .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .header-title-wrapper { display: flex; align-items: center; gap: 16px; }
    .header-icon-box {
        width: 48px; height: 48px; border-radius: 12px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 20px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.4);
    }
    .header-text h1 { font-size: 22px; font-weight: 700; margin: 0; color: var(--text-main); line-height: 1.2; }
    .header-text p { font-size: 13px; color: var(--text-sub); margin: 2px 0 0; }

    /* Toggle Switch */
    .view-toggle { display: inline-flex; background: #e2e8f0; padding: 4px; border-radius: 10px; }
    .view-toggle a {
        padding: 8px 18px; font-size: 12px; font-weight: 600; text-decoration: none; color: #64748b;
        border-radius: 8px; transition: all 0.2s;
    }
    .view-toggle a.active { background: white; color: var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .view-toggle a:hover:not(.active) { color: var(--text-main); }

    /* Breadcrumb */
    .top-bar { margin-bottom: 20px; }
    .breadcrumb-custom { display: flex; gap: 8px; font-size: 12px; color: var(--text-sub); align-items: center; }
    .breadcrumb-custom a { text-decoration: none; color: var(--primary); font-weight: 500; }
    .breadcrumb-custom .sep { color: #cbd5e1; }

    /* --- ITEM CARD --- */
    .item-card {
        background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius);
        margin-bottom: 24px; overflow: hidden; box-shadow: 0 2px 6px -1px rgba(0,0,0,0.03);
        transition: all 0.2s;
    }
    /* Error State Card */
    .item-card.error-state { 
        border-color: var(--danger); 
        box-shadow: 0 0 0 1px var(--danger-border), 0 4px 12px rgba(239, 68, 68, 0.1); 
    }
    /* Done State Card (Hijau tipis) */
    .item-card.done-state { border-color: #10b981; }

    /* Card Top */
    .card-top {
        padding: 16px 20px; border-bottom: 1px solid var(--border); background: #fff;
        display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;
    }
    .item-info { flex: 1; }
    .item-code {
        font-family: 'Monaco', monospace; font-size: 11px; color: var(--primary);
        background: var(--primary-soft); padding: 3px 8px; border-radius: 4px; display: inline-block; margin-bottom: 6px;
        border: 1px solid #dbeafe;
    }
    .item-name { font-size: 15px; font-weight: 700; color: var(--text-main); line-height: 1.4; }
    
    .item-stats { text-align: right; min-width: 150px; }
    .stat-label { font-size: 11px; color: var(--text-sub); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
    .stat-number { font-size: 20px; font-weight: 700; color: var(--text-main); }
    .stat-unit { font-size: 12px; color: var(--text-sub); font-weight: 500; margin-left: 2px; }

    /* Progress Bar */
    .progress-hero { height: 6px; background: #f1f5f9; width: 100%; position: relative; }
    .progress-fill { height: 100%; background: var(--primary); width: 0%; transition: width 0.3s ease, background-color 0.3s; }
    .progress-fill.over { background: var(--danger); }
    .progress-fill.full { background: var(--success); }

    /* --- GRID SYSTEM & INPUTS --- */
    .card-body { padding: 24px; background: #fcfcfc; }
    
    .dept-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); /* Responsive columns */
        gap: 12px;
    }

    /* INPUT BOX STYLING */
    .dept-box {
        background: white; border: 1px solid var(--border); border-radius: 10px;
        padding: 10px 12px; display: flex; flex-direction: column; gap: 4px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); cursor: text; position: relative;
    }
    .dept-box:hover { border-color: #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .dept-box:focus-within { 
        border-color: var(--primary); 
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); 
        transform: translateY(-1px); z-index: 2;
    }
    
    /* IMPROVEMENT 1: ACTIVE STATE (SMART HIGHLIGHT) */
    .dept-box.has-value {
        background: #f0f9ff; /* Biru Sangat Muda */
        border-color: #93c5fd; /* Biru Muda */
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.05);
    }
    .dept-box.has-value .dept-label { color: var(--primary-text); }
    .dept-box.has-value .dept-input { color: #1e3a8a; font-weight: 700; }

    /* Validasi Error pada input box */
    .item-card.error-state .dept-box.has-value {
        background: #fef2f2; border-color: #fca5a5;
    }
    .item-card.error-state .dept-box.has-value input { color: #b91c1c; }

    .dept-label {
        font-size: 10px; font-weight: 700; color: var(--text-sub);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .dept-input {
        border: none; background: transparent; font-size: 15px; color: var(--text-main);
        width: 100%; outline: none; padding: 2px 0 0; font-family: 'Inter', sans-serif;
        -moz-appearance: textfield; font-weight: 500;
    }
    .dept-input::-webkit-outer-spin-button, .dept-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

    /* Card Footer */
    .card-footer {
        padding: 12px 20px; background: #fff; border-top: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
    }
    .status-text { font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; color: var(--text-sub); }
    .status-badge { padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; }
    .badge-blue { background: #e0e7ff; color: #3730a3; }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-red { background: #fee2e2; color: #991b1b; }

    .action-group { display: flex; gap: 8px; }
    .btn-tool {
        background: white; border: 1px solid var(--border); padding: 6px 14px;
        border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--text-sub);
        cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-tool:hover { background: #f8fafc; color: var(--text-main); border-color: #cbd5e1; }

    /* --- IMPROVEMENT 2: SUMMARY TABLE GHOSTING --- */
    .summary-card { background: white; border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .table-mod { width: 100%; border-collapse: collapse; }
    .table-mod th { background: #f8fafc; padding: 14px 24px; font-size: 12px; font-weight: 600; text-align: left; color: var(--text-sub); border-bottom: 1px solid var(--border); text-transform: uppercase; letter-spacing: 0.5px; }
    .table-mod td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: var(--text-main); transition: all 0.2s; }
    
    /* GHOSTING CLASS: Untuk baris yang 0 */
    .tr-muted td { 
        color: var(--text-muted) !important; 
        font-style: italic;
    }
    .tr-muted td:first-child { 
        font-weight: 500; font-style: normal; /* Nama dept tetap agak jelas */
    }
    /* HIGHLIGHT CLASS: Untuk baris aktif */
    .tr-active td {
        font-weight: 600; color: var(--text-main); background: #fafbff;
    }

    .progress-mini { height: 6px; width: 100px; background: #f1f5f9; border-radius: 99px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: 8px; }
    .progress-mini-bar { height: 100%; background: var(--primary); }

    /* --- IMPROVEMENT 3: STICKY FOOTER GLASSMORPHISM --- */
    .sticky-footer {
        position: fixed; bottom: 0; left: 0; right: 0;
        /* Glass Effect */
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        
        padding: 16px 40px;
        border-top: 1px solid rgba(226, 232, 240, 0.6);
        box-shadow: 0 -4px 20px rgba(0,0,0,0.03);
        display: flex; justify-content: flex-end; gap: 12px; z-index: 100;
        
        /* Adjust margin based on sidebar if needed (assuming ~250px) */
        margin-left: 250px; 
    }
    @media(max-width: 768px) { .sticky-footer { margin-left: 0; padding: 15px 20px; } }

    .btn-main {
        padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 13px;
        border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.2s;
    }
    .btn-cancel { background: white; border: 1px solid var(--border); color: var(--text-sub); }
    .btn-cancel:hover { background: #f8fafc; color: var(--text-main); }
    
    .btn-save { background: var(--primary); color: white; box-shadow: 0 4px 10px -2px rgba(37, 99, 235, 0.3); }
    .btn-save:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 6px 15px -3px rgba(37, 99, 235, 0.4); }
</style>

<div class="page-container">

    {{-- HEADER --}}
    <div class="dashboard-header">
        <div class="header-title-wrapper">
            <div class="header-icon-box">
                <i class="fa fa-sitemap"></i>
            </div>
            <div class="header-text">
                <h1>Atur Jatah LPB</h1>
                <p>{{ $lpb->lpb_number }} &bull; {{ $lpb->date->format('d M Y') }}</p>
            </div>
        </div>
        <div class="view-toggle">
            <a href="{{ request()->fullUrlWithQuery(['view'=>'item']) }}" class="{{ $mode==='item'?'active':'' }}">
                <i class="fa fa-list"></i> Per Item
            </a>
            <a href="{{ request()->fullUrlWithQuery(['view'=>'dept']) }}" class="{{ $mode==='dept'?'active':'' }}">
                <i class="fa fa-building-o"></i> Ringkasan Dept
            </a>
        </div>
    </div>

    {{-- BREADCRUMB --}}
    <div class="top-bar">
        <div class="breadcrumb-custom">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="sep">/</span>
            <a href="{{ route('lpbs.index') }}">LPB Masuk</a>
            <span class="sep">/</span>
            <span>Atur Jatah</span>
        </div>
    </div>

    @if ($mode === 'item')
        <form action="{{ route('lpbs.quotas.update', $lpb->id) }}" method="POST" id="quotaForm">
            {{ csrf_field() }} {{ method_field('PUT') }}

            @foreach($lpb->details as $d)
                @php
                    $qtyTotal = (int)$d->quantity;
                    $allocated = 0;
                    if(isset($existingMap[$d->id])) {
                        $allocated = array_sum($existingMap[$d->id]);
                    }
                    $remaining = $qtyTotal - $allocated;
                @endphp

                <div class="item-card" id="card-{{ $d->id }}" data-total="{{ $qtyTotal }}">
                    {{-- CARD HEADER --}}
                    <div class="card-top">
                        <div class="item-info">
                            <span class="item-code">{{ $d->item->code }}</span>
                            <div class="item-name">{{ $d->item->name }}</div>
                        </div>
                        <div class="item-stats">
                            <div class="stat-label">Total Terima</div>
                            <span class="stat-number">{{ number_format($qtyTotal, 0, ',', '.') }}</span>
                            <span class="stat-unit">{{ $d->item->unit }}</span>
                        </div>
                    </div>

                    {{-- PROGRESS BAR --}}
                    <div class="progress-hero">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>

                    {{-- CARD BODY: GRID INPUT --}}
                    <div class="card-body">
                        <div class="dept-grid">
                            @foreach($sortedDepartments as $dept)
                                @php
                                    $val = 0;
                                    if(isset($existingMap[$d->id]) && isset($existingMap[$d->id][$dept->id])) {
                                        $val = $existingMap[$d->id][$dept->id];
                                    }
                                    // Class has-value untuk styling awal
                                    $boxClass = $val > 0 ? ' has-value' : '';
                                @endphp
                                <div class="dept-box{{ $boxClass }}" onclick="focusInput(this)">
                                    <label class="dept-label" title="{{ $dept->name }}">{{ $dept->name }}</label>
                                    <input type="number" 
                                           name="quota[{{ $d->id }}][{{ $dept->id }}]" 
                                           class="dept-input js-quota-input" 
                                           value="{{ $val }}"
                                           min="0"
                                           onfocus="this.select()" 
                                           oninput="updateCard({{ $d->id }})"
                                    >
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- CARD FOOTER: TOOLS & STATUS --}}
                    <div class="card-footer">
                        <div class="status-text">
                            <span>Sisa Alokasi:</span>
                            <span class="status-badge badge-blue js-remain-badge">0</span>
                        </div>
                        <div class="action-group">
                            <button type="button" class="btn-tool" onclick="distributeEven({{ $d->id }})">
                                <i class="fa fa-balance-scale"></i> Bagi Rata
                            </button>
                            <button type="button" class="btn-tool" onclick="clearCard({{ $d->id }})">
                                <i class="fa fa-eraser"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- STICKY FOOTER --}}
            <div class="sticky-footer">
                <a href="{{ route('lpbs.index') }}" class="btn-main btn-cancel">Batal</a>
                <button type="submit" class="btn-main btn-save">
                    <i class="fa fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>

    @else
        {{-- MODE VIEW: SUMMARY DEPARTEMEN --}}
        <div class="summary-card">
            <div class="card-top">
                <div class="item-info">
                    <div class="item-name">Ringkasan Distribusi Barang</div>
                    <p style="margin:4px 0 0; color:var(--text-sub); font-size:13px;">Total Barang Masuk: <strong>{{ number_format($totalLPBQty, 0, ',', '.') }}</strong> Unit</p>
                </div>
            </div>
            <table class="table-mod">
                <thead>
                    <tr>
                        <th>Departemen</th>
                        <th style="text-align:center;">Total Jatah</th>
                        <th style="text-align:center;">Jenis Barang</th>
                        <th>Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sortedDepartments as $dept)
                        @php
                            $sum = isset($deptSummary[$dept->id]) ? $deptSummary[$dept->id]['qty'] : 0;
                            $cnt = isset($deptSummary[$dept->id]) ? $deptSummary[$dept->id]['items'] : 0;
                            $pct = ($totalLPBQty > 0) ? ($sum / $totalLPBQty) * 100 : 0;
                            
                            // LOGIC GHOSTING: Jika sum 0, pakai class muted
                            $rowClass = ($sum > 0) ? 'tr-active' : 'tr-muted';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>{{ $dept->name }}</td>
                            <td style="text-align:center;">{{ number_format($sum, 0, ',', '.') }}</td>
                            <td style="text-align:center;">{{ $cnt }}</td>
                            <td>
                                @if($sum > 0)
                                    <div class="progress-mini">
                                        <div class="progress-mini-bar" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span style="font-size:11px; color:var(--text-sub); font-weight:600;">{{ number_format($pct, 1) }}%</span>
                                @else
                                    <span style="font-size:11px; color:var(--text-muted);">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="sticky-footer">
            <a href="{{ route('lpbs.index') }}" class="btn-main btn-cancel">Kembali ke Daftar</a>
            <a href="{{ request()->fullUrlWithQuery(['view'=>'item']) }}" class="btn-main btn-save">
                <i class="fa fa-pencil"></i> Ubah Jatah
            </a>
        </div>
    @endif

</div>

{{-- JAVASCRIPT LOGIC --}}
@if ($mode === 'item')
<script>
    // Helper: Fokus ke input saat box diklik
    function focusInput(box) {
        box.querySelector('input').focus();
    }

    // Core Logic: Hitung ulang per kartu
    function updateCard(cardId) {
        var card = document.getElementById('card-' + cardId);
        var totalQty = parseInt(card.dataset.total) || 0;
        var inputs = card.querySelectorAll('.js-quota-input');
        var currentSum = 0;

        inputs.forEach(function(input) {
            var val = parseInt(input.value) || 0;
            // Visual feedback untuk input aktif (SMART HIGHLIGHT)
            var parentBox = input.closest('.dept-box');
            if(val > 0) {
                parentBox.classList.add('has-value');
            } else {
                parentBox.classList.remove('has-value');
            }
            currentSum += val;
        });

        var remaining = totalQty - currentSum;
        var percentage = totalQty > 0 ? (currentSum / totalQty) * 100 : 0;

        // Update Badge Sisa
        var badge = card.querySelector('.js-remain-badge');
        badge.innerText = remaining.toLocaleString('id-ID');

        // Logic Warna Badge & Progress
        var progressBar = card.querySelector('.progress-fill');
        
        // Reset Classes
        badge.className = 'status-badge js-remain-badge';
        progressBar.className = 'progress-fill';
        card.classList.remove('error-state', 'done-state');

        if (remaining < 0) {
            // Over quota (Merah)
            badge.classList.add('badge-red');
            progressBar.classList.add('over');
            progressBar.style.width = '100%';
            card.classList.add('error-state');
        } else if (remaining === 0) {
            // Pas (Hijau)
            badge.classList.add('badge-green');
            progressBar.classList.add('full');
            progressBar.style.width = '100%';
            card.classList.add('done-state');
        } else {
            // Masih ada sisa (Biru)
            badge.classList.add('badge-blue');
            progressBar.style.width = percentage + '%';
        }
    }

    // Tools: Bagi Rata
    function distributeEven(cardId) {
        var card = document.getElementById('card-' + cardId);
        var totalQty = parseInt(card.dataset.total) || 0;
        var inputs = card.querySelectorAll('.js-quota-input');
        var count = inputs.length;

        if (totalQty > 0 && count > 0) {
            var base = Math.floor(totalQty / count);
            var remainder = totalQty % count;

            inputs.forEach(function(input, index) {
                var extra = (index < remainder) ? 1 : 0;
                input.value = base + extra;
            });
            updateCard(cardId);
        }
    }

    // Tools: Reset / Kosongkan
    function clearCard(cardId) {
        var card = document.getElementById('card-' + cardId);
        var inputs = card.querySelectorAll('.js-quota-input');
        inputs.forEach(function(input) {
            input.value = 0;
        });
        updateCard(cardId);
    }

    // Init: Jalankan perhitungan saat halaman load
    document.addEventListener("DOMContentLoaded", function() {
        @foreach($lpb->details as $d)
            updateCard({{ $d->id }});
        @endforeach
    });
</script>
@endif

@endsection
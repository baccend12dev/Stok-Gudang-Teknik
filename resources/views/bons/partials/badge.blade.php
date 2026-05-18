@php
  $raw = is_string($status) ? strtoupper(trim($status)) : '';
  $map = [
      'PENDING'   => ['label' => 'PENDING',   'class' => 'badge-pending',   'hint' => 'Menunggu pengecekan jatah & stok'],
      'APPROVED'  => ['label' => 'APPROVED',  'class' => 'badge-approved',  'hint' => 'Siap di-ISSUE, jatah & stok aman'],
      'ISSUED'    => ['label' => 'ISSUED',    'class' => 'badge-issued',    'hint' => 'Barang sudah keluar & stok terpotong'],
      'CANCELLED' => ['label' => 'CANCELLED', 'class' => 'badge-cancelled', 'hint' => 'BON dibatalkan'],
      'REJECTED'  => ['label' => 'REJECTED',  'class' => 'badge-rejected',  'hint' => 'BON ditolak'],
  ];
  $meta = isset($map[$raw]) ? $map[$raw] : ['label' => ($raw ?: '-'), 'class' => 'badge-default', 'hint' => ''];
@endphp

<style>
    .badge-status{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border-radius:999px;
        font-size:11px;
        font-weight:600;
        padding:4px 12px;
        border:1px solid transparent;
        letter-spacing:0.03em;
        text-transform:uppercase;
        cursor:default;
        white-space:nowrap;
    }
    .badge-status.badge-pending{
        background:#fefce8;
        border-color:#fef3c7;
        color:#854d0e;
    }
    .badge-status.badge-approved{
        background:#ecfdf3;
        border-color:#bbf7d0;
        color:#166534;
    }
    .badge-status.badge-issued{
        background:#eff6ff;
        border-color:#bfdbfe;
        color:#1d4ed8;
    }
    .badge-status.badge-cancelled,
    .badge-status.badge-rejected{
        background:#fef2f2;
        border-color:#fecaca;
        color:#b91c1c;
    }
    .badge-status.badge-default{
        background:#f3f4f6;
        border-color:#e5e7eb;
        color:#374151;
    }
</style>

<span class="badge-status {{ $meta['class'] }}"
      @if($meta['hint']) title="{{ $meta['hint'] }}" @endif>
    {{ $meta['label'] }}
</span>

@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL EDIT BARANG === --}}
@section('help-content')
    <h4 class="help-h"><i class="fa fa-pencil text-primary"></i> Edit Data Master</h4>
    <p class="help-p">Halaman ini digunakan untuk memperbaiki data atribut barang (Nama, Kategori, Satuan) jika ada kesalahan penulisan.</p>

    <div class="help-alert" style="background:#fee2e2; border-left-color:#dc2626; color:#991b1b;">
        <i class="fa fa-exclamation-triangle"></i> 
        <strong>PERHATIAN KERAS - STOK BARANG:</strong><br>
        Mohon <strong>JANGAN</strong> mengubah angka "Stok Saat Ini" dari halaman ini untuk kebutuhan transaksi rutin (barang masuk/keluar).<br>
        <br>
        Perubahan stok di sini tidak tercatat dalam riwayat transaksi (BON/LPB) dan akan menyebabkan selisih audit. Gunakan menu <strong>LPB</strong> (Masuk) atau <strong>BON</strong> (Keluar).
    </div>

    <h4 class="help-h"><i class="fa fa-cog text-primary"></i> Kapan Harus Mengedit?</h4>
    <ul class="help-list">
        <li><strong>Koreksi Typo:</strong> Salah ketik nama barang atau kode.</li>
        <li><strong>Update Lokasi:</strong> Barang dipindah ke rak lain.</li>
        <li><strong>Status Non-Aktif:</strong> Barang sudah tidak diproduksi/discontinue.</li>
        <li><strong>Koreksi Saldo Awal:</strong> Hanya jika terjadi kesalahan input saldo awal saat migrasi sistem (sebelum ada transaksi).</li>
    </ul>
@endsection

@section('content')
<style>
    /* --- DESIGN TOKENS --- */
    :root {
        --primary: #4F46E5;
        --primary-dark: #4338CA;
        --secondary: #64748B;
        --placeholder: #94A3B8;
        --dark: #111827;
        --surface: #FFFFFF;
        --background: #F1F5F9;
        --border: #E2E8F0;
        --radius: 12px;
        --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        --font-main: 'Inter', system-ui, sans-serif;
    }

    /* --- RESET & LAYOUT --- */
    body { background-color: var(--background); color: var(--dark); font-family: var(--font-main); }
    .content-header { display: none; }
    .app-container { width: 100%; padding: 10px 40px 80px; box-sizing: border-box; }

    /* --- HEADER --- */
    .page-header { display: flex; justify-content: space-between; align-items: center; padding: 10px 0 30px; }
    .header-left { display: flex; align-items: center; gap: 15px; }
    .header-icon {
        width: 48px; height: 48px;
        background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);
        color: var(--primary); border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.4);
    }
    .header-title h1 { font-size: 24px; font-weight: 800; color: var(--dark); margin: 0; line-height: 1.2; }
    .header-subtitle { font-size: 13px; color: var(--secondary); margin-top: 2px; }

    .btn-back {
        background: white; border: 1px solid var(--border); color: var(--dark);
        padding: 10px 24px; border-radius: 50px; font-size: 13px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 8px; text-decoration: none !important;
        transition: all 0.2s; box-shadow: var(--shadow-card);
    }
    .btn-back:hover { background: #F8FAFC; transform: translateY(-1px); border-color: #CBD5E1; }

    /* --- FORM CARD --- */
    .form-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-card); border: 1px solid var(--border); overflow: hidden; }
    .form-body { padding: 40px; }
    
    /* --- INPUT STYLING --- */
    .form-group { margin-bottom: 24px; position: relative; }
    .form-label { display: block; font-size: 12px; font-weight: 600; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
    .form-label span.required { color: var(--danger); margin-left: 2px; }
    
    .form-control-modern {
        display: block; width: 100%; padding: 12px 16px;
        font-size: 14px; font-weight: 500; color: var(--dark);
        background-color: #fff; border: 1px solid #CBD5E1; border-radius: 8px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .form-control-modern:focus { border-color: var(--primary); outline: 0; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    .form-control-modern::placeholder { color: var(--placeholder); opacity: 1; }
    
    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='[http://www.w3.org/2000/svg](http://www.w3.org/2000/svg)' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem;
    }
    /* Logic: Kalau selected empty, warna abu. Kalau ada isi, warna hitam */
    select.form-control-modern.placeholder-shown { color: var(--placeholder); }
    select.form-control-modern option { color: var(--dark); }

    .helper-text { font-size: 12px; color: var(--secondary); margin-top: 6px; display: block; }

    /* --- Quick Chips (Satuan) --- */
    .unit-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .chip {
        background: #F1F5F9; border: 1px solid #E2E8F0; color: #64748B;
        padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
        cursor: pointer; transition: all 0.2s; user-select: none;
    }
    .chip:hover { background: #E0E7FF; color: var(--primary); border-color: #C7D2FE; }

    /* --- SECTION TITLE --- */
    .form-section-title { font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
    .form-section-title i { color: var(--primary); }

    /* --- ACTIONS --- */
    .form-actions { background: #F8FAFC; padding: 24px 40px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 12px; }
    .btn-save { background: var(--primary); color: white; border: none; padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3); }
    .btn-save:hover { background: var(--primary-dark); transform: translateY(-1px); }
    .btn-cancel { background: white; color: var(--secondary); border: 1px solid var(--border); padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.2s; }
    .btn-cancel:hover { background: #F1F5F9; color: var(--dark); border-color: #CBD5E1; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
    @media(max-width: 992px) { .grid-2 { grid-template-columns: 1fr; gap: 30px; } }
</style>

<div class="app-container">
    <div class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa fa-pencil-square-o"></i></div>
            <div class="header-title">
                <h1>Edit Item</h1>
                <div class="header-subtitle">Perbarui informasi untuk item: <strong>{{ $item->code }}</strong></div>
            </div>
        </div>
        <a href="{{ route('items.index') }}" class="btn-back"><i class="fa fa-arrow-left"></i> Kembali</a>
    </div>

    <form method="POST" action="{{ route('items.update', $item->id) }}" class="form-card">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        
        <div class="form-body">
            <div class="grid-2">
                <div>
                    <div class="form-section-title"><i class="fa fa-cube"></i> Informasi Utama</div>

                    <div class="form-group">
                        <label class="form-label">Kode Barang <span class="required">*</span></label>
                        <input type="text" name="code" class="form-control-modern" value="{{ $item->code }}" required autocomplete="off">
                        <span class="helper-text">Pastikan kode di input dengan benar.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Barang <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control-modern" value="{{ $item->name }}" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kategori Barang <span class="required">*</span></label>
                        <select name="category_id" id="categorySelect" class="form-control-modern placeholder-shown" required onchange="updateSelectColor(this)">
                            <option value="" disabled>-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                @php
                                    // Custom Mapping Visual (Tampilan Saja)
                                    $visualCode = $cat->code;
                                    if($cat->code == 'AKB') $visualCode = 'AB'; 
                                    if($cat->code == 'SBN') $visualCode = 'SB'; 
                                    if($cat->code == 'UMM') $visualCode = 'UM';
                                @endphp
                                <option value="{{ $cat->id }}" {{ (isset($item) && $item->category_id == $cat->id) ? 'selected' : '' }}>
                                    {{ $visualCode }} - {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Satuan Unit <span class="required">*</span></label>
                        <input type="text" id="unitInput" name="unit" class="form-control-modern" value="{{ $item->unit }}" required autocomplete="off" placeholder="Ketik atau pilih...">
                        
                        <div class="unit-chips">
                            <span class="chip" onclick="selectUnit('PCS')">PCS</span>
                            <span class="chip" onclick="selectUnit('BOX')">BOX</span>
                            <span class="chip" onclick="selectUnit('UNIT')">UNIT</span>
                            <span class="chip" onclick="selectUnit('SET')">SET</span>
                            <span class="chip" onclick="selectUnit('DUS')">DUS</span>
                            <span class="chip" onclick="selectUnit('RIM')">RIM</span>
                            <span class="chip" onclick="selectUnit('LBR')">LBR</span>
                            <span class="chip" onclick="selectUnit('KG')">KG</span>
                            <span class="chip" onclick="selectUnit('BTL')">BTL</span>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="form-section-title"><i class="fa fa-sliders"></i> Kontrol Inventori</div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label">Stok Saat Ini <span class="required">*</span></label>
                                {{-- FIX: Ubah (int) ke (float) dan tambah step="0.01" --}}
                                <input type="number" name="current_stock" class="form-control-modern" value="{{ (float)$item->current_stock }}" min="0" step="0.01" required>
                                <span class="helper-text">Jumlah fisik di gudang.</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label">Buffer Minimum <span class="required">*</span></label>
                                {{-- FIX: Ubah (int) ke (float) dan tambah step="0.01" --}}
                                <input type="number" name="buffer_min" class="form-control-modern" value="{{ (float)$item->buffer_min }}" min="0" step="0.01" required>
                                <span class="helper-text">Batas aman peringatan.</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Lokasi Rak / Gudang</label>
                        <div class="input-group" style="width:100%;">
                            <span class="input-group-addon" style="background:#F8FAFC; border-color:#CBD5E1;"><i class="fa fa-map-marker"></i></span>
                            <input type="text" name="location" class="form-control-modern" value="{{ $item->location }}" style="border-top-left-radius:0; border-bottom-left-radius:0;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Item <span class="required">*</span></label>
                        @php $cur = strtoupper($item->current_status); @endphp
                        <select name="current_status" class="form-control-modern" required>
                            <option value="ACTIVE"    {{ $cur==='ACTIVE' || $cur==='AKTIF' ? 'selected':'' }}>AKTIF - Bisa digunakan</option>
                            <option value="NONACTIVE" {{ $cur==='NONACTIVE' ? 'selected':'' }}>NONAKTIF - Arsip</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-top: 20px;">
                <div class="col-md-12">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="note" class="form-control-modern" rows="3" placeholder="Opsional...">{{ isset($item->note)?$item->note:'' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('items.index') }}" class="btn-cancel">Batal</a>
            <button type="submit" class="btn-save"><i class="fa fa-save"></i> Perbarui Data</button>
        </div>
    </form>
</div>

<script>
    function updateSelectColor(select) {
        if (select.value === "") {
            select.classList.add('placeholder-shown');
        } else {
            select.classList.remove('placeholder-shown');
        }
    }

    function selectUnit(value) {
        document.getElementById('unitInput').value = value;
    }

    // Run on load untuk Edit: cek apakah value sudah ada
    document.addEventListener("DOMContentLoaded", function() {
        var catSelect = document.getElementById('categorySelect');
        updateSelectColor(catSelect);
    });
</script>
@endsection
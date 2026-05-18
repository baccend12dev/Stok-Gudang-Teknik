@extends('layouts.app')

{{-- === PANDUAN KONTEKSTUAL ATUR PLAFON (BUDGETING) === --}}
@section('help-content')
    <div class="help-alert">
        <i class="fa fa-info-circle"></i> 
        <strong>KONSEP DASAR:</strong> Halaman ini menggunakan tampilan <strong>Matriks (Grid)</strong>. Baris adalah Barang, Kolom adalah Departemen.
    </div>

    <h4 class="help-h"><i class="fa fa-shield text-primary"></i> Fungsi & Tujuan</h4>
    <p class="help-p">
        Fitur ini berfungsi sebagai <strong>Kontrol Distribusi</strong>. Angka yang Anda masukkan di sini akan menjadi batas maksimal (Kuota) yang boleh diminta oleh departemen tersebut dalam satu bulan.
    </p>
    <ul class="help-list">
        <li><strong>Pemerataan Stok:</strong> Mencegah satu departemen mengambil stok berlebihan yang dapat menyebabkan departemen lain kehabisan jatah.</li>
        <li><strong>Efisiensi Budget:</strong> Menjaga agar pengeluaran barang sesuai dengan alokasi yang direncanakan manajemen.</li>
    </ul>

    <h4 class="help-h"><i class="fa fa-magic text-primary"></i> Cara Penggunaan Cepat (Power User)</h4>
    <p class="help-p">Anda tidak perlu mengetik satu-persatu ribuan kolom. Gunakan fitur pintar berikut:</p>
    <ul class="help-list">
        <li>
            <strong>Isi Massal per Barang (Baris):</strong> 
            Klik ikon <i class="fa fa-pencil-square-o text-primary"></i> di samping <strong>Nama Barang</strong> untuk mengisi angka jatah yang sama ke <strong>SEMUA Departemen</strong> sekaligus.
        </li>
        <li>
            <strong>Isi Massal per Dept (Kolom):</strong> 
            Klik ikon <i class="fa fa-pencil-square-o text-primary"></i> di bawah <strong>Kode Departemen</strong> (Header) untuk menyamakan jatah semua barang bagi departemen tersebut.
        </li>
    </ul>

    <h4 class="help-h"><i class="fa fa-floppy-o text-primary"></i> Penyimpanan Data</h4>
    <p class="help-p">
        Sistem menggunakan <strong>Auto-Detect</strong>. Tombol "Simpan Perubahan" hanya akan muncul melayang di bawah layar jika Anda melakukan perubahan data. Pastikan klik simpan sebelum pindah halaman.
    </p>
@endsection

@section('content')
<style>
    /* --- 1. DESIGN TOKENS (2025 Standard) --- */
    :root {
        --primary: #4F46E5;       /* Indigo Modern */
        --primary-hover: #4338CA;
        --secondary: #64748B;     /* Slate Gray */
        --border: #E2E8F0;
        --surface: #FFFFFF;
        --background: #F8FAFC;
        --table-head: #F1F5F9;
        
        --font-main: 'Inter', system-ui, -apple-system, sans-serif;
        --radius: 10px;
        --shadow-elevation: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* --- 2. LAYOUT & TYPOGRAPHY --- */
    .app-container { padding: 25px 30px; font-family: var(--font-main); }
    
    /* Header Section */
    .pg-header { 
        display: flex; justify-content: space-between; align-items: flex-start; 
        margin-bottom: 25px; border-bottom: 1px solid var(--border); padding-bottom: 20px;
    }
    .pg-title { margin: 0; display: flex; align-items: center; gap: 15px; }
    
    .pg-icon-box {
        width: 42px; height: 42px; background: #EEF2FF; color: var(--primary);
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 20px; border: 1px solid #E0E7FF;
        flex-shrink: 0;
    }
    
    .pg-text-wrap h1 { 
        font-size: 22px; font-weight: 800; color: #0F172A; margin: 0; letter-spacing: -0.5px; line-height: 1.2;
    }
    .pg-subtitle { font-size: 13px; color: var(--secondary); margin-top: 2px; font-weight: 500; line-height: 1.2; }

    /* --- 3. CATEGORY TABS --- */
    .cat-tabs { 
        display: flex; gap: 8px; overflow-x: auto; padding-bottom: 2px; margin-bottom: 20px; 
        scrollbar-width: none; -ms-overflow-style: none;
    }
    .cat-tabs::-webkit-scrollbar { display: none; }
    
    .tab-btn {
        text-decoration: none !important; 
        padding: 9px 18px; border-radius: 8px; 
        font-weight: 600; font-size: 13px; color: #64748B; 
        background: white; border: 1px solid var(--border); 
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); white-space: nowrap;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .tab-btn:hover { 
        background: #F8FAFC; color: var(--primary); transform: translateY(-1px); border-color: #CBD5E1; 
        text-decoration: none; 
    }
    .tab-btn.active { 
        background: var(--primary); color: white; border-color: var(--primary); 
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.25);
    }
    .tab-btn:focus, .tab-btn:active { text-decoration: none; outline: none; }

    /* --- 4. TOOLBAR --- */
    .toolbar { 
        display: flex; justify-content: space-between; align-items: center; 
        margin-bottom: 15px; gap: 20px; background: #fff; padding: 10px; 
        border-radius: var(--radius); border: 1px solid var(--border);
    }
    .search-box { position: relative; flex: 1; max-width: 350px; }
    .search-input {
        width: 100%; padding: 10px 15px 10px 38px; border-radius: 6px; border: 1px solid var(--border);
        font-size: 13px; color: #1E293B; transition: all 0.2s; background: #F8FAFC;
    }
    .search-input:focus { 
        border-color: var(--primary); outline: none; background: #fff; 
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); 
    }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 14px; }

    /* --- 5. MATRIX TABLE --- */
    .matrix-wrap { 
        max-height: 65vh; overflow: auto; background: white; border-radius: var(--radius); 
        border: 1px solid var(--border); box-shadow: var(--shadow-elevation); 
        position: relative;
        scrollbar-width: none; -ms-overflow-style: none;
    }
    .matrix-wrap::-webkit-scrollbar { width: 0px; height: 0px; background: transparent; display: none; }

    .matrix-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; }
    
    /* Header Styling */
    .matrix-table th {
        background: var(--table-head); padding: 10px 5px; 
        border-bottom: 1px solid #CBD5E1; border-right: 1px solid var(--border);
        text-align: center; vertical-align: middle; color: #475569; 
        font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;
        position: sticky; top: 0; z-index: 10;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    
    /* Sticky First Column */
    .matrix-table td:first-child, .matrix-table th:first-child {
        position: sticky; left: 0; z-index: 20; background: #fff; 
        border-right: 2px solid #E2E8F0;
        width: 280px; min-width: 280px; max-width: 280px;
    }
    .matrix-table th:first-child { 
        z-index: 30; background: var(--table-head); 
        color: var(--primary); font-size: 12px; padding-left: 15px; text-align: left;
    } 

    /* Cell Input (Fix Cutting & Centering) */
    .matrix-table td { padding: 0; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); height: 1px; }
    .cell-input {
        width: 100%; height: 100%; border: none; padding: 0; 
        line-height: 38px; text-align: center;
        font-weight: 600; color: #0F172A; font-size: 13px; background: transparent;
        transition: background 0.1s; display: block;
    }
    .cell-input:focus { background: #EEF2FF; color: var(--primary); outline: none; box-shadow: inset 0 0 0 2px var(--primary); }
    .cell-input:hover { background: #F8FAFC; }
    .cell-input.changed { background: #FEF3C7; color: #B45309; }

    /* Item Meta Info */
    .item-meta { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; }
    .item-info { display: flex; flex-direction: column; overflow: hidden; }
    .item-name { font-weight: 700; color: #1E293B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 13px; }
    .item-sub { font-size: 10px; color: #64748B; margin-top: 2px; display: flex; align-items: center; gap: 6px; }
    .item-code { background: #F1F5F9; padding: 1px 4px; border-radius: 3px; font-family: monospace; letter-spacing: -0.5px; }

    /* Mass Edit Icons */
    .mass-btn { 
        cursor: pointer; color: #CBD5E1; transition: all 0.2s; font-size: 14px; padding: 4px;
    }
    .mass-btn:hover { color: var(--primary); transform: scale(1.1); }
    
    /* --- FLOATING SAVE (FAB) --- */
    .floating-save {
        position: fixed; bottom: 40px; right: 40px; z-index: 999;
        display: none; /* Hidden by default */
        animation: slideUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-save {
        background: var(--primary); color: white; border: none; padding: 14px 28px;
        border-radius: 50px; font-weight: 700; font-size: 14px;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.5);
        cursor: pointer; display: flex; align-items: center; gap: 10px;
        transition: transform 0.2s;
    }
    .btn-save:hover { background: var(--primary-hover); transform: translateY(-3px); }
    .btn-save i { font-size: 16px; }
    
    @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* Empty State */
    .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius); border: 1px solid var(--border); }
    .empty-icon { width: 80px; height: 80px; margin-bottom: 20px; opacity: 0.8; }

    /* --- SWEETALERT MODERN COMPACT --- */
    .swal2-popup.modern-popup {
        border-radius: 14px !important;
        padding: 20px !important; 
        width: 380px !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }
    .swal2-title {
        font-family: var(--font-main) !important;
        font-size: 18px !important;
        font-weight: 800 !important;
        color: #1e293b !important;
        padding: 0 0 10px 0 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px; 
    }
    .swal2-html-container {
        font-family: var(--font-main) !important;
        font-size: 13px !important;
        color: #64748b !important;
        line-height: 1.5 !important;
        margin: 0 !important;
    }
    .swal2-input {
        height: 42px !important;
        padding: 0 15px !important;
        font-size: 16px !important;
        font-weight: 700 !important;
        text-align: center !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 10px !important;
        margin: 15px auto !important;
        color: var(--primary) !important;
        transition: all 0.2s !important;
        box-shadow: none !important;
        width: 70% !important;
    }
    .swal2-input:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
    }
    /* Buttons SweetAlert */
    .swal2-actions {
        gap: 10px !important;
        width: 100% !important;
        justify-content: center !important;
        margin-top: 5px !important;
    }
    .swal2-confirm, .swal2-cancel {
        padding: 10px 24px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        border-radius: 8px !important;
        font-family: var(--font-main) !important;
        box-shadow: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .swal2-confirm {
        background-color: var(--primary) !important;
        color: #ffffff !important;
    }
    .swal2-confirm:hover {
        background-color: var(--primary-hover) !important;
    }
    .swal2-cancel {
        background-color: #ffffff !important;
        color: #64748b !important;
        border: 1px solid #e2e8f0 !important;
    }
    .swal2-cancel:hover {
        background-color: #f8fafc !important;
        color: #475569 !important;
    }
</style>

<div class="app-container">
    
    {{-- Header --}}
    <div class="pg-header">
        <div class="pg-title">
            <div class="pg-icon-box">
                <i class="fa fa-sliders"></i>
            </div>
            <div class="pg-text-wrap">
                <h1>Atur Plafon</h1>
                <div class="pg-subtitle">Kelola batas maksimal permintaan bulanan (Budgeting)</div>
            </div>
        </div>
    </div>

    {{-- Tabs Kategori --}}
    <div class="cat-tabs">
        @foreach($categories as $cat)
            @if(stripos($cat->name, 'uncategorized') !== false) 
                @continue 
            @endif

            <a href="{{ route('budgets.index', ['cat_id' => $cat->id]) }}" 
               class="tab-btn {{ $activeCatId == $cat->id ? 'active' : '' }}">
                {{ $cat->name }}
            </a>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <div class="toolbar">
        <div class="search-box">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="tableSearch" class="search-input" placeholder="Ketik nama barang untuk filter cepat...">
        </div>
        <div style="font-size:12px; color:#64748b; font-weight:500; display:flex; align-items:center; gap:5px;">
            <i class="fa fa-lightbulb-o text-warning"></i>
            <span>Tips: Klik <i class="fa fa-pencil-square-o"></i> untuk isi otomatis sebaris/sekolom.</span>
        </div>
    </div>

    @if(count($items) > 0)
        <form action="{{ route('budgets.store') }}" method="POST" id="budgetForm">
            {{ csrf_field() }}
            <input type="hidden" name="active_cat_id" value="{{ $activeCatId }}">

            <div class="matrix-wrap">
                <table class="matrix-table" id="budgetTable">
                    <thead>
                        <tr>
                            <th>ITEM BARANG</th>
                            @foreach($departments as $dept)
                                <th style="min-width: 70px;">
                                    <div style="display:flex; flex-direction:column; align-items:center; gap:4px;">
                                        <span title="{{ $dept->name }}">{{ $dept->code }}</span>
                                        <i class="fa fa-pencil-square-o mass-btn" 
                                           title="Isi Massal: {{ $dept->name }}" 
                                           onclick="massEditColumn({{ $dept->id }}, '{{ $dept->name }}')"></i>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr class="item-row">
                                <td>
                                    <div class="item-meta">
                                        <div class="item-info">
                                            <span class="item-name" title="{{ $item->name }}">{{ $item->name }}</span>
                                            <div class="item-sub">
                                                <span class="item-code">{{ $item->code }}</span>
                                                <span>{{ $item->unit }}</span>
                                            </div>
                                        </div>
                                        <i class="fa fa-pencil-square-o mass-btn" 
                                           title="Isi Massal: {{ $item->name }}" 
                                           onclick="massEditRow({{ $item->id }}, '{{ $item->name }}')"></i>
                                    </div>
                                </td>

                                @foreach($departments as $dept)
                                    @php
                                        $val = 0;
                                        if (isset($budgets[$item->id]) && isset($budgets[$item->id][$dept->id])) {
                                            $val = $budgets[$item->id][$dept->id];
                                        }
                                    @endphp
                                    <td>
                                        <input type="number" 
                                               name="budget[{{ $item->id }}][{{ $dept->id }}]" 
                                               value="{{ $val == 0 ? '0' : $val }}" 
                                               class="cell-input col-{{ $dept->id }} row-{{ $item->id }}" 
                                               min="0"
                                               onfocus="if(this.value=='0'){this.value=''}" 
                                               onblur="if(this.value==''){this.value='0'}" 
                                               onchange="markChanged(this)">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="floating-save" id="saveDock">
                <button type="submit" class="btn-save">
                    <i class="fa fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    @else
        <div class="empty-state">
            <img src="https://img.icons8.com/fluency/96/nothing-found.png" class="empty-icon" alt="Empty"/>
            <h3 style="margin:0; font-weight:700; color:#1E293B;">Tidak ada item aktif</h3>
            <p style="color:#64748B; margin-top:8px;">Pilih kategori lain atau tambahkan item baru di Master Data.</p>
        </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // --- NAVIGATION GUARD VARIABLES ---
    let isDirty = false;

    // 1. Live Search
    document.getElementById('tableSearch').addEventListener('keyup', function() {
        var filter = this.value.toLowerCase();
        var rows = document.querySelectorAll('#budgetTable tbody tr');

        rows.forEach(function(row) {
            var text = row.querySelector('.item-name').textContent.toLowerCase() + 
                       " " + 
                       row.querySelector('.item-code').textContent.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // 2. Change Detection & Dirty State
    window.markChanged = function(el) {
        el.classList.add('changed');
        if(el.value === '') el.value = 0;
        document.getElementById('saveDock').style.display = 'block';
        isDirty = true; // Flag Dirty
    }

    // 3. Navigation Guard (Prevent Refresh/Close)
    window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = ''; // Standard browser alert
        }
    });

    // 4. Form Submit (Release Guard)
    const form = document.getElementById('budgetForm');
    if (form) {
        form.addEventListener('submit', function() {
            isDirty = false; // Allow exit
        });
    }

    // 5. Sidebar & Link Interceptor (Custom Alert)
    document.addEventListener('click', function(e) {
        let target = e.target.closest('a');
        if (!target) return;

        // If dirty and link is internal (not hash, not submit)
        if (isDirty && target.getAttribute('href') && !target.getAttribute('href').startsWith('#') && !target.getAttribute('target')) {
            // Ignore if it's strictly a UI toggle (like datatables pagination if any)
            // But intercept Sidebar, Tabs, Logout
            if (target.closest('.sidebar-nav') || target.closest('.cat-tabs') || target.closest('.top-navbar')) {
                e.preventDefault();
                let href = target.getAttribute('href');

                Swal.fire({
                    title: 'Perubahan Belum Disimpan!',
                    html: "Anda memiliki data yang belum disimpan.<br>Yakin ingin meninggalkan halaman ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tinggalkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#EF4444', // Red for danger action
                    cancelButtonColor: '#64748B',
                    customClass: {
                        popup: 'modern-popup',
                        title: 'swal2-title',
                        htmlContainer: 'swal2-html-container',
                        confirmButton: 'swal2-confirm',
                        cancelButton: 'swal2-cancel',
                        actions: 'swal2-actions'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        isDirty = false; // Release
                        window.location.href = href;
                    }
                });
            }
        }
    });

    // 6. Mass Edit Column
    window.massEditColumn = function(deptId, deptName) {
        Swal.fire({
            title: '<i class="fa fa-pencil-square-o"></i> Isi Otomatis',
            html: `Masukkan angka jatah untuk semua item<br>pada departemen <strong style="color:#4F46E5">${deptName}</strong>`,
            input: 'number',
            inputAttributes: { min: 0, autofocus: 'true' },
            inputPlaceholder: '',
            backdrop: `rgba(0,0,0,0.5)`,
            showCancelButton: true,
            confirmButtonText: 'Terapkan',
            cancelButtonText: 'Batal',
            buttonsStyling: false,
            customClass: {
                popup: 'modern-popup',
                title: 'swal2-title',
                htmlContainer: 'swal2-html-container',
                input: 'swal2-input',
                confirmButton: 'swal2-confirm',
                cancelButton: 'swal2-cancel',
                actions: 'swal2-actions'
            }
        }).then((result) => {
            if (result.isConfirmed && result.value !== "") {
                var val = result.value;
                var inputs = document.querySelectorAll('.col-' + deptId);
                var count = 0;
                inputs.forEach(function(inp) {
                    if(inp.closest('tr').style.display !== 'none'){
                        inp.value = val;
                        inp.classList.add('changed');
                        count++;
                    }
                });
                document.getElementById('saveDock').style.display = 'block';
                isDirty = true;
                const Toast = Swal.mixin({toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true});
                Toast.fire({icon: 'success', title: count + ' item diperbarui.'});
            }
        });
    }

    // 7. Mass Edit Row
    window.massEditRow = function(itemId, itemName) {
        Swal.fire({
            title: '<i class="fa fa-pencil-square-o"></i> Isi Otomatis',
            html: `Masukkan angka jatah barang<br><strong style="color:#4F46E5">${itemName}</strong><br>untuk SEMUA departemen.`,
            input: 'number',
            inputAttributes: { min: 0, autofocus: 'true' },
            inputPlaceholder: '',
            backdrop: `rgba(0,0,0,0.5)`,
            showCancelButton: true,
            confirmButtonText: 'Terapkan',
            cancelButtonText: 'Batal',
            buttonsStyling: false,
            customClass: {
                popup: 'modern-popup',
                title: 'swal2-title',
                htmlContainer: 'swal2-html-container',
                input: 'swal2-input',
                confirmButton: 'swal2-confirm',
                cancelButton: 'swal2-cancel',
                actions: 'swal2-actions'
            }
        }).then((result) => {
            if (result.isConfirmed && result.value !== "") {
                var val = result.value;
                var inputs = document.querySelectorAll('.row-' + itemId);
                inputs.forEach(function(inp) {
                    inp.value = val;
                    inp.classList.add('changed');
                });
                document.getElementById('saveDock').style.display = 'block';
                isDirty = true;
                const Toast = Swal.mixin({toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true});
                Toast.fire({icon: 'success', title: 'Semua departemen diperbarui.'});
            }
        });
    }
</script>
@endsection
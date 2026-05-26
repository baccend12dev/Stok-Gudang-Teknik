@extends('layouts.app')
@section('title', 'Edit Departemen')

{{-- === PANDUAN KONTEKSTUAL EDIT DEPARTEMEN === --}}
@section('help-content')
    <div class="help-alert" style="background:#eff6ff; border-left-color:#2563eb; color:#1e40af;">
        <i class="fa fa-info-circle"></i>
        <strong>BACA SAJA (READ-ONLY):</strong> Kode departemen bersifat permanen dan tidak dapat diubah setelah dibuat untuk menjaga keterkaitan data transaksi.
    </div>

    <h4 class="help-h"><i class="fa fa-sitemap text-primary"></i> Hubungan Divisi / Bagian</h4>
    <p class="help-p">
        Anda dapat menambah, mengubah, atau menghapus <strong>Divisi / Bagian</strong> dari departemen ini secara fleksibel.
    </p>
    <ul class="help-list">
        <li><strong>Tambah Divisi</strong>: Klik tombol "+ Tambah Baris Divisi" lalu lengkapi isinya.</li>
        <li><strong>Edit Divisi</strong>: Ubah langsung kode, nama, atau keterangan divisi pada baris tabel yang bersangkutan.</li>
        <li><strong>Hapus Divisi</strong>: Klik tombol tempat sampah merah <i class="fa fa-trash text-danger"></i>. Perubahan baru disimpan permanen setelah Anda mengklik <strong>"Perbarui Departemen"</strong>.</li>
    </ul>
@endsection

@section('content')
<style>
    .wrap-form { padding: 20px; max-width: 800px; margin: 0 auto; }
    .cardx { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .cardx-head { background: #f8fafc; padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
    .cardx-head h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; }
    .cardx-body { padding: 24px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control { 
        height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: none; padding: 8px 12px; font-size: 14px; color: #0f172a; width: 100%; transition: all .2s; 
    }
    .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); outline: none; }
    
    .form-control[readonly] { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; border-color: #cbd5e1; }
    
    textarea.form-control { height: auto; min-height: 80px; resize: vertical; }
    
    .text-muted { font-size: 12px; color: #94a3b8; margin-top: 4px; display: block; }
    
    /* Button Styling */
    .btnx { display: block; width: 100%; padding: 12px; border-radius: 8px; font-weight: 700; font-size: 14px; text-align: center; cursor: pointer; text-decoration: none; margin-bottom: 10px; border: 1px solid transparent; transition: .15s; }
    .btn-save { background: #2563eb; color: #fff; border: none; }
    .btn-save:hover { background: #1d4ed8; }
    .btn-cancel { background: #fff; border-color: #e2e8f0; color: #64748b; }
    .btn-cancel:hover { background: #f1f5f9; color: #0f172a; }
    
    .btn-remove-div:hover { background: #ef4444 !important; color: white !important; }
    .btn-add-div:hover { background: #e2e8f0 !important; border-color: #cbd5e1 !important; }
</style>

<div class="wrap-form">
    <div class="cardx">
        <div class="cardx-head">
            <h2><i class="fa fa-pencil" style="color:#2563eb;"></i> Edit Departemen</h2>
        </div>
        
        <div class="cardx-body">
            {{-- Alert error --}}
            @if($errors->any())
                <div style="background:#fef2f2; border-left:4px solid #dc2626; padding:15px; border-radius:8px; margin-bottom:20px; color:#7f1d1d; font-size:14px; font-weight: 600;">
                    <i class="fa fa-exclamation-circle" style="margin-right:8px; color:#dc2626;"></i>
                    Harap perbaiki kesalahan berikut:
                    <ul style="margin: 5px 0 0 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('departments.update', $department->id) }}" method="POST" autocomplete="off" id="deptForm">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                    <div class="form-group">
                        <label>Kode Departemen</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code', $department->code) }}" readonly>
                        <span class="text-muted"><i class="fa fa-lock"></i> Kode departemen dikunci.</span>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Departemen</label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: Produksi, Teknik, Logistik" value="{{ old('name', $department->name) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Deskripsi / Keterangan</label>
                    <textarea name="description" class="form-control" placeholder="Keterangan opsional mengenai departemen ini...">{{ old('description', $department->description) }}</textarea>
                </div>

                {{-- Bagian Input Dinamis Divisi --}}
                <div style="margin-top: 30px; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fa fa-sitemap" style="color:#2563eb;"></i> Daftar Divisi / Bagian
                        </h3>
                        <button type="button" class="btnx btn-add-div" id="btn-add-division" style="margin-bottom: 0; width: auto; padding: 8px 16px; font-size: 12px; background: #f8fafc; border: 1px solid #cbd5e1; color: #475569; border-radius: 8px; font-weight: 700; transition: 0.15s;">
                            <i class="fa fa-plus"></i> Tambah Baris Divisi
                        </button>
                    </div>

                    <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                        <table style="width: 100%; border-collapse: collapse;" id="divisions-table">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                    <th style="padding: 12px; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; width: 180px;">Kode Divisi</th>
                                    <th style="padding: 12px; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; width: 280px;">Nama Divisi</th>
                                    <th style="padding: 12px; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Deskripsi / Keterangan</th>
                                    <th style="padding: 12px; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; width: 60px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Baris divisi akan ditambahkan dinamis --}}
                            </tbody>
                        </table>
                        <div id="no-divisions-msg" style="text-align: center; padding: 30px; color: #94a3b8; font-size: 13px; background:#f8fafc;">
                            <i class="fa fa-sitemap" style="font-size: 24px; margin-bottom: 8px; display: block; color:#cbd5e1;"></i>
                            Belum ada divisi yang ditambahkan untuk departemen ini.
                        </div>
                    </div>
                </div>

                <hr style="margin:25px 0; border-top:1px solid #f1f5f9;">
                
                <button type="submit" class="btnx btn-save"><i class="fa fa-save"></i> Perbarui Departemen</button>
                <a href="{{ route('departments.index') }}" class="btnx btn-cancel">Batal</a>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script>
    $(document).ready(function() {
        var divIdx = 0;

        function addDivisionRow(id, code, name, desc) {
            $('#no-divisions-msg').hide();
            var hiddenId = id ? `<input type="hidden" name="divisions[${divIdx}][id]" value="${id}">` : '';
            var tr = `
                <tr style="border-bottom: 1px solid #f1f5f9; background: #fff;">
                    ${hiddenId}
                    <td style="padding: 8px 12px; vertical-align: middle;">
                        <input type="text" name="divisions[${divIdx}][code]" class="form-control" style="height: 36px; font-size: 13px; border-radius: 6px;" required placeholder="Contoh: ADM" value="${code || ''}" maxlength="10">
                    </td>
                    <td style="padding: 8px 12px; vertical-align: middle;">
                        <input type="text" name="divisions[${divIdx}][name]" class="form-control" style="height: 36px; font-size: 13px; border-radius: 6px;" required placeholder="Contoh: Administrasi" value="${name || ''}">
                    </td>
                    <td style="padding: 8px 12px; vertical-align: middle;">
                        <input type="text" name="divisions[${divIdx}][description]" class="form-control" style="height: 36px; font-size: 13px; border-radius: 6px;" placeholder="Keterangan opsional" value="${desc || ''}">
                    </td>
                    <td style="padding: 8px 12px; text-align: center; vertical-align: middle;">
                        <button type="button" class="btn-remove-div" style="background: #fee2e2; color: #ef4444; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: 0.15s;">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#divisions-table tbody').append(tr);
            divIdx++;
        }

        $('#btn-add-division').click(function() {
            addDivisionRow(null, '', '', '');
        });

        $('#divisions-table').on('click', '.btn-remove-div', function() {
            $(this).closest('tr').remove();
            if ($('#divisions-table tbody tr').length === 0) {
                $('#no-divisions-msg').show();
            }
        });

        // Load divisions (dari old input jika ada, jika tidak, dari database)
        var oldDivisions = {!! json_encode(old('divisions')) !!};
        var dbDivisions = {!! json_encode($department->divisions) !!};

        if (oldDivisions && Object.keys(oldDivisions).length > 0) {
            $.each(oldDivisions, function(key, val) {
                addDivisionRow(val.id || null, val.code, val.name, val.description);
            });
        } else if (dbDivisions && dbDivisions.length > 0) {
            $.each(dbDivisions, function(key, val) {
                addDivisionRow(val.id, val.code, val.name, val.description);
            });
        }
    });
</script>
@endsection

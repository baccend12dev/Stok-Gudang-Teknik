@extends('layouts.app')
@section('title','Import Departments')
@section('content')
<h4 class="mb-3">Import Departments (CSV)</h4>
<p class="text-muted">Kolom yang dikenali (bebas urutan): <code>code</code>, <code>name</code>, <code>is_active</code> (isi: 1/0, ya/tidak, true/false).</p>
<form action="{{ url('/departments/import') }}" method="post" enctype="multipart/form-data" class="mb-3">
  {{ csrf_field() }}
  <div class="form-group">
    <input type="file" name="file" class="form-control-file" required>
  </div>
  <div class="form-check mb-3">
    <input type="checkbox" name="replace_all" class="form-check-input" id="rall">
    <label class="form-check-label" for="rall">Replace all (truncate & isi ulang dari file)</label>
  </div>
  <button class="btn btn-primary">Upload & Import</button>
  <a href="{{ url('/departments') }}" class="btn btn-light">Batal</a>
</form>

<div class="card">
  <div class="card-header">Contoh CSV</div>
  <div class="card-body">
<pre class="mb-0">code,name,is_active
01,PRODUKSI,1
02,QA,1
03,PACKING,1
99,LAIN-LAIN,0</pre>
  </div>
</div>
@endsection

@extends('layouts.app')
@section('title','Departments')
@section('content')
<div class="card">
  <form method="get" class="toolbar">
    <input type="text" name="q" value="{{ isset($q) ? $q : '' }}" placeholder="Cari kode/nama...">
    <button class="btn">Terapkan</button>
    @if(isset($q) && $q !== '')
      <a class="btn btn-outline" href="{{ route('departments.index') }}">Reset</a>
    @endif
  </form>

  @if(!$has)
    <div class="muted">Tabel <code>m_departments</code> belum ada. Buat cepat di Postgres:</div>
    <pre>CREATE TABLE m_departments (
  id serial PRIMARY KEY,
  code varchar(20) UNIQUE,
  name varchar(120)
);</pre>
  @else
    <table>
      <thead><tr><th style="width:120px;">Kode</th><th>Nama</th></tr></thead>
      <tbody>
        @forelse($rows as $r)
          <tr><td>{{ $r->code }}</td><td>{{ $r->name }}</td></tr>
        @empty
          <tr><td colspan="2" class="muted">Belum ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div style="margin-top:10px;">{!! $rows->links() !!}</div>
  @endif
</div>
@endsection

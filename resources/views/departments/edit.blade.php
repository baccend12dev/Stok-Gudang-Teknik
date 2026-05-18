@extends('layouts.app')
@section('content')
<h3 class="ui header">Edit Department</h3>
<form class="ui form" method="post" action="/departments/{{ $department->id }}/update">
  {{ csrf_field() }}
  <div class="two fields">
    <div class="field"><label>Kode</label><input value="{{ $department->code }}" disabled></div>
    <div class="field"><label>Nama</label><input name="name" value="{{ $department->name }}" required></div>
  </div>
  <div class="field"><div class="ui checkbox"><input type="checkbox" name="is_active" {{ $department->is_active ? 'checked':'' }}><label>Aktif</label></div></div>
  <button class="ui primary button">Simpan</button>
  <a class="ui button" href="/departments">Batal</a>
</form>
@endsection

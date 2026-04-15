@extends('layouts.app')
@section('title', isset($number) && $number ? 'Edit Nomor' : 'Tambah Nomor')
@section('page-title', isset($number) && $number ? 'Edit Nomor' : 'Tambah Nomor')

@section('content')
<div class="card" style="max-width:480px">
  <form action="{{ $number ? route('allowlist.update', $number) : route('allowlist.store') }}" method="POST">
    @csrf
    @if($number) @method('PUT') @endif

    <div class="form-group">
      <label>Nomor WhatsApp <span style="color:#f85149">*</span></label>
      <input type="text" name="phone_number" value="{{ old('phone_number', $number?->phone_number) }}"
             class="form-control {{ $errors->has('phone_number') ? 'is-invalid' : '' }}"
             placeholder="628123456789">
      @error('phone_number')
        <small style="color:#f85149;font-size:.8rem">{{ $message }}</small>
      @enderror
      <small style="color:#8b949e;font-size:.8rem">Format: 628xxx (tanpa + atau spasi)</small>
    </div>

    <div class="form-group">
      <label>Label / Nama <small style="color:#8b949e">(opsional)</small></label>
      <input type="text" name="label" value="{{ old('label', $number?->label) }}"
             class="form-control" placeholder="Contoh: Teman Kantor">
    </div>

    <div class="form-group" style="display:flex;align-items:center;gap:.75rem">
      <input type="checkbox" name="is_active" id="is_active" value="1"
             {{ old('is_active', $number ? ($number->is_active ? '1' : '') : '1') == '1' ? 'checked' : '' }}
             style="width:16px;height:16px;accent-color:var(--accent)">
      <label for="is_active" style="margin-bottom:0;cursor:pointer">Aktif (akan mendapat auto-reply)</label>
    </div>

    <div style="display:flex;gap:.75rem;margin-top:1.25rem">
      <button type="submit" class="btn btn-primary">
        {{ $number ? '💾 Simpan Perubahan' : '+ Tambah Nomor' }}
      </button>
      <a href="{{ route('allowlist.index') }}" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
@endsection

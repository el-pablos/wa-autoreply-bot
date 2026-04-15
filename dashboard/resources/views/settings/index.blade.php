@extends('layouts.app')
@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan Bot')

@section('content')
<div class="card" style="max-width:560px">
  <form action="{{ route('settings.update') }}" method="POST">
    @csrf

    <div class="form-group">
      <label>Pesan Balasan Otomatis</label>
      <textarea name="reply_message" class="form-control {{ $errors->has('reply_message') ? 'is-invalid' : '' }}"
                rows="4" placeholder="Masukkan pesan balasan...">{{ old('reply_message', $settings['reply_message']?->value ?? '') }}</textarea>
      @error('reply_message')
        <small style="color:#f85149;font-size:.8rem">{{ $message }}</small>
      @enderror
      <small style="color:#8b949e;font-size:.8rem">Pesan ini akan dikirim ke nomor yang ada di allow-list</small>
    </div>

    <div class="form-group">
      <label>Delay Sebelum Balas (ms)</label>
      <input type="number" name="reply_delay_ms" min="0" max="10000"
             value="{{ old('reply_delay_ms', $settings['reply_delay_ms']?->value ?? 1500) }}"
             class="form-control {{ $errors->has('reply_delay_ms') ? 'is-invalid' : '' }}">
      <small style="color:#8b949e;font-size:.8rem">1500 ms = 1.5 detik (biar keliatan natural)</small>
    </div>

    <div class="form-group" style="display:flex;align-items:center;gap:.75rem">
      <input type="checkbox" name="auto_reply_enabled" id="auto_reply" value="true"
             {{ ($settings['auto_reply_enabled']?->value ?? 'false') === 'true' ? 'checked' : '' }}
             style="width:16px;height:16px;accent-color:var(--accent)">
      <label for="auto_reply" style="margin-bottom:0;cursor:pointer">Aktifkan Auto-Reply</label>
    </div>

    <div class="form-group" style="display:flex;align-items:center;gap:.75rem">
      <input type="checkbox" name="ignore_groups" id="ignore_groups" value="true"
             {{ ($settings['ignore_groups']?->value ?? 'true') === 'true' ? 'checked' : '' }}
             style="width:16px;height:16px;accent-color:var(--accent)">
      <label for="ignore_groups" style="margin-bottom:0;cursor:pointer">Abaikan Pesan dari Grup</label>
    </div>

    <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border)">
      <div style="font-size:.8rem;color:#8b949e;margin-bottom:1rem">Status Bot Saat Ini</div>
      @php $bs = $settings['bot_status']?->value ?? 'offline' @endphp
      <span class="status-badge {{ $bs }}">
        <span class="status-dot"></span> {{ ucfirst($bs) }}
      </span>
    </div>

    <button type="submit" class="btn btn-primary" style="margin-top:1.5rem">💾 Simpan Pengaturan</button>
  </form>
</div>
@endsection

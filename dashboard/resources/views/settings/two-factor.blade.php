@extends('layouts.app')

@php
  $pageTitle = 'Pengaturan 2FA';
  $pageEyebrow = 'SECURITY';
  $navActive = 'settings';

  $twoFactorEnabled = (bool) ($user->two_factor_enabled ?? false);
  $hasSetupSecret = is_string($setupSecret ?? null) && ($setupSecret ?? '') !== '';
  $codes = is_array($backupCodes ?? null) ? $backupCodes : [];
@endphp

@section('content')
<div class="space-y-5 max-w-4xl">
  @if (session('success'))
    <x-ui.card editorial padding="sm">
      <div class="flex items-start gap-3">
        <x-ui.badge variant="verified" size="sm" :dot="true">Saved</x-ui.badge>
        <p class="text-sm text-[var(--color-ink)]">{{ session('success') }}</p>
      </div>
    </x-ui.card>
  @endif

  @if ($errors->any())
    <x-ui.card editorial padding="sm">
      <div class="flex items-start gap-3">
        <x-ui.badge variant="danger" size="sm" :dot="true">Error</x-ui.badge>
        <p class="text-sm text-[var(--color-danger)]">{{ $errors->first() }}</p>
      </div>
    </x-ui.card>
  @endif

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">TWO FACTOR AUTH</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Authenticator TOTP</h2>
        <p class="display-italic text-sm">Tambahkan lapisan keamanan ekstra untuk login dashboard.</p>
      </div>
    </x-slot:header>
    <x-slot:headerActions>
      <x-ui.badge variant="{{ $twoFactorEnabled ? 'verified' : 'pending' }}" :dot="true">
        {{ $twoFactorEnabled ? 'ACTIVE' : 'INACTIVE' }}
      </x-ui.badge>
    </x-slot:headerActions>

    @if ($twoFactorEnabled)
      <div class="space-y-4">
        <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
          <p class="text-sm text-[var(--color-ink)]">2FA aktif untuk akun <span class="font-mono">{{ $user->email }}</span>. Login berikutnya akan meminta OTP atau backup code.</p>
        </x-ui.card>

        <form method="POST" action="{{ route('settings.2fa.disable') }}" class="space-y-4">
          @csrf

          <x-ui.input
            name="password"
            type="password"
            label="Password Saat Ini"
            placeholder="Masukkan password akun"
            required
          />

          <x-ui.input
            name="code"
            type="text"
            label="Kode OTP / Backup Code"
            placeholder="123456"
            required
          />

          <div class="flex items-center gap-2">
            <x-ui.button type="submit" variant="danger" icon="lucide-shield-off">Nonaktifkan 2FA</x-ui.button>
            <x-ui.button href="{{ route('settings.index') }}" variant="ghost">Kembali ke Settings</x-ui.button>
          </div>
        </form>
      </div>
    @else
      <div class="space-y-4">
        <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
          <p class="text-sm text-[var(--color-ink)]">Mulai setup dengan generate secret lalu scan QR dari authenticator app (Google Authenticator, 1Password, Authy, dll).</p>
        </x-ui.card>

        @if (!$hasSetupSecret)
          <form method="POST" action="{{ route('settings.2fa.setup') }}" class="space-y-3">
            @csrf
            <x-ui.button type="submit" variant="primary" icon="lucide-shield-check">Generate Secret 2FA</x-ui.button>
          </form>
        @else
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui.card padding="sm">
              <div class="eyebrow">STEP 1</div>
              <h3 class="font-display font-bold text-lg text-[var(--color-ink)]">Scan QR Code</h3>
              <div class="mt-3 flex items-center justify-center bg-white border border-[var(--color-rule)] rounded-md p-3">
                @if ($qrInline)
                  <img src="{{ $qrInline }}" alt="QR Code 2FA" class="w-44 h-44 object-contain">
                @else
                  <p class="text-xs text-[var(--color-ink-muted)]">QR tidak tersedia. Pakai secret manual di sisi kanan.</p>
                @endif
              </div>
            </x-ui.card>

            <x-ui.card padding="sm">
              <div class="eyebrow">STEP 2</div>
              <h3 class="font-display font-bold text-lg text-[var(--color-ink)]">Verifikasi OTP</h3>
              <p class="text-xs text-[var(--color-ink-muted)] mt-1">Manual secret:</p>
              <div class="mt-1 font-mono text-xs bg-[var(--color-card-muted)] border border-[var(--color-rule)] rounded-md px-2 py-1 break-all">{{ $setupSecret }}</div>

              <form method="POST" action="{{ route('settings.2fa.enable') }}" class="mt-3 space-y-3">
                @csrf
                <x-ui.input
                  name="code"
                  type="text"
                  label="Kode OTP"
                  placeholder="123456"
                  required
                />
                <div class="flex items-center gap-2">
                  <x-ui.button type="submit" variant="primary" icon="lucide-check-check">Aktifkan 2FA</x-ui.button>
                </div>
              </form>

              <form method="POST" action="{{ route('settings.2fa.setup') }}" class="mt-2">
                @csrf
                <x-ui.button type="submit" variant="ghost" size="sm">Regenerate Secret</x-ui.button>
              </form>
            </x-ui.card>
          </div>
        @endif
      </div>
    @endif
  </x-ui.card>

  @if (!empty($codes))
    <x-ui.card editorial>
      <x-slot:header>
        <div>
          <div class="eyebrow">BACKUP CODES</div>
          <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Simpan Kode Darurat</h2>
        </div>
      </x-slot:header>

      <p class="text-sm text-[var(--color-ink-muted)]">Setiap kode hanya bisa dipakai satu kali. Simpan offline di tempat aman.</p>

      <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
        @foreach ($codes as $code)
          <div class="font-mono text-sm border border-[var(--color-rule)] bg-[var(--color-card-muted)] rounded-md px-3 py-2">{{ $code }}</div>
        @endforeach
      </div>
    </x-ui.card>
  @endif
</div>
@endsection

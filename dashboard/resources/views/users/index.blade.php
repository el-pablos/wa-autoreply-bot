@extends('layouts.app')

@php
  $pageTitle = 'Users';
  $pageEyebrow = 'SISTEM';
  $navActive = 'users';
@endphp

@section('content')
<div class="space-y-5">
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
        <div class="eyebrow">B2 · RBAC</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">User Management</h2>
        <p class="display-italic text-sm">Kelola akun operator owner/admin/viewer dan status 2FA.</p>
      </div>
    </x-slot:header>

    <form action="{{ route('users.store') }}" method="POST" class="space-y-4 border border-[var(--color-rule)] rounded-md p-4 bg-[var(--color-card-muted)]">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <x-ui.input
          name="name"
          label="Nama"
          :value="old('name')"
          :error="$errors->first('name')"
          required
        />

        <x-ui.input
          name="email"
          type="email"
          label="Email"
          :value="old('email')"
          :error="$errors->first('email')"
          required
        />

        <x-ui.select
          name="role"
          label="Role"
          :options="[
            'owner' => 'Owner',
            'admin' => 'Admin',
            'viewer' => 'Viewer',
          ]"
          :value="old('role', 'viewer')"
          :error="$errors->first('role')"
        />

        <x-ui.input
          name="password"
          type="password"
          label="Password"
          :error="$errors->first('password')"
          required
        />
      </div>

      <x-ui.button type="submit" variant="primary" icon="lucide-user-plus">Tambah User</x-ui.button>
    </form>

    <div class="mt-4 space-y-3">
      @if ($users->isEmpty())
        <x-ui.empty
          title="Belum ada user"
          description="Tambahkan user baru untuk membagi akses operator sesuai role."
          icon="lucide-users"
        />
      @else
        @foreach ($users as $row)
          <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
            <form action="{{ route('users.update', $row) }}" method="POST" class="space-y-3">
              @csrf
              @method('PUT')

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="{{ $row->role === 'owner' ? 'danger' : ($row->role === 'admin' ? 'info' : 'muted') }}" size="sm">{{ strtoupper($row->role) }}</x-ui.badge>
                <x-ui.badge :variant="$row->two_factor_enabled ? 'verified' : 'pending'" size="sm">{{ $row->two_factor_enabled ? '2FA ON' : '2FA OFF' }}</x-ui.badge>
                <span class="font-mono text-xs text-[var(--color-ink-muted)]">last login: {{ $row->last_login_at?->format('d/m/Y H:i:s') ?? '-' }}</span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-ui.input name="name" label="Nama" :value="$row->name" required />
                <x-ui.input name="email" type="email" label="Email" :value="$row->email" required />
                <x-ui.select
                  name="role"
                  label="Role"
                  :options="[
                    'owner' => 'Owner',
                    'admin' => 'Admin',
                    'viewer' => 'Viewer',
                  ]"
                  :value="$row->role"
                />
                <x-ui.input name="password" type="password" label="Password baru (opsional)" hint="Kosongkan jika tidak ingin ganti password." />
              </div>

              <x-ui.button type="submit" variant="primary" size="sm" icon="lucide-save">Simpan</x-ui.button>
            </form>

            <div class="mt-2">
              <form action="{{ route('users.destroy', $row) }}" method="POST" onsubmit="return confirm('Hapus user ini?');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger" size="sm" icon="lucide-user-minus">Hapus User</x-ui.button>
              </form>
            </div>
          </x-ui.card>
        @endforeach

        <x-ui.pagination :paginator="$users" />
      @endif
    </div>
  </x-ui.card>
</div>
@endsection

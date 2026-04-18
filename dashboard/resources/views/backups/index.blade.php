@extends('layouts.app')

@php
  $pageTitle = 'Backups';
  $pageEyebrow = 'SISTEM';
  $navActive = 'backups';

  $formatBytes = function (int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $value = (float) max(0, $bytes);
    $unitIndex = 0;

    while ($value >= 1024 && $unitIndex < count($units) - 1) {
      $value /= 1024;
      $unitIndex++;
    }

    return number_format($value, $unitIndex === 0 ? 0 : 2) . ' ' . $units[$unitIndex];
  };
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

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
    <x-ui.stat-card eyebrow="TOTAL" :value="number_format($summary['total'])" label="Semua backup" icon="lucide-database-backup" />
    <x-ui.stat-card eyebrow="DB" :value="number_format($summary['db'])" label="Database" icon="lucide-database" />
    <x-ui.stat-card eyebrow="SESSION" :value="number_format($summary['session'])" label="Auth/session" icon="lucide-shield-check" />
    <x-ui.stat-card eyebrow="SIZE" :value="$formatBytes((int) $summary['size_bytes'])" label="Total ukuran" icon="lucide-hard-drive" />
  </div>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">B5 · BACKUP CONTROL</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Manual Backup Trigger</h2>
        <p class="display-italic text-sm">Trigger backup metadata untuk validasi workflow dashboard sebelum command backup server dihubungkan.</p>
      </div>
    </x-slot:header>

    <form action="{{ route('backups.run') }}" method="POST" class="grid grid-cols-1 md:grid-cols-[220px_auto] gap-3 items-end">
      @csrf
      <x-ui.select
        name="type"
        label="Type"
        :options="[
          'db' => 'Database',
          'session' => 'Session/Auth',
        ]"
        :value="old('type', 'db')"
        :error="$errors->first('type')"
      />
      <x-ui.button type="submit" variant="primary" icon="lucide-play">Jalankan Backup</x-ui.button>
    </form>
  </x-ui.card>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">ARTIFACTS</div>
        <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Backup List</h3>
      </div>
    </x-slot:header>

    @if ($backups->isEmpty())
      <x-ui.empty
        title="Belum ada backup"
        description="Jalankan backup manual pertama untuk membuat artifact di daftar ini."
        icon="lucide-package-open"
      />
    @else
      <x-ui.table :columns="[
        ['key' => 'created', 'label' => 'Waktu', 'class' => 'w-44'],
        ['key' => 'type', 'label' => 'Type', 'class' => 'w-24'],
        ['key' => 'path', 'label' => 'Path'],
        ['key' => 'size', 'label' => 'Size', 'class' => 'w-28'],
        ['key' => 'checksum', 'label' => 'Checksum', 'class' => 'w-40'],
        ['key' => 'action', 'label' => 'Action', 'class' => 'w-28'],
      ]">
        @foreach ($backups as $backup)
          <tr class="hover:bg-[var(--color-card-muted)]">
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)] whitespace-nowrap">{{ $backup->created_at?->format('d/m/Y H:i:s') }}</td>
            <td class="px-4 py-3"><x-ui.badge variant="{{ $backup->type === 'db' ? 'info' : 'pending' }}" size="sm">{{ strtoupper($backup->type) }}</x-ui.badge></td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink)] break-all">{{ $backup->path }}</td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)]">{{ $formatBytes((int) $backup->size_bytes) }}</td>
            <td class="px-4 py-3 font-mono text-[10px] text-[var(--color-ink-muted)]">{{ substr((string) $backup->checksum, 0, 12) }}...</td>
            <td class="px-4 py-3">
              <form action="{{ route('backups.destroy', $backup) }}" method="POST" onsubmit="return confirm('Hapus backup entry ini?');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger" size="sm" icon="lucide-trash-2">Hapus</x-ui.button>
              </form>
            </td>
          </tr>
        @endforeach
      </x-ui.table>

      <x-ui.pagination :paginator="$backups" />
    @endif
  </x-ui.card>
</div>
@endsection

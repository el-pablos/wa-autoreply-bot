@extends('layouts.app')

@php
    $pageTitle = 'Allow-List';
    $pageEyebrow = 'OPERATIONS';
    $navActive = 'allowlist';

    $totalActive = $numbers->where('is_active', true)->count();
    $totalInactive = $numbers->where('is_active', false)->count();
@endphp

@section('content')
<div class="space-y-5">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="rounded-md border border-[var(--color-verified)] bg-[var(--color-verified-soft)] px-4 py-3 text-sm text-[var(--color-verified)] font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-md border border-[var(--color-danger)] bg-[var(--color-danger-soft)] px-4 py-3 text-sm text-[var(--color-danger)] font-medium">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header section --}}
    <section class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div>
            <div class="eyebrow">DIRECTORY</div>
            <h2 class="font-display font-extrabold text-2xl md:text-3xl text-[var(--color-ink)]">Nomor Allow-List</h2>
            <p class="display-italic text-sm">Nomor yang akan menerima auto-reply dari bot.</p>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.badge variant="ink">{{ $numbers->total() }} total</x-ui.badge>
            <x-ui.button :href="route('allowlist.create')" variant="primary" icon="lucide-plus">Tambah Nomor</x-ui.button>
        </div>
    </section>

    {{-- Filter form --}}
    <x-ui.card padding="sm">
        <form method="GET" action="{{ route('allowlist.index') }}" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-2 md:gap-3 items-end">
            <x-ui.input
                name="search"
                label="Cari"
                :value="request('search')"
                placeholder="Nomor atau label..."
                mono
            />
            <x-ui.select
                name="status"
                label="Status"
                :options="[
                    '' => 'Semua status',
                    'active' => 'Aktif',
                    'inactive' => 'Nonaktif',
                ]"
                :value="request('status')"
            />
            <div class="flex gap-2">
                <x-ui.button type="submit" variant="primary" size="md" icon="lucide-filter">Filter</x-ui.button>
                @if (request()->anyFilled(['search', 'status']))
                    <x-ui.button :href="route('allowlist.index')" variant="ghost" size="md">Reset</x-ui.button>
                @endif
            </div>
        </form>
    </x-ui.card>

    {{-- Numbers list --}}
    @if ($numbers->isEmpty())
        <x-ui.card editorial padding="lg">
            <x-ui.empty
                title="Belum ada nomor di allow-list"
                description="{{ request()->anyFilled(['search', 'status']) ? 'Tidak ada hasil yang cocok dengan filter. Coba reset pencarian.' : 'Tambahkan nomor WhatsApp pertama supaya bot mulai membalas pesan dari nomor tersebut.' }}"
                icon="lucide-list-checks"
            >
                <x-slot:action>
                    <x-ui.button :href="route('allowlist.create')" variant="primary" icon="lucide-plus">Tambah Nomor</x-ui.button>
                </x-slot:action>
            </x-ui.empty>
        </x-ui.card>
    @else
        {{-- Mobile: card stack --}}
        <div class="md:hidden grid gap-3">
            @foreach ($numbers as $n)
                <x-ui.session-card
                    :title="$n->phone_number"
                    :subtitle="$n->label ?: 'Tanpa label'"
                    :phone="'ditambah ' . $n->created_at->diffForHumans()"
                    :status="$n->is_active ? 'verified' : 'muted'"
                    :statusLabel="$n->is_active ? 'AKTIF' : 'NONAKTIF'"
                    :meta="$n->created_at->format('d/m/Y')"
                >
                    <x-slot:actions>
                        <x-ui.button :href="route('allowlist.edit', $n)" variant="secondary" size="sm" icon="lucide-pencil">Edit</x-ui.button>
                        <form action="{{ route('allowlist.toggle', $n) }}" method="POST">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" :variant="$n->is_active ? 'outline' : 'primary'" size="sm">
                                {{ $n->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </x-ui.button>
                        </form>
                        <form action="{{ route('allowlist.destroy', $n) }}" method="POST"
                              onsubmit="return confirm('Hapus nomor {{ $n->phone_number }}?');">
                            @csrf @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm" icon="lucide-trash-2">Hapus</x-ui.button>
                        </form>
                    </x-slot:actions>
                </x-ui.session-card>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden md:block">
            <x-ui.table :columns="[
                ['key' => 'phone', 'label' => 'Nomor'],
                ['key' => 'label', 'label' => 'Label'],
                ['key' => 'status', 'label' => 'Status', 'class' => 'w-28'],
                ['key' => 'created', 'label' => 'Ditambah', 'class' => 'w-36'],
                ['key' => 'actions', 'label' => 'Aksi', 'class' => 'w-64 text-right'],
            ]">
                @foreach ($numbers as $n)
                    <tr class="hover:bg-[var(--color-card-muted)]">
                        <td class="px-4 py-3 font-mono text-sm text-[var(--color-ink)]">{{ $n->phone_number }}</td>
                        <td class="px-4 py-3 text-sm text-[var(--color-ink)]">
                            {{ $n->label ?: '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($n->is_active)
                                <x-ui.badge variant="verified" :dot="true">Aktif</x-ui.badge>
                            @else
                                <x-ui.badge variant="muted" :dot="true">Nonaktif</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)] whitespace-nowrap">
                            {{ $n->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2 flex-wrap">
                                <x-ui.button :href="route('allowlist.edit', $n)" variant="ghost" size="sm" icon="lucide-pencil">Edit</x-ui.button>
                                <form action="{{ route('allowlist.toggle', $n) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <x-ui.button type="submit" variant="ghost" size="sm">
                                        {{ $n->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </x-ui.button>
                                </form>
                                <form action="{{ route('allowlist.destroy', $n) }}" method="POST"
                                      onsubmit="return confirm('Hapus nomor {{ $n->phone_number }}?');">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm">Hapus</x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </div>

        <x-ui.pagination :paginator="$numbers" />
    @endif

</div>
@endsection

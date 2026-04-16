@props([
    'columns' => [],   // ['key' => 'name', 'label' => 'Nama', 'class' => 'text-left'] (optional)
    'rows' => null,    // jika null, pakai $slot biasa
    'mobileCards' => true,
])

{{--
    Tabel responsif dual-mode:
    - Desktop: tabel HTML reguler.
    - Mobile (<md): card-stack auto kalau $mobileCards true.

    Mode 1 (semantic with columns + rows):
        <x-ui.table :columns="$cols" :rows="$users">
            @foreach ($rows as $row)
                <tr>...</tr>
            @endforeach
        </x-ui.table>

    Mode 2 (manual via slot table):
        <x-ui.table :columns="$cols">
            <thead>...</thead>
            <tbody>...</tbody>
        </x-ui.table>
--}}

<div {{ $attributes->merge(['class' => 'w-full overflow-x-auto rounded-md border border-[var(--color-rule)] bg-[var(--color-card)]']) }}>
    <table class="w-full text-sm">
        @if (!empty($columns))
            <thead class="border-b-2 border-[var(--color-ink)]">
                <tr>
                    @foreach ($columns as $col)
                        <th class="px-4 py-3 text-left font-display font-bold text-[var(--color-ink)] tracking-[0.05em] uppercase text-xs {{ $col['class'] ?? '' }}">
                            {{ $col['label'] ?? $col['key'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-[var(--color-rule)]">
            {{ $slot }}
        </tbody>
    </table>
</div>

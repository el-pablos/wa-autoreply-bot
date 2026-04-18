@extends('layouts.app')

@php
    $isEdit = isset($number) && $number;
    $pageTitle = $isEdit ? 'Edit Nomor' : 'Tambah Nomor';
    $pageEyebrow = $isEdit ? 'EDIT RECORD' : 'NEW RECORD';
    $navActive = 'allowlist';
@endphp

@section('content')
<div class="max-w-2xl mx-auto md:mx-0 space-y-5">

    {{-- Breadcrumb / back --}}
    <div class="flex items-center gap-2">
        <x-ui.button :href="route('allowlist.index')" variant="ghost" size="sm" icon="lucide-arrow-left">Kembali ke Allow-list</x-ui.button>
    </div>

    {{-- Header --}}
    <div>
        <div class="eyebrow">{{ $isEdit ? 'EDITORIAL UPDATE' : 'EDITORIAL INTAKE' }}</div>
        <h2 class="font-display font-extrabold text-2xl md:text-3xl text-[var(--color-ink)]">
            {{ $isEdit ? 'Ubah Nomor ' . $number->phone_number : 'Tambah Nomor Baru' }}
        </h2>
        <p class="display-italic text-sm">
            {{ $isEdit ? 'Perbarui label atau status aktif untuk nomor ini.' : 'Masukkan nomor WhatsApp yang akan menerima auto-reply dari bot.' }}
        </p>
    </div>

    {{-- Form --}}
    <x-ui.card editorial padding="lg">
        <form action="{{ $isEdit ? route('allowlist.update', $number) : route('allowlist.store') }}" method="POST" class="space-y-5">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <x-ui.input
                name="phone_number"
                id="phone_number"
                label="Nomor WhatsApp"
                type="text"
                :value="old('phone_number', $number?->phone_number)"
                placeholder="+628123456789 / 628123456789 / 08123456789"
                :error="$errors->first('phone_number')"
                hint="Format diterima: +62..., 62..., atau 08... (angka saja). Sistem akan normalisasi ke 62xxxx."
                required
                mono
                prefix="WA"
            />

            <x-ui.input
                name="label"
                id="label"
                label="Label / Nama"
                type="text"
                :value="old('label', $number?->label)"
                placeholder="Contoh: Teman Kantor"
                :error="$errors->first('label')"
                hint="Opsional — memudahkan identifikasi di daftar."
            />

            <div class="pt-2 border-t border-[var(--color-rule)]">
                <x-ui.toggle
                    name="is_active"
                    id="is_active"
                    label="Status aktif"
                    description="Nomor aktif akan menerima auto-reply dari bot."
                    :checked="old('is_active', $isEdit ? (bool) $number->is_active : true)"
                    value="1"
                />
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-3 border-t border-[var(--color-ink)]">
                <x-ui.button :href="route('allowlist.index')" variant="ghost" size="md">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary" size="md" :icon="$isEdit ? 'lucide-save' : 'lucide-plus'">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Nomor' }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Helper note --}}
    @unless ($isEdit)
        <x-ui.card padding="md" class="bg-[var(--color-brass-50)]">
            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[var(--color-card)] border border-[var(--color-brass)] text-[var(--color-brass-700)] shrink-0">
                    @svg('lucide-info', 'w-4 h-4')
                </span>
                <div class="text-sm text-[var(--color-ink)]">
                    <div class="eyebrow mb-1">TIPS REDAKSI</div>
                    <p>Gunakan nomor WA aktif. Nomor duplikat akan ditolak. Sistem menyimpan dalam format 62xxxx tanpa tanda + atau spasi.</p>
                </div>
            </div>
        </x-ui.card>
    @endunless

</div>
@endsection

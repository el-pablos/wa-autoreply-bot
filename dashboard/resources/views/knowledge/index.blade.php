@extends('layouts.app')

@php
  $pageTitle = 'Knowledge Base';
  $pageEyebrow = 'INTELLIGENCE';
  $navActive = 'knowledge';
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

  <x-ui.card editorial padding="sm">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end">
      <x-ui.input
        name="search"
        label="Cari FAQ"
        placeholder="kata kunci di pertanyaan atau jawaban"
        :value="request('search')"
      />

      <div class="flex gap-2">
        <x-ui.button type="submit" variant="primary" icon="lucide-search">Cari</x-ui.button>
        @if (request()->query())
          <x-ui.button :href="route('knowledge.index')" variant="ghost">Reset</x-ui.button>
        @endif
      </div>
    </form>
  </x-ui.card>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">D1 · FAQ MATCHER</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Knowledge Base Entries</h2>
        <p class="display-italic text-sm">Data ini dipakai fuzzy matcher sebelum fallback ke AI.</p>
      </div>
    </x-slot:header>

    <form action="{{ route('knowledge.store') }}" method="POST" class="space-y-4 border border-[var(--color-rule)] rounded-md p-4 bg-[var(--color-card-muted)]">
      @csrf

      <x-ui.input
        name="question"
        label="Pertanyaan"
        :value="old('question')"
        :error="$errors->first('question')"
        placeholder="Contoh: Jam operasional hari ini sampai jam berapa?"
        required
      />

      <x-ui.input
        name="keywords"
        label="Keywords (opsional)"
        :value="old('keywords')"
        :error="$errors->first('keywords')"
        placeholder="jam operasional, buka, tutup"
        hint="Pisahkan dengan koma."
      />

      <x-ui.textarea
        name="answer"
        label="Jawaban"
        :value="old('answer')"
        :error="$errors->first('answer')"
        rows="4"
        maxlength="5000"
        counter
        required
      />

      <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
        <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked(old('is_active', 'true') === 'true')>
        <span>Aktif</span>
      </label>

      <x-ui.button type="submit" variant="primary" icon="lucide-plus">Tambah Entry</x-ui.button>
    </form>

    <div class="mt-4 space-y-3">
      @if ($entries->isEmpty())
        <x-ui.empty
          title="Knowledge base masih kosong"
          description="Tambah entry pertama agar FAQ matcher bisa mulai menjawab otomatis."
          icon="lucide-book-open"
        />
      @else
        @foreach ($entries as $entry)
          <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
            <form action="{{ route('knowledge.update', $entry) }}" method="POST" class="space-y-3">
              @csrf
              @method('PUT')

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$entry->is_active ? 'verified' : 'pending'" size="sm" :dot="$entry->is_active">{{ $entry->is_active ? 'ACTIVE' : 'INACTIVE' }}</x-ui.badge>
                <x-ui.badge variant="info" size="sm">match: {{ $entry->match_count }}</x-ui.badge>
              </div>

              <x-ui.input name="question" label="Pertanyaan" :value="$entry->question" required />

              <x-ui.input
                name="keywords"
                label="Keywords"
                :value="is_array($entry->keywords) ? implode(', ', $entry->keywords) : ''"
                hint="Pisahkan dengan koma."
              />

              <x-ui.textarea
                name="answer"
                label="Jawaban"
                :value="$entry->answer"
                rows="4"
                maxlength="5000"
                counter
                required
              />

              <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
                <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked($entry->is_active)>
                <span>Aktif</span>
              </label>

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" variant="primary" size="sm" icon="lucide-save">Simpan</x-ui.button>
              </div>
            </form>

            <div class="mt-2 flex flex-wrap items-center gap-2">
              <form action="{{ route('knowledge.toggle', $entry) }}" method="POST">
                @csrf
                @method('PATCH')
                <x-ui.button type="submit" variant="secondary" size="sm">Toggle Status</x-ui.button>
              </form>

              <form action="{{ route('knowledge.destroy', $entry) }}" method="POST" onsubmit="return confirm('Hapus knowledge entry ini?');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger" size="sm" icon="lucide-trash-2">Hapus</x-ui.button>
              </form>
            </div>
          </x-ui.card>
        @endforeach

        <x-ui.pagination :paginator="$entries" />
      @endif
    </div>
  </x-ui.card>
</div>
@endsection

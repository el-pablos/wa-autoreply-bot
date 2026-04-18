@extends('layouts.app')

@php
  $pageTitle = 'Reply Templates';
  $pageEyebrow = 'INTELLIGENCE';
  $navActive = 'templates';
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
        <div class="eyebrow">A1 · TEMPLATE DINAMIS</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Reply Template Library</h2>
        <p class="display-italic text-sm">Template ini dipakai untuk fallback balasan dan bisa diikat ke allowlist tertentu.</p>
      </div>
    </x-slot:header>

    <form action="{{ route('templates.reply.store') }}" method="POST" class="space-y-4 border border-[var(--color-rule)] rounded-md p-4 bg-[var(--color-card-muted)]">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.input
          name="name"
          label="Nama Template"
          placeholder="Contoh: Balasan VIP"
          :value="old('name')"
          :error="$errors->first('name')"
          required
        />

        <x-ui.textarea
          name="conditions_json"
          label="Conditions JSON (opsional)"
          :value="old('conditions_json')"
          :error="$errors->first('conditions_json')"
          placeholder='{"segment":"vip"}'
          rows="2"
          hint="Simpan metadata kondisi bila perlu, format JSON valid."
        />
      </div>

      <x-ui.textarea
        name="body"
        label="Isi Template"
        :value="old('body')"
        :error="$errors->first('body')"
        rows="4"
        maxlength="4000"
        counter
        required
      />

      <div class="flex flex-wrap items-center gap-4">
        <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
          <input type="checkbox" name="is_default" value="1" class="rounded border-[var(--color-rule)]" @checked(old('is_default'))>
          <span>Jadikan default</span>
        </label>

        <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
          <input type="checkbox" name="is_active" value="1" class="rounded border-[var(--color-rule)]" checked>
          <span>Aktif</span>
        </label>
      </div>

      <x-ui.button type="submit" variant="primary" icon="lucide-plus">Tambah Template</x-ui.button>
    </form>

    <div class="mt-4 space-y-3">
      @if ($replyTemplates->isEmpty())
        <x-ui.empty
          title="Belum ada reply template"
          description="Tambahkan template pertama untuk mulai mengatur fallback balasan."
          icon="lucide-file-text"
        />
      @else
        @foreach ($replyTemplates as $template)
          <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
            <form action="{{ route('templates.reply.update', $template) }}" method="POST" class="space-y-3">
              @csrf
              @method('PUT')

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$template->is_default ? 'verified' : 'muted'" size="sm" :dot="$template->is_default">{{ $template->is_default ? 'DEFAULT' : 'TEMPLATE' }}</x-ui.badge>
                <x-ui.badge :variant="$template->is_active ? 'info' : 'pending'" size="sm">{{ $template->is_active ? 'ACTIVE' : 'INACTIVE' }}</x-ui.badge>
              </div>

              <x-ui.input
                name="name"
                label="Nama"
                :value="$template->name"
                required
              />

              <x-ui.textarea
                name="body"
                label="Body"
                :value="$template->body"
                rows="3"
                maxlength="4000"
                counter
                required
              />

              <x-ui.textarea
                name="conditions_json"
                label="Conditions JSON"
                :value="$template->conditions_json ? json_encode($template->conditions_json, JSON_UNESCAPED_UNICODE) : ''"
                rows="2"
              />

              <div class="flex flex-wrap items-center gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
                  <input type="checkbox" name="is_default" value="1" class="rounded border-[var(--color-rule)]" @checked($template->is_default)>
                  <span>Default</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
                  <input type="checkbox" name="is_active" value="1" class="rounded border-[var(--color-rule)]" @checked($template->is_active)>
                  <span>Aktif</span>
                </label>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" variant="primary" size="sm" icon="lucide-save">Simpan</x-ui.button>
              </div>
            </form>

            <div class="mt-2 flex flex-wrap items-center gap-2">
              <form action="{{ route('templates.reply.default', $template) }}" method="POST">
                @csrf
                <x-ui.button type="submit" variant="secondary" size="sm" icon="lucide-badge-check">Jadikan Default</x-ui.button>
              </form>

              <form action="{{ route('templates.reply.destroy', $template) }}" method="POST" onsubmit="return confirm('Hapus template {{ $template->name }}?');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger" size="sm" icon="lucide-trash-2">Hapus</x-ui.button>
              </form>
            </div>
          </x-ui.card>
        @endforeach
      @endif
    </div>
  </x-ui.card>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">A3 · TEMPLATE PER JENIS PESAN</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Message Type Templates</h2>
        <p class="display-italic text-sm">Override khusus berdasarkan jenis incoming message (text, image, video, dll).</p>
      </div>
    </x-slot:header>

    <div class="space-y-3">
      @foreach ($supportedMessageTypes as $type)
        @php
          $item = $messageTypeTemplates->get($type);
        @endphp
        <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
          <form action="{{ route('templates.type.upsert') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="message_type" value="{{ $type }}">

            <div class="flex flex-wrap items-center gap-2">
              <x-ui.badge variant="muted" size="sm">{{ strtoupper($type) }}</x-ui.badge>
              <x-ui.badge :variant="($item?->is_active ?? true) ? 'info' : 'pending'" size="sm">{{ ($item?->is_active ?? true) ? 'ACTIVE' : 'INACTIVE' }}</x-ui.badge>
            </div>

            <x-ui.textarea
              name="body"
              label="Template"
              :value="$item?->body"
              rows="3"
              maxlength="4000"
              counter
              required
            />

            <div class="flex flex-wrap items-center gap-3">
              <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
                <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked($item ? $item->is_active : true)>
                <span>Aktif</span>
              </label>

              <x-ui.button type="submit" variant="primary" size="sm" icon="lucide-save">Simpan</x-ui.button>
            </div>
          </form>

          @if ($item)
            <form action="{{ route('templates.type.toggle', $type) }}" method="POST" class="mt-2">
              @csrf
              @method('PATCH')
              <x-ui.button type="submit" variant="ghost" size="sm">Toggle Status</x-ui.button>
            </form>
          @endif
        </x-ui.card>
      @endforeach
    </div>
  </x-ui.card>
</div>
@endsection

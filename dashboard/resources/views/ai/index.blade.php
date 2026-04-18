@extends('layouts.app')

@php
  $pageTitle = 'AI Reply';
  $pageEyebrow = 'INTELLIGENCE';
  $navActive = 'ai';

  $enabledValue = old('ai_reply_enabled', $settings['ai_reply_enabled']) === 'true';
  $modelValue = old('ai_model', $settings['ai_model']);
  $promptValue = old('ai_system_prompt', $settings['ai_system_prompt']);
  $contextValue = old('ai_max_context_messages', $settings['ai_max_context_messages']);
  $temperatureValue = old('ai_temperature', $settings['ai_temperature']);
  $fallbackValue = old('ai_fallback_provider', $settings['ai_fallback_provider']);
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

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">D2 · AI CONTROL</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">AI Reply Configuration</h2>
        <p class="display-italic text-sm">Atur model utama, prompt sistem, fallback provider, dan ukuran context multi-turn.</p>
      </div>
    </x-slot:header>

    <form action="{{ route('ai.update') }}" method="POST" class="space-y-4">
      @csrf

      <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
        <input type="checkbox" name="ai_reply_enabled" value="true" class="rounded border-[var(--color-rule)]" @checked($enabledValue)>
        <span>Aktifkan AI Reply</span>
      </label>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <x-ui.input
          name="ai_model"
          label="Model"
          :value="$modelValue"
          :error="$errors->first('ai_model')"
          placeholder="groq/llama-3.1-8b-instant"
          required
        />

        <x-ui.select
          name="ai_fallback_provider"
          label="Fallback Provider"
          :options="[
            'none' => 'No fallback',
            'groq' => 'Groq',
            'openai' => 'OpenAI',
          ]"
          :value="$fallbackValue"
          :error="$errors->first('ai_fallback_provider')"
        />

        <x-ui.input
          name="ai_max_context_messages"
          type="number"
          label="Max Context Messages"
          :value="$contextValue"
          min="1"
          max="50"
          :error="$errors->first('ai_max_context_messages')"
          required
        />

        <x-ui.input
          name="ai_temperature"
          type="number"
          step="0.1"
          min="0"
          max="2"
          label="Temperature"
          :value="$temperatureValue"
          :error="$errors->first('ai_temperature')"
          required
        />
      </div>

      <x-ui.textarea
        name="ai_system_prompt"
        label="System Prompt"
        :value="$promptValue"
        :error="$errors->first('ai_system_prompt')"
        rows="6"
        maxlength="4000"
        counter
        required
      />

      <x-ui.button type="submit" variant="primary" icon="lucide-save">Simpan Konfigurasi AI</x-ui.button>
    </form>
  </x-ui.card>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">AI HISTORY</div>
        <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Conversation Snippets</h3>
      </div>
    </x-slot:header>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end mb-4">
      <x-ui.input
        name="phone_number"
        label="Filter Nomor"
        placeholder="6281..."
        :value="request('phone_number')"
      />
      <div class="flex gap-2">
        <x-ui.button type="submit" variant="primary" icon="lucide-filter">Filter</x-ui.button>
        @if (request()->query())
          <x-ui.button :href="route('ai.index')" variant="ghost">Reset</x-ui.button>
        @endif
      </div>
    </form>

    @if ($history->isEmpty())
      <x-ui.empty
        title="Belum ada riwayat AI"
        description="Riwayat akan muncul saat AI reply aktif dan pipeline memproses percakapan user."
        icon="lucide-brain-cog"
      />
    @else
      <x-ui.table :columns="[
        ['key' => 'number', 'label' => 'Nomor', 'class' => 'w-44'],
        ['key' => 'role', 'label' => 'Role', 'class' => 'w-28'],
        ['key' => 'content', 'label' => 'Konten'],
        ['key' => 'tokens', 'label' => 'Tokens', 'class' => 'w-24'],
        ['key' => 'time', 'label' => 'Waktu', 'class' => 'w-44'],
      ]">
        @foreach ($history as $row)
          <tr class="hover:bg-[var(--color-card-muted)]">
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink)]">{{ $row->phone_number }}</td>
            <td class="px-4 py-3">
              <x-ui.badge variant="{{ $row->role === 'assistant' ? 'verified' : ($row->role === 'system' ? 'info' : 'muted') }}" size="sm">{{ strtoupper($row->role) }}</x-ui.badge>
            </td>
            <td class="px-4 py-3 text-sm text-[var(--color-ink)]">
              <span class="block truncate max-w-xl">{{ $row->content }}</span>
            </td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)]">{{ $row->tokens ?? '-' }}</td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)] whitespace-nowrap">{{ $row->created_at?->format('d/m/Y H:i:s') }}</td>
          </tr>
        @endforeach
      </x-ui.table>

      <x-ui.pagination :paginator="$history" />
    @endif
  </x-ui.card>
</div>
@endsection

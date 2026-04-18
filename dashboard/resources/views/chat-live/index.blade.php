@extends('layouts.app')

@php
  $pageTitle = 'Chat Live';
  $pageEyebrow = 'OPERASIONAL';
  $navActive = 'chat-live';
@endphp

@section('content')
<div class="space-y-5" x-data="chatLiveWatcher({ latestId: {{ (int) $latestId }}, feedUrl: @js(route('chat-live.feed')) })">
  <x-ui.card editorial padding="sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <div class="eyebrow">C3 · LIVE VIEWER</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Live Incoming Stream</h2>
        <p class="display-italic text-sm">Pantau pesan terbaru dan status balas tanpa buka raw logs.</p>
      </div>

      <div class="flex items-center gap-2">
        <x-ui.badge variant="info" size="sm" :dot="true">Polling 5s</x-ui.badge>
        <button type="button" class="text-xs font-semibold tracking-[0.08em] uppercase text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]" @click="enabled = !enabled">
          <span x-text="enabled ? 'Pause' : 'Resume'"></span>
        </button>
      </div>
    </div>

    <div x-show="hasNew" class="mt-3" style="display: none;">
      <x-ui.card padding="sm" class="bg-[var(--color-card-muted)] border-dashed">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="text-sm text-[var(--color-ink-muted)]"><span x-text="newCount"></span> pesan baru terdeteksi.</p>
          <x-ui.button variant="primary" size="sm" icon="lucide-refresh-cw" x-on:click="refreshNow()">Refresh Feed</x-ui.button>
        </div>
      </x-ui.card>
    </div>
  </x-ui.card>

  <x-ui.card editorial padding="sm">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
      <x-ui.input
        name="number"
        label="Nomor"
        :value="request('number')"
        placeholder="628..."
        mono
      />

      <x-ui.select
        name="replied"
        label="Status Balas"
        :options="[
          '' => 'Semua',
          'yes' => 'Sudah dibalas',
          'no' => 'Belum dibalas',
        ]"
        :value="request('replied')"
      />

      <x-ui.select
        name="is_allowed"
        label="Allowlist"
        :options="[
          '' => 'Semua',
          'yes' => 'Allowed',
          'no' => 'Blocked',
        ]"
        :value="request('is_allowed')"
      />

      <div class="flex gap-2">
        <x-ui.button type="submit" variant="primary" icon="lucide-filter">Filter</x-ui.button>
        @if (request()->query())
          <x-ui.button :href="route('chat-live.index')" variant="ghost">Reset</x-ui.button>
        @endif
      </div>
    </form>
  </x-ui.card>

  @if ($messages->isEmpty())
    <x-ui.card editorial>
      <x-ui.empty
        title="Belum ada pesan masuk"
        description="Live viewer akan terisi otomatis saat bot menerima message baru."
        icon="lucide-message-circle"
      />
    </x-ui.card>
  @else
    <div class="space-y-3">
      @foreach ($messages as $row)
        <x-ui.card editorial padding="sm">
          <div class="flex flex-wrap items-center gap-2 mb-2">
            <x-ui.badge variant="{{ $row->replied ? 'verified' : ($row->is_allowed ? 'pending' : 'danger') }}" size="sm" :dot="$row->replied">
              {{ $row->replied ? 'REPLIED' : ($row->is_allowed ? 'QUEUED' : 'BLOCKED') }}
            </x-ui.badge>
            <x-ui.badge variant="muted" size="sm">{{ strtoupper((string) $row->message_type) }}</x-ui.badge>
            <span class="font-mono text-xs text-[var(--color-ink-muted)]">{{ $row->received_at?->format('d/m/Y H:i:s') }}</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-3">
            <div>
              <div class="eyebrow">FROM</div>
              <div class="font-mono text-xs text-[var(--color-ink)] break-all">{{ $row->from_number }}</div>
            </div>

            <div>
              <div class="eyebrow">MESSAGE</div>
              <p class="text-sm text-[var(--color-ink)] whitespace-pre-wrap break-words">{{ $row->message_text ?: '—' }}</p>

              @if ($row->reply_text)
                <div class="mt-2 pt-2 border-t border-[var(--color-rule)]">
                  <div class="eyebrow">REPLY</div>
                  <p class="text-sm text-[var(--color-ink-muted)] whitespace-pre-wrap break-words">{{ $row->reply_text }}</p>
                </div>
              @endif
            </div>
          </div>
        </x-ui.card>
      @endforeach
    </div>

    <x-ui.pagination :paginator="$messages" />
  @endif
</div>
@endsection

@push('scripts')
<script>
function chatLiveWatcher(config) {
  return {
    enabled: true,
    hasNew: false,
    newCount: 0,
    latestId: Number(config.latestId || 0),
    feedUrl: String(config.feedUrl || ''),
    timer: null,

    init() {
      var self = this;
      this.timer = window.setInterval(function () {
        self.checkFeed();
      }, 5000);
    },

    async checkFeed() {
      if (!this.enabled || !this.feedUrl) {
        return;
      }

      try {
        var url = this.feedUrl + '?after_id=' + encodeURIComponent(String(this.latestId));
        var response = await fetch(url, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!response.ok) {
          return;
        }

        var data = await response.json();
        var count = Number(data.count || 0);
        if (count > 0) {
          this.hasNew = true;
          this.newCount = count;
          this.latestId = Number(data.latest_id || this.latestId);
        }
      } catch (error) {
        // Ignore intermittent polling failures in the UI watcher.
      }
    },

    refreshNow() {
      window.location.reload();
    }
  };
}
</script>
@endpush

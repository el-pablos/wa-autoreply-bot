@extends('layouts.app')

@php
  $pageTitle = 'Pengaturan Bot';
  $pageEyebrow = 'OPERATIONS';
  $navActive = 'settings';

  $replyMessageValue = old('reply_message', $settings['reply_message']?->value ?? '');
  $replyDelayValue = old('reply_delay_ms', $settings['reply_delay_ms']?->value ?? '1500');
  $autoReplyValue = old('auto_reply_enabled', $settings['auto_reply_enabled']?->value ?? 'false') === 'true';
  $ignoreGroupsValue = old('ignore_groups', $settings['ignore_groups']?->value ?? 'true') === 'true';

  $botStatus = $settings['bot_status']?->value ?? 'offline';
  $botStatusLabel = strtoupper($botStatus);
@endphp

@section('content')
<form
  action="{{ route('settings.update') }}"
  method="POST"
  x-data="{
    initial: {
      reply_message: @js((string) $replyMessageValue),
      reply_delay_ms: @js((string) $replyDelayValue),
      auto_reply_enabled: @js($autoReplyValue),
      ignore_groups: @js($ignoreGroupsValue),
    },
    form: {
      reply_message: @js((string) $replyMessageValue),
      reply_delay_ms: @js((string) $replyDelayValue),
      auto_reply_enabled: @js($autoReplyValue),
      ignore_groups: @js($ignoreGroupsValue),
    },
    saving: false,
    countDiff() {
      var keys = Object.keys(this.form);
      var changed = 0;
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        if (String(this.form[key]) !== String(this.initial[key])) {
          changed++;
        }
      }
      return changed;
    },
    resetToInitial() {
      this.form = {
        reply_message: this.initial.reply_message,
        reply_delay_ms: this.initial.reply_delay_ms,
        auto_reply_enabled: this.initial.auto_reply_enabled,
        ignore_groups: this.initial.ignore_groups,
      };
    },
  }"
  @submit="saving = true"
  class="space-y-5"
>
  @csrf

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
        <div class="eyebrow">CONFIGURATION</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Control Center</h2>
        <p class="display-italic text-sm">General, balasan, anti-spam, AI, webhook, backup, dan 2FA.</p>
      </div>
    </x-slot:header>
    <x-slot:headerActions>
      <x-ui.badge variant="{{ $botStatus === 'online' ? 'verified' : ($botStatus === 'connecting' ? 'pending' : 'danger') }}" size="sm" :dot="true">
        {{ strtoupper($botStatus) }}
      </x-ui.badge>
    </x-slot:headerActions>

    <x-ui.tabs
      :tabs="[
        ['key' => 'general', 'label' => 'General'],
        ['key' => 'reply', 'label' => 'Reply'],
        ['key' => 'schedule', 'label' => 'Schedule'],
        ['key' => 'anti-spam', 'label' => 'Anti-Spam'],
        ['key' => 'ai', 'label' => 'AI'],
        ['key' => 'webhook', 'label' => 'Webhook'],
        ['key' => 'backup', 'label' => 'Backup'],
        ['key' => 'two-fa', 'label' => '2FA'],
      ]"
      storage="settings-active-tab"
    >
      <section x-show="active === 'general'" class="space-y-4" style="display: none;">
        <div>
          <div class="eyebrow">SYSTEM STATUS</div>
          <h3 class="font-display font-bold text-xl text-[var(--color-ink)]">Ringkasan Runtime</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <x-ui.card padding="sm">
            <div class="eyebrow">BOT</div>
            <div class="mt-1"><x-ui.badge variant="{{ $botStatus === 'online' ? 'verified' : ($botStatus === 'connecting' ? 'pending' : 'danger') }}" :dot="true">{{ strtoupper($botStatus) }}</x-ui.badge></div>
          </x-ui.card>
          <x-ui.card padding="sm">
            <div class="eyebrow">REPLY MODE</div>
            <div class="mt-1">
              <x-ui.badge variant="{{ $autoReplyValue ? 'verified' : 'muted' }}">{{ $autoReplyValue ? 'AUTO' : 'MANUAL' }}</x-ui.badge>
            </div>
          </x-ui.card>
          <x-ui.card padding="sm">
            <div class="eyebrow">GROUP POLICY</div>
            <div class="mt-1">
              <x-ui.badge variant="{{ $ignoreGroupsValue ? 'info' : 'pending' }}">{{ $ignoreGroupsValue ? 'IGNORE GROUPS' : 'PROCESS GROUPS' }}</x-ui.badge>
            </div>
          </x-ui.card>
        </div>
      </section>

      <section x-show="active === 'reply'" class="space-y-4" style="display: none;">
        <div>
          <div class="eyebrow">AUTO REPLY</div>
          <h3 class="font-display font-bold text-xl text-[var(--color-ink)]">Template Balasan Default</h3>
        </div>

        <x-ui.textarea
          name="reply_message"
          label="Pesan Balasan"
          placeholder="Masukkan pesan balasan otomatis..."
          :error="$errors->first('reply_message')"
          hint="Pesan ini dipakai saat template lain belum dikonfigurasi."
          rows="5"
          counter
          maxlength="1000"
          x-model="form.reply_message"
        />

        <x-ui.input
          name="reply_delay_ms"
          type="number"
          label="Delay Sebelum Balas"
          hint="1500 ms = 1.5 detik supaya respon terasa natural."
          :error="$errors->first('reply_delay_ms')"
          min="0"
          max="10000"
          x-model="form.reply_delay_ms"
          suffix="ms"
        />
      </section>

      <section x-show="active === 'schedule'" class="space-y-3" style="display: none;">
        <div>
          <div class="eyebrow">SCHEDULE</div>
          <h3 class="font-display font-bold text-xl text-[var(--color-ink)]">Business Hours</h3>
          <p class="text-sm text-[var(--color-ink-muted)]">Kelola jam operasional mingguan, timezone, outside-hours message, dan jadwal OoF.</p>
        </div>
        <x-ui.card padding="sm" class="bg-[var(--color-card-muted)] border-dashed">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-sm text-[var(--color-ink-muted)]">Buka halaman khusus untuk setup jadwal per hari + override OoF yang dipakai langsung oleh pipeline bot.</p>
            <x-ui.button href="{{ route('business-hours.index') }}" variant="primary" size="sm" icon="lucide-clock-3">Buka Business Hours</x-ui.button>
          </div>
        </x-ui.card>
      </section>

      <section x-show="active === 'anti-spam'" class="space-y-4" style="display: none;">
        <div>
          <div class="eyebrow">ANTI SPAM</div>
          <h3 class="font-display font-bold text-xl text-[var(--color-ink)]">Proteksi Dasar</h3>
        </div>

        <label class="flex items-start justify-between gap-4 min-tap py-2 cursor-pointer">
          <div class="flex-1 min-w-0">
            <div class="font-display font-bold text-sm text-[var(--color-ink)]">Aktifkan Auto-Reply</div>
            <div class="text-xs text-[var(--color-ink-muted)] mt-0.5">Jika nonaktif, bot hanya mencatat log tanpa membalas.</div>
          </div>
          <span class="relative inline-flex items-center">
            <input type="checkbox" name="auto_reply_enabled" value="true" x-model="form.auto_reply_enabled" class="sr-only peer">
            <span class="w-11 h-6 rounded-full border-2 border-[var(--color-ink)] bg-[var(--color-paper)] transition-colors peer-checked:bg-[var(--color-verified)] peer-checked:border-[var(--color-verified)]"></span>
            <span class="absolute left-0.5 top-1/2 -translate-y-1/2 w-4 h-4 bg-[var(--color-card)] rounded-full border border-[var(--color-ink)] transition-transform peer-checked:translate-x-5 peer-checked:bg-[var(--color-paper)]"></span>
          </span>
        </label>

        <label class="flex items-start justify-between gap-4 min-tap py-2 cursor-pointer border-t border-[var(--color-rule)]">
          <div class="flex-1 min-w-0">
            <div class="font-display font-bold text-sm text-[var(--color-ink)]">Abaikan Pesan Grup</div>
            <div class="text-xs text-[var(--color-ink-muted)] mt-0.5">Saat aktif, hanya DM personal yang akan diproses bot.</div>
          </div>
          <span class="relative inline-flex items-center">
            <input type="checkbox" name="ignore_groups" value="true" x-model="form.ignore_groups" class="sr-only peer">
            <span class="w-11 h-6 rounded-full border-2 border-[var(--color-ink)] bg-[var(--color-paper)] transition-colors peer-checked:bg-[var(--color-verified)] peer-checked:border-[var(--color-verified)]"></span>
            <span class="absolute left-0.5 top-1/2 -translate-y-1/2 w-4 h-4 bg-[var(--color-card)] rounded-full border border-[var(--color-ink)] transition-transform peer-checked:translate-x-5 peer-checked:bg-[var(--color-paper)]"></span>
          </span>
        </label>
      </section>

      <section x-show="active === 'ai'" class="space-y-3" style="display: none;">
        <div>
          <div class="eyebrow">AI REPLY</div>
          <h3 class="font-display font-bold text-xl text-[var(--color-ink)]">Groq / OpenAI Control</h3>
          <p class="text-sm text-[var(--color-ink-muted)]">Kelola model, system prompt, fallback provider, dan pantau riwayat percakapan AI.</p>
        </div>
        <x-ui.card padding="sm" class="bg-[var(--color-card-muted)] border-dashed">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-sm text-[var(--color-ink-muted)]">Buka halaman AI Reply untuk atur konfigurasi model dan validasi context history yang dipakai pipeline.</p>
            <x-ui.button href="{{ route('ai.index') }}" variant="primary" size="sm" icon="lucide-sparkles">Buka AI Control</x-ui.button>
          </div>
        </x-ui.card>
      </section>

      <section x-show="active === 'webhook'" class="space-y-3" style="display: none;">
        <div>
          <div class="eyebrow">WEBHOOK</div>
          <h3 class="font-display font-bold text-xl text-[var(--color-ink)]">Integrasi Outbound</h3>
          <p class="text-sm text-[var(--color-ink-muted)]">Kelola endpoint webhook dan API key publik untuk integrasi eksternal.</p>
        </div>
        <x-ui.card padding="sm" class="bg-[var(--color-card-muted)] border-dashed">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-sm text-[var(--color-ink-muted)]">Buka halaman integrasi untuk create/update endpoint webhook, generate API key, dan revoke key.</p>
            <x-ui.button href="{{ route('webhooks.index') }}" variant="primary" size="sm" icon="lucide-webhook">Buka Webhooks & API</x-ui.button>
          </div>
        </x-ui.card>
      </section>

      <section x-show="active === 'backup'" class="space-y-3" style="display: none;">
        <div>
          <div class="eyebrow">BACKUP</div>
          <h3 class="font-display font-bold text-xl text-[var(--color-ink)]">Backup & Restore</h3>
          <p class="text-sm text-[var(--color-ink-muted)]">Kelola trigger backup manual, inventory backup, dan aksi housekeeping artifact.</p>
        </div>
        <x-ui.card padding="sm" class="bg-[var(--color-card-muted)] border-dashed">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-sm text-[var(--color-ink-muted)]">Buka halaman Backups untuk menjalankan backup DB/session dan memonitor daftar artifact terbaru.</p>
            <x-ui.button href="{{ route('backups.index') }}" variant="primary" size="sm" icon="lucide-database-backup">Buka Backups</x-ui.button>
          </div>
        </x-ui.card>
      </section>

      <section x-show="active === 'two-fa'" class="space-y-3" style="display: none;">
        <div>
          <div class="eyebrow">TWO FACTOR AUTH</div>
          <h3 class="font-display font-bold text-xl text-[var(--color-ink)]">2FA TOTP</h3>
          <p class="text-sm text-[var(--color-ink-muted)]">Setup authenticator, verifikasi OTP, dan backup code sekarang tersedia.</p>
        </div>

        <x-ui.card padding="sm" class="bg-[var(--color-card-muted)] border-dashed">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-sm text-[var(--color-ink-muted)]">Buka halaman khusus 2FA untuk generate QR, aktivasi, dan disable dengan verifikasi ulang.</p>
            <x-ui.button href="{{ route('settings.2fa.index') }}" variant="primary" size="sm" icon="lucide-shield-check">Buka Pengaturan 2FA</x-ui.button>
          </div>
        </x-ui.card>
      </section>
    </x-ui.tabs>
  </x-ui.card>

  <div class="fixed inset-x-0 bottom-20 md:bottom-4 z-40 px-4 md:px-6 lg:px-8 pointer-events-none">
    <div class="max-w-5xl mx-auto pointer-events-auto" x-show="countDiff() > 0" style="display: none;" x-transition>
      <x-ui.card editorial padding="sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div class="flex items-center gap-2">
            <x-ui.badge variant="pending" size="sm">
              <span x-text="countDiff()"></span> perubahan belum disimpan
            </x-ui.badge>
            <p class="text-sm text-[var(--color-ink-muted)]">Simpan untuk menerapkan konfigurasi terbaru.</p>
          </div>

          <div class="flex items-center gap-2">
            <x-ui.button type="button" variant="ghost" @click="resetToInitial()">Reset</x-ui.button>
            <x-ui.button type="submit" variant="primary" icon="lucide-save" x-bind:disabled="saving">
              Simpan Pengaturan
            </x-ui.button>
          </div>
        </div>
      </x-ui.card>
    </div>
  </div>
</form>
@endsection

@extends('layouts.app')

@php
  $pageTitle = 'Alerts';
  $pageEyebrow = 'INSIGHT';
  $navActive = 'alerts';
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
        <div class="eyebrow">C1 · ALERTING</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Alert Channels</h2>
        <p class="display-italic text-sm">Konfigurasi channel notifikasi untuk event bot penting dan kirim test alert cepat.</p>
      </div>
    </x-slot:header>

    <form action="{{ route('alerts.channels.store') }}" method="POST" class="space-y-4 border border-[var(--color-rule)] rounded-md p-4 bg-[var(--color-card-muted)]">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <x-ui.select
          name="type"
          label="Type"
          :options="['email' => 'Email (Gmail)']"
          :value="old('type', 'email')"
          :error="$errors->first('type')"
        />

        <x-ui.input
          name="target"
          label="Target"
          :value="old('target')"
          :error="$errors->first('target')"
          placeholder="6281... atau email@domain.com"
          required
        />
      </div>

      <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
        <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked(old('is_active', 'true') === 'true')>
        <span>Aktif</span>
      </label>

      <x-ui.button type="submit" variant="primary" icon="lucide-plus">Tambah Channel</x-ui.button>
    </form>

    <div class="mt-4 space-y-3">
      @if ($channels->isEmpty())
        <x-ui.empty
          title="Belum ada channel"
          description="Tambahkan channel WA/email supaya alert operasional bisa dikirim otomatis."
          icon="lucide-bell"
        />
      @else
        @foreach ($channels as $channel)
          <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
            <form action="{{ route('alerts.channels.update', $channel) }}" method="POST" class="space-y-3">
              @csrf
              @method('PUT')

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$channel->is_active ? 'verified' : 'pending'" size="sm" :dot="$channel->is_active">{{ $channel->is_active ? 'ACTIVE' : 'INACTIVE' }}</x-ui.badge>
                <x-ui.badge variant="muted" size="sm">{{ strtoupper($channel->type) }}</x-ui.badge>
                <span class="font-mono text-xs text-[var(--color-ink-muted)]">last: {{ $channel->last_alert_at?->format('d/m/Y H:i:s') ?? '-' }}</span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-ui.select
                  name="type"
                  label="Type"
                  :options="[
                    'wa' => 'WhatsApp',
                    'email' => 'Email',
                  ]"
                  :value="$channel->type"
                />

                <x-ui.input
                  name="target"
                  label="Target"
                  :value="$channel->target"
                  required
                />
              </div>

              <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
                <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked($channel->is_active)>
                <span>Aktif</span>
              </label>

              <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" variant="primary" size="sm" icon="lucide-save">Simpan</x-ui.button>
              </div>
            </form>

            <div class="mt-2 flex flex-wrap items-center gap-2">
              <form action="{{ route('alerts.channels.toggle', $channel) }}" method="POST">
                @csrf
                @method('PATCH')
                <x-ui.button type="submit" variant="secondary" size="sm">Toggle Status</x-ui.button>
              </form>

              <form action="{{ route('alerts.channels.test', $channel) }}" method="POST">
                @csrf
                <x-ui.button type="submit" variant="outline" size="sm" icon="lucide-send">Send Test</x-ui.button>
              </form>

              <form action="{{ route('alerts.channels.destroy', $channel) }}" method="POST" onsubmit="return confirm('Hapus alert channel ini?');">
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

  {{-- EmailJS Send Report Section --}}
  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">LAPORAN</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Kirim Laporan ke Gmail</h2>
        <p class="display-italic text-sm">Kirim ringkasan operasional bot langsung ke email kamu via EmailJS.</p>
      </div>
    </x-slot:header>

    <div
      x-data="{
        toEmail: '',
        subject: 'WA Bot \u2014 Laporan Operasional ' + new Date().toLocaleDateString('id-ID'),
        notes: '',
        sending: false,
        status: null,
        statusMsg: '',
        async sendReport() {
          if (!this.toEmail) {
            this.status = 'error';
            this.statusMsg = 'Email tujuan wajib diisi.';
            return;
          }
          this.sending = true;
          this.status = null;
          try {
            const res = await fetch('/alerts/report-data', {
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
              },
            });
            const data = await res.json();
            const recentLines = (data.recent_alerts || []).map(function(a) {
              return '[' + a.severity.toUpperCase() + '] ' + a.message + ' \u2014 ' + (a.success ? 'OK' : 'GAGAL');
            });
            const bodyParts = [
              '=== RINGKASAN OPERASIONAL WA BOT ===',
              '',
              'Dibuat: ' + new Date(data.generated_at).toLocaleString('id-ID'),
              '',
              '--- STATISTIK PESAN ---',
              'Total Pesan: ' + (data.total_messages || 0).toLocaleString('id-ID'),
              'Pesan Hari Ini: ' + (data.today_messages || 0).toLocaleString('id-ID'),
              'Alert Channel Aktif: ' + (data.active_channels || 0),
              '',
              '--- ALERT TERBARU ---',
              ...recentLines,
              '',
            ];
            if (this.notes) {
              bodyParts.push('--- CATATAN ---');
              bodyParts.push(this.notes);
              bodyParts.push('');
            }
            bodyParts.push('=== akhir laporan ===');
            const body = bodyParts.join('\n');

            await emailjs.send(
              'service_l0d0dy1',
              'template_f5yxmcs',
              {
                to_email: this.toEmail,
                subject: this.subject,
                message: body,
                from_name: 'WA Bot Dashboard',
              },
              'VL37ccvDIDg0haxhm'
            );
            this.status = 'success';
            this.statusMsg = 'Laporan berhasil dikirim ke ' + this.toEmail;
          } catch (err) {
            this.status = 'error';
            this.statusMsg = 'Gagal kirim: ' + (err.text || err.message || 'Unknown error');
          } finally {
            this.sending = false;
          }
        },
      }"
      class="space-y-4"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <x-ui.input
          name="to_email_js"
          type="email"
          label="Email Tujuan"
          placeholder="owner@gmail.com"
          x-model="toEmail"
          required
        />
        <x-ui.input
          name="subject_js"
          type="text"
          label="Subject"
          x-model="subject"
        />
      </div>

      <x-ui.textarea
        name="notes_js"
        label="Catatan Tambahan (opsional)"
        placeholder="Tulis catatan atau konteks tambahan..."
        rows="3"
        x-model="notes"
      />

      <div class="flex flex-wrap items-center gap-3">
        <x-ui.button
          type="button"
          variant="primary"
          icon="lucide-mail"
          x-bind:disabled="sending"
          @click="sendReport()"
        >
          <span x-show="!sending">Kirim Laporan ke Gmail</span>
          <span x-show="sending" x-cloak>Mengirim...</span>
        </x-ui.button>

        <div x-show="status === 'success'" x-cloak>
          <x-ui.badge variant="verified" :dot="true">
            <span x-text="statusMsg"></span>
          </x-ui.badge>
        </div>
        <div x-show="status === 'error'" x-cloak>
          <x-ui.badge variant="danger">
            <span x-text="statusMsg"></span>
          </x-ui.badge>
        </div>
      </div>
    </div>
  </x-ui.card>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">DELIVERY HISTORY</div>
        <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Alert History</h3>
      </div>
    </x-slot:header>

    @if ($history->isEmpty())
      <x-ui.empty
        title="Belum ada histori alert"
        description="Riwayat akan muncul setiap alert dikirim dari scheduler atau test channel."
        icon="lucide-history"
      />
    @else
      <x-ui.table :columns="[
        ['key' => 'time', 'label' => 'Waktu', 'class' => 'w-44'],
        ['key' => 'channel', 'label' => 'Channel', 'class' => 'w-28'],
        ['key' => 'target', 'label' => 'Target', 'class' => 'w-56'],
        ['key' => 'severity', 'label' => 'Severity', 'class' => 'w-28'],
        ['key' => 'message', 'label' => 'Pesan'],
        ['key' => 'status', 'label' => 'Status', 'class' => 'w-24'],
      ]">
        @foreach ($history as $row)
          <tr class="hover:bg-[var(--color-card-muted)]">
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)] whitespace-nowrap">{{ $row->created_at?->format('d/m/Y H:i:s') }}</td>
            <td class="px-4 py-3"><x-ui.badge variant="muted" size="sm">{{ strtoupper((string) optional($row->channel)->type) }}</x-ui.badge></td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink)]">{{ optional($row->channel)->target ?? '-' }}</td>
            <td class="px-4 py-3"><x-ui.badge variant="{{ $row->severity === 'err' ? 'danger' : ($row->severity === 'warn' ? 'pending' : 'info') }}" size="sm">{{ strtoupper((string) $row->severity) }}</x-ui.badge></td>
            <td class="px-4 py-3 text-sm text-[var(--color-ink)]">{{ $row->message }}</td>
            <td class="px-4 py-3">
              <x-ui.badge :variant="$row->success ? 'verified' : 'danger'" size="sm">{{ $row->success ? 'OK' : 'FAIL' }}</x-ui.badge>
            </td>
          </tr>
        @endforeach
      </x-ui.table>
    @endif
  </x-ui.card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof emailjs !== 'undefined') {
      emailjs.init({ publicKey: 'VL37ccvDIDg0haxhm' });
    }
  });
</script>
@endpush

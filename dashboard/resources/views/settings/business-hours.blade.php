@extends('layouts.app')

@php
  $pageTitle = 'Business Hours';
  $pageEyebrow = 'INTELLIGENCE';
  $navActive = 'business-hours';
@endphp

@section('content')
<form action="{{ route('business-hours.update') }}" method="POST" class="space-y-5">
  @csrf

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
        <div class="eyebrow">A2 · BUSINESS HOURS</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Jadwal Operasional Mingguan</h2>
        <p class="display-italic text-sm">Data di halaman ini dipakai bot untuk override jawaban saat di luar jam kerja.</p>
      </div>
    </x-slot:header>

    <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <label class="flex items-start justify-between gap-4 min-tap py-2 cursor-pointer border-b border-[var(--color-rule)] md:border-b-0">
          <div class="flex-1 min-w-0">
            <div class="font-display font-bold text-sm text-[var(--color-ink)]">Aktifkan Business Hours</div>
            <div class="text-xs text-[var(--color-ink-muted)] mt-0.5">Kalau aktif, bot kirim pesan outside-hours di luar jadwal ini.</div>
          </div>
          <span class="relative inline-flex items-center">
            <input type="checkbox" name="business_hours_enabled" value="true" class="sr-only peer" @checked(old('business_hours_enabled', $businessHoursEnabled ? 'true' : 'false') === 'true')>
            <span class="w-11 h-6 rounded-full border-2 border-[var(--color-ink)] bg-[var(--color-paper)] transition-colors peer-checked:bg-[var(--color-verified)] peer-checked:border-[var(--color-verified)]"></span>
            <span class="absolute left-0.5 top-1/2 -translate-y-1/2 w-4 h-4 bg-[var(--color-card)] rounded-full border border-[var(--color-ink)] transition-transform peer-checked:translate-x-5 peer-checked:bg-[var(--color-paper)]"></span>
          </span>
        </label>

        <x-ui.input
          name="timezone"
          label="Timezone"
          placeholder="Asia/Jakarta"
          :value="old('timezone', $timezone)"
          :error="$errors->first('timezone')"
          required
        />
      </div>

      <x-ui.textarea
        name="outside_business_hours_message"
        label="Pesan Saat Di Luar Jam Operasional"
        :value="old('outside_business_hours_message', $outsideHoursMessage)"
        :error="$errors->first('outside_business_hours_message')"
        rows="3"
        maxlength="1000"
        counter
        hint="Pesan ini dikirim jika incoming message masuk di luar jadwal aktif."
      />

      <div class="space-y-2">
        <div class="eyebrow">WEEKLY SCHEDULE</div>
        <div class="grid gap-2">
          @foreach ($weekdayNames as $weekday => $label)
            @php
              $enabled = old("schedule.$weekday.enabled", ($schedule[$weekday]['enabled'] ?? false) ? '1' : '0') === '1';
              $start = old("schedule.$weekday.start_time", $schedule[$weekday]['start_time'] ?? '09:00');
              $end = old("schedule.$weekday.end_time", $schedule[$weekday]['end_time'] ?? '17:00');
            @endphp
            <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
              <div class="grid grid-cols-1 md:grid-cols-[160px_120px_1fr_1fr] gap-3 md:items-end">
                <div>
                  <div class="font-display font-bold text-sm text-[var(--color-ink)]">{{ $label }}</div>
                  <div class="text-xs text-[var(--color-ink-muted)]">ISO {{ $weekday }}</div>
                </div>

                <div class="flex items-center gap-2 min-tap">
                  <input type="hidden" name="schedule[{{ $weekday }}][enabled]" value="0">
                  <input id="schedule-{{ $weekday }}-enabled" type="checkbox" name="schedule[{{ $weekday }}][enabled]" value="1" class="rounded border-[var(--color-rule)]" @checked($enabled)>
                  <label for="schedule-{{ $weekday }}-enabled" class="text-xs text-[var(--color-ink-muted)]">Aktif</label>
                </div>

                <x-ui.input
                  type="time"
                  name="schedule[{{ $weekday }}][start_time]"
                  label="Mulai"
                  :value="$start"
                  :error="$errors->first("schedule.$weekday.start_time")"
                />

                <x-ui.input
                  type="time"
                  name="schedule[{{ $weekday }}][end_time]"
                  label="Selesai"
                  :value="$end"
                  :error="$errors->first("schedule.$weekday.end_time")"
                />
              </div>
            </x-ui.card>
          @endforeach
        </div>
      </div>
    </div>
  </x-ui.card>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">A2 · OUT OF OFFICE</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Jadwal OoF</h2>
        <p class="display-italic text-sm">Saat OoF aktif, balasan bot akan diprioritaskan ke pesan OoF.</p>
      </div>
    </x-slot:header>

    <div class="space-y-4">
      <label class="flex items-start justify-between gap-4 min-tap py-2 cursor-pointer border-b border-[var(--color-rule)]">
        <div class="flex-1 min-w-0">
          <div class="font-display font-bold text-sm text-[var(--color-ink)]">Aktifkan Out-of-Office</div>
          <div class="text-xs text-[var(--color-ink-muted)] mt-0.5">Jika aktif, isi tanggal + pesan agar override berjalan otomatis.</div>
        </div>
        <span class="relative inline-flex items-center">
          <input type="checkbox" name="oof_enabled" value="true" class="sr-only peer" @checked(old('oof_enabled', $oofEnabled ? 'true' : 'false') === 'true')>
          <span class="w-11 h-6 rounded-full border-2 border-[var(--color-ink)] bg-[var(--color-paper)] transition-colors peer-checked:bg-[var(--color-verified)] peer-checked:border-[var(--color-verified)]"></span>
          <span class="absolute left-0.5 top-1/2 -translate-y-1/2 w-4 h-4 bg-[var(--color-card)] rounded-full border border-[var(--color-ink)] transition-transform peer-checked:translate-x-5 peer-checked:bg-[var(--color-paper)]"></span>
        </span>
      </label>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.input
          type="date"
          name="oof_start_date"
          label="Tanggal Mulai"
          :value="old('oof_start_date', $activeOof?->start_date?->format('Y-m-d'))"
          :error="$errors->first('oof_start_date')"
        />

        <x-ui.input
          type="date"
          name="oof_end_date"
          label="Tanggal Selesai"
          :value="old('oof_end_date', $activeOof?->end_date?->format('Y-m-d'))"
          :error="$errors->first('oof_end_date')"
        />
      </div>

      <x-ui.textarea
        name="oof_message"
        label="Pesan OoF"
        :value="old('oof_message', $activeOof?->message)"
        :error="$errors->first('oof_message')"
        rows="4"
        maxlength="1000"
        counter
        hint="Contoh: Tim sedang libur nasional, pesanmu tetap kami terima dan akan dibalas setelah operasional kembali."
      />

      @if ($activeOof)
        <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
          <div class="text-xs text-[var(--color-ink-muted)]">
            Active OoF saat ini: {{ $activeOof->start_date?->format('d M Y') }} - {{ $activeOof->end_date?->format('d M Y') }}
          </div>
        </x-ui.card>
      @endif
    </div>
  </x-ui.card>

  <div class="flex items-center gap-2">
    <x-ui.button type="submit" variant="primary" icon="lucide-save">Simpan Jadwal</x-ui.button>
    <x-ui.button :href="route('settings.index')" variant="ghost">Kembali ke Settings</x-ui.button>
  </div>
</form>
@endsection

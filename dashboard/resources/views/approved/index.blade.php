@extends('layouts.app')

@php
    $pageTitle = 'Approved Sessions';
    $pageEyebrow = 'OPERATIONS';
    $navActive = 'approved';
@endphp

@section('content')
<div class="space-y-6">

    {{-- Active sessions --}}
    <section>
        <div class="flex items-end justify-between mb-3">
            <div>
                <div class="eyebrow">ACTIVE</div>
                <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Sesi Aktif</h2>
                <p class="display-italic text-sm">Disetujui oleh owner. Auto-expire saat idle.</p>
            </div>
            <x-ui.badge variant="ink">{{ $activeSessions->total() }} aktif</x-ui.badge>
        </div>

        @if ($activeSessions->isEmpty())
            <x-ui.empty
                title="Belum ada sesi aktif"
                description="Sesi muncul setelah owner mengirim perintah /approve di chat WA."
                icon="lucide-shield-check"
            />
        @else
            <div class="grid gap-3">
                @foreach ($activeSessions as $session)
                    <x-ui.session-card
                        :title="$session->phone_number"
                        :subtitle="'Disetujui oleh ' . $session->approved_by"
                        :phone="'approved ' . $session->approved_at?->diffForHumans()"
                        status="verified"
                        statusLabel="ACTIVE"
                        :meta="'expires ' . $session->expires_at?->diffForHumans()"
                    >
                        <x-slot:body>
                            <dl class="grid grid-cols-2 gap-2 text-xs font-mono text-[var(--color-ink-muted)]">
                                <div><dt class="eyebrow text-[var(--color-ink-muted)]">APPROVED</dt><dd class="text-[var(--color-ink)]">{{ $session->approved_at?->format('d/m/Y H:i') }}</dd></div>
                                <div><dt class="eyebrow text-[var(--color-ink-muted)]">LAST ACTIVITY</dt><dd class="text-[var(--color-ink)]">{{ $session->last_activity_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                                <div><dt class="eyebrow text-[var(--color-ink-muted)]">EXPIRES</dt><dd class="text-[var(--color-ink)]">{{ $session->expires_at?->format('d/m/Y H:i') }}</dd></div>
                            </dl>
                        </x-slot:body>
                        <x-slot:actions>
                            <form action="{{ route('approved.revoke', $session->id) }}" method="POST"
                                  onsubmit="return confirm('Cabut sesi {{ $session->phone_number }}?');">
                                @csrf
                                <x-ui.button type="submit" variant="danger" size="sm">Revoke</x-ui.button>
                            </form>
                        </x-slot:actions>
                    </x-ui.session-card>
                @endforeach
            </div>
            <x-ui.pagination :paginator="$activeSessions" />
        @endif
    </section>

    {{-- History --}}
    <section>
        <div class="flex items-end justify-between mb-3">
            <div>
                <div class="eyebrow">HISTORY</div>
                <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Riwayat Sesi</h2>
                <p class="display-italic text-sm">Sesi yang sudah expired atau di-revoke.</p>
            </div>
        </div>

        @if ($historySessions->isEmpty())
            <x-ui.empty
                title="Belum ada riwayat sesi"
                description="History muncul saat sesi expired atau di-revoke."
                icon="lucide-history"
            />
        @else
            <div class="grid gap-3">
                @foreach ($historySessions as $session)
                    <x-ui.session-card
                        :title="$session->phone_number"
                        :subtitle="'Disetujui oleh ' . $session->approved_by"
                        :status="$session->revoked_at ? 'danger' : 'muted'"
                        :statusLabel="$session->revoked_at ? 'REVOKED' : 'EXPIRED'"
                        :meta="$session->revoked_at?->format('d/m/Y H:i') ?? $session->expires_at?->format('d/m/Y H:i')"
                    >
                        <x-slot:body>
                            <dl class="grid grid-cols-2 gap-2 text-xs font-mono text-[var(--color-ink-muted)]">
                                <div><dt class="eyebrow text-[var(--color-ink-muted)]">APPROVED</dt><dd class="text-[var(--color-ink)]">{{ $session->approved_at?->format('d/m/Y H:i') }}</dd></div>
                                <div><dt class="eyebrow text-[var(--color-ink-muted)]">EXPIRED</dt><dd class="text-[var(--color-ink)]">{{ $session->expires_at?->format('d/m/Y H:i') }}</dd></div>
                                <div><dt class="eyebrow text-[var(--color-ink-muted)]">REVOKED</dt><dd class="text-[var(--color-ink)]">{{ $session->revoked_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                            </dl>
                        </x-slot:body>
                    </x-ui.session-card>
                @endforeach
            </div>
            <x-ui.pagination :paginator="$historySessions" />
        @endif
    </section>

</div>
@endsection

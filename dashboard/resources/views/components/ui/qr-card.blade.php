@props([
    'src' => null,           // URL ke endpoint QR (bot /qr) atau data URI
    'refreshInterval' => 30, // detik
    'title' => 'Scan QR WhatsApp',
    'description' => 'Buka WhatsApp di HP → Settings → Linked Devices → Link a Device',
])

<x-ui.card editorial padding="lg" {{ $attributes }}>
    <div class="text-center" x-data="{
        seconds: {{ $refreshInterval }},
        bust: Date.now(),
        tick() {
            this.seconds--;
            if (this.seconds <= 0) {
                this.bust = Date.now();
                this.seconds = {{ $refreshInterval }};
            }
        }
    }" x-init="setInterval(() => tick(), 1000)">
        <div class="eyebrow">PAIRING</div>
        <h3 class="font-display font-extrabold text-2xl text-[var(--color-ink)] mt-1">{{ $title }}</h3>
        <p class="display-italic text-sm mt-2 max-w-xs mx-auto">{{ $description }}</p>

        <div class="my-6 inline-block bg-[var(--color-card)] border-2 border-[var(--color-ink)] rounded-md p-4 shadow-stamp">
            @if ($src)
                <img :src="`{{ $src }}?t=${bust}`" alt="QR Code WhatsApp" class="w-56 h-56 md:w-64 md:h-64 block" />
            @else
                <div class="w-56 h-56 md:w-64 md:h-64 flex items-center justify-center text-[var(--color-ink-muted)] font-mono text-xs">
                    QR belum tersedia
                </div>
            @endif
        </div>

        <div class="text-xs font-mono text-[var(--color-ink-muted)]">
            Refresh otomatis dalam <strong class="text-[var(--color-ink)]" x-text="seconds"></strong>s
        </div>
    </div>
</x-ui.card>

{{-- Toast stack listener Alpine — dipanggil oleh window.toast(message, type) --}}
<div
    x-data="{
        toasts: [],
        push(detail) {
            const t = { ...detail, id: detail.id ?? Date.now() };
            this.toasts.push(t);
            setTimeout(() => { this.toasts = this.toasts.filter(x => x.id !== t.id); }, t.timeout ?? 3500);
        }
    }"
    @toast.window="push($event.detail)"
    class="fixed top-4 right-4 z-[60] flex flex-col gap-2 pointer-events-none w-[calc(100vw-2rem)] sm:w-96"
    aria-live="polite"
    aria-atomic="true"
>
    {{-- Server-side flash messages --}}
    @if (session('success'))
        <div class="pointer-events-auto bg-[var(--color-card)] border border-[var(--color-verified)] rounded-md p-3 shadow-stamp-sm flex gap-2"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)">
            <span class="status-bar bg-[var(--color-verified)]"></span>
            <div class="flex-1">
                <div class="eyebrow text-[var(--color-verified)]">BERHASIL</div>
                <div class="text-sm text-[var(--color-ink)]">{{ session('success') }}</div>
            </div>
            <button type="button" @click="show = false" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)] text-sm" aria-label="Tutup">×</button>
        </div>
    @endif

    @if (session('error'))
        <div class="pointer-events-auto bg-[var(--color-card)] border border-[var(--color-danger)] rounded-md p-3 shadow-stamp-sm flex gap-2"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <span class="status-bar bg-[var(--color-danger)]"></span>
            <div class="flex-1">
                <div class="eyebrow text-[var(--color-danger)]">GAGAL</div>
                <div class="text-sm text-[var(--color-ink)]">{{ session('error') }}</div>
            </div>
            <button type="button" @click="show = false" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)] text-sm" aria-label="Tutup">×</button>
        </div>
    @endif

    {{-- Client-side toasts --}}
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="pointer-events-auto bg-[var(--color-card)] rounded-md p-3 shadow-stamp-sm flex gap-2 border"
            :class="{
                'border-[var(--color-verified)]': toast.type === 'success',
                'border-[var(--color-danger)]': toast.type === 'error',
                'border-[var(--color-info)]': toast.type === 'info' || !toast.type,
                'border-[var(--color-pending)]': toast.type === 'warning',
            }"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <span class="status-bar"
                  :class="{
                      'bg-[var(--color-verified)]': toast.type === 'success',
                      'bg-[var(--color-danger)]': toast.type === 'error',
                      'bg-[var(--color-info)]': toast.type === 'info' || !toast.type,
                      'bg-[var(--color-pending)]': toast.type === 'warning',
                  }"
            ></span>
            <div class="flex-1">
                <div class="eyebrow"
                     :class="{
                         'text-[var(--color-verified)]': toast.type === 'success',
                         'text-[var(--color-danger)]': toast.type === 'error',
                         'text-[var(--color-info)]': toast.type === 'info' || !toast.type,
                         'text-[var(--color-pending)]': toast.type === 'warning',
                     }"
                     x-text="(toast.type ?? 'info').toUpperCase()"
                ></div>
                <div class="text-sm text-[var(--color-ink)]" x-text="toast.message"></div>
            </div>
            <button
                type="button"
                @click="toasts = toasts.filter(t => t.id !== toast.id)"
                class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)] text-sm"
                aria-label="Tutup"
            >×</button>
        </div>
    </template>
</div>

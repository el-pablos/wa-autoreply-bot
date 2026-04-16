@props([
    'active' => null,
])

@php
    $sections = [
        [
            'label' => 'OPERASIONAL',
            'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'lucide-layout-dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard*'],
                ['key' => 'allowlist', 'label' => 'Allowlist', 'icon' => 'lucide-list-checks', 'route' => 'allowlist.index', 'pattern' => 'allowlist*'],
                ['key' => 'approved', 'label' => 'Approved Sessions', 'icon' => 'lucide-shield-check', 'route' => 'approved.index', 'pattern' => 'approved*'],
                ['key' => 'chat-live', 'label' => 'Chat Live', 'icon' => 'lucide-message-circle', 'route' => 'chat-live.index', 'pattern' => 'chat-live*', 'optional' => true],
                ['key' => 'logs', 'label' => 'Logs', 'icon' => 'lucide-scroll-text', 'route' => 'logs.index', 'pattern' => 'logs*'],
            ],
        ],
        [
            'label' => 'INTELLIGENCE',
            'items' => [
                ['key' => 'templates', 'label' => 'Reply Templates', 'icon' => 'lucide-file-text', 'route' => 'templates.index', 'pattern' => 'templates*', 'optional' => true],
                ['key' => 'knowledge', 'label' => 'Knowledge Base', 'icon' => 'lucide-book-open', 'route' => 'knowledge.index', 'pattern' => 'knowledge*', 'optional' => true],
                ['key' => 'ai', 'label' => 'AI Reply', 'icon' => 'lucide-sparkles', 'route' => 'ai.index', 'pattern' => 'ai*', 'optional' => true],
                ['key' => 'business-hours', 'label' => 'Business Hours', 'icon' => 'lucide-clock', 'route' => 'business-hours.index', 'pattern' => 'business-hours*', 'optional' => true],
            ],
        ],
        [
            'label' => 'INTEGRATION',
            'items' => [
                ['key' => 'webhooks', 'label' => 'Webhooks & API', 'icon' => 'lucide-webhook', 'route' => 'webhooks.index', 'pattern' => 'webhooks*', 'optional' => true],
                ['key' => 'escalation', 'label' => 'Escalation', 'icon' => 'lucide-megaphone', 'route' => 'escalation.index', 'pattern' => 'escalation*', 'optional' => true],
            ],
        ],
        [
            'label' => 'INSIGHT',
            'items' => [
                ['key' => 'analytics', 'label' => 'Analytics', 'icon' => 'lucide-bar-chart-3', 'route' => 'analytics.index', 'pattern' => 'analytics*', 'optional' => true],
                ['key' => 'audit', 'label' => 'Audit Trail', 'icon' => 'lucide-history', 'route' => 'audit.index', 'pattern' => 'audit*', 'optional' => true],
                ['key' => 'blacklist', 'label' => 'Blacklist', 'icon' => 'lucide-ban', 'route' => 'blacklist.index', 'pattern' => 'blacklist*', 'optional' => true],
            ],
        ],
        [
            'label' => 'SISTEM',
            'items' => [
                ['key' => 'backups', 'label' => 'Backups', 'icon' => 'lucide-database-backup', 'route' => 'backups.index', 'pattern' => 'backups*', 'optional' => true],
                ['key' => 'alerts', 'label' => 'Alerts', 'icon' => 'lucide-bell', 'route' => 'alerts.index', 'pattern' => 'alerts*', 'optional' => true],
                ['key' => 'users', 'label' => 'Users', 'icon' => 'lucide-users', 'route' => 'users.index', 'pattern' => 'users*', 'optional' => true],
                ['key' => 'settings', 'label' => 'Settings', 'icon' => 'lucide-settings', 'route' => 'settings.index', 'pattern' => 'settings*'],
            ],
        ],
    ];

    $hasRoute = function (?string $name): bool {
        return $name !== null && \Illuminate\Support\Facades\Route::has($name);
    };
@endphp

<aside {{ $attributes->merge(['class' => 'w-60 shrink-0 bg-[var(--color-card)] border-r border-[var(--color-ink)] h-screen sticky top-0 overflow-y-auto']) }}>
    <div class="px-5 py-5 border-b border-[var(--color-ink)]">
        <div class="eyebrow">OPERATOR'S CONSOLE</div>
        <div class="font-display font-black text-xl text-[var(--color-ink)] leading-tight">WA Bot</div>
        <div class="display-italic text-xs">Paper Editorial</div>
    </div>

    <nav class="py-3">
        @foreach ($sections as $section)
            <div class="px-5 pt-4 pb-2">
                <div class="eyebrow">{{ $section['label'] }}</div>
            </div>
            @foreach ($section['items'] as $item)
                @if (!($item['optional'] ?? false) || $hasRoute($item['route'] ?? null))
                    @php
                        $isActive = $active === $item['key']
                            || (isset($item['pattern']) && request()->routeIs($item['pattern']));
                        $href = $hasRoute($item['route'] ?? null) ? route($item['route']) : '#';
                    @endphp
                    <a
                        href="{{ $href }}"
                        @class([
                            'flex items-center gap-3 px-5 py-2.5 text-sm font-medium border-l-4 transition-colors min-tap',
                            'border-[var(--color-ink)] bg-[var(--color-paper)] text-[var(--color-ink)] font-semibold' => $isActive,
                            'border-transparent text-[var(--color-ink-muted)] hover:bg-[var(--color-paper)] hover:text-[var(--color-ink)]' => !$isActive,
                        ])
                    >
                        <span class="w-5 h-5 inline-flex items-center justify-center" aria-hidden="true">
                            <span class="block w-2 h-2 rounded-full {{ $isActive ? 'bg-[var(--color-ink)]' : 'bg-[var(--color-brass)]' }}"></span>
                        </span>
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        @endforeach
    </nav>

    <div class="px-5 py-4 mt-auto border-t border-[var(--color-rule)]">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full text-left text-xs font-semibold tracking-[0.15em] uppercase text-[var(--color-ink-muted)] hover:text-[var(--color-danger)] transition-colors">
                Keluar
            </button>
        </form>
    </div>
</aside>

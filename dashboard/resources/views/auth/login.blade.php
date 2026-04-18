<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#f6f3ec">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — Operator's Console</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&family=Playfair+Display:ital,wght@0,400;0,700;0,800;0,900;1,400;1,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[var(--color-paper)] text-[var(--color-ink)] font-body antialiased min-h-screen bg-grain">

<main class="min-h-screen grid md:grid-cols-2">
    {{-- Editorial illustration column (desktop only) --}}
    <aside class="hidden md:flex bg-[var(--color-ink)] text-[var(--color-paper)] flex-col justify-between p-12 relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-96 h-96 rounded-full bg-[var(--color-brass)]/10 blur-3xl"></div>

        <div class="relative">
            <div class="text-[var(--color-brass)] eyebrow">PAPER EDITORIAL · MMXXVI</div>
            <div class="font-display font-black text-5xl mt-4 leading-none">
                Operator's<br>Console.
            </div>
            <div class="font-display italic text-xl text-[var(--color-brass)] mt-3">Untuk yang menjaga channel WA tetap hidup.</div>
        </div>

        <div class="relative space-y-3 max-w-md">
            <div class="border-t border-[var(--color-brass)] pt-4">
                <div class="eyebrow text-[var(--color-brass)]">EDITORIAL</div>
                <p class="text-[var(--color-paper)]/80 mt-2 text-sm leading-relaxed">
                    "Setiap percakapan yang lewat butuh penjaga. Dashboard ini memastikan tidak ada yang terlewat — sambil
                    membiarkan kamu tidur tenang."
                </p>
            </div>
            <div class="font-mono text-xs text-[var(--color-brass)]">
                v.{{ config('app.version', '2.0.0') }} · cihuy.deploy
            </div>
        </div>
    </aside>

    {{-- Form column --}}
    <section class="flex items-center justify-center p-6 md:p-12">
        <div class="w-full max-w-sm">
            <div class="md:hidden mb-8 text-center">
                <div class="eyebrow">PAPER EDITORIAL</div>
                <div class="font-display font-black text-3xl mt-1 leading-none">Operator's Console</div>
                <div class="display-italic text-sm mt-1">WhatsApp auto-reply bot</div>
            </div>

            <x-ui.card editorial padding="lg">
                <div class="text-center mb-6">
                    <div class="eyebrow">SIGN IN</div>
                    <h1 class="font-display font-extrabold text-3xl text-[var(--color-ink)] mt-1">Masuk</h1>
                    <p class="display-italic text-sm mt-1">Masukkan email dan password operator untuk lanjut</p>
                </div>

                <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                    @csrf

                    <x-ui.input
                        name="email"
                        type="email"
                        label="Email"
                        placeholder="owner@local.test"
                        :value="old('email')"
                        :error="$errors->first('email')"
                        required
                        autofocus
                    />

                    <x-ui.input
                        name="password"
                        type="password"
                        label="Password"
                        placeholder="••••••••"
                        :error="$errors->first('password')"
                        required
                    />

                    <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
                        <input type="checkbox" name="remember" value="1" class="rounded border-[var(--color-rule)]">
                        <span>Ingat sesi login ini</span>
                    </label>

                    <x-ui.button type="submit" variant="primary" size="lg" block>
                        Masuk →
                    </x-ui.button>
                </form>

                <div class="mt-6 pt-4 border-t border-[var(--color-rule)] text-center">
                    <div class="eyebrow">PROTECTED BY</div>
                    <div class="text-xs font-mono text-[var(--color-ink-muted)] mt-1">laravel.auth · CSRF · session</div>
                </div>
            </x-ui.card>
        </div>
    </section>
</main>

</body>
</html>

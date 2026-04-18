<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Challenge - WA Bot</title>
    <meta name="theme-color" content="#f6f3ec">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&family=Playfair+Display:ital,wght@0,700;0,800;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-paper)] text-[var(--color-ink)] font-body antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-lg">
            <x-ui.card editorial>
                <div class="text-center mb-6">
                    <div class="eyebrow">SECURITY CHECK</div>
                    <h1 class="font-display font-extrabold text-3xl text-[var(--color-ink)] mt-1">Verifikasi 2FA</h1>
                    <p class="display-italic text-sm mt-1">Masukkan OTP dari authenticator atau backup code untuk akun {{ $pendingEmail }}</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-[var(--color-danger)] bg-red-50 px-3 py-2 text-sm text-[var(--color-danger)]">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
                    @csrf

                    <x-ui.input
                        name="code"
                        type="text"
                        label="Kode OTP / Backup Code"
                        placeholder="123456"
                        :value="old('code')"
                        :error="$errors->first('code')"
                        required
                        autofocus
                    />

                    <x-ui.button type="submit" variant="primary" block>
                        Verifikasi & Masuk
                    </x-ui.button>
                </form>

                <div class="mt-5 border-t border-[var(--color-rule)] pt-4 text-center">
                    <p class="text-xs text-[var(--color-ink-muted)]">Tidak punya akses ke authenticator? Gunakan backup code yang disimpan saat setup 2FA.</p>
                </div>
            </x-ui.card>
        </div>
    </div>
</body>
</html>

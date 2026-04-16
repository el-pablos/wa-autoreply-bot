<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#f6f3ec">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="WA Bot">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>@yield('title', 'WA Bot Operator') — Paper Editorial</title>

    {{-- PWA manifest (file akan dibuat di Phase 9 E2; aman jika 404 sementara) --}}
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' fill='%23f6f3ec'/%3E%3Ctext x='50%25' y='62%25' font-family='Georgia,serif' font-weight='900' font-size='20' text-anchor='middle' fill='%231a1a1a'%3EW%3C/text%3E%3C/svg%3E">

    {{-- Google Fonts: Playfair Display + Inter + JetBrains Mono --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&family=Playfair+Display:ital,wght@0,400;0,700;0,800;0,900;1,400;1,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="bg-[var(--color-paper)] text-[var(--color-ink)] font-body antialiased">

<div class="min-h-screen md:flex">
    {{-- Sidebar (desktop only) --}}
    <x-shell.sidebar class="hidden md:block" :active="$navActive ?? null" />

    {{-- Main column --}}
    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-shell.topbar
            :title="$pageTitle ?? 'Dashboard'"
            :eyebrow="$pageEyebrow ?? null"
            :status="$botStatus ?? null"
            :statusLabel="$botStatusLabel ?? null"
        />


        <main class="flex-1 px-4 md:px-6 lg:px-8 pb-28 md:pb-10 pt-4 md:pt-6">
            @yield('content')
        </main>
    </div>
</div>

{{-- Floating bottom-pill nav (mobile only) --}}
<x-shell.floating-nav :active="$navActive ?? null" />

{{-- Toast stack --}}
<x-shell.toast-stack />

@stack('scripts')
</body>
</html>

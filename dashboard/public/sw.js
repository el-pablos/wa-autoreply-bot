/**
 * Service Worker — WA Auto-Reply Bot Operator Console
 * Cache version: paper-editorial-v1 (naikan saat deploy)
 *
 * Strategies:
 *   - Pre-cache  : /offline.html, /manifest.json, icons
 *   - Network-first : halaman HTML (fallback offline.html)
 *   - Cache-first   : /build/* (Vite assets), icons, manifest
 *   - Skip cache    : /api/*, /chat-live, /logout, /login
 */

const CACHE_VERSION = "paper-editorial-v1";

const PRECACHE_URLS = [
    "/offline.html",
    "/manifest.json",
    "/icons/icon-192.png",
    "/icons/icon-512.png",
    "/icons/icon-maskable-512.png",
];

// URL patterns yang tidak boleh di-cache sama sekali
const SKIP_CACHE_PATTERNS = [
    /^\/api\//,
    /^\/chat-live/,
    /^\/logout/,
    /^\/login/,
];

// URL patterns yang pakai cache-first (static assets)
const CACHE_FIRST_PATTERNS = [/^\/build\//, /^\/icons\//, /^\/manifest\.json/];

// ─── Install ──────────────────────────────────────────────────────────────────

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => {
            return cache.addAll(PRECACHE_URLS);
        }),
    );
    // Langsung aktif tanpa nunggu tab lama tutup
    self.skipWaiting();
});

// ─── Activate ─────────────────────────────────────────────────────────────────

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_VERSION)
                    .map((name) => caches.delete(name)),
            );
        }),
    );
    // Klaim semua client yang ada tanpa perlu reload
    self.clients.claim();
});

// ─── Fetch ────────────────────────────────────────────────────────────────────

self.addEventListener("fetch", (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Hanya handle same-origin request
    if (url.origin !== self.location.origin) return;

    // Skip cache untuk pattern tertentu — langsung ke network
    if (SKIP_CACHE_PATTERNS.some((pattern) => pattern.test(url.pathname))) {
        return; // browser handle sendiri
    }

    // Cache-first untuk Vite build assets, icons, manifest
    if (CACHE_FIRST_PATTERNS.some((pattern) => pattern.test(url.pathname))) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Network-first untuk semua HTML/halaman (termasuk navigasi)
    if (
        request.mode === "navigate" ||
        request.headers.get("accept")?.includes("text/html")
    ) {
        event.respondWith(networkFirstWithOfflineFallback(request));
        return;
    }

    // Default: network-first untuk resource lainnya
    event.respondWith(networkFirst(request));
});

// ─── Message ──────────────────────────────────────────────────────────────────

self.addEventListener("message", (event) => {
    if (event.data === "SKIP_WAITING") {
        self.skipWaiting();
    }
});

// ─── Strategy: Cache-First ────────────────────────────────────────────────────

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch {
        // Kalau resource ini ada di pre-cache, return dari sana
        const fallback = await caches.match(request);
        if (fallback) return fallback;
        return new Response("Resource tidak tersedia offline.", {
            status: 503,
            headers: { "Content-Type": "text/plain; charset=utf-8" },
        });
    }
}

// ─── Strategy: Network-First ─────────────────────────────────────────────────

async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;
        return new Response("Resource tidak tersedia offline.", {
            status: 503,
            headers: { "Content-Type": "text/plain; charset=utf-8" },
        });
    }
}

// ─── Strategy: Network-First + Offline Fallback (untuk HTML) ─────────────────

async function networkFirstWithOfflineFallback(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch {
        // Coba dari cache dulu
        const cached = await caches.match(request);
        if (cached) return cached;

        // Fallback ke offline.html
        const offlinePage = await caches.match("/offline.html");
        if (offlinePage) return offlinePage;

        return new Response(
            "<!DOCTYPE html><html><body><h1>Kamu offline.</h1></body></html>",
            {
                status: 503,
                headers: { "Content-Type": "text/html; charset=utf-8" },
            },
        );
    }
}

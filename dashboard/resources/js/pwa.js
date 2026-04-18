/**
 * PWA bootstrap — service worker registration + install prompt
 *
 * Install prompt muncul setelah 3 kunjungan dan hanya sekali
 * (sampai user dismiss atau install).
 */

// ── Service Worker Registration ───────────────────────────────────────────────

if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker
            .register("/sw.js", { scope: "/" })
            .then((registration) => {
                // Cek update saat ada SW baru yang waiting
                registration.addEventListener("updatefound", () => {
                    const newWorker = registration.installing;
                    if (!newWorker) return;

                    newWorker.addEventListener("statechange", () => {
                        if (
                            newWorker.state === "installed" &&
                            navigator.serviceWorker.controller
                        ) {
                            // SW baru siap — kirim event agar UI bisa notif user
                            window.dispatchEvent(
                                new CustomEvent("pwa-update-available"),
                            );
                        }
                    });
                });
            })
            .catch(() => {
                // SW registration gagal — silent fail, app tetap jalan normal
            });
    });

    // Reload halaman setelah SW baru aktif (skipWaiting sudah dipanggil)
    let refreshing = false;
    navigator.serviceWorker.addEventListener("controllerchange", () => {
        if (!refreshing) {
            refreshing = true;
            window.location.reload();
        }
    });
}

// ── Install Prompt ────────────────────────────────────────────────────────────

let deferredPrompt = null;

window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();
    deferredPrompt = e;

    const count =
        parseInt(localStorage.getItem("wa_visit_count") ?? "0", 10) + 1;
    localStorage.setItem("wa_visit_count", String(count));

    if (count >= 3 && !localStorage.getItem("wa_install_dismissed")) {
        window.dispatchEvent(
            new CustomEvent("pwa-install-available", {
                detail: {
                    prompt: () => deferredPrompt && deferredPrompt.prompt(),
                },
            }),
        );
    }
});

window.addEventListener("appinstalled", () => {
    localStorage.setItem("wa_install_dismissed", "1");
    deferredPrompt = null;
});

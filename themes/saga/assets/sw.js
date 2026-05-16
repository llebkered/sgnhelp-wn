const CACHE_VERSION = 'v1';
const STATIC_CACHE  = `saga-static-${CACHE_VERSION}`;
const IMAGE_CACHE   = `saga-images-${CACHE_VERSION}`;
const ALL_CACHES    = [STATIC_CACHE, IMAGE_CACHE];

const OFFLINE_URL = '/offline';

const PRECACHE_URLS = [
    '/',
    OFFLINE_URL,
    '/themes/saga/assets/css/style.min.css',
    '/themes/saga/assets/js/app.js',
    '/themes/saga/assets/images/logo.svg',
    '/themes/saga/assets/manifest.webmanifest',
];

// ── Install: precache essential assets ───────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// ── Activate: remove old caches ───────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(k => !ALL_CACHES.includes(k)).map(k => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

// ── Fetch ─────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Navigation: network-first, fall back to offline page
    if (event.request.mode === 'navigate') {
        event.respondWith(networkFirst(event.request));
        return;
    }

    // Images: stale-while-revalidate with dedicated cache
    const isImage = /\.(png|jpe?g|gif|webp|svg|avif|ico)$/i.test(url.pathname);
    if (isImage) {
        event.respondWith(staleWhileRevalidate(event.request, IMAGE_CACHE));
        return;
    }

    // Static theme assets: cache-first
    if (url.pathname.startsWith('/themes/saga/assets/')) {
        event.respondWith(cacheFirst(event.request, STATIC_CACHE));
        return;
    }
});

// ── Strategies ────────────────────────────────────────────────────────────────
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        return response;
    } catch {
        const cached = await caches.match(request);
        return cached || caches.match(OFFLINE_URL);
    }
}

async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (response && response.status === 200) {
        const cache = await caches.open(cacheName);
        cache.put(request, response.clone());
    }
    return response;
}

async function staleWhileRevalidate(request, cacheName) {
    const cache  = await caches.open(cacheName);
    const cached = await cache.match(request);
    const fetchPromise = fetch(request).then(response => {
        if (response && response.status === 200) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => cached);
    return cached || fetchPromise;
}

/**
 * StayIn — Service Worker
 * Provides offline fallback for HTML navigation requests.
 * Cache-first for static assets, network-first for HTML (with offline fallback).
 */
const CACHE_NAME = 'stayin-v4-local-tanzania-images';
const ASSETS = [
  '/',
  '/offline.html',
  '/assets/css/tokens.css',
  '/assets/css/base.css',
  '/assets/css/layout.css',
  '/assets/css/components.css',
  '/assets/css/utilities.css',
  '/assets/css/pages.css',
  '/assets/css/dashboard.css',
  '/assets/css/system.css',
  '/assets/js/app.js',
  '/assets/images/logo.png',
  '/assets/images/placeholder-stay.svg',
  '/assets/images/placeholder-hero.svg',
  '/assets/images/og-default.svg',
  '/assets/images/stays/tanzania-hero.svg',
  '/assets/images/stays/zanzibar-villa.svg',
  '/assets/images/stays/serengeti-safari-lodge.svg',
  '/assets/images/stays/kilimanjaro-cabin.svg',
  '/assets/images/stays/dar-es-salaam-apartment.svg',
  '/assets/images/stays/arusha-garden-hotel.svg',
  '/assets/images/stays/lake-victoria-guesthouse.svg',
  '/favicon.ico',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => Promise.allSettled(ASSETS.map((asset) => cache.add(asset))))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.map((key) => key !== CACHE_NAME && caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  if (url.pathname.startsWith('/api/')) {
    // API requests: network-first.
    event.respondWith(fetch(event.request).catch(() => new Response('{"error":"offline"}', { status: 503, headers: { 'Content-Type': 'application/json' } })));
    return;
  }
  if (event.request.destination === 'document') {
    // HTML: network-first with offline fallback.
    event.respondWith(
      fetch(event.request).catch(() => caches.match('/offline.html').then((fallback) => fallback || caches.match('/')))
    );
    return;
  }
  // Other assets: cache-first.
  event.respondWith(
    caches.match(event.request).then((cached) => cached || fetch(event.request))
  );
});

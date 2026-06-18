const CACHE_NAME = 'nexus-fiber-cache-v1';
const urlsToCache = [
  './',
  './manifest.json',
  './icon.png',
  'https://cdn.jsdelivr.net/npm/geist@1.3.0/dist/fonts/geist.css',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
  'https://unpkg.com/leaflet-kmz@latest/dist/leaflet-kmz.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  // Estratégia Stale-while-revalidate para recursos em cache
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      })
  );
});

const CACHE_NAME = "field-app-v2";
const ASSETS = [
    "/field-app",
    "/field-app/styles.css",
    "/field-app/app.js",
    "/field-app/manifest.webmanifest",
];

self.addEventListener("install", (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)));
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        )
    );
});

self.addEventListener("fetch", (event) => {
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request).then((cached) => cached || caches.match("/field-app")))
    );
});

const CACHE_NAME = 'Finance-Gestion-pro-v1.0.0'; // Change le v1 en v2 pour déclencher la mise à jour
const OFFLINE_URL = 'offline.html';

self.addEventListener('install', (event) => {
    self.skipWaiting(); // Force le nouveau SW à s'activer immédiatement
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll([OFFLINE_URL]);
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        // 1. Nettoyage des vieux caches
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // 2. AFFICHAGE DU BADGE
            // Dès que le site est mis à jour, on affiche "1" sur l'icône
            if ('setAppBadge' in navigator) {
                navigator.setAppBadge(1).catch((error) => {
                    console.error("Erreur badge:", error);
                });
            }
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                return response;
            })
            .catch(() => {
                return caches.match(event.request).then((res) => {
                    return res || caches.match(OFFLINE_URL);
                });
            })
    );
});
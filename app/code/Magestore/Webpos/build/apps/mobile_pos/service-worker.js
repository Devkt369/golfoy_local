var CACHE_STATIC_NAME = 'static-v1675670505177';
var CACHE_DYNAMIC_NAME = 'dynamic-v1675670505177';
var STATIC_FILES = [];

/* Allow cache static folder (React JS Building) and some not regular change */
var ALLOW_CACHES = [
    'mobile_pos/static',
    'mobile_pos/css',
    'fonts.googleapis.com'
];

self.addEventListener('install', function (event) {
    console.log('[Service Worker] Installing Service Worker ...', event);
    event.waitUntil(
        caches.open(CACHE_STATIC_NAME)
            .then(function (cache) {
                console.log('[Service Worker] Precaching App Shell');
                cache.addAll(STATIC_FILES);
            })
    )
});

self.addEventListener('activate', function (event) {
    console.log('[Service Worker] Activating Service Worker ....', event);
    event.waitUntil(
        caches.keys()
            .then(function (keyList) {
                return Promise.all(keyList.map(function (key) {
                    if (key !== CACHE_STATIC_NAME && key !== CACHE_DYNAMIC_NAME) {
                        console.log('[Service Worker] Removing old cache.', key);
                        return caches.delete(key);
                    }
                }));
            })
    );
    return self.clients.claim();
});

self.addEventListener('fetch', function(event) {
    // check if request is made by chrome extensions or web page
    // if request is made for web page url must contains http.
    if (!(event.request.url.indexOf('http') === 0)) return;
    // skip the request. if request is not made with http protocol

    let canCache = 0;
    ALLOW_CACHES.map(
        (value) => {
            if (event.request.url.indexOf(value) > -1) {
                canCache = 1;
            }
            return value;
        }
    )

    if (!canCache) {
        return;
    }
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                } else {
                    return fetch(event.request)
                        .then(function(res) {
                            return caches.open(CACHE_DYNAMIC_NAME)
                                .then(function(cache) {
                                    cache.put(event.request.url, res.clone());
                                    return res;
                                })
                        })
                        .catch(function(err) {

                        });
                }
            })
    );
});

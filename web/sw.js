// Service Worker - 缓存字体文件
const CACHE_NAME = 'minecraft-fonts-v1';
const FONT_FILES = [
    'Minecraft_English.otf',
    'Minecraft_Chinese.ttf'
];

// 安装时缓存字体文件
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] 缓存字体文件...');
            return cache.addAll(FONT_FILES).catch((err) => {
                console.error('[SW] 缓存字体失败:', err);
            });
        }).then(() => {
            return self.skipWaiting();
        })
    );
});

// 激活时清理旧缓存
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => {
                        console.log('[SW] 删除旧缓存:', name);
                        return caches.delete(name);
                    })
            );
        }).then(() => {
            return self.clients.claim();
        })
    );
});

// 拦截字体请求，优先从缓存获取
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // 只处理字体文件请求
    if (request.destination === 'font' || 
        url.pathname.endsWith('.otf') || 
        url.pathname.endsWith('.ttf') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.woff2')) {
        
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                // 缓存命中，直接返回
                if (cachedResponse) {
                    console.log('[SW] 从缓存返回字体:', url.pathname);
                    return cachedResponse;
                }

                // 缓存未命中，从网络获取并缓存
                return fetch(request).then((networkResponse) => {
                    if (!networkResponse || networkResponse.status !== 200) {
                        return networkResponse;
                    }

                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                        console.log('[SW] 缓存新字体:', url.pathname);
                    });

                    return networkResponse;
                }).catch((error) => {
                    console.error('[SW] 获取字体失败:', error);
                    // 可以返回一个默认字体或错误响应
                    throw error;
                });
            })
        );
    }
});

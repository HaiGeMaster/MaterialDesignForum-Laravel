// PWA Service Worker
const CACHE_NAME = 'mdf-cache-v1';

// 安装事件 - 预缓存关键资源
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

// 激活事件 - 清理旧缓存
self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

// 网络请求拦截（必须有，否则 Chrome 不会触发安装提示）
self.addEventListener('fetch', (event) => {
  event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
});

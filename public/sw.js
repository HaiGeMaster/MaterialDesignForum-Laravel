// PWA Service Worker
const CACHE_NAME = 'mdf-offline-v1';

// 需要预缓存的资源（离线页面依赖，必须在 install 阶段缓存好）
const PRECACHE_URLS = [
  '/offline.html',
  '/icon-512.png',
];

// 安装事件 - 预缓存关键资源
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS))
  );
  self.skipWaiting();
});

// 激活事件 - 清理旧缓存
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((key) => key !== CACHE_NAME)
          .map((key) => caches.delete(key))
      )
    )
  );
  event.waitUntil(self.clients.claim());
});

// 网络请求拦截
// 策略：
//   - 导航请求（页面跳转/刷新）：network-first，失败时回退到缓存的离线页
//   - 其他请求（API、静态资源等）：直接放行，不做离线缓存
self.addEventListener('fetch', (event) => {
  const { request } = event;

  // 只处理 GET 请求
  if (request.method !== 'GET') return;

  // 仅对导航请求做离线回退
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // 可选：把成功的页面响应缓存起来，便于后续离线访问
          // 这里不缓存，保持 network-first 行为
          return response;
        })
        .catch(() => {
          // 网络失败时，返回预缓存的离线页
          return caches.match('/offline.html');
        })
    );
    return;
  }

  // 非导航请求：正常发起网络请求（不拦截，避免影响 API 等）
  // 若需要静态资源离线缓存，可在此处扩展 cache-first 策略
});

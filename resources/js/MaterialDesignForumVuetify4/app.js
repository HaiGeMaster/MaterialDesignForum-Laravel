import { createApp } from 'vue'
import { createPinia } from 'pinia'
// import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { i18n } from './i18n'
import { loadThemeConfig } from './themeConfig'
import { initClarity } from './clarity'

const app = createApp(App)

const pinia = createPinia()
// pinia.use(piniaPluginPersistedstate)

app.use(pinia)
app.use(router)
app.use(vuetify)
app.use(i18n)

// 注册 Service Worker（PWA 安装前提）
// 仅在生产环境注册，避免开发模式下 SW 缓存干扰热更新
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js')
            .then((registration) => {
                console.log('[PWA] Service Worker registered, scope:', registration.scope)
            })
            .catch((error) => {
                console.error('[PWA] Service Worker registration failed:', error)
            })
    })
}

initClarity()

loadThemeConfig(vuetify).finally(() => app.mount('#app'))

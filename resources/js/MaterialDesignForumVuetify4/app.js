import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { i18n } from './i18n'
import { loadThemeConfig } from './themeConfig'
import { initClarity } from './clarity'
// import { WatermarkPlugin } from '@watermarkify/vue'
// import { watermark } from 'watermark-dom'

// watermark({ content: '管理员·张三', watermark: '#admin-watermark' })
const app = createApp(App)

// app.use(WatermarkPlugin)

app.use(createPinia())
app.use(router)
app.use(vuetify)
app.use(i18n)

initClarity()

loadThemeConfig(vuetify).finally(() => app.mount('#app'))

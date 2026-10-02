import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Admin from './admin.vue'
import adminRouter from './router/admin.js'
import vuetify from './plugins/vuetify'
import { i18n } from './i18n'
import '../../css/app.css'
import { loadThemeConfig } from './themeConfig'
import { initClarity } from './clarity'

const app = createApp(Admin)

app.use(createPinia())
app.use(adminRouter)
app.use(vuetify)
app.use(i18n)

initClarity()

loadThemeConfig(vuetify).finally(() => app.mount('#admin-app'))

import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import vuetify from './plugins/vuetify'
import InstallerWizard from './components/InstallerWizard.vue'
import deDE from './locales/de_DE.json'
import enGB from './locales/en_GB.json'
import enUS from './locales/en_US.json'
import frFR from './locales/fr_FR.json'
import jaJP from './locales/ja_JP.json'
import koKR from './locales/ko_KR.json'
import ruRU from './locales/ru_RU.json'
import zhCN from './locales/zh_CN.json'
import zhTW from './locales/zh_TW.json'

const locale = localStorage.getItem('installer_locale') || window.__INSTALLER_LOCALE__ || 'zh_CN'

const i18n = createI18n({
  legacy: false,
  locale: locale,
  fallbackLocale: 'zh_CN',
  messages: {
    de_DE: deDE,
    en_GB: enGB,
    en_US: enUS,
    fr_FR: frFR,
    ja_JP: jaJP,
    ko_KR: koKR,
    ru_RU: ruRU,
    zh_CN: zhCN,
    zh_TW: zhTW,
  },
})

const app = createApp(InstallerWizard, {
  version: window.__INSTALLER_VERSION__ || '1.0.0',
  locale: locale,
  phpVersion: window.__PHP_VERSION__ || '',
  envCheckData: window.__ENV_CHECK__ || {},
})

app.use(vuetify)
app.use(i18n)
app.mount('#installer')

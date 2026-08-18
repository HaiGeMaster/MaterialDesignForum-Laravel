import { createI18n } from 'vue-i18n'
import zh from '../locales/zh.json'
import en from '../locales/en.json'

const savedLocale = localStorage.getItem('locale')
const browserLocale = (navigator.language || '').toLowerCase()
const locale = savedLocale || (browserLocale.startsWith('zh') ? 'zh' : 'en')

export const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale,
  fallbackLocale: 'zh',
  messages: { zh, en },
})

/** 将前端语言同步到 <html lang>，并写入 cookie 供后端渲染首帧使用 */
export function syncHtmlLang(lang) {
  document.documentElement.lang = lang === 'zh' ? 'zh-CN' : lang
  document.cookie = `locale=${lang}; path=/; max-age=31536000; samesite=lax`
}

// 初始化同步一次，保证 JS 加载后 <html lang> 与 i18n 一致
syncHtmlLang(locale)

/** 切换语言并持久化 */
export function setLocale(lang) {
  i18n.global.locale.value = lang
  localStorage.setItem('locale', lang)
  syncHtmlLang(lang)
}

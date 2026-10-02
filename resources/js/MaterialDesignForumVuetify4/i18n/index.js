import { createI18n } from 'vue-i18n'

// "D:\Administrator\Documents\Vue\MDForumLaravel\resources\js\locales\de-DE.json"
// "D:\Administrator\Documents\Vue\MDForumLaravel\resources\js\locales\en-GB.json"
// "D:\Administrator\Documents\Vue\MDForumLaravel\resources\js\locales\en-US.json"
// "D:\Administrator\Documents\Vue\MDForumLaravel\resources\js\locales\fr-FR.json"
// "D:\Administrator\Documents\Vue\MDForumLaravel\resources\js\locales\ja-JP.json"
// "D:\Administrator\Documents\Vue\MDForumLaravel\resources\js\locales\ko-KR.json"
// "D:\Administrator\Documents\Vue\MDForumLaravel\resources\js\locales\ru-RU.json"
// "D:\Administrator\Documents\Vue\MDForumLaravel\resources\js\locales\zh-CN.json"
// "D:\Administrator\Documents\Vue\MDForumLaravel\resources\js\locales\zh-TW.json"

import deDE from '../locales/de-DE.json'
import enGB from '../locales/en-GB.json'
import enUS from '../locales/en-US.json'
import frFR from '../locales/fr-FR.json'
import jaJP from '../locales/ja-JP.json'
import koKR from '../locales/ko-KR.json'
import ruRU from '../locales/ru-RU.json'
import zhCN from '../locales/zh-CN.json'
import zhTW from '../locales/zh-TW.json'

const savedLocale = localStorage.getItem('locale')
const browserLocale = (navigator.language || '').toLowerCase()
const locale = savedLocale || (browserLocale.startsWith('zh-CN') ? 'zh-CN' : 'en-US')

export const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale,
  fallbackLocale: 'en-US',
  messages: { 
    'de-DE': deDE,
    'en-GB': enGB,
    'en-US': enUS,
    'fr-FR': frFR,
    'ja-JP': jaJP,
    'ko-KR': koKR,
    'ru-RU': ruRU,
    'zh-CN': zhCN,
    'zh-TW': zhTW,
   },
})

/** 将前端语言同步到 <html lang>，并写入 cookie 供后端渲染首帧使用 */
export function syncHtmlLang(lang) {
  document.documentElement.lang = lang;// === 'zh' ? 'zh-CN' : lang
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

/** 使用指定的语言包 */
export function tIn(locale, key, named) {
  return i18n.global.getLocaleMessage(locale)[key] || key
}

// 获取基本语言包信息简介：baseLanguagePackInfo 生成列表
// export function getBaseLanguagePackList() {
//   const messages = i18n.global.messages
//   const list = []

//   for (const lang in messages) {
//     const info = messages[lang]?.baseLanguagePackInfo

//     if (info) {
//       list.push({
//         code: lang,                       // 语言 code，如 zh-CN / en-US
//         langname: info.langname,    // 语言名称
//         // ...info                     // 其他字段（可选）
//       })
//     }
//   }

//   console.log('getBaseLanguagePackList', list)

//   return list
// }

import { ref } from 'vue'
import http from './api'

/** 站点名称（由 /theme 接口同步，默认值兜底） */
export const siteName = ref('')
/** ICP 备案号（未设置时为空字符串） */
export const siteIcpBeian = ref('')
/** 公网备案号（未设置时为空字符串） */
export const siteGonganBeian = ref('')

/** 管理员配置后可用的主题名称列表（前台主题选择器据此渲染） */
export const availableThemes = ref([])
/** 管理员设置的默认主题 */
export const defaultTheme = ref('system')

/**
 * 将 Vuetify 当前主题的 surface 色同步到 PWA 顶部窗口栏（theme-color meta）。
 * Vuetify 3 的 --v-theme-surface 存储的是 RGB 三元组（如 "33, 33, 33"），
 * 需包装为 rgb(r, g, b) 后写入 meta 标签。
 */
export function syncThemeColor() {
  try {
    // Vuetify 主题变量默认挂在 :root（documentElement），
    // 部分配置下可能在 .v-application 上，依次尝试
    const sources = [
      document.documentElement,
      document.body,
      document.querySelector('.v-application'),
    ]
    let raw = ''
    for (const el of sources) {
      if (!el) continue
      raw = getComputedStyle(el).getPropertyValue('--v-theme-surface').trim()
      if (raw) break
    }

    if (!raw) {
    //   console.warn('[PWA] --v-theme-surface not found')
      return
    }

    // raw 可能是 "33, 33, 33"，包装为 rgb(...)
    const color = raw.startsWith('rgb') ? raw : `rgb(${raw})`

    let meta = document.querySelector('meta[name="theme-color"]')
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('name', 'theme-color')
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', color)
    // console.log('[PWA] theme-color synced:', color)
  } catch (e) {
    // console.error('[PWA] syncThemeColor failed:', e)
  }
}

/**
 * 将用户选择的主题名解析为实际 Vuetify 主题名。
 * 'system' 时根据操作系统偏好返回 'light' 或 'dark'。
 */
export function resolveTheme(themeName) {
  if (themeName === 'system') {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  return themeName
}

/**
 * 将后端返回的主题配置应用到 Vuetify 实例。
 */
export function applyThemeConfig(vuetify, config) {
  if (!config) return

  const themeApi = vuetify.theme || vuetify

  // 1. 同步可用主题列表 & 默认主题
  if (Array.isArray(config.available_themes) && config.available_themes.length) {
    availableThemes.value = config.available_themes
  }
  if (config.default_theme) {
    defaultTheme.value = config.default_theme
  }

  // 2. 应用主题
  const saved = localStorage.getItem('vuetify-theme')
  const target = saved || config.default_theme || 'system'
  const allThemes = themeApi.themes.value

  if (target === 'system') {
    themeApi.change(resolveTheme('system'))
  } else if (allThemes[target]) {
    themeApi.change(target)
  } else {
    // 回退到第一个可用主题或 light
    const fallback = availableThemes.value.find(n => allThemes[n]) || 'light'
    themeApi.change(fallback)
  }

  // 主题应用后同步 PWA 顶部窗口栏颜色
  requestAnimationFrame(syncThemeColor)
}

export async function loadThemeConfig(vuetify) {
  try {
    const { data } = await http.get('/theme')
    applyThemeConfig(vuetify, data)
    if (data.site_name) siteName.value = data.site_name
    siteIcpBeian.value = data.site_icp_beian || ''
    siteGonganBeian.value = data.site_gongan_beian || ''
    return data
  } catch (error) {
    return null
  }
}

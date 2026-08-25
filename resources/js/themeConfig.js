import { ref } from 'vue'
import http from './api'

/** 站点名称（由 /theme 接口同步，默认值兜底） */
export const siteName = ref('MDForum')
/** ICP 备案号（未设置时为空字符串） */
export const siteIcpBeian = ref('')
/** 公网备案号（未设置时为空字符串） */
export const siteGonganBeian = ref('')

/** 管理员配置后可用的主题名称列表（前台主题选择器据此渲染） */
export const availableThemes = ref([])
/** 管理员设置的默认主题 */
export const defaultTheme = ref('system')

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
 * 兼容旧版（仅 light/dark 颜色）和新版（含 available_themes / custom_themes）。
 */
export function applyThemeConfig(vuetify, config) {
  if (!config) return

  const themeApi = vuetify.theme || vuetify
  const themes = themeApi.themes.value

  // 1. 注册自定义主题（动态创建 Vuetify theme）
  if (config.custom_themes?.length) {
    applyCustomThemes(themeApi, config.custom_themes)
  }

  // 2. 覆盖内置 light/dark 的主色（兼容旧逻辑）
  for (const mode of ['light', 'dark']) {
    const colors = config.colors?.[mode]
    if (!colors || !themes[mode]?.colors) continue
    for (const key of ['primary', 'secondary', 'accent']) {
      if (colors[key]) {
        themes[mode].colors[key] = colors[key]
      }
    }
  }

  // 3. 同步可用主题列表 & 默认主题
  if (Array.isArray(config.available_themes) && config.available_themes.length) {
    availableThemes.value = config.available_themes
  }
  if (config.default_theme) {
    defaultTheme.value = config.default_theme
  }

  // 4. 应用主题
  const saved = localStorage.getItem('mdforum-theme')
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
}

/**
 * 将自定义主题动态注册到 Vuetify 运行时。
 * 每个 custom theme 结构：{ name, dark, colors: { primary, secondary, accent } }
 */
function applyCustomThemes(themeApi, customThemes) {
  const themes = themeApi.themes.value
  for (const ct of customThemes) {
    if (!ct.name || themes[ct.name]) continue
    // 以 light 或 dark 为基底，再覆盖自定义颜色
    const base = ct.dark ? themes.dark : themes.light
    const newTheme = {
      dark: !!ct.dark,
      colors: {
        ...(base?.colors || {}),
        primary: ct.colors?.primary || (ct.dark ? '#2196F3' : '#1867C0'),
        secondary: ct.colors?.secondary || (ct.dark ? '#54B6B2' : '#48A9A6'),
        accent: ct.colors?.accent || (ct.dark ? '#FB8C00' : '#FB8C00'),
      },
      variables: base?.variables || {},
    }
    themes[ct.name] = newTheme
  }
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

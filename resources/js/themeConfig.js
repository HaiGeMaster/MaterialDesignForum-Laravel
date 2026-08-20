import { ref } from 'vue'
import http from './api'

/** 站点名称（由 /theme 接口同步，默认值兜底） */
export const siteName = ref('MDForum')
/** ICP 备案号（未设置时为空字符串） */
export const siteIcpBeian = ref('')
/** 公网备案号（未设置时为空字符串） */
export const siteGonganBeian = ref('')

export function applyThemeConfig(vuetify, config) {
  if (!config) return

  const themeApi = vuetify.theme || vuetify
  const themes = themeApi.themes.value
  for (const mode of ['light', 'dark']) {
    const colors = config.colors?.[mode]
    if (!colors || !themes[mode]?.colors) continue

    for (const key of ['primary', 'secondary', 'accent']) {
      if (colors[key]) {
        themes[mode].colors[key] = colors[key]
      }
    }
  }

  if (config.theme === 'light' || config.theme === 'dark') {
    themeApi.change(config.theme)
    localStorage.setItem('mdforum-theme', config.theme)
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

import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import http from '../../api'

// 第三方登录服务商展示信息
export const OAUTH_META = {
  github: { icon: 'mdi-github', label: 'GitHub' },
  google: { icon: 'mdi-google', label: 'Google' },
  microsoft: { icon: 'mdi-microsoft', label: 'Microsoft' },
}

/**
 * 第三方账号绑定（SettingsOAuthView）数据 store
 *
 * 负责可用服务商、已绑定列表的拉取与解绑；绑定跳转（含 token）由视图处理。
 */
export const useSettingsOAuthViewStore = defineStore('settingsOAuthView', () => {
  const enabledProviders = ref([])
  const oauthBindings = ref([])
  const loading = ref(false)
  // 正在解绑的服务商名称
  const unbinding = ref(null)

  // 服务商 × 绑定状态 的行数据（界面渲染用）
  const oauthRows = computed(() =>
    enabledProviders.value.map((provider) => {
      const meta = OAUTH_META[provider] || { icon: 'mdi-account', label: provider }
      const bound = oauthBindings.value.find((binding) => binding.oauth_name === provider)
      return {
        provider,
        icon: meta.icon,
        label: meta.label,
        bound,
      }
    }),
  )

  async function fetchProviders() {
    try {
      const { data } = await http.get('/oauth/providers')
      enabledProviders.value = Array.isArray(data?.providers) ? data.providers : []
      return enabledProviders.value
    } catch (e) {
      enabledProviders.value = []
      throw e
    }
  }

  async function fetchBindings() {
    loading.value = true
    try {
      const { data } = await http.get('/user/oauth')
      oauthBindings.value = Array.isArray(data?.data) ? data.data : []
      return oauthBindings.value
    } catch (e) {
      oauthBindings.value = []
      throw e
    } finally {
      loading.value = false
    }
  }

  /** 解绑：成功后本地移除该绑定，失败抛出由视图提示 */
  async function unbind(row) {
    if (!row?.bound) return
    unbinding.value = row.provider
    try {
      await http.delete(`/user/oauth/${row.bound.oauth_id}`)
      oauthBindings.value = oauthBindings.value.filter(
        (binding) => binding.oauth_id !== row.bound.oauth_id,
      )
    } finally {
      unbinding.value = null
    }
  }

  function reset() {
    enabledProviders.value = []
    oauthBindings.value = []
    unbinding.value = null
  }

  return {
    enabledProviders,
    oauthBindings,
    loading,
    unbinding,
    oauthRows,
    fetchProviders,
    fetchBindings,
    unbind,
    reset,
  }
})

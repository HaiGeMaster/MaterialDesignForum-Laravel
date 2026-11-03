import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../../api'

/**
 * 设备会话（SettingsSessionsView）数据 store
 *
 * 负责会话列表拉取、单个会话撤销、全部其他会话撤销。
 */
export const useSettingsSessionsViewStore = defineStore('settingsSessionsView', () => {
  const sessionTokens = ref([])
  const loading = ref(false)
  // 正在删除的会话：token 值 / 'all' / ''
  const deleting = ref('')

  async function fetchSessions() {
    loading.value = true
    try {
      const { data } = await http.get('/user/tokens')
      sessionTokens.value = Array.isArray(data?.data) ? data.data : []
      return sessionTokens.value
    } catch (e) {
      sessionTokens.value = []
      throw e
    } finally {
      loading.value = false
    }
  }

  /** 撤销单个会话后刷新列表 */
  async function revokeSession(token) {
    if (!token) return
    deleting.value = token
    try {
      await http.delete(`/user/tokens/${encodeURIComponent(token)}`)
      await fetchSessions()
    } finally {
      deleting.value = ''
    }
  }

  /** 撤销除当前设备外的全部会话 */
  async function revokeOtherSessions() {
    deleting.value = 'all'
    try {
      await http.delete('/user/tokens')
      await fetchSessions()
    } finally {
      deleting.value = ''
    }
  }

  function reset() {
    sessionTokens.value = []
    deleting.value = ''
  }

  return {
    sessionTokens,
    loading,
    deleting,
    fetchSessions,
    revokeSession,
    revokeOtherSessions,
    reset,
  }
})

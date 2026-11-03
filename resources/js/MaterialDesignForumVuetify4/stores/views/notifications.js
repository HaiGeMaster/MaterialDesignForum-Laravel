import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../../api'
import { useAuthStore } from '../auth'

/**
 * 通知列表（NotificationsView）数据 store
 *
 * 负责分页加载通知、批量标记已读与清空全部通知。
 * 标记已读 / 清空后会派发 notification:updated 事件，供应用栏徽章刷新。
 */
export const useNotificationsViewStore = defineStore('notificationsView', () => {
  const notifications = ref([])
  const loading = ref(false)
  const error = ref(false)
  const page = ref(1)
  const finished = ref(false)
  const clearing = ref(false)

  async function load(reset = false) {
    const authStore = useAuthStore()
    if (!authStore.isLoggedIn) return
    if (!reset && (loading.value || finished.value)) return
    if (reset) {
      page.value = 1
      notifications.value = []
      finished.value = false
      error.value = false
    }
    loading.value = true
    error.value = false
    try {
      const { data } = await http.get('/notifications', {
        params: { page: page.value, per_page: 20 },
      })
      const items = data.data || []
      notifications.value = reset ? items : [...notifications.value, ...items]
      finished.value = !data.data || page.value >= (data.last_page ?? 1)
      markReadIfNeeded(items)
    } catch (e) {
      error.value = true
      throw e
    } finally {
      loading.value = false
    }
  }

  /** 本次加载到未读通知时，标记全部已读并通知应用栏刷新徽章 */
  async function markReadIfNeeded(items) {
    const hasUnread = items.some((n) => !n.read_time)
    if (!hasUnread) return
    try {
      await http.put('/notifications/read')
      window.dispatchEvent(new CustomEvent('notification:updated'))
    } catch (e) {
      // 标记已读失败不阻塞浏览
    }
  }

  function loadNext() {
    if (loading.value || finished.value || error.value) return
    page.value += 1
    return load()
  }

  function retry() {
    if (loading.value) return
    return load()
  }

  /** 清空全部通知 */
  async function clearAll() {
    clearing.value = true
    try {
      await http.delete('/notifications')
      notifications.value = []
      finished.value = true
      window.dispatchEvent(new CustomEvent('notification:updated'))
    } finally {
      clearing.value = false
    }
  }

  function reset() {
    notifications.value = []
    loading.value = false
    error.value = false
    page.value = 1
    finished.value = false
  }

  return {
    notifications,
    loading,
    error,
    page,
    finished,
    clearing,
    load,
    loadNext,
    retry,
    clearAll,
    reset,
  }
})

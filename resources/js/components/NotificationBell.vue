<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import http from '../api'

const authStore = useAuthStore()

// 未读通知数量（应用栏徽章），每 30 秒轮询一次
const POLL_INTERVAL = 30 * 1000
const unreadCount = ref(0)
let pollTimer = null

async function fetchUnreadCount() {
  if (!authStore.isLoggedIn) return
  try {
    const { data } = await http.get('/notifications/unread-count')
    unreadCount.value = data.unread_count ?? 0
  } catch (e) {
    // 轮询失败静默处理，等待下一次
  }
}

function startPolling() {
  stopPolling()
  fetchUnreadCount()
  pollTimer = setInterval(fetchUnreadCount, POLL_INTERVAL)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

// 其他页面操作通知（查看 / 删除 / 清空）后主动刷新徽章
function handleNotificationUpdated() {
  fetchUnreadCount()
}

watch(
  () => authStore.isLoggedIn,
  (loggedIn) => {
    if (loggedIn) {
      startPolling()
    } else {
      stopPolling()
      unreadCount.value = 0
    }
  },
)

onMounted(() => {
  // 已登录则启动未读通知轮询，并监听通知数据变化
  if (authStore.isLoggedIn) {
    startPolling()
  }
  window.addEventListener('notification:updated', handleNotificationUpdated)
})

onBeforeUnmount(() => {
  stopPolling()
  window.removeEventListener('notification:updated', handleNotificationUpdated)
})
</script>

<template>
  <!-- 通知图标：未读时右上角显示徽章数量 -->
  <v-btn
    v-if="authStore.isLoggedIn"
    icon
    variant="text"
    to="/notifications"
    :title="$t('appBar.notifications')"
  >
    <v-badge
      :content="unreadCount > 99 ? '99+' : unreadCount"
      :model-value="unreadCount > 0"
      color="error"
      offset-x="-6"
      offset-y="-6"
    >
      <v-icon icon="mdi-bell-outline" />
    </v-badge>
  </v-btn>
</template>

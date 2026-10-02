<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import http from '../api'

const { t } = useI18n()
const authStore = useAuthStore()

// 未读通知数量（应用栏徽章），每 30 秒轮询一次
const POLL_INTERVAL = 30 * 1000
const unreadCount = ref(0)
let pollTimer = null

// ---- 浏览器通知 ----
const browserNotifPermission = ref(Notification?.permission ?? 'unsupported') // 'granted' | 'denied' | 'default' | 'unsupported'
const showPermissionSnackbar = ref(false)
// 每个会话只询问一次，避免反复打扰
let hasPromptedThisSession = false

/** 请求浏览器通知权限（由用户点击 snackbar 按钮触发，满足 user-gesture 要求） */
async function requestNotificationPermission() {
  if (!window.Notification) return
  try {
    const result = await Notification.requestPermission()
    browserNotifPermission.value = result
  } catch {
    // 部分浏览器（如旧版 Safari）可能抛异常
  }
}

/** 当未读数增长且权限已授予时，推送一条浏览器通知 */
function sendBrowserNotification(newCount, prevCount) {
  if (!window.Notification || Notification.permission !== 'granted') return
  // 仅在页面不可见时推送（用户正在看页面就没必要弹系统通知）
  if (!document.hidden) return
  const delta = newCount - prevCount
  const body = delta === 1
    ? t('notificationBell.newNotification')
    : t('notificationBell.newNotificationCount', { count: delta })
  const notif = new Notification(t('notificationBell.title'), {
    body,
    icon: '/favicon.ico',
    tag: 'notification-bell', // 同 tag 的通知会互相覆盖，避免堆叠
  })
  // 点击通知跳转到通知页
  notif.onclick = () => {
    window.focus()
    window.location.href = '/notifications'
    notif.close()
  }
}

async function fetchUnreadCount() {
  if (!authStore.isLoggedIn) return
  try {
    const prevCount = unreadCount.value
    const { data } = await http.get('/notifications/unread-count')
    const newCount = data.unread_count ?? 0
    unreadCount.value = newCount

    // 未读数增长 → 有新通知
    if (newCount > prevCount && prevCount > 0) {
      if (browserNotifPermission.value === 'granted') {
        sendBrowserNotification(newCount, prevCount)
      } else if (
        browserNotifPermission.value === 'default' &&
        !hasPromptedThisSession
      ) {
        // 权限未决定且本会话未询问过 → 弹出 snackbar 引导用户开启
        hasPromptedThisSession = true
        showPermissionSnackbar.value = true
      }
    }
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

  <!-- 浏览器通知权限引导 -->
  <v-snackbar
    v-model="showPermissionSnackbar"
    :timeout="10000"
    location="bottom right"
    color="primary"
  >
    {{ $t('notificationBell.permissionPrompt') }}
    <template #actions>
      <v-btn variant="flat" @click="requestNotificationPermission">
        {{ $t('notificationBell.permissionEnable') }}
      </v-btn>
      <v-btn variant="text" @click="showPermissionSnackbar = false">
        {{ $t('common.close') }}
      </v-btn>
    </template>
  </v-snackbar>
</template>

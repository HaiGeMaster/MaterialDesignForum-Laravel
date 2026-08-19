<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useDisplay, useTheme } from 'vuetify'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import http from '../api'
import LanguageSwitcher from './LanguageSwitcher.vue'
import AppTabBar from './AppTabBar.vue'
import { useTabBar } from '../composables/useTabBar'

const emit = defineEmits(['toggle-drawer'])

const authStore = useAuthStore()
const theme = useTheme()
const { mobile } = useDisplay()
const { t } = useI18n()
const { visible: showTabBar } = useTabBar()

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

// 当前实际生效是否为深色（system 模式下跟随系统）
// 注意：theme.current 是 Ref，当前主题对象在其 .value 上
const isDark = computed(() => theme.current.value?.dark ?? false)

// 手动切换浅色/深色，并记住用户选择
function toggleTheme() {
  const next = isDark.value ? 'light' : 'dark'
  theme.change(next)
  localStorage.setItem('mdforum-theme', next)
}

const profilePath = computed(() =>
  authStore.isLoggedIn ? `/users/${authStore.user?.user_id}` : '/users/me',
)

const userInitial = () => authStore.user?.username?.slice(0, 1).toUpperCase() || '?'

async function handleLogout() {
  try {
    await authStore.logout()
  } catch (error) {
    // 即使接口失败也清理本地登录态
  }
  useFeedback().show(t('appBar.logoutSuccess'), 'info')
}

onMounted(() => {
  // 恢复用户上次手动选择的主题（未选过则保持跟随系统）
  const saved = localStorage.getItem('mdforum-theme')
  if (saved === 'light' || saved === 'dark') {
    theme.change(saved)
  }
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
  <v-app-bar density="comfortable">
    <v-app-bar-nav-icon @click="emit('toggle-drawer')" />

    <v-app-bar-title>
      <v-icon icon="mdi-forum" class="mr-2" />
      MDForum
    </v-app-bar-title>

    <v-spacer />

    <LanguageSwitcher />

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

    <v-btn
      icon
      variant="text"
      :title="isDark ? $t('appBar.switchLight') : $t('appBar.switchDark')"
      @click="toggleTheme"
    >
      <v-icon :icon="isDark ? 'mdi-white-balance-sunny' : 'mdi-weather-night'" />
    </v-btn>

    <template v-if="authStore.isLoggedIn">
      <v-menu  min-width="200">
        <template #activator="{ props }">
          <v-btn v-if="!mobile" variant="text" v-bind="props">
            <v-avatar size="28" color="secondary" class="mr-2">
              <v-img
                v-if="authStore.user?.avatar"
                :src="authStore.user.avatar"
                cover
                class="cursor-pointer"
              />
              <span v-else class="text-caption">{{ userInitial() }}</span>
            </v-avatar>
            {{ authStore.user?.username }}
            <v-icon icon="mdi-chevron-down" size="small" class="ml-1" />
          </v-btn>
          <v-btn v-else icon variant="text" v-bind="props">
            <v-avatar size="40" color="secondary">
              <v-img
                v-if="authStore.user?.avatar"
                :src="authStore.user.avatar"
                cover
                class="cursor-pointer"
              />
              <span v-else class="text-caption">{{ userInitial() }}</span>
            </v-avatar>
          </v-btn>
        </template>
        <v-list density="compact" nav>
          <v-list-subheader>{{ authStore.user?.username }}</v-list-subheader>
          <v-divider class="my-1" />
          <v-list-item :to="profilePath" prepend-icon="mdi-account" :title="$t('nav.profile')" />
          <v-list-item to="/settings" prepend-icon="mdi-cog" :title="$t('appBar.settings')" />
          <v-divider class="my-1" />
          <v-list-item prepend-icon="mdi-logout" :title="$t('appBar.logout')" @click="handleLogout" />
        </v-list>
      </v-menu>
    </template>

    <v-btn v-else variant="tonal" to="/users/me" prepend-icon="mdi-account">
      {{ $t('appBar.loginRegister') }}
    </v-btn>

    <!-- 路由页标签栏：渲染在应用栏扩展区，随应用栏固定；无 tab 时不渲染扩展区 -->
    <template v-if="showTabBar" #extension>
      <AppTabBar />
    </template>
  </v-app-bar>
</template>

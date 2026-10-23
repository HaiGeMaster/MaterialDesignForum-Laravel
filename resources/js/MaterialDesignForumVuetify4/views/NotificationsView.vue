<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import http from '../api'
import { useAuthStore } from '../stores/auth'
import { useDeleteDialogStore } from '../stores/deleteDialog'
import { useFeedback } from '../composables/useFeedback'
import NotificationItem from '../components/NotificationItem.vue'
import InfiniteScroll from '../components/InfiniteScroll.vue'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { show } = useFeedback()

const deleteDialog = useDeleteDialogStore()

const notifications = ref([])
const loading = ref(false)
const error = ref(false)
const page = ref(1)
const finished = ref(false)
const clearing = ref(false)

async function load(reset = false) {
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
    show(e.message, 'error')
  } finally {
    loading.value = false
  }
}

// 本次加载到未读通知时，标记全部已读并通知应用栏刷新徽章
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
  load()
}

function retry() {
  load()
}

function askClearAll() {
  deleteDialog.open({
    title: t('notifications.clearAllTitle'),
    text: t('notifications.clearAllConfirm'),
    onConfirm: confirmClearAll,
  })
}

async function confirmClearAll() {
  await http.delete('/notifications')
  notifications.value = []
  finished.value = true
  window.dispatchEvent(new CustomEvent('notification:updated'))
  show(t('notifications.clearAllSuccess'), 'success')
}

function goToSetting() {
  router.push('/notifications/setting')
}

function goToLogin() {
  router.push('/login')
}

onMounted(() => {
  if (authStore.isLoggedIn) load(true)
})
</script>

<template>
  <v-container :class="xs ? 'pa-0' : 'px-4'" class="notifications-view" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-card rounded="lg" class="mx-auto">
      <v-toolbar :title="$t('notifications.title')">
        <template #prepend>
          <v-btn icon variant="text" @click="router.back()">
            <v-icon>mdi-arrow-left</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.back') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <v-menu v-if="authStore.isLoggedIn">
            <template #activator="{ props }">
              <v-btn icon="mdi-dots-vertical" variant="text" v-bind="props" />
            </template>
            <v-list rounded="lg" density="compact" nav>
              <v-list-item
                :prepend-icon="'mdi-delete-sweep'"
                :title="$t('notifications.clearAll')"
                @click="askClearAll"
              />
              <v-list-item
                :prepend-icon="'mdi-cog'"
                :title="$t('notifications.setting')"
                @click="goToSetting"
              />
            </v-list>
          </v-menu>
        </template>
      </v-toolbar>

      <v-divider />

      <!-- 未登录：提示登录 -->
      <v-card-text v-if="!authStore.isLoggedIn" class="pa-6">
        <v-empty-state
          icon="mdi-bell-outline"
          :title="$t('notifications.loginTitle')"
          :text="$t('notifications.loginText')"
        >
          <template #actions>
            <v-btn color="primary" variant="tonal" @click="goToLogin">
              {{ $t('common.login') }}
            </v-btn>
          </template>
        </v-empty-state>
      </v-card-text>

      <!-- 已登录：通知列表 -->
      <v-card-text v-else-if="notifications.length === 0 && !loading" class="pa-6">
        <v-empty-state
          icon="mdi-bell-off-outline"
          :title="$t('notifications.emptyTitle')"
          :text="$t('notifications.emptyText')"
        />
      </v-card-text>

      <v-card-text v-else >
        <v-list rounded="lg" lines="two" class="pa-0">
          <template v-for="(notification, i) in notifications" :key="notification.notification_id">
            <NotificationItem :item="notification" />
            <!-- <v-divider v-if="i < notifications.length - 1" /> -->
          </template>
        </v-list>
        <InfiniteScroll
          :loading="loading"
          :error="error"
          :finished="finished"
          @load="loadNext"
          @retry="retry"
        />
      </v-card-text>
    </v-card>

  </v-container>
</template>

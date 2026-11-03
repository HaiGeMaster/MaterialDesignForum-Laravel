<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../stores/auth'
import { useDeleteDialogStore } from '../stores/deleteDialog'
import { useNotificationsViewStore } from '../stores/views/notifications'
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
const notificationsStore = useNotificationsViewStore()
const { notifications, loading, error, finished } = storeToRefs(notificationsStore)

async function load(reset = false) {
  try {
    await notificationsStore.load(reset)
  } catch (e) {
    show(e.message, 'error')
  }
}

async function loadNext() {
  try {
    await notificationsStore.loadNext()
  } catch (e) {
    show(e.message, 'error')
  }
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
  await notificationsStore.clearAll()
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
            <v-list rounded="lg" density="compact">
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

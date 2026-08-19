<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import http from '../api'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { show } = useFeedback()

// 24 种通知类型（与旧项目一致）
const NOTIFICATION_TYPES = [
  'user_follow',
  'topic_follow',
  'topic_delete',
  'question_follow',
  'question_comment',
  'question_answer',
  'question_delete',
  'article_follow',
  'article_comment',
  'article_like',
  'article_delete',
  'answer_comment',
  'answer_like',
  'answer_delete',
  'comment_like',
  'comment_reply',
  'comment_delete',
  'reply_like',
  'reply_reply',
  'reply_delete',
  'follow_user_update',
  'follow_topic_update',
  'follow_question_update',
  'follow_article_update',
]

// 通知类型分组（沿用旧项目规划：关注 / 互动 / 点赞 / 删除 / 更新）
const NOTIFICATION_GROUPS = [
  {
    name: 'follows',
    icon: 'mdi-account-heart-outline',
    types: ['user_follow', 'topic_follow', 'question_follow', 'article_follow'],
  },
  {
    name: 'interactions',
    icon: 'mdi-chat-processing-outline',
    types: ['question_comment', 'question_answer', 'article_comment', 'answer_comment', 'comment_reply', 'reply_reply'],
  },
  {
    name: 'likes',
    icon: 'mdi-thumb-up-outline',
    types: ['article_like', 'answer_like', 'comment_like', 'reply_like'],
  },
  {
    name: 'deletes',
    icon: 'mdi-delete-outline',
    types: ['topic_delete', 'question_delete', 'article_delete', 'answer_delete', 'comment_delete', 'reply_delete'],
  },
  {
    name: 'updates',
    icon: 'mdi-bell-ring-outline',
    types: ['follow_user_update', 'follow_topic_update', 'follow_question_update', 'follow_article_update'],
  },
]

// 每个类型：站内通知 / 邮件通知 开关
const settings = ref(
  NOTIFICATION_TYPES.map((name) => ({
    name,
    web_message: true,
    email_message: true,
  })),
)

// 按分组组织设置项（用于界面分类展示）
const groupedSettings = computed(() =>
  NOTIFICATION_GROUPS.map((group) => ({
    ...group,
    items: settings.value.filter((s) => group.types.includes(s.name)),
  })),
)

// 列级总开关：一键全部开启 / 关闭
const allWeb = computed({
  get: () => settings.value.every((s) => s.web_message),
  set: (v) => settings.value.forEach((s) => (s.web_message = v)),
})
const allEmail = computed({
  get: () => settings.value.every((s) => s.email_message),
  set: (v) => settings.value.forEach((s) => (s.email_message = v)),
})

const loading = ref(false)
const saving = ref(false)

async function loadSettings() {
  loading.value = true
  try {
    const { data } = await http.get('/user-options/notifications')
    if (data?.value && Array.isArray(data.value)) {
      const savedMap = new Map(data.value.map((item) => [item.name, item]))
      settings.value = NOTIFICATION_TYPES.map((name) => ({
        name,
        web_message: savedMap.get(name)?.web_message ?? true,
        email_message: savedMap.get(name)?.email_message ?? true,
      }))
    }
  } catch (e) {
    show(e.message, 'error')
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  saving.value = true
  try {
    await http.put('/user-options/notifications', { value: settings.value })
    show(t('notificationSetting.saved'), 'success')
  } catch (e) {
    show(e.message, 'error')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (authStore.isLoggedIn) loadSettings()
})
</script>

<template>
  <v-container :class="xs ? 'px-0' : 'px-4'" class=" notification-setting-view" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-card class="mx-auto my-4" max-width="720" rounded="lg">
      <v-toolbar >
        <v-btn icon="mdi-arrow-left" variant="text" @click="router.back()" />
        <v-toolbar-title>{{ $t('notificationSetting.title') }}</v-toolbar-title>
        <v-btn
          color="primary"
          variant="tonal"
          :loading="saving"
          :disabled="!authStore.isLoggedIn"
          @click="saveSettings"
        >
          <v-icon start>mdi-check</v-icon>
          {{ $t('notificationSetting.save') }}
        </v-btn>
      </v-toolbar>

      <v-divider />

      <!-- 未登录：提示登录 -->
      <v-card-text v-if="!authStore.isLoggedIn" class="pa-6">
        <v-empty-state
          icon="mdi-bell-cog-outline"
          :title="$t('notifications.loginTitle')"
          :text="$t('notifications.loginText')"
        >
          <template #actions>
            <v-btn color="primary" variant="tonal" @click="router.push('/users/me')">
              {{ $t('common.login') }}
            </v-btn>
          </template>
        </v-empty-state>
      </v-card-text>

      <v-card-text v-else class="pa-0">
        <v-table density="comfortable">
          <thead>
            <tr>
              <th>{{ $t('notificationSetting.item') }}</th>
              <th class="text-center">
                <div class="d-flex flex-column align-center">
                  <span>{{ $t('notificationSetting.siteNotification') }}</span>
                  <v-checkbox
                    v-model="allWeb"
                    density="compact"
                    hide-details
                    color="primary"
                    :title="$t('notificationSetting.toggleAll')"
                  />
                </div>
              </th>
              <th class="text-center">
                <div class="d-flex flex-column align-center">
                  <span>{{ $t('notificationSetting.emailNotification') }}</span>
                  <v-checkbox
                    v-model="allEmail"
                    density="compact"
                    hide-details
                    color="primary"
                    :title="$t('notificationSetting.toggleAll')"
                  />
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <template v-for="group in groupedSettings" :key="group.name">
              <tr class="group-header">
                <td colspan="3">
                  <div class="d-flex align-center ga-2 px-1">
                    <v-icon :icon="group.icon" size="small" />
                    <span>{{ $t(`notificationCategory.${group.name}`) }}</span>
                    <span class="text-caption opacity-70">({{ group.items.length }})</span>
                  </div>
                </td>
              </tr>
              <tr v-for="item in group.items" :key="item.name">
                <td class="text-body-2">{{ $t(`notificationTypeName.${item.name}`) }}</td>
                <td class="text-center">
                  <v-checkbox v-model="item.web_message" density="compact" hide-details class="d-inline-flex" />
                </td>
                <td class="text-center">
                  <v-checkbox v-model="item.email_message" density="compact" hide-details class="d-inline-flex" />
                </td>
              </tr>
            </template>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<style scoped>
/* 分组标题行：浅色背景 + 主色文字，自动适配明暗主题 */
.group-header td {
  background: rgb(var(--v-theme-surface-light));
  /* color: rgb(var(--v-theme-primary)); */
  font-weight: 600;
  letter-spacing: 0.02em;
}
</style>

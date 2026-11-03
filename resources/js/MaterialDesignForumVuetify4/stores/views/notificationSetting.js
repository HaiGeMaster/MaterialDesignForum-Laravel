import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import http from '../../api'

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

/**
 * 通知设置（NotificationSettingView）数据 store
 *
 * 负责「站内 / 邮件」通知开关的拉取与保存，并按分组派生界面数据。
 */
export const useNotificationSettingViewStore = defineStore('notificationSettingView', () => {
  // 每个类型：站内通知 / 邮件通知 开关
  const settings = ref(
    NOTIFICATION_TYPES.map((name) => ({
      name,
      web_message: true,
      email_message: false,
    })),
  )

  const loading = ref(false)
  const saving = ref(false)
  // 是否已成功加载过（进入页面时复用缓存）
  const loaded = ref(false)
  const error = ref('')

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

  async function loadSettings() {
    if (loading.value) return
    loading.value = true
    error.value = ''
    try {
      const { data } = await http.get('/user-options/notifications')
      if (data?.value && Array.isArray(data.value)) {
        const savedMap = new Map(data.value.map((item) => [item.name, item]))
        settings.value = NOTIFICATION_TYPES.map((name) => ({
          name,
          web_message: savedMap.get(name)?.web_message ?? true,
          email_message: savedMap.get(name)?.email_message ?? false,
        }))
      }
      loaded.value = true
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  /** 进入页面时调用：已加载过则复用缓存，force 为 true 时强制刷新 */
  async function ensureLoaded(force = false) {
    if (loading.value) return
    if (loaded.value && !force) return
    await loadSettings()
  }

  async function saveSettings() {
    saving.value = true
    error.value = ''
    try {
      await http.put('/user-options/notifications', { value: settings.value })
      loaded.value = true
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      saving.value = false
    }
  }

  function reset() {
    settings.value = NOTIFICATION_TYPES.map((name) => ({
      name,
      web_message: true,
      email_message: false,
    }))
    loaded.value = false
    error.value = ''
  }

  return {
    settings,
    groupedSettings,
    allWeb,
    allEmail,
    loading,
    saving,
    loaded,
    error,
    loadSettings,
    ensureLoaded,
    saveSettings,
    reset,
  }
})

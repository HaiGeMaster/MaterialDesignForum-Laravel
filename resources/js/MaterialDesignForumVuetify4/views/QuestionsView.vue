<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { storeToRefs } from 'pinia'
import ContentListItem from '../components/ContentListItem.vue'
import InfiniteScroll from '../components/InfiniteScroll.vue'
import { useFeedback } from '../composables/useFeedback'
import { useAuthStore } from '../stores/auth'
import { useQuestionsViewStore } from '../stores/views/questions'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const { show } = useFeedback()
const { t } = useI18n()

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const questionsStore = useQuestionsViewStore()
const { questions, loading, error, finished } = storeToRefs(questionsStore)

// 当前打开头像卡片菜单的问题 id（同时只开一个）
const activeAvatar = ref(null)

// 关注状态回写到作者数据
function handleToggleFollow(item, payload) {
  questionsStore.updateCreatorFollow(item, payload)
}

// 小卡片里的更多操作（私信 / 举报）暂未实现
function handleMore(item, action) {
  show(t('common.developing'), 'info')
}

// 当前 tab：未登录时不显示「我关注的」
const tab = computed(() => {
  const t = route.hash.slice(1)
  if (t === 'latest') return 'latest'
  if (t === 'following' && authStore.isLoggedIn) return 'following'
  return 'hot'
})

// 每个 tab 的空状态文案不同
const emptyState = computed(() => {
  const map = {
    hot: { icon: 'mdi-fire', title: 'list.emptyHotQuestionsTitle', text: 'list.emptyHotQuestionsText' },
    latest: { icon: 'mdi-clock-fast', title: 'list.emptyLatestQuestionsTitle', text: 'list.emptyLatestQuestionsText' },
    following: { icon: 'mdi-account-heart-outline', title: 'list.emptyFollowingQuestionsTitle', text: 'list.emptyFollowingQuestionsText' },
  }
  return map[tab.value] ?? map.hot
})

// 点击列表项进入问题详情
function handleOpen(item) {
  router.push(`/questions/${item.question_id}`)
}

// 编辑器窗口发布成功后刷新：创建跳到详情页，编辑留在列表
function handleContentPublished(e) {
  const { mode, action, item } = e.detail || {}
  if (mode !== 'question') return
  if (action === 'create' && item?.question_id) {
    router.push(`/questions/${item.question_id}`)
  } else {
    questionsStore.refresh()
  }
}

onMounted(() => {
  questionsStore.loadTab(tab.value)
  window.addEventListener('content:published', handleContentPublished)
})
onUnmounted(() => {
  window.removeEventListener('content:published', handleContentPublished)
})
watch(tab, () => questionsStore.loadTab(tab.value))
</script>

<template>
  <v-container  :class="xs ? 'pa-0' : 'px-4'" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-progress-linear v-if="loading && !questions.length" indeterminate color="primary" class="mb-3" />

    <v-card rounded="lg" v-if="questions.length"  class="overflow-hidden mb-3">
      <v-list rounded="lg" lines="two" style="padding: 0">
        <ContentListItem
          v-for="q in questions"
          :key="q.question_id"
          :item="q"
          item-type="question"
          :active="activeAvatar === q.question_id"
          @update:active="(v) => (activeAvatar = v ? q.question_id : null)"
          @toggle-follow="handleToggleFollow"
          @more="handleMore"
          @open="handleOpen"
        />
      </v-list>
    </v-card>

    <InfiniteScroll
      v-if="questions.length"
      :finished="finished"
      :loading="loading"
      :error="error"
      @load="questionsStore.loadNext"
      @retry="questionsStore.retry"
    />

    <v-empty-state
      v-if="!questions.length && !loading"
      :icon="emptyState.icon"
      :title="$t(emptyState.title)"
      :text="$t(emptyState.text)"
    />
  </v-container>
</template>

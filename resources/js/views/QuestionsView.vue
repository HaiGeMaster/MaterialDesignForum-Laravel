<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'
import ContentListItem from '../components/ContentListItem.vue'
import InfiniteScroll from '../components/InfiniteScroll.vue'
import { useFeedback } from '../composables/useFeedback'
import { useInfiniteList } from '../composables/useInfiniteList'
import { useAuthStore } from '../stores/auth'

const { show } = useFeedback()
const { t } = useI18n()

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// 当前打开头像卡片菜单的问题 id（同时只开一个）
const activeAvatar = ref(null)

// 关注状态回写到作者数据
function handleToggleFollow(item, { following, followerCount }) {
  const creator = item?.creator
  if (!creator) return
  creator.is_following = following
  if (followerCount !== undefined) {
    creator.follower_count = followerCount
  }
}

// 小卡片里的更多操作（私信 / 举报）暂未实现
function handleMore(item, action) {
  show(t('common.developing'), 'info')
}

// 当前 tab：未登录时不显示「我关注的」
const tab = computed(() => {
  const t = route.query.tab
  if (t === 'latest') return 'latest'
  if (t === 'following' && authStore.isLoggedIn) return 'following'
  return 'hot'
})

const {
  list: questions,
  loading,
  error,
  finished,
  load,
  loadNext,
  retry,
} = useInfiniteList(async (page) => {
  const { data } = await http.get('/questions', { params: { tab: tab.value, page } })
  return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
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
    load(true)
  }
}

onMounted(() => {
  load(true)
  window.addEventListener('content:published', handleContentPublished)
})
onUnmounted(() => {
  window.removeEventListener('content:published', handleContentPublished)
})
watch(tab, () => load(true))
</script>

<template>
  <div>
    <v-progress-linear v-if="loading && !questions.length" indeterminate color="primary" class="mb-3" />

    <v-card v-if="questions.length"  class="overflow-hidden mb-3">
      <v-list lines="two">
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
      @load="loadNext"
      @retry="retry"
    />

    <v-empty-state
      v-if="!questions.length && !loading"
      icon="mdi-help-circle"
      :title="$t('list.emptyQuestionsTitle')"
      :text="$t('list.emptyQuestionsText')"
    />
  </div>
</template>

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

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const { show } = useFeedback()
const { t } = useI18n()

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// 当前打开头像卡片菜单的文章 id（同时只开一个）
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

// 每个 tab 的空状态文案不同
const emptyState = computed(() => {
  const map = {
    hot: { icon: 'mdi-fire', title: 'list.emptyHotArticlesTitle', text: 'list.emptyHotArticlesText' },
    latest: { icon: 'mdi-clock-fast', title: 'list.emptyLatestArticlesTitle', text: 'list.emptyLatestArticlesText' },
    following: { icon: 'mdi-account-heart-outline', title: 'list.emptyFollowingArticlesTitle', text: 'list.emptyFollowingArticlesText' },
  }
  return map[tab.value] ?? map.hot
})

const {
  list: articles,
  loading,
  error,
  finished,
  load,
  loadNext,
  retry,
} = useInfiniteList(async (page) => {
  const { data } = await http.get('/articles', { params: { tab: tab.value, page } })
  return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
})

// 点击列表项进入文章详情
function handleOpen(item) {
  router.push(`/articles/${item.article_id}`)
}

// 编辑器窗口发布成功后刷新：创建跳到详情页，编辑留在列表
function handleContentPublished(e) {
  const { mode, action, item } = e.detail || {}
  if (mode !== 'article') return
  if (action === 'create' && item?.article_id) {
    router.push(`/articles/${item.article_id}`)
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
  <v-container  :class="xs ? 'pa-0' : 'px-4'" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-progress-linear v-if="loading && !articles.length" indeterminate color="primary" class="mb-3" />

    <v-card v-if="articles.length"  class="overflow-hidden mb-3">
      <v-list lines="two">
        <ContentListItem
          v-for="a in articles"
          :key="a.article_id"
          :item="a"
          item-type="article"
          :active="activeAvatar === a.article_id"
          @update:active="(v) => (activeAvatar = v ? a.article_id : null)"
          @toggle-follow="handleToggleFollow"
          @more="handleMore"
          @open="handleOpen"
        />
      </v-list>
    </v-card>

    <InfiniteScroll
      v-if="articles.length"
      :finished="finished"
      :loading="loading"
      :error="error"
      @load="loadNext"
      @retry="retry"
    />

    <v-empty-state
      v-if="!articles.length && !loading"
      :icon="emptyState.icon"
      :title="$t(emptyState.title)"
      :text="$t(emptyState.text)"
    />
  </v-container>
</template>

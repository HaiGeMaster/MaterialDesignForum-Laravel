<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import http from '../api'
import { useAuthStore } from '../stores/auth'
import { useInfiniteList } from '../composables/useInfiniteList'
import InfiniteScroll from '../components/InfiniteScroll.vue'
import TopicCard from '../components/TopicCard.vue'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const route = useRoute()
const authStore = useAuthStore()

// 当前 tab：未登录时强制推荐话题
const tab = computed(() => {
  if (!authStore.isLoggedIn) return 'recommend'
  return route.hash.slice(1) === 'following' ? 'following' : 'recommend'
})

// 每个 tab 的空状态文案不同
const emptyState = computed(() =>
  tab.value === 'following'
    ? { icon: 'mdi-account-heart-outline', title: 'topics.emptyFollowingTitle', text: 'topics.emptyFollowingText' }
    : { icon: 'mdi-tag-multiple', title: 'topics.emptyRecommendTitle', text: 'topics.emptyRecommendText' },
)

const {
  list: topics,
  loading,
  error,
  finished,
  load,
  loadNext,
  retry,
} = useInfiniteList(async (page) => {
  const { data } = await http.get('/topics', { params: { tab: tab.value, page } })
  if (Array.isArray(data)) return { items: data, hasMore: false }
  return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
})

onMounted(() => load(true))
watch(tab, () => load(true))

// 关注状态由服务器随列表数据返回（topic.is_following），切换后回写最新状态与关注数
function toggleFollow(topic, { following, followerCount }) {
  topic.is_following = following
  if (followerCount !== undefined) {
    topic.follower_count = followerCount
  }
}
</script>

<template>
  <v-container  class="pa-4" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-row v-if="topics.length">
      <v-col v-for="t in topics" :key="t.topic_id" cols="12" sm="6" md="4" lg="3">
        <TopicCard :topic="t" :following="t.is_following" @toggle-follow="toggleFollow" />
      </v-col>
    </v-row>

    <InfiniteScroll
      v-if="topics.length"
      :finished="finished"
      :loading="loading"
      :error="error"
      @load="loadNext"
      @retry="retry"
    />

    <v-empty-state
      v-else-if="!loading"
      :icon="emptyState.icon"
      :title="$t(emptyState.title)"
      :text="$t(emptyState.text)"
    />
  </v-container>
</template>

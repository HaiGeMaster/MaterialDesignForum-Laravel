<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useUserStore } from '../stores/user'
import { useFeedback } from '../composables/useFeedback'
import InfiniteScroll from '../components/InfiniteScroll.vue'
import PersonCard from '../components/PersonCard.vue'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const route = useRoute()
const router = useRouter()

const authStore = useAuthStore()
const userStore = useUserStore()
const { show } = useFeedback()
const { t } = useI18n()

const users = computed(() => userStore.list)

// 分页状态（数据本身存放在 userStore.list 中）
const page = ref(1)
const finished = ref(false)
const error = ref(false)

// 当前 tab：未登录时强制推荐人脉
const tab = computed(() => {
  if (!authStore.isLoggedIn) return 'recommend'
  const t = route.query.tab
  return t === 'following' || t === 'followers' ? t : 'recommend'
})

// 每个 tab 的空状态文案不同
const emptyState = computed(() => {
  const map = {
    following: { icon: 'mdi-account-heart-outline', title: 'people.emptyFollowingTitle', text: 'people.emptyFollowingText' },
    followers: { icon: 'mdi-account-multiple-outline', title: 'people.emptyFollowersTitle', text: 'people.emptyFollowersText' },
    recommend: { icon: 'mdi-account-search', title: 'people.emptyRecommendTitle', text: 'people.emptyRecommendText' },
  }
  return map[tab.value] ?? map.recommend
})

async function refresh(reset = true) {
  if (userStore.loading) return
  if (reset) {
    page.value = 1
    finished.value = false
    error.value = false
  } else if (finished.value) {
    return
  }

  error.value = false
  try {
    const data = await userStore.fetchUsers({ tab: tab.value, page: page.value })
    finished.value = !(data.current_page < data.last_page)
  } catch {
    error.value = true
  }
}

/** 加载下一页（由 InfiniteScroll 触发） */
function loadNext() {
  if (userStore.loading || finished.value || error.value) return
  page.value += 1
  refresh(false)
}

onMounted(() => refresh())
watch(tab, () => refresh(true))

// 关注状态由服务器随列表数据返回（user.is_following），切换后回写最新状态与关注数
function toggleFollow(user, { following, followerCount }) {
  user.is_following = following
  if (followerCount !== undefined) {
    user.follower_count = followerCount
  }
}

function goToProfile(user) {
  router.push(`/users/${user.user_id}`)
}

function openPlaceholder(action) {
  show(t('common.developing'), 'info')
}
</script>

<template>
  <v-container  class="pa-4" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">

    <v-row v-if="users.length">
      <v-col v-for="u in users" :key="u.user_id" cols="6" sm="6" md="4" lg="3">
        <PersonCard
          :person="u"
          :following="u.is_following"
          @click="goToProfile"
          @toggle-follow="toggleFollow"
          @more="(person, action) => openPlaceholder(action)"
        />
      </v-col>
    </v-row>

    <InfiniteScroll
      v-if="users.length"
      :finished="finished"
      :loading="userStore.loading"
      :error="error"
      @load="loadNext"
      @retry="() => refresh(false)"
    />

    <v-empty-state
      v-else-if="!userStore.loading"
      :icon="emptyState.icon"
      :title="$t(emptyState.title)"
      :text="$t(emptyState.text)"
    />
  </v-container>
</template>

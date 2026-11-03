<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../stores/auth'
import { usePeopleViewStore } from '../stores/views/people'
import { useFeedback } from '../composables/useFeedback'
import InfiniteScroll from '../components/InfiniteScroll.vue'
import PersonCard from '../components/PersonCard.vue'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const route = useRoute()
const router = useRouter()

const authStore = useAuthStore()
const peopleStore = usePeopleViewStore()
const { users, loading, error, finished } = storeToRefs(peopleStore)
const { show } = useFeedback()
const { t } = useI18n()

// 当前 tab：未登录时强制推荐人脉
const tab = computed(() => {
  if (!authStore.isLoggedIn) return 'recommend'
  const t = route.hash.slice(1)
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

onMounted(() => peopleStore.loadTab(tab.value))
watch(tab, () => peopleStore.loadTab(tab.value))

// 关注状态由服务器随列表数据返回（user.is_following），切换后回写最新状态与关注数
function toggleFollow(user, payload) {
  peopleStore.updateFollow(user, payload)
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
      <v-col v-for="u in users" :key="u.user_id" cols="12" sm="6" md="4" lg="3">
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
      :loading="loading"
      :error="error"
      @load="peopleStore.loadNext"
      @retry="peopleStore.retry"
    />

    <v-empty-state
      v-else-if="!loading"
      :icon="emptyState.icon"
      :title="$t(emptyState.title)"
      :text="$t(emptyState.text)"
    />
  </v-container>
</template>

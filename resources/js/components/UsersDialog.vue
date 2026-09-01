<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useUsersDialogStore } from '../stores/usersDialog'
import { useAuthStore } from '../stores/auth'
import FollowButton from './FollowButton.vue'
import InfiniteScroll from './InfiniteScroll.vue'

const store = useUsersDialogStore()
const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { mobile } = useDisplay()

const users = ref([])
const page = ref(1)
const total = ref(0)
const loading = ref(false)
const error = ref(false)
const finished = ref(false)

// v-dialog 直接绑定 store，任何入口调用 store.open() 即打开
const model = computed({
  get: () => store.model,
  set: (value) => (store.model = value),
})

// 标题：followers → 多少人关注（对象不限人，也可为话题/文章/提问）；followees → 关注了多少人
const title = computed(() =>
  store.modes === 'followers'
    ? t('usersDialog.followersTitle', { count: total.value })
    : t('usersDialog.followeesTitle', { count: total.value }),
)

async function load() {
  if (loading.value || finished.value) return
  loading.value = true
  error.value = false
  try {
    const { data } = await http.post('/follows/get', {
      modes: store.modes,
      followable_type: store.type,
      followable_id: store.id,
      page: page.value,
      per_page: 20,
    })
    users.value = page.value === 1 ? data.data : [...users.value, ...data.data]
    total.value = data.total
    finished.value = page.value >= data.last_page
    page.value += 1
  } catch (e) {
    error.value = true
  } finally {
    loading.value = false
  }
}

function reset() {
  users.value = []
  page.value = 1
  total.value = 0
  finished.value = false
  error.value = false
}

// 打开时重置并加载第一页
watch(
  () => store.model,
  (open) => {
    if (open) {
      reset()
      load()
    }
  },
)

// 打开期间参数被切换（如从「关注」切到「粉丝」）时重新加载
watch(
  () => [store.type, store.id, store.modes],
  () => {
    if (store.model) {
      reset()
      load()
    }
  },
)

// 跳转后关闭，避免对话框残留在其他页面
watch(
  () => route.fullPath,
  () => store.close(),
)

function goProfile(user) {
  store.close()
  router.push(`/users/${user.user_id}`)
}

function isSelf(user) {
  return (
    Boolean(authStore.user) &&
    Number(authStore.user.user_id) === Number(user.user_id)
  )
}
</script>

<template>
  <v-dialog
    v-model="model"
    :fullscreen="mobile"
    max-width="530"
  >
    <v-card rounded="lg" >
      <v-toolbar :title="title">
        <template #append>
          <v-btn icon variant="text" @click="model = false">
            <v-icon>
              mdi-close
            </v-icon>
            <v-tooltip activator="parent" location="left">
              {{ $t('common.cancel') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-divider />

      <v-list v-if="users.length" lines="two" class="pa-0" max-height="60vh">
        <v-list-item
          v-for="user in users"
          :key="user.user_id"
          class="cursor-pointer"
          @click="goProfile(user)"
        >
          <template #prepend>
            <v-avatar size="44" color="primary"  class="mr-2">
              <v-img v-if="user.avatar" :src="user.avatar" cover />
              <span v-else >
                {{ user.username?.slice(0, 1).toUpperCase() || '?' }}
              </span>
            </v-avatar>
          </template>

          <v-list-item-title class="font-weight-bold text-truncate">
            {{ user.username }}
          </v-list-item-title>

          <v-list-item-subtitle class="text-medium-emphasis text-truncate">
            {{ user.headline || user.bio || $t('usersDialog.noBio') }}
          </v-list-item-subtitle>

          <template #append>
            <FollowButton
              v-if="!isSelf(user)"
              type="user"
              :target-id="user.user_id"
              :following="Boolean(user.is_following)"
            />
          </template>
        </v-list-item>

        <InfiniteScroll
          :finished="finished"
          :loading="loading"
          :error="error"
          @load="load"
          @retry="load"
        />
      </v-list>

      <v-empty-state
        v-else-if="!loading && !error"
        icon="mdi-account-multiple"
        :title="$t('usersDialog.empty')"
      />
    </v-card>
  </v-dialog>
</template>

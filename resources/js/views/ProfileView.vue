<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useUserStore } from '../stores/user'
import { useFeedback } from '../composables/useFeedback'
import AuthForm from '../components/AuthForm.vue'
import UserContentPanel from '../components/UserContentPanel.vue'
import UserProfileCard from '../components/UserProfileCard.vue'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const authStore = useAuthStore()
const userStore = useUserStore()
const route = useRoute()
const { show: feedback } = useFeedback()
const { t } = useI18n()

const isLoggedIn = computed(() => authStore.isLoggedIn)
const currentUser = computed(() => authStore.user)

// ---------- 用户主页（/users/:user_id） ----------
const viewedUser = ref(null)
const isSelf = computed(() => {
  if (!isLoggedIn.value || !currentUser.value) return false
  const id = route.params.user_id
  return id === 'me' || String(id) === String(currentUser.value.user_id)
})
// 查看他人主页：未登录访问具体用户 ID 时也允许查看基本信息
const viewingOther = computed(() => {
  if (route.params.user_id === 'me') return false
  return !isSelf.value
})

watch(
  () => [route.params.user_id, isLoggedIn.value],
  async () => {
    if (viewingOther.value) {
      try {
        viewedUser.value = await userStore.fetchUser(route.params.user_id)
      } catch (error) {
        feedback(error.message, 'error')
        viewedUser.value = null
      }
    } else {
      viewedUser.value = null
    }
  },
  { immediate: true },
)

// 他人主页：当前用户是否已关注 TA（由服务器返回 user.is_following）
const isOtherFollowing = computed(() => Boolean(viewedUser.value?.is_following))

// 关注状态已由 FollowButton 调用 API 完成，这里回写最新状态与粉丝数
function handleToggleFollow({ following, followerCount }) {
  if (viewedUser.value && following !== undefined) {
    viewedUser.value.is_following = following
  }
  if (viewedUser.value && followerCount !== undefined) {
    viewedUser.value.follower_count = followerCount
  }
}

// ---------- 登录 / 注册 ----------
async function handleLogin(payload) {
  try {
    const result = await authStore.login(payload)
    feedback(t('profile.welcomeBack', { username: result.user.username }), 'success')
  } catch (error) {
    feedback(error.message, 'error')
  }
}

async function handleRegister(payload) {
  try {
    const result = await authStore.register(payload)
    feedback(t('profile.registered', { username: result.user.username }), 'success')
  } catch (error) {
    feedback(error.message, 'error')
  }
}

async function handleResetPassword(payload) {
  try {
    const result = await authStore.resetPassword(payload)
    feedback(t('profile.passwordReset', { username: result.user.username }), 'success')
  } catch (error) {
    feedback(error.message, 'error')
  }
}

function handleMore(action) {
  feedback(t('common.developing'), 'info')
}
</script>

<template>
  <v-container  :class="xs ? 'pa-0' : 'px-4'" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <!-- 未登录且访问"me"或无法确认目标时：登录 / 注册 -->
    <AuthForm v-if="!isLoggedIn && !viewingOther"
      :loading="authStore.loading"
      @login="handleLogin"
      @register="handleRegister"
      @reset-password="handleResetPassword"
    />

    <!-- 他人主页（未登录也可查看基本信息） -->
    <template v-else-if="viewingOther">
      <UserProfileCard
        v-if="viewedUser"
        mode="other"
        :user="viewedUser"
        :following="isOtherFollowing"
        @toggle-follow="handleToggleFollow"
        @more="handleMore"
      />
      <UserContentPanel v-if="viewedUser" class="mt-4" :user-id="viewedUser.user_id" />
    </template>

    <!-- 已登录：个人主页展示 -->
    <template v-else>
      <UserProfileCard v-if="currentUser" mode="self" :user="currentUser" />
      <UserContentPanel v-if="currentUser" class="mt-4" :user-id="currentUser.user_id" />
    </template>
  </v-container>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFollowStore } from '../stores/follow'
import { useFeedback } from '../composables/useFeedback'

const props = defineProps({
  // 关注目标类型：user 用户 / topic 话题 / question 提问 / article 文章
  type: { type: String, default: 'user' },
  // 关注目标的 ID
  targetId: { type: [Number, String], required: true },
  // 是否已关注（父组件传入的初始/外部状态）
  following: { type: Boolean, default: false },
    size: { type: String, default: 'small' },
  variant: { type: String, default: 'tonal' },
})

const emit = defineEmits(['toggle-follow'])

const followStore = useFollowStore()
const authStore = useAuthStore()
const router = useRouter()
const { show } = useFeedback()
const { t } = useI18n()

// 本地展示状态，与外部 following 保持同步
const localFollowing = ref(props.following)
const loading = ref(false)

watch(
  () => props.following,
  (value) => {
    localFollowing.value = value
  },
)

// 关注文案按目标类型区分
const TYPE_LABEL_KEY = {
  user: 'follow.user',
  topic: 'follow.topic',
  question: 'follow.question',
  article: 'follow.article',
}
const typeLabel = computed(() => t(TYPE_LABEL_KEY[props.type] || 'follow.target'))

async function handleToggle() {
  if (loading.value) return
  // 未登录时引导到登录页（个人资料页）
  if (!authStore.isLoggedIn) {
    show(t('follow.loginFirst'), 'info')
    router.push('/login')
    return
  }
  loading.value = true
  try {
    const result = await followStore.toggle(props.type, props.targetId)
    localFollowing.value = result.following
    const label = typeLabel.value
    show(
      result.following ? t('follow.followed', { label }) : t('follow.unfollowed', { label }),
      result.following ? 'success' : 'info',
    )
    // 通知父组件最新状态（含最新关注人数），便于同步计数
    emit('toggle-follow', {
      following: result.following,
      followerCount: result.follower_count,
    })
  } catch (error) {
    show(error.message, 'error')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <!-- 圆形关注按钮：未关注显示空心星（primary 实心），已关注显示实心星（tonal 弱化） -->
  <v-tooltip
    :text="localFollowing ? $t('follow.unfollow') : $t('follow.follow')"

  >
    <template v-slot:activator="{ props }">
      <v-btn
        v-bind="props"
        icon
        :size="size"
        :variant="variant"
        :loading="loading"
        @click.stop="handleToggle"
      >
        <v-icon :color="!localFollowing ? '' : 'primary'" :icon="localFollowing ? 'mdi-star' : 'mdi-star-outline'" />
      </v-btn>
    </template>
  </v-tooltip>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { show: feedback } = useFeedback()
const { t } = useI18n()

const processing = ref(true)

onMounted(async () => {
  const token = String(route.query.token || '')
  const errorReason = String(route.query.error || '')

  // 后端处理失败：根据 reason 显示对应文案后回到登录页
  if (errorReason) {
    processing.value = false
    feedback(t(`oauth.errors.${errorReason}`, { defaultValue: t('oauth.errors.default') }), 'error')
    router.replace('/login')
    return
  }

  if (!token) {
    processing.value = false
    feedback(t('oauth.errors.invalid_callback'), 'error')
    router.replace('/login')
    return
  }

  try {
    const user = await authStore.completeOAuthLogin(token)
    if (user) {
      feedback(t('oauth.welcomeBack', { username: user.username }), 'success')
      router.replace('/')
    } else {
      feedback(t('oauth.errors.fetch_me_failed'), 'error')
      router.replace('/login')
    }
  } catch (error) {
    processing.value = false
    feedback(error.message || t('oauth.errors.fetch_me_failed'), 'error')
    router.replace('/login')
  }
})
</script>

<template>
  <v-container class="d-flex align-center justify-center" style="min-height: 50vh">
    <div class="text-center">
      <v-progress-circular v-if="processing" indeterminate color="primary" size="48" />
      <div class="text-body-1 mt-4">{{ $t('oauth.processing') }}</div>
    </div>
  </v-container>
</template>

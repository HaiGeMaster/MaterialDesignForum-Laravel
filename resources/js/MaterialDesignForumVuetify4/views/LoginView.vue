<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import AuthForm from '../components/AuthForm.vue'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const { show: feedback } = useFeedback()
const { t } = useI18n()

// 登录成功后跳转的目标：仅接受站内路径，避免开放重定向
const redirectTo = computed(() => {
  const r = route.query.redirect
  return typeof r === 'string' && r.startsWith('/') && !r.startsWith('//') ? r : '/'
})

function handleSuccess(message) {
  feedback(message, 'success')
  const target = redirectTo.value
  // 管理后台是独立入口（独立 router），需整页跳转
  if (target === '/admin' || target.startsWith('/admin/')) {
    window.location.assign(target)
    return
  }
  router.replace(target)
}

async function handleLogin(payload) {
  try {
    const result = await authStore.login(payload)
    handleSuccess(t('profile.welcomeBack', { username: result.user.username }))
  } catch (error) {
    feedback(error.message, 'error')
  }
}

async function handleRegister(payload) {
  try {
    const result = await authStore.register(payload)
    handleSuccess(t('profile.registered', { username: result.user.username }))
  } catch (error) {
    feedback(error.message, 'error')
  }
}

async function handleResetPassword(payload) {
  try {
    const result = await authStore.resetPassword(payload)
    handleSuccess(t('profile.passwordReset', { username: result.user.username }))
  } catch (error) {
    feedback(error.message, 'error')
  }
}
</script>

<template>
  <v-container class="d-flex justify-center align-center" >
    <!-- style="min-height: 60vh" -->
    <div style="max-width: 860px; width: 100%">
      <AuthForm
        :loading="authStore.loading"
        @login="handleLogin"
        @register="handleRegister"
        @reset-password="handleResetPassword"
      />
    </div>
  </v-container>
</template>

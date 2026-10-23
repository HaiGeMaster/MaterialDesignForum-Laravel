<script setup>
import { onMounted, onUnmounted, reactive, ref,watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import http from '../api'
import OAuthSocialButtons from './OAuthSocialButtons.vue'
import { useRoute, useRouter } from 'vue-router'
const router = useRouter()

defineProps({
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['login', 'register', 'reset-password'])

const authStore = useAuthStore()
const { show: feedback } = useFeedback()
const { t } = useI18n()

const activeTab = ref('login')
const loginFormRef = ref()
const registerFormRef = ref()
const resetFormRef = ref()

//监听路由哈希值，如果是 register 则切换到注册表单，如果是 reset 则切换到重置密码表单
watch(
  () => router.currentRoute.value.hash,
  (hash) => {
    if (hash === '#register') {
      activeTab.value = 'register'
    } else if (hash === '#reset') {
      activeTab.value = 'reset'
    } else {
      activeTab.value = 'login'
    }
  },
  { immediate: true },
)

//监听activeTab的变化，如果是 register 则切换路由哈希
watch(
  () => activeTab.value,
  (tab) => {
    if (tab === 'register') {
      router.replace({ hash: '#register' })
    } else if (tab === 'reset') {
      router.replace({ hash: '#reset' })
    } else {
      router.replace({ hash: '' })
    }
  },
  { immediate: true },
)

const loginForm = reactive({ email: '', password: '' })
const registerForm = reactive({
  username: '',
  email: '',
  email_code: '',
  password: '',
  password_confirmation: '',
})
const resetForm = reactive({
  email: '',
  email_code: '',
  password: '',
  password_confirmation: '',
})

const codeSending = ref(false)
const codeCooldown = ref(0)
let codeTimer = null

// 后端启用的 OAuth 提供方（仅显示后端已配置 client_id/secret 的图标按钮）
const oauthProviders = ref([])

const rules = {
  required: (v) => !!v || t('auth.rules.required'),
  email: (v) => !v || /.+@.+\..+/.test(v) || t('auth.rules.email'),
  username: (v) => !v || v.length >= 2 || t('auth.rules.username'),
  password: (v) => !v || v.length >= 6 || t('auth.rules.password'),
  confirmRegister: (v) => v === registerForm.password || t('auth.rules.confirmMismatch'),
  confirmReset: (v) => v === resetForm.password || t('auth.rules.confirmMismatch'),
  code: (v) => !v || /^\d{6}$/.test(v) || t('auth.rules.code'),
}

async function handleLogin() {
  const { valid } = await loginFormRef.value.validate()
  if (!valid) return
  emit('login', { ...loginForm })
}

async function handleRegister() {
  const { valid } = await registerFormRef.value.validate()
  if (!valid) return
  emit('register', { ...registerForm })
}

async function handleResetPassword() {
  const { valid } = await resetFormRef.value.validate()
  if (!valid) return
  emit('reset-password', { ...resetForm })
}

// 跳转到后端 OAuth 重定向端点（浏览器整页跳转，由后端再 302 到第三方授权页）
function loginWithOAuth(provider) {
  window.location.href = `/oauth/redirect/${provider}`
}

function startCooldown() {
  codeCooldown.value = 60
  codeTimer = setInterval(() => {
    codeCooldown.value -= 1
    if (codeCooldown.value <= 0) {
      clearInterval(codeTimer)
      codeTimer = null
      codeCooldown.value = 0
    }
  }, 1000)
}

async function handleSendCode() {
  const email = activeTab.value === 'reset' ? resetForm.email : registerForm.email
  if (!email || rules.email(email) !== true) {
    feedback(t('auth.emailInvalid'), 'error')
    return
  }

  codeSending.value = true
  try {
    if (activeTab.value === 'reset') {
      await authStore.sendResetCode(email)
    } else {
      await authStore.sendEmailCode(email)
    }
    feedback(t('auth.codeSent'), 'success')

    // 开发模式下不启动倒计时，便于反复测试
    if (!import.meta.env.DEV) {
      startCooldown()
    }

  } catch (error) {
    feedback(error.message, 'error')
  } finally {
    codeSending.value = false
  }
}

async function loadOAuthProviders() {
  try {
    const { data } = await http.get('/oauth/providers')
    oauthProviders.value = Array.isArray(data?.providers) ? data.providers : []
  } catch (e) {
    oauthProviders.value = []
  }
}

onMounted(() => {
  loadOAuthProviders()
})

onUnmounted(() => {
  if (codeTimer) clearInterval(codeTimer)
})
</script>

<template>
  <v-card rounded="lg" >
    <v-tabs v-model="activeTab" color="primary" grow>
      <v-tab value="login">{{ $t('auth.login') }}</v-tab>
      <v-tab value="register">{{ $t('auth.register') }}</v-tab>
      <v-tab value="reset">{{ $t('auth.forgotPassword') }}</v-tab>
    </v-tabs>

    <v-card-text class="pa-6">
      <!-- 登录 -->
      <v-form v-if="activeTab === 'login'" ref="loginFormRef" @submit.prevent="handleLogin">
        <v-text-field color="primary"
          v-model="loginForm.email"
          :label="$t('auth.email')"
          type="email"
          prepend-inner-icon="mdi-email"
          :rules="[rules.required, rules.email]"
          autocomplete="email"
        />
        <v-text-field color="primary"
          v-model="loginForm.password"
          :label="$t('auth.password')"
          type="password"
          prepend-inner-icon="mdi-lock"
          :rules="[rules.required]"
          autocomplete="current-password"
        />
        <v-btn size="large" type="submit" color="primary" block class="mt-2" :loading="loading">
          {{ $t('auth.login') }}
        </v-btn>
        <OAuthSocialButtons v-if="oauthProviders.length" :providers="oauthProviders" @select="loginWithOAuth" />
      </v-form>

      <!-- 注册 -->
      <v-form v-else-if="activeTab === 'register'" ref="registerFormRef" @submit.prevent="handleRegister">
        <v-text-field color="primary"
          v-model="registerForm.username"
          :label="$t('auth.username')"
          prepend-inner-icon="mdi-account"
          :rules="[rules.required, rules.username]"
          autocomplete="username"
        />
        <v-text-field color="primary"
          v-model="registerForm.email"
          :label="$t('auth.email')"
          type="email"
          prepend-inner-icon="mdi-email"
          :rules="[rules.required, rules.email]"
          autocomplete="email"
        />
        <v-text-field color="primary"
          v-model="registerForm.email_code"
          :label="$t('auth.emailCode')"
          prepend-inner-icon="mdi-shield-key-outline"
          :rules="[rules.required, rules.code]"
          autocomplete="one-time-code"
        >
          <template #append>
            <v-btn
              type="button"
              variant="text"
              color="primary"
              :disabled="codeCooldown > 0 || codeSending"
              :loading="codeSending"
              @click="handleSendCode"
            >
              {{ codeCooldown > 0 ? $t('auth.resendIn', { s: codeCooldown }) : $t('auth.getCode') }}
            </v-btn>
          </template>
        </v-text-field>
        <v-text-field color="primary"
          v-model="registerForm.password"
          :label="$t('auth.password')"
          type="password"
          prepend-inner-icon="mdi-lock"
          :rules="[rules.required, rules.password]"
          autocomplete="new-password"
        />
        <v-text-field color="primary"
          v-model="registerForm.password_confirmation"
          :label="$t('auth.confirmPassword')"
          type="password"
          prepend-inner-icon="mdi-lock"
          :rules="[rules.confirmRegister]"
          autocomplete="new-password"
        />
        <v-btn size="large" type="submit" color="primary" block class="mt-2" :loading="loading">
          {{ $t('auth.register') }}
        </v-btn>
        <OAuthSocialButtons v-if="oauthProviders.length" :providers="oauthProviders" @select="loginWithOAuth" />
      </v-form>

      <!-- 找回密码 -->
      <v-form v-else ref="resetFormRef" @submit.prevent="handleResetPassword">
        <v-text-field color="primary"
          v-model="resetForm.email"
          :label="$t('auth.email')"
          type="email"
          prepend-inner-icon="mdi-email"
          :rules="[rules.required, rules.email]"
          autocomplete="email"
        />
        <v-text-field color="primary"
          v-model="resetForm.email_code"
          :label="$t('auth.emailCode')"
          prepend-inner-icon="mdi-shield-key-outline"
          :rules="[rules.required, rules.code]"
          autocomplete="one-time-code"
        >
          <template #append>
            <v-btn
              type="button"
              variant="text"
              color="primary"
              :disabled="codeCooldown > 0 || codeSending"
              :loading="codeSending"
              @click="handleSendCode"
            >
              {{ codeCooldown > 0 ? $t('auth.resendIn', { s: codeCooldown }) : $t('auth.getCode') }}
            </v-btn>
          </template>
        </v-text-field>
        <v-text-field color="primary"
          v-model="resetForm.password"
          :label="$t('auth.newPassword')"
          type="password"
          prepend-inner-icon="mdi-lock"
          :rules="[rules.required, rules.password]"
          autocomplete="new-password"
        />
        <v-text-field color="primary"
          v-model="resetForm.password_confirmation"
          :label="$t('auth.confirmNewPassword')"
          type="password"
          prepend-inner-icon="mdi-lock"
          :rules="[rules.confirmReset]"
          autocomplete="new-password"
        />
        <v-btn size="large" type="submit" color="primary" block class="mt-2" :loading="loading">
          {{ $t('auth.resetPassword') }}
        </v-btn>
      </v-form>
    </v-card-text>
  </v-card>
</template>

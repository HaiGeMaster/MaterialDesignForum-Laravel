<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useUserStore } from '../stores/user'
import { useFeedback } from '../composables/useFeedback'
import http from '../api'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const authStore = useAuthStore()
const userStore = useUserStore()
const router = useRouter()
const route = useRoute()
const { show: feedback } = useFeedback()
const { t } = useI18n()

const isLoggedIn = computed(() => authStore.isLoggedIn)

// 当前展示的区块：list=选项列表 / profile=编辑个人资料 / password=修改密码 / oauth=第三方绑定
const section = ref('list')
const sessionTokens = ref([])
const sessionLoading = ref(false)
const sessionDeleting = ref(false)

// 未登录直接回登录页
watch(
  isLoggedIn,
  (val) => {
    if (!val) router.replace('/login')
  },
  { immediate: true },
)

// ---------- 修改密码 ----------
const passwordFormRef = ref()
const passwordForm = reactive({
  old_password: '',
  password: '',
  password_confirmation: '',
})

const passwordRules = {
  old_password: (v) => !!v || t('settings.rules.oldPassword'),
  password: (v) => (v && v.length >= 6) || t('settings.rules.password'),
  confirm: (v) => v === passwordForm.password || t('settings.rules.confirm'),
}

async function handleUpdatePassword() {
  const { valid } = await passwordFormRef.value.validate()
  if (!valid) return
  try {
    const result = await userStore.updatePassword({
      old_password: passwordForm.old_password,
      password: passwordForm.password,
      password_confirmation: passwordForm.password_confirmation,
    })
    feedback(result.message || t('settings.passwordUpdated'), 'success')
    passwordForm.old_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (error) {
    feedback(error.message, 'error')
  }
}

// ---------- 第三方账号绑定 ----------
const OAUTH_META = {
  github: { icon: 'mdi-github', label: 'GitHub' },
  google: { icon: 'mdi-google', label: 'Google' },
  microsoft: { icon: 'mdi-microsoft', label: 'Microsoft' },
}

const oauthLoading = ref(false)
const oauthUnbinding = ref(null)
// 后端已启用的 provider 列表（决定能展示几个绑定入口）
const enabledProviders = ref([])
// 当前用户已绑定的记录：[{ oauth_id, oauth_name, oauth_user_id, oauth_user_name, oauth_user_email }]
const oauthBindings = ref([])

const oauthRows = computed(() => {
  return enabledProviders.value.map((provider) => {
    const meta = OAUTH_META[provider] || { icon: 'mdi-account', label: provider }
    const bound = oauthBindings.value.find((b) => b.oauth_name === provider)
    return {
      provider,
      icon: meta.icon,
      label: meta.label,
      bound,
    }
  })
})

async function loadSessionTokens() {
  sessionLoading.value = true
  try {
    const { data } = await http.get('/user/tokens')
    sessionTokens.value = Array.isArray(data?.data) ? data.data : []
  } catch (error) {
    sessionTokens.value = []
    feedback(error.message || t('settings.deviceSessionsLoadFailed'), 'error')
  } finally {
    sessionLoading.value = false
  }
}

async function revokeSessionToken(token) {
  if (!token) return
  try {
    sessionDeleting.value = token
    await http.delete(`/user/tokens/${encodeURIComponent(token)}`)
    await loadSessionTokens()
    feedback(t('settings.deviceSessionRevoked'), 'success')
  } catch (error) {
    feedback(error.message || t('settings.deviceSessionRevokeFailed'), 'error')
  } finally {
    sessionDeleting.value = ''
  }
}

async function revokeOtherSessions() {
  try {
    sessionDeleting.value = 'all'
    await http.delete('/user/tokens')
    await loadSessionTokens()
    feedback(t('settings.deviceSessionsRevoked'), 'success')
  } catch (error) {
    feedback(error.message || t('settings.deviceSessionsRevokeFailed'), 'error')
  } finally {
    sessionDeleting.value = ''
  }
}

async function loadEnabledProviders() {
  try {
    const { data } = await http.get('/oauth/providers')
    enabledProviders.value = Array.isArray(data?.providers) ? data.providers : []
  } catch (e) {
    enabledProviders.value = []
  }
}

async function loadOauthBindings() {
  oauthLoading.value = true
  try {
    const { data } = await http.get('/user/oauth')
    oauthBindings.value = Array.isArray(data?.data) ? data.data : []
  } catch (e) {
    oauthBindings.value = []
  } finally {
    oauthLoading.value = false
  }
}

// 发起绑定：跳到后端，由后端 302 到第三方授权页
// 注意：整页跳转浏览器不会带 Authorization 头，必须把 token 作为 query 参数传给后端兜底解析
function bindProvider(provider) {
  const token = authStore.token
  if (!token) {
    feedback(t('oauth.errors.auth_required'), 'error')
    router.replace('/login')
    return
  }
  window.location.href = `/oauth/redirect/${provider}?mode=bind&token=${encodeURIComponent(token)}`
}

async function unbindProvider(row) {
  if (!row?.bound) return
  oauthUnbinding.value = row.provider
  try {
    await http.delete(`/user/oauth/${row.bound.oauth_id}`)
    oauthBindings.value = oauthBindings.value.filter((b) => b.oauth_id !== row.bound.oauth_id)
    feedback(t('oauth.unbindSuccess', { provider: row.label }), 'success')
  } catch (e) {
    feedback(e.message, 'error')
  } finally {
    oauthUnbinding.value = null
  }
}

// 从回调 URL 读取 ?bound=provider / ?error=... 提示用户绑定结果
function consumeCallbackQuery() {
  const bound = route.query.bound
  const errorReason = route.query.error
  if (bound) {
    const meta = OAUTH_META[bound] || { label: bound }
    feedback(t('oauth.bindSuccess', { provider: meta.label }), 'success')
    router.replace({ query: {} })
  } else if (errorReason) {
    feedback(t(`oauth.errors.${errorReason}`, { defaultValue: t('oauth.errors.default') }), 'error')
    router.replace({ query: {} })
  }
}

onMounted(() => {
  loadEnabledProviders()
  loadOauthBindings()
  consumeCallbackQuery()
})

watch(
  () => section.value,
  (value) => {
    if (value === 'sessions') {
      loadSessionTokens()
    }
  },
  { immediate: true }
)
</script>

<template>
  <v-container  :class="xs ? 'px-0' : 'px-4'" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <!-- <v-row justify="center">
      <v-col cols="12" md="10" lg="8"> -->
        <!-- 设置选项列表 -->
        <template v-if="section === 'list'">
          <v-card rounded="lg" >


            <v-toolbar :title="$t('settings.title')">
                <template #prepend>
                <v-btn icon variant="text" @click="router.back()">
                    <v-icon>mdi-arrow-left</v-icon>
                    <v-tooltip activator="parent" location="right">
                    {{ $t('common.back') }}
                    </v-tooltip>
                </v-btn>
                </template>
                <template #append>
                </template>
            </v-toolbar>

            <!-- <v-card-title >
              <v-icon icon="mdi-cog-outline" />
              {{ $t('settings.title') }}
            </v-card-title> -->
            <!-- <v-divider /> -->
            <v-list rounded="lg" >
              <v-list-item
                prepend-icon="mdi-shield-lock"
                :title="$t('settings.changePassword')"
                :subtitle="$t('settings.changePasswordSub')"
                append-icon="mdi-chevron-right"
                class="pa-4"
                @click="section = 'password'"
              />
              <v-divider />
              <v-list-item
                prepend-icon="mdi-monitor-lock"
                :title="$t('settings.deviceSessions')"
                :subtitle="$t('settings.deviceSessionsSub')"
                append-icon="mdi-chevron-right"
                class="pa-4"
                @click="section = 'sessions'"
              />
              <v-divider />
              <v-list-item
                prepend-icon="mdi-account-network-outline"
                :title="$t('oauth.bindingsTitle')"
                :subtitle="$t('oauth.bindingsSub')"
                append-icon="mdi-chevron-right"
                class="pa-4"
                @click="section = 'oauth'"
              />
            </v-list>
          </v-card>
        </template>

        <!-- 登录设备凭证 -->
        <template v-else-if="section === 'sessions'">
          <v-card rounded="lg">
            <v-toolbar :title="$t('settings.deviceSessions')">
              <template #prepend>
                <v-btn icon variant="text" @click="section = 'list'">
                  <v-icon>mdi-arrow-left</v-icon>
                  <v-tooltip activator="parent" location="right">
                    {{ $t('common.back') }}
                  </v-tooltip>
                </v-btn>
              </template>
              <template #append>
                <v-btn
                  v-if="sessionTokens.length > 1"
                  variant="text"
                  :loading="sessionDeleting === 'all'"
                  @click="revokeOtherSessions"
                >
                  {{ $t('settings.revokeOtherSessions') }}
                </v-btn>
              </template>
            </v-toolbar>

            <v-card-text>
              <v-progress-linear v-if="sessionLoading" indeterminate color="primary" class="mb-4" />

              <v-list v-else rounded="lg" lines="two" class="px-0">
                <template v-for="(session, idx) in sessionTokens" :key="session.token">
                  <v-divider v-if="idx" />
                  <v-list-item class="px-0">
                    <template #prepend>
                      <v-avatar color="primary" variant="tonal">
                        <v-icon :icon="session.is_current ? 'mdi-laptop' : 'mdi-cellphone'" />
                      </v-avatar>
                    </template>
                    <v-list-item-title>
                      {{ session.device || $t('settings.unknownDevice') }}
                      <span v-if="session.is_current" class="text-caption text-primary ml-2">{{ $t('settings.currentDevice') }}</span>
                    </v-list-item-title>
                    <v-list-item-subtitle>
                      {{ session.create_time }}
                    </v-list-item-subtitle>
                    <template #append>
                      <v-btn
                        v-if="!session.is_current"
                        variant="text"
                        color="error"
                        :loading="sessionDeleting === session.token"
                        @click="revokeSessionToken(session.token)"
                      >
                        {{ $t('settings.revokeSession') }}
                      </v-btn>
                    </template>
                  </v-list-item>
                </template>
              </v-list>
            </v-card-text>
          </v-card>
        </template>

        <!-- 第三方账号绑定 -->
        <template v-else-if="section === 'oauth'">
          <v-card rounded="lg" >

            <v-toolbar :title="$t('oauth.bindingsTitle')">
                <template #prepend>
                <v-btn icon variant="text" @click="section = 'list'">
                    <v-icon>mdi-arrow-left</v-icon>
                    <v-tooltip activator="parent" location="right">
                    {{ $t('common.back') }}
                    </v-tooltip>
                </v-btn>
                </template>
                <template #append>
                </template>
            </v-toolbar>

            <!-- <v-card-title>
              <v-btn
                icon="mdi-arrow-left"
                size="small"
                variant="text"
                class="mr-1"
                @click="section = 'list'"
              />
              <v-icon icon="mdi-account-network-outline" class="mr-2" />
              {{ $t('oauth.bindingsTitle') }}
            </v-card-title>
            <v-divider /> -->
            <v-card-text>
              <v-progress-linear v-if="oauthLoading" indeterminate color="primary" class="mb-4" />

              <div v-if="!oauthRows.length && !oauthLoading" class="text-body-2 text-medium-emphasis py-4">
                {{ $t('oauth.noProvidersConfigured') }}
              </div>

              <v-list rounded="lg" lines="two" class="px-0">
                <template v-for="(row, idx) in oauthRows" :key="row.provider">
                  <v-divider v-if="idx" />
                  <v-list-item class="px-0">
                    <template #prepend>
                      <v-avatar >
                        <v-icon :icon="row.icon" />
                      </v-avatar>
                    </template>
                    <v-list-item-title>{{ row.label }}</v-list-item-title>
                    <v-list-item-subtitle>
                      <template v-if="row.bound">
                        {{ $t('oauth.boundAs', { name: row.bound.oauth_user_name || row.bound.oauth_user_email || row.bound.oauth_user_id }) }}
                      </template>
                      <template v-else>
                        {{ $t('oauth.notBound') }}
                      </template>
                    </v-list-item-subtitle>
                    <template #append>
                      <v-btn
                        v-if="row.bound"
                        color="error"
                        variant="text"
                        :loading="oauthUnbinding === row.provider"
                        @click="unbindProvider(row)"
                      >
                        {{ $t('oauth.unbind') }}
                      </v-btn>
                      <v-btn
                        v-else
                        color="primary"
                        variant="tonal"
                        @click="bindProvider(row.provider)"
                      >
                        {{ $t('oauth.bind') }}
                      </v-btn>
                    </template>
                  </v-list-item>
                </template>
              </v-list>
            </v-card-text>
          </v-card>
        </template>

        <!-- 修改密码 -->
        <template v-else>
          <v-card rounded="lg" >

            <v-toolbar :title="$t('settings.changePassword')">
                <template #prepend>
                <v-btn icon variant="text" @click="section = 'list'">
                    <v-icon>mdi-arrow-left</v-icon>
                    <v-tooltip activator="parent" location="right">
                    {{ $t('common.back') }}
                    </v-tooltip>
                </v-btn>
                </template>
                <template #append>
                </template>
            </v-toolbar>

            <!-- <v-card-title>
              <v-btn
                icon="mdi-arrow-left"
                size="small"
                variant="text"
                class="mr-1"
                @click="section = 'list'"
              />
              <v-icon icon="mdi-shield-lock" class="mr-2" />
              {{ $t('settings.changePassword') }}
            </v-card-title>
            <v-divider /> -->
            <v-card-text>
              <v-form ref="passwordFormRef" @submit.prevent="handleUpdatePassword">
                <v-text-field color="primary" 
                  v-model="passwordForm.old_password"
                  :label="$t('settings.oldPassword')"
                  type="password"
                  prepend-inner-icon="mdi-lock"
                  :rules="[passwordRules.old_password]"
                  autocomplete="current-password"
                />
                <v-text-field color="primary" 
                  v-model="passwordForm.password"
                  :label="$t('settings.newPassword')"
                  type="password"
                  prepend-inner-icon="mdi-lock"
                  :rules="[passwordRules.password]"
                  autocomplete="new-password"
                />
                <v-text-field color="primary" 
                  v-model="passwordForm.password_confirmation"
                  :label="$t('settings.confirmNewPassword')"
                  type="password"
                  prepend-inner-icon="mdi-lock"
                  :rules="[passwordRules.confirm]"
                  autocomplete="new-password"
                />
                <v-btn type="submit" color="primary" prepend-icon="mdi-shield-key" :loading="userStore.loading">
                  {{ $t('settings.changePasswordBtn') }}
                </v-btn>
              </v-form>
            </v-card-text>
          </v-card>
        </template>

      <!-- </v-col>
    </v-row> -->
  </v-container>
</template>

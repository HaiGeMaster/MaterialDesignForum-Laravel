<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import { useDisplay } from 'vuetify'
import http from '../api'

const OAUTH_META = {
  github: { icon: 'mdi-github', label: 'GitHub' },
  google: { icon: 'mdi-google', label: 'Google' },
  microsoft: { icon: 'mdi-microsoft', label: 'Microsoft' },
}

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const { show: feedback } = useFeedback()
const { t } = useI18n()
const { xs } = useDisplay()

const oauthLoading = ref(false)
const oauthUnbinding = ref(null)
const enabledProviders = ref([])
const oauthBindings = ref([])

const oauthRows = computed(() => {
  return enabledProviders.value.map((provider) => {
    const meta = OAUTH_META[provider] || { icon: 'mdi-account', label: provider }
    const bound = oauthBindings.value.find((binding) => binding.oauth_name === provider)
    return {
      provider,
      icon: meta.icon,
      label: meta.label,
      bound,
    }
  })
})

async function loadEnabledProviders() {
  try {
    const { data } = await http.get('/oauth/providers')
    enabledProviders.value = Array.isArray(data?.providers) ? data.providers : []
  } catch (error) {
    enabledProviders.value = []
    feedback(error.message || t('oauth.errors.default'), 'error')
  }
}

async function loadOauthBindings() {
  oauthLoading.value = true
  try {
    const { data } = await http.get('/user/oauth')
    oauthBindings.value = Array.isArray(data?.data) ? data.data : []
  } catch (error) {
    oauthBindings.value = []
    feedback(error.message || t('oauth.errors.default'), 'error')
  } finally {
    oauthLoading.value = false
  }
}

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
    oauthBindings.value = oauthBindings.value.filter((binding) => binding.oauth_id !== row.bound.oauth_id)
    feedback(t('oauth.unbindSuccess', { provider: row.label }), 'success')
  } catch (error) {
    feedback(error.message, 'error')
  } finally {
    oauthUnbinding.value = null
  }
}

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
</script>

<template>
  <v-container
    :class="xs ? 'px-0' : 'px-4'"
    :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}"
  >
    <v-card rounded="lg">
      <v-toolbar :title="$t('oauth.bindingsTitle')">
        <template #prepend>
          <v-btn icon variant="text" @click="router.push({ name: 'settings' })">
            <v-icon>mdi-arrow-left</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.back') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

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
                <v-avatar>
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
  </v-container>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../stores/auth'
import { useSettingsOAuthViewStore, OAUTH_META } from '../stores/views/settingsOAuth'
import { useFeedback } from '../composables/useFeedback'
import { useDisplay } from 'vuetify'

const authStore = useAuthStore()
const oauthStore = useSettingsOAuthViewStore()
const { oauthRows, loading: oauthLoading, unbinding: oauthUnbinding } = storeToRefs(oauthStore)
const router = useRouter()
const route = useRoute()
const { show: feedback } = useFeedback()
const { t } = useI18n()
const { xs } = useDisplay()

async function loadEnabledProviders() {
  try {
    await oauthStore.fetchProviders()
  } catch (error) {
    feedback(error.message || t('oauth.errors.default'), 'error')
  }
}

async function loadOauthBindings() {
  try {
    await oauthStore.fetchBindings()
  } catch (error) {
    feedback(error.message || t('oauth.errors.default'), 'error')
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
  try {
    await oauthStore.unbind(row)
    feedback(t('oauth.unbindSuccess', { provider: row.label }), 'success')
  } catch (error) {
    feedback(error.message, 'error')
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

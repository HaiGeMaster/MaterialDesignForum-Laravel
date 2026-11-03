<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { storeToRefs } from 'pinia'
import { useFeedback } from '../composables/useFeedback'
import { useDisplay } from 'vuetify'
import { useSettingsSessionsViewStore } from '../stores/views/settingsSessions'

const router = useRouter()
const { show: feedback } = useFeedback()
const { t } = useI18n()
const { xs } = useDisplay()

const sessionStore = useSettingsSessionsViewStore()
const {
  sessionTokens,
  loading: sessionLoading,
  deleting: sessionDeleting,
} = storeToRefs(sessionStore)

async function loadSessionTokens() {
  try {
    await sessionStore.fetchSessions()
  } catch (error) {
    feedback(error.message || t('settings.deviceSessionsLoadFailed'), 'error')
  }
}

async function revokeSessionToken(token) {
  try {
    await sessionStore.revokeSession(token)
    feedback(t('settings.deviceSessionRevoked'), 'success')
  } catch (error) {
    feedback(error.message || t('settings.deviceSessionRevokeFailed'), 'error')
  }
}

async function revokeOtherSessions() {
  try {
    await sessionStore.revokeOtherSessions()
    feedback(t('settings.deviceSessionsRevoked'), 'success')
  } catch (error) {
    feedback(error.message || t('settings.deviceSessionsRevokeFailed'), 'error')
  }
}

onMounted(loadSessionTokens)
</script>

<template>
  <v-container
    :class="xs ? 'px-0' : 'px-4'"
    :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}"
  >
    <v-card rounded="lg">
      <v-toolbar :title="$t('settings.deviceSessions')">
        <template #prepend>
          <v-btn icon variant="text" @click="router.push({ name: 'settings' })">
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
                <span v-if="session.is_current" class="text-caption text-primary ml-2">
                  {{ $t('settings.currentDevice') }}
                </span>
              </v-list-item-title>
              <v-list-item-subtitle>{{ session.create_time }}</v-list-item-subtitle>
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
  </v-container>
</template>

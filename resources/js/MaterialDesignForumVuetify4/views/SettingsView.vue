<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'

const router = useRouter()
const route = useRoute()
const { xs } = useDisplay()

onMounted(() => {
  if (route.query.bound || route.query.error) {
    router.replace({ name: 'settings-oauth', query: route.query })
  }
})
</script>

<template>
  <v-container
    :class="xs ? 'px-0' : 'px-4'"
    :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}"
  >
    <v-card rounded="lg">
      <v-toolbar :title="$t('settings.title')">
        <template #prepend>
          <v-btn icon variant="text" @click="router.back()">
            <v-icon>mdi-arrow-left</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.back') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-list rounded="lg">
        <v-list-item
          prepend-icon="mdi-shield-lock"
          :title="$t('settings.changePassword')"
          :subtitle="$t('settings.changePasswordSub')"
          append-icon="mdi-chevron-right"
          class="pa-4"
          @click="router.push({ name: 'settings-password' })"
        />
        <v-divider />
        <v-list-item
          prepend-icon="mdi-monitor-lock"
          :title="$t('settings.deviceSessions')"
          :subtitle="$t('settings.deviceSessionsSub')"
          append-icon="mdi-chevron-right"
          class="pa-4"
          @click="router.push({ name: 'settings-sessions' })"
        />
        <v-divider />
        <v-list-item
          prepend-icon="mdi-account-network-outline"
          :title="$t('oauth.bindingsTitle')"
          :subtitle="$t('oauth.bindingsSub')"
          append-icon="mdi-chevron-right"
          class="pa-4"
          @click="router.push({ name: 'settings-oauth' })"
        />
      </v-list>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed } from 'vue'
import { useDisplay } from 'vuetify'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import LanguageSwitcher from './LanguageSwitcher.vue'
import ThemeToggle from './ThemeToggle.vue'
import { siteName } from '../themeConfig'

const emit = defineEmits(['toggle-drawer'])

const authStore = useAuthStore()
const { mobile } = useDisplay()
const { t } = useI18n()

const profilePath = computed(() => `/users/${authStore.user?.user_id}`)

function userInitial() {
  return authStore.user?.username?.slice(0, 1).toUpperCase() || '?'
}

async function handleLogout() {
  await authStore.logout()
  window.location.assign('/')
}

</script>

<template>
  <v-app-bar color="surface" elevation="1">
    <v-app-bar-nav-icon @click="emit('toggle-drawer')" />
    <v-app-bar-title>
      <!-- <v-icon icon="mdi-shield-crown-outline" class="mr-2" /> -->
      {{ siteName }} Admin
    </v-app-bar-title>
    <v-spacer />

    <!-- 语言切换 -->
    <LanguageSwitcher />

    <!-- 深色模式切换 -->
    <ThemeToggle />

    <v-menu min-width="220">
      <template #activator="{ props }">
        <v-btn v-if="!mobile" variant="text" v-bind="props">
          <v-avatar size="30" color="primary" class="mr-2">
            <v-img v-if="authStore.user?.avatar" :src="authStore.user.avatar" cover />
            <span v-else class="text-caption">{{ userInitial() }}</span>
          </v-avatar>
          {{ authStore.user?.username }}
          <v-icon icon="mdi-chevron-down" size="small" class="ml-1" />
        </v-btn>
        <v-btn v-else icon variant="text" v-bind="props">
          <v-avatar size="40" color="primary">
            <v-img v-if="authStore.user?.avatar" :src="authStore.user.avatar" cover />
            <span v-else class="text-caption">{{ userInitial() }}</span>
          </v-avatar>
        </v-btn>
      </template>

      <v-list density="compact" nav>
        <v-list-subheader>{{ authStore.user?.username }}</v-list-subheader>
        <v-divider class="my-1" />
        <v-list-item
          :href="profilePath"
          prepend-icon="mdi-account"
          :title="$t('nav.profile')"
        />
        <v-list-item href="/settings" prepend-icon="mdi-cog" :title="$t('appBar.settings')" />
        <v-list-item
          prepend-icon="mdi-open-in-new"
          href="/"
          :title="$t('appBar.returnHome')"
        />
        <v-divider class="my-1" />
        <v-list-item
          prepend-icon="mdi-logout"
          :title="$t('appBar.logout')"
          @click="handleLogout"
        />
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

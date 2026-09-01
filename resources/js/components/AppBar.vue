<script setup>
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import LanguageSwitcher from './LanguageSwitcher.vue'
import ThemeToggle from './ThemeToggle.vue'
import NotificationBell from './NotificationBell.vue'
import AppTabBar from './AppTabBar.vue'
import UserMenu from './UserMenu.vue'
import { useTabBar } from '../composables/useTabBar'
import { siteName } from '../themeConfig'

const emit = defineEmits(['toggle-drawer'])

const authStore = useAuthStore()
const { t } = useI18n()
const { visible: showTabBar } = useTabBar()

async function handleLogout() {
  try {
    await authStore.logout()
  } catch (error) {
    // 即使接口失败也清理本地登录态
  }
  useFeedback().show(t('appBar.logoutSuccess'), 'info')
}

</script>

<template>
  <v-app-bar density="comfortable">
    <v-app-bar-nav-icon @click="emit('toggle-drawer')" />

    <v-app-bar-title>
      <!-- <v-icon icon="mdi-forum" class="mr-2" /> -->
      {{ siteName }}
    </v-app-bar-title>

    <v-spacer />

    <LanguageSwitcher />

    <NotificationBell />

    <ThemeToggle />

    <UserMenu
      v-if="authStore.isLoggedIn"
      :avatar-size="28"
      link-mode="router"
      show-media-library
      show-admin
      @logout="handleLogout"
    />

    <v-btn v-else variant="tonal" to="/login" prepend-icon="mdi-account">
      {{ $t('appBar.loginRegister') }}
    </v-btn>

    <!-- 路由页标签栏：渲染在应用栏扩展区，随应用栏固定；无 tab 时不渲染扩展区 -->
    <template v-if="showTabBar" #extension>
      <AppTabBar />
    </template>
  </v-app-bar>
</template>

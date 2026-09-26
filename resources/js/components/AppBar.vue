<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDisplay } from 'vuetify'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import LanguageSwitcher from './LanguageSwitcher.vue'
import ThemeToggle from './ThemeToggle.vue'
import NotificationBell from './NotificationBell.vue'
import GuestMenu from './GuestMenu.vue'
import AppTabBar from './AppTabBar.vue'
import UserMenu from './UserMenu.vue'
import { useTabBar } from '../composables/useTabBar'
import { useSearchWindowStore } from '../stores/searchWindow'
import { siteName } from '../themeConfig'

const emit = defineEmits(['toggle-drawer'])

const authStore = useAuthStore()
const { t } = useI18n()
const { smAndDown } = useDisplay()
const { visible: showTabBar } = useTabBar()
const searchWindow = useSearchWindowStore()

function toggleSearch() {
  if (searchWindow.isOpen) {
    searchWindow.close()
    return
  }

  searchWindow.open()
}

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
  <v-app-bar>
    <v-app-bar-nav-icon @click="emit('toggle-drawer')" />

    <v-img v-if="smAndDown" src="/favicon.png" alt="site logo" width="24" height="24" style="width: 24px;max-width: 24px;" />
    <v-app-bar-title v-else>
      {{ siteName }}
    </v-app-bar-title>

    <!-- <v-app-bar-title>
      <template v-if="xs">
        <v-img
          src="/favicon.png"
          alt="site logo"
          width="24"
          height="24"
          cover
        />
      </template>
      <template v-else>
        {{ siteName }}
      </template>
    </v-app-bar-title> -->

    <v-spacer />

    <v-btn
      icon
      variant="text"
      :aria-label="$t('common.search')"
      @click="toggleSearch"
    >
      <v-icon icon="mdi-magnify" />
    </v-btn>

    <LanguageSwitcher />

    <ThemeToggle />

    <NotificationBell />

    <UserMenu
      v-if="authStore.isLoggedIn"
      :avatar-size="28"
      link-mode="router"
      show-media-library
      show-admin
      @logout="handleLogout"
    />

    <!-- <v-btn v-else variant="tonal" to="/login" prepend-icon="mdi-account">
      {{ $t('appBar.loginRegister') }}
    </v-btn> -->

    <GuestMenu v-if="!authStore.isLoggedIn"/>

    <!-- 路由页标签栏：渲染在应用栏扩展区，随应用栏固定；无 tab 时不渲染扩展区 -->
    <template v-if="showTabBar" #extension>
      <AppTabBar />
    </template>
  </v-app-bar>
</template>

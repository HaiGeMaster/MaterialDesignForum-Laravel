<script setup>
import { useAuthStore } from '../stores/auth'
import LanguageSwitcher from './LanguageSwitcher.vue'
import ThemeToggle from './ThemeToggle.vue'
import UserMenu from './UserMenu.vue'
import { siteName } from '../themeConfig'

const emit = defineEmits(['toggle-drawer'])

const authStore = useAuthStore()

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

    <UserMenu
      avatar-color="primary"
      :avatar-size="30"
      link-mode="href"
      show-return-home
      @logout="handleLogout"
    />
  </v-app-bar>
</template>

<script setup>
import { useDisplay } from 'vuetify'
import { useAuthStore } from '../stores/auth'
import LanguageSwitcher from './LanguageSwitcher.vue'
import ThemeToggle from './ThemeToggle.vue'
import UserMenu from './UserMenu.vue'
import { siteName } from '../themeConfig'

const emit = defineEmits(['toggle-drawer'])

const authStore = useAuthStore()
const { smAndDown } = useDisplay()

async function handleLogout() {
  await authStore.logout()
  window.location.assign('/')
}

</script>

<template>
    <!-- color="surface" elevation="1" -->
     <!-- scroll-behavior="elevate" -->
  <v-app-bar>
    <v-app-bar-nav-icon @click="emit('toggle-drawer')" />

    <v-img v-if="smAndDown" src="/favicon.png" alt="site logo" width="24" height="24" style="width: 24px;max-width: 24px;" />
    <v-app-bar-title v-else>
      {{ siteName }} Admin
    </v-app-bar-title>
    <v-spacer />

    <!-- 语言切换 -->
    <LanguageSwitcher />

    <!-- 深色模式切换 -->
    <ThemeToggle />

    <UserMenu avatar-color="primary" :avatar-size="30" link-mode="href" show-return-home @logout="handleLogout" />
  </v-app-bar>
</template>

<script setup>
import { computed } from 'vue'
import { useDisplay } from 'vuetify'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import LanguageSwitcher from './LanguageSwitcher.vue'
import ThemeToggle from './ThemeToggle.vue'
import NotificationBell from './NotificationBell.vue'
import AppTabBar from './AppTabBar.vue'
import { useTabBar } from '../composables/useTabBar'
import { siteName } from '../themeConfig'

const emit = defineEmits(['toggle-drawer'])

const authStore = useAuthStore()
const { mobile } = useDisplay()
const { t } = useI18n()
const { visible: showTabBar } = useTabBar()

const profilePath = computed(() =>
  authStore.isLoggedIn ? `/users/${authStore.user?.user_id}` : '/users/me',
)

const userInitial = () => authStore.user?.username?.slice(0, 1).toUpperCase() || '?'

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
      <v-icon icon="mdi-forum" class="mr-2" />
      {{ siteName }}
    </v-app-bar-title>

    <v-spacer />

    <LanguageSwitcher />

    <NotificationBell />

    <ThemeToggle />

    <template v-if="authStore.isLoggedIn">
      <v-menu  min-width="200">
        <template #activator="{ props }">
          <v-btn v-if="!mobile" variant="text" v-bind="props">
            <v-avatar size="28" color="secondary" class="mr-2">
              <v-img
                v-if="authStore.user?.avatar"
                :src="authStore.user.avatar"
                cover
                class="cursor-pointer"
              />
              <span v-else class="text-caption">{{ userInitial() }}</span>
            </v-avatar>
            {{ authStore.user?.username }}
            <v-icon icon="mdi-chevron-down" size="small" class="ml-1" />
          </v-btn>
          <v-btn v-else icon variant="text" v-bind="props">
            <v-avatar size="40" color="secondary">
              <v-img
                v-if="authStore.user?.avatar"
                :src="authStore.user.avatar"
                cover
                class="cursor-pointer"
              />
              <span v-else class="text-caption">{{ userInitial() }}</span>
            </v-avatar>
          </v-btn>
        </template>
        <v-list density="compact" nav>
          <v-list-subheader>{{ authStore.user?.username }}</v-list-subheader>
          <v-divider class="my-1" />
          <v-list-item :to="profilePath" prepend-icon="mdi-account" :title="$t('nav.profile')" />
          <v-list-item to="/settings" prepend-icon="mdi-cog" :title="$t('appBar.settings')" />
          <v-list-item
            v-if="authStore.canAccessAdmin"
            href="/admin/"
            prepend-icon="mdi-shield-crown-outline"
            :title="$t('nav.admin')"
          />
          <v-divider class="my-1" />
          <v-list-item prepend-icon="mdi-logout" :title="$t('appBar.logout')" @click="handleLogout" />
        </v-list>
      </v-menu>
    </template>

    <v-btn v-else variant="tonal" to="/users/me" prepend-icon="mdi-account">
      {{ $t('appBar.loginRegister') }}
    </v-btn>

    <!-- 路由页标签栏：渲染在应用栏扩展区，随应用栏固定；无 tab 时不渲染扩展区 -->
    <template v-if="showTabBar" #extension>
      <AppTabBar />
    </template>
  </v-app-bar>
</template>

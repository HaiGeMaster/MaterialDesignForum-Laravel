<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDisplay } from 'vuetify'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  // 桌面端头像尺寸（移动端固定 40）
  avatarSize: { type: Number, default: 30 },
  // 头像底色，如 'primary'
  avatarColor: { type: String, default: '' },
  // 菜单宽度
  menuWidth: { type: [Number, String], default: 220 },
  // 菜单项跳转方式：router=站内 SPA 路由（to）；href=整页跳转（独立入口应用）
  linkMode: {
    type: String,
    default: 'router',
    validator: (v) => ['router', 'href'].includes(v),
  },
  // 是否显示「媒体库」菜单项（仅站内 SPA 有意义）
  showMediaLibrary: { type: Boolean, default: false },
  // 是否显示「管理后台」菜单项（登录且有权限时）
  showAdmin: { type: Boolean, default: false },
  // 是否显示「返回首页」菜单项（管理端入口用）
  showReturnHome: { type: Boolean, default: false },
})

const emit = defineEmits(['logout'])

const { t } = useI18n()
const { mobile } = useDisplay()
const authStore = useAuthStore()

const profilePath = computed(() => `/users/${authStore.user?.user_id}`)
const userInitial = () => authStore.user?.username?.slice(0, 1).toUpperCase() || '?'

// 按链接模式生成 v-list-item 的跳转属性（to / href 二选一）
function navAttrs(path) {
  return props.linkMode === 'router' ? { to: path } : { href: path }
}

function handleLogout() {
  emit('logout')
}
</script>

<template>
  <v-menu :min-width="menuWidth">
    <template #activator="{ props }">
      <!-- 只显示头像，点击弹出用户菜单 -->
      <v-btn icon variant="text" v-bind="props">
        <v-avatar :size="mobile ? 40 : avatarSize" >
          <v-img
            v-if="authStore.user?.avatar"
            :src="authStore.user.avatar"
            :alt="authStore.user?.username"
            cover
          />
          <span v-else class="text-caption">{{ userInitial() }}</span>
        </v-avatar>
      </v-btn>
    </template>

    <v-list rounded="lg" density="compact" nav>
      <v-list-subheader>{{ authStore.user?.username || '' }}</v-list-subheader>
      <v-divider class="my-1" />
      <v-list-item v-bind="navAttrs(profilePath)" prepend-icon="mdi-account" :title="t('nav.profile')" />
      <!-- <v-list-item v-bind="navAttrs('/sign-in')" prepend-icon="mdi-calendar-check" :title="t('nav.signIn')" /> -->
      <v-list-item
        v-if="showMediaLibrary"
        v-bind="navAttrs('/media')"
        prepend-icon="mdi-image-multiple-outline"
        :title="t('nav.mediaLibrary')"
      />
      <v-list-item v-bind="navAttrs('/settings')" prepend-icon="mdi-cog" :title="t('appBar.settings')" />
      <v-list-item
        v-if="showAdmin && authStore.canAccessAdmin"
        href="/admin/"
        prepend-icon="mdi-shield-crown-outline"
        :title="t('nav.admin')"
      />
      <v-list-item
        v-if="showReturnHome"
        href="/"
        prepend-icon="mdi-open-in-new"
        :title="t('appBar.returnHome')"
      />
      <v-divider class="my-1" />
      <v-list-item prepend-icon="mdi-logout" :title="t('appBar.logout')" @click="handleLogout" />
    </v-list>
  </v-menu>
</template>

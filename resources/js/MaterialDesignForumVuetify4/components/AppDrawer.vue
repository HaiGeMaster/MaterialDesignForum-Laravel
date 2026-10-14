<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import DrawerCopyright from './DrawerCopyright.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: null },
})
const emit = defineEmits(['update:modelValue'])

const authStore = useAuthStore()
const { t } = useI18n()

const profilePath = computed(() =>
  authStore.isLoggedIn ? `/users/${authStore.user?.user_id}` : '/login',
)

// 内容导航：始终显示
const contentNavItems = computed(() => [
  { title: t('nav.home'), icon: 'mdi-home', to: '/' },
  { title: t('nav.topics'), icon: 'mdi-book-variant', to: '/topics' },
  { title: t('nav.questions'), icon: 'mdi-forum', to: '/questions' },
  { title: t('nav.articles'), icon: 'mdi-file-document', to: '/articles' },
])

// 账号导航：人脉始终显示，个人资料、通知仅在登录后显示
const accountNavItems = computed(() => {
  const items = []
  if (authStore.isLoggedIn) {
    items.push({ title: t('nav.profile'), icon: 'mdi-account-circle', to: profilePath.value })
  }
  items.push({ title: t('nav.people'), icon: 'mdi-account-multiple', to: '/users' })
  if (authStore.isLoggedIn) {
    items.push({ title: t('nav.notifications'), icon: 'mdi-bell', to: '/notifications' })
  }
  return items
})

</script>

<template>
  <v-navigation-drawer
    :model-value="modelValue"
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <v-list rounded="lg" nav  color="primary">
      <v-list-item
        v-for="item in contentNavItems"
        :key="item.to"
        :to="item.to"
        :prepend-icon="item.icon"
        :title="item.title"
        exact
      />

      <v-divider class="my-2" />

      <v-list-item
        v-for="item in accountNavItems"
        :key="item.to"
        :to="item.to"
        :prepend-icon="item.icon"
        :title="item.title"
        exact
      />

    </v-list>

    <template v-slot:append>
      <DrawerCopyright :start-year="2023" />
    </template>
  </v-navigation-drawer>
</template>


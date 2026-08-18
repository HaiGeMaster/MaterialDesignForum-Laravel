<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

// 各路由页面的 tab 配置
// requireLogin: true 表示未登录时整个 tab 栏不显示
// tabs 为函数时按登录状态动态生成
const config = computed(() => {
  switch (route.name) {
    case 'topics':
      return {
        requireLogin: true,
        tabs: () => [
          { label: t('tabs.followingTopics'), value: 'following' },
          { label: t('tabs.recommendedTopics'), value: 'recommend' },
        ],
        default: 'recommend',
      }
    case 'questions':
    case 'articles':
      return {
        requireLogin: false,
        tabs: () => {
          const list = [
            { label: t('tabs.hot'), value: 'hot' },
            { label: t('tabs.latest'), value: 'latest' },
          ]
          if (authStore.isLoggedIn) {
            list.push({ label: t('tabs.following'), value: 'following' })
          }
          return list
        },
        default: 'hot',
      }
    case 'users':
      return {
        requireLogin: true,
        tabs: () => [
          { label: t('tabs.following'), value: 'following' },
          { label: t('tabs.myFollowers'), value: 'followers' },
          { label: t('tabs.recommendedPeople'), value: 'recommend' },
        ],
        default: 'recommend',
      }
    default:
      return null
  }
})

// 当前应显示的 tabs（未登录时过滤掉需登录项）
const tabs = computed(() => {
  if (!config.value) return []
  return typeof config.value.tabs === 'function' ? config.value.tabs() : config.value.tabs
})

// 是否显示 tab 栏
const visible = computed(() => {
  if (!config.value) return false
  if (config.value.requireLogin && !authStore.isLoggedIn) return false
  return tabs.value.length > 0
})

// 当前激活 tab：优先取 URL query，非法/缺失时回退默认
const active = computed(() => {
  if (!config.value) return ''
  const q = String(route.query.tab || '')
  if (tabs.value.some((t) => t.value === q)) return q
  return config.value.default
})

function selectTab(value) {
  if (value === active.value) return
  router.push({ query: { ...route.query, tab: value } })
}
</script>

<template>
  <div v-if="visible" class="app-tab-bar">
    <v-tabs
      :model-value="active"
      color="primary"
      density="comfortable"
      @update:model-value="selectTab"
    >
      <v-tab v-for="t in tabs" :key="t.value" :value="t.value">
        {{ t.label }}
      </v-tab>
    </v-tabs>
  </div>
</template>

<style scoped>
.app-tab-bar {
  position: sticky;
  top: 0;
  z-index: 4;
  background: rgb(var(--v-theme-surface));
  border-bottom: thin solid rgb(var(--v-theme-outline));
}
</style>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useTabBar } from '../composables/useTabBar'

const route = useRoute()
const router = useRouter()
const { mobile } = useDisplay()
const { tabs, visible, active } = useTabBar()

function selectTab(value) {
  if (value === active.value) return
  // Vue Router 4 不会自动补 #，需显式带上前缀，否则 URL 会拼成 /topicsrecommend
  router.push({ hash: `#${value}` })
}
</script>

<template>
  <div v-if="visible" class="app-tab-bar">
    <v-tabs
      :key="route.name"
      :model-value="active"
      color="primary"
      density="comfortable"
      :grow="mobile"
      :align-tabs="mobile ? undefined : 'center'"
    >
      <v-tab
        v-for="t in tabs"
        :key="t.value"
        :value="t.value"
        @click="selectTab(t.value)"
      >
        {{ t.label }}
      </v-tab>
    </v-tabs>
  </div>
</template>

<style scoped>
.app-tab-bar {
  width: 100%;
  border-bottom: thin solid rgb(var(--v-theme-outline));
  background: rgb(var(--v-theme-surface));
}
</style>

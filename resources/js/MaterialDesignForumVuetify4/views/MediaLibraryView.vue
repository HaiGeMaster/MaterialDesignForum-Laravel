<script setup>
import { computed } from 'vue'
import { useDisplay } from 'vuetify'
import { useAuthStore } from '../stores/auth'
import UserMediaLibrary from '../components/UserMediaLibrary.vue'

const { xs } = useDisplay()
const authStore = useAuthStore()

const userId = computed(() => authStore.user?.user_id)
</script>

<template>
  <v-container
    :class="xs ? 'pa-0' : 'px-4'"
    :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}"
  >
    <UserMediaLibrary v-if="userId" :key="userId" :userId="userId" />
  </v-container>
</template>

<script setup>
import { computed } from 'vue'

defineProps({
  providers: { type: Array, default: () => [] },
})

const emit = defineEmits(['select'])

// provider key -> 图标 + 文案
const META = {
  github: { icon: 'mdi-github', label: 'GitHub' },
  google: { icon: 'mdi-google', label: 'Google' },
  microsoft: { icon: 'mdi-microsoft', label: 'Microsoft' },
}

const items = computed(() => META)

function select(provider) {
  emit('select', provider)
}
</script>

<template>
  <div class="oauth-buttons mt-4">
    <div class="oauth-divider text-center text-medium-emphasis text-caption mb-4">
      <span>{{ $t('oauth.orContinueWith') }}</span>
    </div>
    <div class="d-flex justify-center ga-3">
      <v-btn
        v-for="provider in providers"
        :key="provider"
        icon
        variant="outlined"
        size="large"
        :title="items[provider]?.label || provider"
        :aria-label="items[provider]?.label || provider"
        @click="select(provider)"
      >
        <v-icon :icon="items[provider]?.icon || 'mdi-account'" />
      </v-btn>
    </div>
  </div>
</template>

<style scoped>
.oauth-divider {
  display: flex;
  align-items: center;
  gap: 12px;
}

.oauth-divider::before,
.oauth-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: rgba(var(--v-theme-on-surface), 0.12);
}

.oauth-divider span {
  white-space: nowrap;
}
</style>

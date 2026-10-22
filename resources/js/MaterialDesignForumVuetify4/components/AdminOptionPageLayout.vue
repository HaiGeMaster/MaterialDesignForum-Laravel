<script setup>
import { useRouter } from 'vue-router'

defineProps({
  title: { type: String, required: true },
  saving: { type: Boolean, default: false },
})

const emit = defineEmits(['save'])
const router = useRouter()

function goBack() {
  router.push('/options')
}
</script>

<template>
  <v-container class="option-detail-page py-6 px-4 px-md-8">
    <v-card rounded="lg" class="option-detail-card">
      <v-toolbar :title="title">
        <template #prepend>
          <v-btn icon variant="text" @click="goBack">
            <v-icon>mdi-arrow-left</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.back') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <v-btn icon variant="text" :loading="saving" @click="emit('save')">
            <v-icon>mdi-check</v-icon>
            <v-tooltip activator="parent" location="left">
              {{ $t('common.save') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-divider />
      <slot />
    </v-card>
  </v-container>
</template>

<style scoped>
.option-detail-page {
  max-width: 830px;
  min-height: 100%;
}

.option-detail-card {
  overflow: hidden;
}

:slotted(.form-section) {
  padding: 24px;
}

@media (max-width: 600px) {
  :slotted(.form-section) {
    padding: 18px;
  }
}
</style>

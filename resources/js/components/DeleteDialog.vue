<template>
  <v-dialog :model-value="store.model" max-width="420" @update:model-value="store.close">
    <v-card rounded="lg" :title="store.title" :prepend-icon="store.icon">
      <v-card-text>
        {{ store.text }}
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="store.close">
          {{ store.cancelText || t('common.cancel') }}
        </v-btn>
        <v-btn :color="store.color" variant="tonal" :loading="store.loading" @click="store.confirm">
          {{ store.confirmText || t('common.delete') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useDeleteDialogStore } from '../stores/deleteDialog'

const store = useDeleteDialogStore()
const { t } = useI18n()
</script>

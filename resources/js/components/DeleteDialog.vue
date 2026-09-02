<template>
  <v-dialog :model-value="store.model" max-width="420" @update:model-value="store.close">
    <v-card rounded="lg">
      <v-toolbar :title="store.title">
        <template #prepend>
          <v-btn icon variant="text" @click="store.close">
            <v-icon>mdi-close</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ store.cancelText || t('common.cancel') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <!-- variant="text"  -->
          <v-btn icon color="error" variant="tonal" :loading="store.loading" @click="store.confirm">
            <v-icon >mdi-check</v-icon>
            <v-tooltip activator="parent" location="left">
              {{ store.confirmText || t('common.delete') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-card-text>
        {{ store.text }}
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useDeleteDialogStore } from '../stores/deleteDialog'

const store = useDeleteDialogStore()
const { t } = useI18n()
</script>

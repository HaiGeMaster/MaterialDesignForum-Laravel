<template>
  <v-dialog :model-value="store.model" :max-width="store.showDatePicker ? 460 : 420" @update:model-value="store.close">
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
          <v-btn icon :color="store.color" variant="tonal" :loading="store.loading"
            :disabled="store.showDatePicker && !store.permanent && !store.selectedDate" @click="store.confirm">
            <v-icon>mdi-check</v-icon>
            <v-tooltip activator="parent" location="left">
              {{ store.confirmText || t('common.delete') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-card-text>
        {{ store.text }}
        <template v-if="store.showDatePicker">
          <v-checkbox v-if="store.allowPermanent" v-model="store.permanent" :label="store.permanentLabel" color="error"
            density="compact" hide-details />
          <v-expand-transition>

            <div v-if="!store.permanent" class="mt-3" >
              <div class="text-subtitle-2 mb-2">{{ store.datePickerLabel }}</div>
              <v-date-picker v-model="store.selectedDate" :min="store.minDate" hide-header style="width: 100%;"/>
            </div>
          </v-expand-transition>
        </template>
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

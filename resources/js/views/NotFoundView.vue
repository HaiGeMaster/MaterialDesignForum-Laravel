<script setup>
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const { t } = useI18n()
</script>

<template>
  <v-container class="d-flex align-center justify-center py-12" style="min-height: 60vh;">
    <v-card class="mx-auto" max-width="560" rounded="xl" variant="text">
      <v-empty-state
        icon="mdi-map-search"
        :title="t('notFound.title')"
        :text="t('notFound.message')"
      >
        <template #actions>
          <div class="d-flex flex-column flex-sm-row justify-center ga-3">
            <v-btn color="primary" prepend-icon="mdi-home" @click="router.push('/')">
              {{ t('notFound.backHome') }}
            </v-btn>
            <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.back()">
              {{ t('common.back') }}
            </v-btn>
          </div>
        </template>
      </v-empty-state>
    </v-card>
  </v-container>
</template>

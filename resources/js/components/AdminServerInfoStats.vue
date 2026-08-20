<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  data: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const { t } = useI18n()

const fields = computed(() => [
  { key: 'MDF_VERSION', icon: 'mdi-forum-outline' },
  { key: 'OS', icon: 'mdi-monitor' },
  { key: 'PHP_VERSION', icon: 'mdi-language-php' },
  { key: 'WEB_SERVER', icon: 'mdi-web' },
  { key: 'DB_VERSION', icon: 'mdi-database-cog-outline' },
  { key: 'UPLOAD_MAX_FILESIZE', icon: 'mdi-file-upload-outline' },
  { key: 'MAX_EXECUTION_TIME', icon: 'mdi-timer-outline' },
  { key: 'DISK_FREE_SPACE', icon: 'mdi-harddisk' },
  { key: 'DB_SIZE', icon: 'mdi-database-outline' },
  { key: 'ENVIRONMENT', icon: 'mdi-server-outline' },
])
</script>

<template>
  <v-card class="system-info-card h-100" >
    <v-card-title class="d-flex align-center">
      <span>{{ $t('admin.systemInfo') }}</span>
      <v-spacer />
      <v-icon icon="mdi-information-outline" />
    </v-card-title>

    <v-card-text class="system-info-content">
      <v-list v-if="!loading && data" density="compact">
        <v-list-item v-for="field in fields" :key="field.key" :prepend-icon="field.icon">
          <v-list-item-title>{{ $t(`admin.serverFields.${field.key}`) }}</v-list-item-title>
          <template #append>
            <span class="system-value" :title="data[field.key] || '—'">{{ data[field.key] || '—' }}</span>
          </template>
        </v-list-item>
      </v-list>
      <v-list v-else density="compact">
        <v-skeleton-loader v-for="field in fields" :key="field.key" type="list-item-two-line" />
      </v-list>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.system-info-card {
  border-radius: 12px;
}

.system-info-content {
  height: 340px;
  overflow-y: auto;
}

.system-info-content :deep(.v-list-item) {
  min-height: 36px;
}

.system-value {
  max-width: 210px;
  overflow: hidden;
  color: rgba(var(--v-theme-on-surface), 0.72);
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 600px) {
  .system-value {
    max-width: 130px;
  }
}
</style>

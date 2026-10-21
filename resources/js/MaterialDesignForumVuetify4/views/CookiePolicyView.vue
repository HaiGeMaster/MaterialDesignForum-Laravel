<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useFeedback } from '../composables/useFeedback'

const { t } = useI18n()
const { show } = useFeedback()
const content = ref('')
const loading = ref(false)
const failed = ref(false)

async function loadPolicy() {
  loading.value = true
  failed.value = false
  try {
    const { data } = await http.get('/cookie-policy')
    content.value = data.content
  } catch (error) {
    failed.value = true
    show(error.message, 'error')
  } finally {
    loading.value = false
  }
}

onMounted(loadPolicy)
</script>

<template>
  <v-container class="py-6">
    <v-card>
      <v-card-text>
        <div v-if="loading" class="d-flex justify-center pa-8">
          <v-progress-circular indeterminate color="primary" />
        </div>
        <v-alert v-else-if="failed" type="error" variant="tonal">
          {{ t('cookiePolicy.loadFailed') }}
          <template #append>
            <v-btn variant="text" @click="loadPolicy">{{ t('cookiePolicy.retry') }}</v-btn>
          </template>
        </v-alert>
        <article v-else class="cookie-policy-content" v-html="content" />
      </v-card-text>
    </v-card>
  </v-container>
</template>

<style scoped>
.cookie-policy-content {
  color: rgb(var(--v-theme-on-surface));
  line-height: 1.75;
  overflow-wrap: anywhere;
}

.cookie-policy-content :deep(h1),
.cookie-policy-content :deep(h2),
.cookie-policy-content :deep(h3) {
  margin: 1.5em 0 0.65em;
}

.cookie-policy-content :deep(p),
.cookie-policy-content :deep(ul),
.cookie-policy-content :deep(ol),
.cookie-policy-content :deep(blockquote) {
  margin: 0.75em 0;
}

.cookie-policy-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1em 0;
}

.cookie-policy-content :deep(th),
.cookie-policy-content :deep(td) {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  padding: 0.5em;
  text-align: left;
  vertical-align: top;
}

.cookie-policy-content :deep(blockquote) {
  border-left: 3px solid rgb(var(--v-theme-primary));
  padding-left: 1em;
}
</style>
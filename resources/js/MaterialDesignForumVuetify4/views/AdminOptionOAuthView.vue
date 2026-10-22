<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import AdminOptionPageLayout from '../components/AdminOptionPageLayout.vue'
import { useFeedback } from '../composables/useFeedback'

const { t } = useI18n()
const { show } = useFeedback()
const loading = ref(false)
const saving = ref(false)
const providers = ['github', 'google', 'microsoft']
const form = reactive({
  GITHUB_CLIENT_ID: '',
  GITHUB_CLIENT_SECRET: '',
  GITHUB_SECRET_SET: false,
  GOOGLE_CLIENT_ID: '',
  GOOGLE_CLIENT_SECRET: '',
  GOOGLE_SECRET_SET: false,
  MICROSOFT_CLIENT_ID: '',
  MICROSOFT_CLIENT_SECRET: '',
  MICROSOFT_SECRET_SET: false,
})

function applyForm(data) {
  const payload = data?.data || {}
  for (const provider of providers) {
    const upper = provider.toUpperCase()
    form[`${upper}_CLIENT_ID`] = payload[`${upper}_CLIENT_ID`] ?? ''
    form[`${upper}_CLIENT_SECRET`] = ''
    form[`${upper}_SECRET_SET`] = !!payload[`${upper}_SECRET_SET`]
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await http.get('/admin/options/oauth')
    applyForm(data)
  } catch (error) {
    show(error.message || t('admin.oauthLoadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const { data } = await http.put('/admin/options/oauth', form)
    applyForm(data)
    show(t('admin.oauthSaved'), 'success')
  } catch (error) {
    show(error.message || t('admin.siteInfoSaveFailed'), 'error')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <AdminOptionPageLayout :title="$t('admin.oauthSettings')" :saving="saving" @save="save">
    <v-card-text class="form-section">
      <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />
      <v-card v-for="provider in providers" :key="provider" rounded="lg" class="provider-section mb-4" variant="flat">
        <v-card-title class="text-subtitle-1 text-capitalize">{{ provider }}</v-card-title>
        <v-card-text>
          <v-text-field v-model="form[`${provider.toUpperCase()}_CLIENT_ID`]" color="primary" :label="$t('admin.clientId')" />
          <v-text-field v-model="form[`${provider.toUpperCase()}_CLIENT_SECRET`]" color="primary" type="password" :label="$t('admin.clientSecret')" :hint="form[`${provider.toUpperCase()}_SECRET_SET`] ? $t('admin.secretKeepHint') : ''" persistent-hint />
        </v-card-text>
      </v-card>
    </v-card-text>
  </AdminOptionPageLayout>
</template>

<style scoped>
.provider-section {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
}
</style>

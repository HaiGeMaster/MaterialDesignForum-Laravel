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
const form = reactive({
  MAIL_MAILER: 'smtp',
  MAIL_HOST: '',
  MAIL_PORT: '465',
  MAIL_SCHEME: 'smtps',
  MAIL_USERNAME: '',
  MAIL_PASSWORD: '',
  password_set: false,
  MAIL_FROM_ADDRESS: '',
  MAIL_FROM_NAME: '',
})

async function load() {
  loading.value = true
  try {
    const { data } = await http.get('/admin/options/mail')
    Object.assign(form, data.data || {}, { MAIL_PASSWORD: '' })
  } catch (error) {
    show(error.message || t('admin.mailLoadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const { data } = await http.put('/admin/options/mail', form)
    Object.assign(form, data.data || {}, { MAIL_PASSWORD: '' })
    show(t('admin.mailSaved'), 'success')
  } catch (error) {
    show(error.message || t('admin.siteInfoSaveFailed'), 'error')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <AdminOptionPageLayout :title="$t('admin.mailSettings')" :saving="saving" @save="save">
    <v-card-text class="form-section">
      <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />
      <v-text-field v-model="form.MAIL_MAILER" color="primary" :label="$t('admin.mailer')" />
      <v-text-field v-model="form.MAIL_HOST" color="primary" :label="$t('admin.smtpHost')" />
      <v-text-field v-model="form.MAIL_PORT" color="primary" :label="$t('admin.smtpPort')" />
      <v-select v-model="form.MAIL_SCHEME" :items="[{ title: 'NULL', value: '' }, { title: 'SSL', value: 'smtps' }, { title: 'TLS', value: 'smtp' }]" :label="$t('admin.smtpEncryption')" />
      <v-text-field v-model="form.MAIL_USERNAME" color="primary" :label="$t('admin.smtpUsername')" />
      <v-text-field v-model="form.MAIL_PASSWORD" color="primary" type="password" :label="$t('admin.smtpPassword')" :hint="form.password_set ? $t('admin.passwordKeepHint') : ''" persistent-hint />
      <v-text-field v-model="form.MAIL_FROM_ADDRESS" color="primary" :label="$t('admin.mailFromAddress')" />
      <v-text-field v-model="form.MAIL_FROM_NAME" color="primary" :label="$t('admin.mailFromName')" />
    </v-card-text>
  </AdminOptionPageLayout>
</template>

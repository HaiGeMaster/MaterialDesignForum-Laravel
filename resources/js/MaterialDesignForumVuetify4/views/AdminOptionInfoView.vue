<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import AdminOptionPageLayout from '../components/AdminOptionPageLayout.vue'
import { useFeedback } from '../composables/useFeedback'

const { t } = useI18n()
const { show } = useFeedback()
const loading = ref(false)
const saving = ref(false)
const form = reactive({
  site_name: '',
  site_description: '',
  site_keywords: '',
  site_icp_beian: '',
  site_gongan_beian: '',
  default_language: '',
  media_disk: 'local',
})

const mediaDiskOptions = computed(() => ['local', 'ftp', 'sftp', 'aliyun', 'upyun', 'qiniu', 'aws'].map(value => ({
  value,
  title: t(`admin.mediaDisk${value[0].toUpperCase()}${value.slice(1)}`),
})))

const infoLanguages = [
  { code: 'de-DE', label: 'Deutsch (Deutschland)' },
  { code: 'en-GB', label: 'English (United Kingdom)' },
  { code: 'en-US', label: 'English (United States)' },
  { code: 'fr-FR', label: 'Français (France)' },
  { code: 'ja-JP', label: '日本語 (日本)' },
  { code: 'ko-KR', label: '한국어 (대한민국)' },
  { code: 'ru-RU', label: 'Русский (Россия)' },
  { code: 'zh-CN', label: '简体中文(中国)' },
  { code: 'zh-TW', label: '繁體中文(中国台灣)' },
]

async function load() {
  loading.value = true
  try {
    const { data } = await http.get('/admin/options/site-info')
    Object.assign(form, data.data || {})
  } catch (error) {
    show(error.message || t('admin.siteInfoLoadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const { data } = await http.put('/admin/options/site-info', form)
    Object.assign(form, data.data || {})
    show(t('admin.siteInfoSaved'), 'success')
  } catch (error) {
    show(error.message || t('admin.siteInfoSaveFailed'), 'error')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <AdminOptionPageLayout :title="$t('admin.siteInfo')" :saving="saving" @save="save">
    <v-card-text class="form-section">
      <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />
      <v-text-field v-model="form.site_name" color="primary" :label="$t('admin.siteName')" />
      <v-textarea v-model="form.site_description" color="primary" :label="$t('admin.siteDescription')" rows="3" />
      <v-text-field v-model="form.site_keywords" color="primary" :label="$t('admin.siteKeywords')" :hint="$t('admin.siteKeywordsHint')" persistent-hint />
      <v-text-field v-model="form.site_icp_beian" color="primary" :label="$t('admin.siteIcp')" />
      <v-text-field v-model="form.site_gongan_beian" color="primary" :label="$t('admin.sitePolice')" />
      <v-select v-model="form.default_language" :items="infoLanguages" item-title="label" item-value="code" :label="$t('admin.defaultLanguage')" />
      <v-select v-model="form.media_disk" :items="mediaDiskOptions" :label="$t('admin.mediaDisk')" />
      <div class="text-caption text-medium-emphasis mt-n4 mb-4">
        {{ $t('admin.mediaDiskHint') }}
      </div>
    </v-card-text>
  </AdminOptionPageLayout>
</template>

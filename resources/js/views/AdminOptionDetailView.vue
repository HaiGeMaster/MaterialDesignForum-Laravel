<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'
import http from '../api'
import { useFeedback } from '../composables/useFeedback'
import { applyThemeConfig } from '../themeConfig'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const theme = useTheme()
const { show } = useFeedback()
const saving = ref(false)
const loading = ref(false)

const forms = reactive({
  info: { site_name: '', site_description: '', site_keywords: '', site_icp_beian: '', site_gongan_beian: '', default_language: 'zh' },
  mail: { MAIL_MAILER: 'smtp', MAIL_HOST: '', MAIL_PORT: '465', MAIL_SCHEME: 'smtps', MAIL_USERNAME: '', MAIL_PASSWORD: '', password_set: false, MAIL_FROM_ADDRESS: '', MAIL_FROM_NAME: '' },
  oauth: { GITHUB_CLIENT_ID: '', GITHUB_CLIENT_SECRET: '', GITHUB_SECRET_SET: false, GOOGLE_CLIENT_ID: '', GOOGLE_CLIENT_SECRET: '', GOOGLE_SECRET_SET: false, MICROSOFT_CLIENT_ID: '', MICROSOFT_CLIENT_SECRET: '', MICROSOFT_SECRET_SET: false },
  theme: { current: 'light', lightPrimary: '#1867C0', lightSecondary: '#48A9A6', lightAccent: '#FB8C00', darkPrimary: '#2196F3', darkSecondary: '#54B6B2', darkAccent: '#FB8C00' },
})

const page = computed(() => ({
  info: { title: t('admin.siteInfo'), icon: 'mdi-information-box-outline' },
  theme: { title: t('admin.themeSettings'), icon: 'mdi-palette-outline' },
  mail: { title: t('admin.mailSettings'), icon: 'mdi-email-outline' },
  oauth: { title: t('admin.oauthSettings'), icon: 'mdi-account-network-outline' },
}[route.params.setting] || { title: t('admin.manageSettings'), icon: 'mdi-cog' }))

const infoLanguages = computed(() => [
  { title: t('admin.chinese'), value: 'zh' },
  { title: t('admin.english'), value: 'en' },
])

function goBack() {
  router.push('/options')
}

async function save() {
  if (!['info', 'theme', 'mail', 'oauth'].includes(route.params.setting)) {
    show(t('common.developing'), 'info')
    return
  }

  saving.value = true
  try {
    if (route.params.setting === 'info') {
      const { data } = await http.put('/admin/options/site-info', forms.info)
      forms.info = { ...forms.info, ...(data.data || {}) }
      show(t('admin.siteInfoSaved'), 'success')
    } else if (route.params.setting === 'theme') {
      const { data } = await http.put('/admin/options/theme', {
        theme: forms.theme.current,
        colors: {
          light: {
            primary: forms.theme.lightPrimary,
            secondary: forms.theme.lightSecondary,
            accent: forms.theme.lightAccent,
          },
          dark: {
            primary: forms.theme.darkPrimary,
            secondary: forms.theme.darkSecondary,
            accent: forms.theme.darkAccent,
          },
        },
      })
      applyThemeForm(data)
      applyThemeConfig(theme, data)
      show(t('admin.themeSaved'), 'success')
    } else if (route.params.setting === 'oauth') {
      const { data } = await http.put('/admin/options/oauth', forms.oauth)
      applyOauthForm(data)
      show(t('admin.oauthSaved'), 'success')
    } else {
      const { data } = await http.put('/admin/options/mail', forms.mail)
      forms.mail = { ...forms.mail, ...(data.data || {}), MAIL_PASSWORD: '' }
      show(t('admin.mailSaved'), 'success')
    }
  } catch (error) {
    show(error.message || t('admin.siteInfoSaveFailed'), 'error')
  } finally {
    saving.value = false
  }
}

async function loadSiteInfo() {
  loading.value = true
  try {
    const { data } = await http.get('/admin/options/site-info')
    forms.info = { ...forms.info, ...(data.data || {}) }
  } catch (error) {
    show(error.message || t('admin.siteInfoLoadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

function applyThemeForm(data) {
  forms.theme.current = data.theme || forms.theme.current
  forms.theme.lightPrimary = data.colors?.light?.primary || forms.theme.lightPrimary
  forms.theme.lightSecondary = data.colors?.light?.secondary || forms.theme.lightSecondary
  forms.theme.lightAccent = data.colors?.light?.accent || forms.theme.lightAccent
  forms.theme.darkPrimary = data.colors?.dark?.primary || forms.theme.darkPrimary
  forms.theme.darkSecondary = data.colors?.dark?.secondary || forms.theme.darkSecondary
  forms.theme.darkAccent = data.colors?.dark?.accent || forms.theme.darkAccent
}

async function loadTheme() {
  loading.value = true
  try {
    const { data } = await http.get('/theme')
    applyThemeForm(data)
  } catch (error) {
    show(error.message || t('admin.themeLoadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function loadMail() {
  loading.value = true
  try {
    const { data } = await http.get('/admin/options/mail')
    forms.mail = { ...forms.mail, ...(data.data || {}), MAIL_PASSWORD: '' }
  } catch (error) {
    show(error.message || t('admin.mailLoadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

function applyOauthForm(data) {
  const payload = data?.data || {}
  for (const provider of ['github', 'google', 'microsoft']) {
    const upper = provider.toUpperCase()
    forms.oauth[`${upper}_CLIENT_ID`] = payload[`${upper}_CLIENT_ID`] ?? ''
    forms.oauth[`${upper}_CLIENT_SECRET`] = ''
    forms.oauth[`${upper}_SECRET_SET`] = !!payload[`${upper}_SECRET_SET`]
  }
}

async function loadOauth() {
  loading.value = true
  try {
    const { data } = await http.get('/admin/options/oauth')
    applyOauthForm(data)
  } catch (error) {
    show(error.message || t('admin.oauthLoadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (route.params.setting === 'info') loadSiteInfo()
  if (route.params.setting === 'theme') loadTheme()
  if (route.params.setting === 'mail') loadMail()
  if (route.params.setting === 'oauth') loadOauth()
})
</script>

<template>
  <v-container class="option-detail-page py-6 px-4 px-md-8">
    <v-card class="option-detail-card" >
      <v-toolbar :title="page.title" color="transparent">
        <template #prepend>
          <v-btn icon="mdi-arrow-left" variant="text" :title="$t('common.back')" @click="goBack" />
        </template>
        <template #append>
          <v-btn icon="mdi-check" variant="text" :loading="saving" :title="$t('common.save')" @click="save" />
        </template>
      </v-toolbar>

      <v-divider />

      <v-card-text v-if="route.params.setting === 'info'" class="form-section">
        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />
        <v-text-field v-model="forms.info.site_name" :label="$t('admin.siteName')" />
        <v-textarea v-model="forms.info.site_description" :label="$t('admin.siteDescription')" rows="3" />
        <v-text-field v-model="forms.info.site_keywords" :label="$t('admin.siteKeywords')" :hint="$t('admin.siteKeywordsHint')" persistent-hint />
        <v-text-field v-model="forms.info.site_icp_beian" :label="$t('admin.siteIcp')" />
        <v-text-field v-model="forms.info.site_gongan_beian" :label="$t('admin.sitePolice')" />
        <v-select v-model="forms.info.default_language" :items="infoLanguages" item-title="title" item-value="value" :label="$t('admin.defaultLanguage')" />
      </v-card-text>

      <v-card-text v-else-if="route.params.setting === 'mail'" class="form-section">
        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />
        <v-text-field v-model="forms.mail.MAIL_MAILER" :label="$t('admin.mailer')" />
        <v-text-field v-model="forms.mail.MAIL_HOST" :label="$t('admin.smtpHost')" />
        <v-text-field v-model="forms.mail.MAIL_PORT" :label="$t('admin.smtpPort')" />
        <v-select v-model="forms.mail.MAIL_SCHEME" :items="[{ title: 'NULL', value: '' }, { title: 'SSL', value: 'smtps' }, { title: 'TLS', value: 'smtp' }]" :label="$t('admin.smtpEncryption')" />
        <v-text-field v-model="forms.mail.MAIL_USERNAME" :label="$t('admin.smtpUsername')" />
        <v-text-field v-model="forms.mail.MAIL_PASSWORD" type="password" :label="$t('admin.smtpPassword')" :hint="forms.mail.password_set ? $t('admin.passwordKeepHint') : ''" persistent-hint />
        <v-text-field v-model="forms.mail.MAIL_FROM_ADDRESS" :label="$t('admin.mailFromAddress')" />
        <v-text-field v-model="forms.mail.MAIL_FROM_NAME" :label="$t('admin.mailFromName')" />
      </v-card-text>

      <v-card-text v-else-if="route.params.setting === 'oauth'" class="form-section">
        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />
        <v-card v-for="provider in ['github', 'google', 'microsoft']" :key="provider" class="provider-section mb-4" variant="flat">
          <v-card-title class="text-subtitle-1 text-capitalize">{{ provider }}</v-card-title>
          <v-card-text>
            <v-text-field v-model="forms.oauth[`${provider.toUpperCase()}_CLIENT_ID`]" :label="$t('admin.clientId')" />
            <v-text-field v-model="forms.oauth[`${provider.toUpperCase()}_CLIENT_SECRET`]" type="password" :label="$t('admin.clientSecret')" :hint="forms.oauth[`${provider.toUpperCase()}_SECRET_SET`] ? $t('admin.secretKeepHint') : ''" persistent-hint />
          </v-card-text>
        </v-card>
      </v-card-text>

      <v-card-text v-else-if="route.params.setting === 'theme'" class="form-section">
        <v-select v-model="forms.theme.current" :items="[{ title: 'Light', value: 'light' }, { title: 'Dark', value: 'dark' }]" :label="$t('admin.currentTheme')" />
        <v-divider class="my-5" />
        <div class="text-subtitle-1 font-weight-bold mb-3">{{ $t('admin.lightTheme') }}</div>
        <v-row>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.lightPrimary" :label="$t('admin.primaryColor')" /></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.lightSecondary" :label="$t('admin.secondaryColor')" /></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.lightAccent" :label="$t('admin.accentColor')" /></v-col>
        </v-row>
        <div class="text-subtitle-1 font-weight-bold mb-3">{{ $t('admin.darkTheme') }}</div>
        <v-row>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.darkPrimary" :label="$t('admin.primaryColor')" /></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.darkSecondary" :label="$t('admin.secondaryColor')" /></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.darkAccent" :label="$t('admin.accentColor')" /></v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<style scoped>
.option-detail-page {
  max-width: 830px;
  min-height: 100%;
}

.option-detail-card {
  border-radius: 12px;
}

.form-section {
  padding: 24px;
}

.provider-section {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 10px;
}

@media (max-width: 600px) {
  .form-section {
    padding: 18px;
  }
}
</style>

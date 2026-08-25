<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'
import http from '../api'
import { useFeedback } from '../composables/useFeedback'
import { applyThemeConfig } from '../themeConfig'
import { BUILTIN_THEMES } from '../plugins/vuetify'

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
  theme: {
    default_theme: 'system',
    available_themes: ['light', 'dark'],
    custom_themes: [],
    lightPrimary: '#1867C0', lightSecondary: '#48A9A6', lightAccent: '#FB8C00',
    darkPrimary: '#2196F3', darkSecondary: '#54B6B2', darkAccent: '#FB8C00',
  },
})

// ---- 主题管理辅助 ----
const newThemeDialog = ref(false)
const newTheme = reactive({ name: '', dark: true, primary: '#2196F3', secondary: '#54B6B2', accent: '#FB8C00' })

// 所有内置主题名称列表
const builtinThemeNames = computed(() => BUILTIN_THEMES.map(th => th.name))

// 默认主题下拉选项（内置主题 + 自定义主题 + system）
const defaultThemeItems = computed(() => {
  const items = [{ title: `${t('themeDialog.system')} (Light/Dark)`, value: 'system' }]
  for (const th of BUILTIN_THEMES) {
    if (forms.theme.available_themes.includes(th.name)) {
      items.push({ title: th.label, value: th.name })
    }
  }
  for (const ct of forms.theme.custom_themes) {
    if (forms.theme.available_themes.includes(ct.name)) {
      items.push({ title: ct.name, value: ct.name })
    }
  }
  return items
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
        default_theme: forms.theme.default_theme,
        available_themes: forms.theme.available_themes,
        custom_themes: forms.theme.custom_themes,
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
  forms.theme.default_theme = data.default_theme || data.theme || forms.theme.default_theme
  forms.theme.available_themes = Array.isArray(data.available_themes) ? data.available_themes : forms.theme.available_themes
  forms.theme.custom_themes = Array.isArray(data.custom_themes) ? data.custom_themes : []
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

// ---- 新建自定义主题 ----
function resetNewTheme() {
  newTheme.name = ''
  newTheme.dark = true
  newTheme.primary = '#2196F3'
  newTheme.secondary = '#54B6B2'
  newTheme.accent = '#FB8C00'
}

function addCustomTheme() {
  if (!newTheme.name.trim()) return
  // 检查名称是否已存在
  const exists = forms.theme.custom_themes.some(ct => ct.name === newTheme.name)
    || builtinThemeNames.value.includes(newTheme.name)
  if (exists) {
    show(t('admin.themeNameExists'), 'error')
    return
  }
  forms.theme.custom_themes.push({
    name: newTheme.name.trim(),
    dark: newTheme.dark,
    colors: { primary: newTheme.primary, secondary: newTheme.secondary, accent: newTheme.accent },
  })
  // 自动启用新主题
  if (!forms.theme.available_themes.includes(newTheme.name.trim())) {
    forms.theme.available_themes.push(newTheme.name.trim())
  }
  resetNewTheme()
  newThemeDialog.value = false
}

function removeCustomTheme(name) {
  forms.theme.custom_themes = forms.theme.custom_themes.filter(ct => ct.name !== name)
  forms.theme.available_themes = forms.theme.available_themes.filter(t => t !== name)
  if (forms.theme.default_theme === name) {
    forms.theme.default_theme = 'system'
  }
}

function toggleBuiltinTheme(name, enabled) {
  if (enabled && !forms.theme.available_themes.includes(name)) {
    forms.theme.available_themes.push(name)
  } else if (!enabled) {
    forms.theme.available_themes = forms.theme.available_themes.filter(t => t !== name)
    // 如果当前默认主题是被禁用的，重置为 system
    if (forms.theme.default_theme === name) {
      forms.theme.default_theme = 'system'
    }
  }
}
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
        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

        <!-- 默认主题 -->
        <v-select
          v-model="forms.theme.default_theme"
          :items="defaultThemeItems"
          :label="$t('admin.defaultTheme')"
        />

        <v-divider class="my-5" />

        <!-- 内置主题启用/禁用 -->
        <div class="text-subtitle-1 font-weight-bold mb-3">{{ $t('admin.builtinThemes') }}</div>
        <v-list density="compact">
          <v-list-item
            v-for="th in BUILTIN_THEMES"
            :key="th.name"
            :prepend-icon="th.icon"
            :title="th.label"
            :subtitle="th.dark ? $t('admin.darkMode') : $t('admin.lightMode')"
          >
            <template v-slot:append>
              <v-switch
                :model-value="forms.theme.available_themes.includes(th.name)"
                color="primary"
                hide-details
                density="compact"
                @update:model-value="(v) => toggleBuiltinTheme(th.name, v)"
              />
            </template>
          </v-list-item>
        </v-list>

        <v-divider class="my-5" />

        <!-- 自定义主题 -->
        <div class="d-flex align-center mb-3">
          <div class="text-subtitle-1 font-weight-bold">{{ $t('admin.customThemes') }}</div>
          <v-spacer />
          <v-btn size="small" color="primary" variant="tonal" prepend-icon="mdi-plus" @click="newThemeDialog = true">
            {{ $t('admin.newTheme') }}
          </v-btn>
        </div>

        <v-list v-if="forms.theme.custom_themes.length" density="compact">
          <v-list-item
            v-for="ct in forms.theme.custom_themes"
            :key="ct.name"
            :title="ct.name"
            :subtitle="ct.dark ? $t('admin.darkMode') : $t('admin.lightMode')"
            prepend-icon="mdi-palette"
          >
            <template v-slot:append>
              <v-btn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="removeCustomTheme(ct.name)" />
            </template>
          </v-list-item>
        </v-list>
        <div v-else class="text-body-2 text-medium-emphasis mb-4">{{ $t('admin.noCustomThemes') }}</div>

        <v-divider class="my-5" />

        <!-- Light / Dark 主色配置 -->
        <div class="text-subtitle-1 font-weight-bold mb-3">{{ $t('admin.lightTheme') }}</div>
        <v-row>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.lightPrimary" :color="forms.theme.lightPrimary" :label="$t('admin.primaryColor')" /></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.lightSecondary" :color="forms.theme.lightSecondary" :label="$t('admin.secondaryColor')" /></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.lightAccent" :color="forms.theme.lightAccent" :label="$t('admin.accentColor')" /></v-col>
        </v-row>
        <div class="text-subtitle-1 font-weight-bold mb-3 mt-2">{{ $t('admin.darkTheme') }}</div>
        <v-row>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.darkPrimary" :color="forms.theme.darkPrimary" :label="$t('admin.primaryColor')" /></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.darkSecondary" :color="forms.theme.darkSecondary" :label="$t('admin.secondaryColor')" /></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="forms.theme.darkAccent" :color="forms.theme.darkAccent" :label="$t('admin.accentColor')" /></v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- 新建自定义主题对话框 -->
    <v-dialog v-model="newThemeDialog" max-width="480">
      <v-card>
        <v-card-title>{{ $t('admin.newTheme') }}</v-card-title>
        <v-card-text>
          <v-text-field v-model="newTheme.name" :label="$t('admin.themeName')" :rules="[v => !!v || $t('auth.rules.required')]" />
          <v-switch v-model="newTheme.dark" :label="$t('admin.darkMode')" color="primary" hide-details class="mb-3" />
          <v-row>
            <v-col cols="12" sm="4"><v-text-field v-model="newTheme.primary" :label="$t('admin.primaryColor')" type="color" /></v-col>
            <v-col cols="12" sm="4"><v-text-field v-model="newTheme.secondary" :label="$t('admin.secondaryColor')" type="color" /></v-col>
            <v-col cols="12" sm="4"><v-text-field v-model="newTheme.accent" :label="$t('admin.accentColor')" type="color" /></v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="newThemeDialog = false">{{ $t('common.cancel') }}</v-btn>
          <v-btn color="primary" variant="tonal" @click="addCustomTheme">{{ $t('common.confirm') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
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

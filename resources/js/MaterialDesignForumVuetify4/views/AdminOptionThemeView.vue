<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'
import http from '../api'
import AdminOptionPageLayout from '../components/AdminOptionPageLayout.vue'
import { useFeedback } from '../composables/useFeedback'
import { applyThemeConfig } from '../themeConfig'
import { BUILTIN_THEMES } from '../plugins/vuetify'

const { t } = useI18n()
const theme = useTheme()
const { show } = useFeedback()
const loading = ref(false)
const saving = ref(false)
const form = reactive({
  default_theme: 'system',
  available_themes: ['light', 'dark'],
})

const defaultThemeItems = computed(() => {
  const items = [{ title: `${t('themeDialog.system')} (Light/Dark)`, value: 'system' }]
  for (const builtinTheme of BUILTIN_THEMES) {
    if (form.available_themes.includes(builtinTheme.name)) {
      items.push({ title: builtinTheme.label, value: builtinTheme.name })
    }
  }
  return items
})

function applyForm(data) {
  form.default_theme = data.default_theme || data.theme || form.default_theme
  form.available_themes = Array.isArray(data.available_themes) ? data.available_themes : form.available_themes
}

async function load() {
  loading.value = true
  try {
    const { data } = await http.get('/theme')
    applyForm(data)
  } catch (error) {
    show(error.message || t('admin.themeLoadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const { data } = await http.put('/admin/options/theme', {
      default_theme: form.default_theme,
      available_themes: form.available_themes,
    })
    applyForm(data)
    applyThemeConfig(theme, data)
    show(t('admin.themeSaved'), 'success')
  } catch (error) {
    show(error.message || t('admin.siteInfoSaveFailed'), 'error')
  } finally {
    saving.value = false
  }
}

function toggleBuiltinTheme(name, enabled) {
  if (enabled && !form.available_themes.includes(name)) {
    form.available_themes.push(name)
  } else if (!enabled) {
    form.available_themes = form.available_themes.filter(themeName => themeName !== name)
    if (form.default_theme === name) {
      form.default_theme = 'system'
    }
  }
}

onMounted(load)
</script>

<template>
  <AdminOptionPageLayout :title="$t('admin.themeSettings')" :saving="saving" @save="save">
    <v-card-text class="form-section">
      <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />
      <v-select v-model="form.default_theme" :items="defaultThemeItems" :label="$t('admin.defaultTheme')" />
      <v-divider class="my-5" />
      <div class="text-subtitle-1 font-weight-bold mb-3">{{ $t('admin.builtinThemes') }}</div>
      <v-list rounded="lg" density="compact">
        <v-list-item
          v-for="builtinTheme in BUILTIN_THEMES"
          :key="builtinTheme.name"
          :prepend-icon="builtinTheme.icon"
          :title="builtinTheme.label"
          :subtitle="builtinTheme.dark ? $t('admin.darkMode') : $t('admin.lightMode')"
        >
          <template #append>
            <v-switch
              :model-value="form.available_themes.includes(builtinTheme.name)"
              color="primary"
              hide-details
              density="compact"
              @update:model-value="enabled => toggleBuiltinTheme(builtinTheme.name, enabled)"
            />
          </template>
        </v-list-item>
      </v-list>
    </v-card-text>
  </AdminOptionPageLayout>
</template>

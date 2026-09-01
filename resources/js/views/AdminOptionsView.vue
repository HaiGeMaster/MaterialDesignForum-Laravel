<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'

const router = useRouter()
const { t } = useI18n()

const settingGroups = computed(() => [
  {
    title: t('admin.systemSettings'),
    items: [
      { key: 'info', title: t('admin.siteInfo'), description: t('admin.siteInfoDescription'), icon: 'mdi-information-box-outline' },
      { key: 'theme', title: t('admin.themeSettings'), description: t('admin.themeSettingsDescription'), icon: 'mdi-palette-outline' },
      { key: 'mail', title: t('admin.mailSettings'), description: t('admin.mailSettingsDescription'), icon: 'mdi-email-outline' },
      { key: 'oauth', title: t('admin.oauthSettings'), description: t('admin.oauthSettingsDescription'), icon: 'mdi-account-network-outline' },
    ],
  },
  {
    title: t('admin.aboutApplication'),
    items: [
      { key: 'about', title: t('admin.about'), description: t('admin.aboutDescription'), icon: 'mdi-information-outline' },
      { key: 'updates', title: t('admin.checkUpdates'), description: t('admin.checkUpdatesDescription'), icon: 'mdi-update' },
    ],
  },
])

const LINK_KEYS = ['info', 'theme', 'mail', 'oauth']

const aboutDialog = ref(false)
const aboutLoading = ref(false)
const aboutData = ref(null)
const updateLoading = ref(false)
const updateResult = ref(null)
const sourceDialog = ref(false)
const sourceLoading = ref(false)
const sourceSaving = ref(false)
const sourceUrl = ref('')

const aboutRows = computed(() => {
  const data = aboutData.value
  if (!data) return []
  return [
    { key: 'appName', value: data.name },
    { key: 'appVersion', value: `v${data.version}` },
    { key: 'appLaravel', value: data.laravel_version },
    { key: 'appPhp', value: data.php_version },
    { key: 'appEnvironment', value: data.environment },
    { key: 'appUrl', value: data.url },
  ]
})

function openSetting(key) {
  if (LINK_KEYS.includes(key)) {
    router.push(`/options/${key}`)
    return
  }
  if (key === 'about') {
    openAbout()
    return
  }
  if (key === 'updates') {
    checkUpdate()
  }
}

async function openAbout() {
  aboutDialog.value = true
  if (aboutData.value || aboutLoading.value) return
  aboutLoading.value = true
  try {
    const { data } = await http.get('/admin/options/about')
    aboutData.value = data
  } catch {
    aboutData.value = null
  } finally {
    aboutLoading.value = false
  }
}

async function checkUpdate() {
  if (updateLoading.value) return
  updateLoading.value = true
  updateResult.value = null
  try {
    const { data } = await http.get('/admin/options/update')
    updateResult.value = data
  } catch {
    updateResult.value = { check_failed: true }
  } finally {
    updateLoading.value = false
  }
}

async function openSourceDialog() {
  sourceDialog.value = true
  if (sourceLoading.value) return
  sourceLoading.value = true
  try {
    const { data } = await http.get('/admin/options/update-source')
    sourceUrl.value = data.update_source || ''
  } catch {
    sourceUrl.value = ''
  } finally {
    sourceLoading.value = false
  }
}

async function saveSource() {
  if (sourceSaving.value) return
  sourceSaving.value = true
  try {
    await http.put('/admin/options/update-source', { update_source: sourceUrl.value.trim() })
    sourceDialog.value = false
    updateResult.value = null
    await checkUpdate()
  } catch {
    // 校验失败时由表单错误提示处理
  } finally {
    sourceSaving.value = false
  }
}
</script>

<template>
  <v-container class="options-page py-6 px-4 px-md-8">
    <!-- <div class="options-heading mb-6">
      <div>
        <div class="text-overline text-primary font-weight-bold">MDForum / ADMIN / OPTIONS</div>
        <h1 class="text-h4 font-weight-bold mt-1">{{ $t('admin.manageSettings') }}</h1>
      </div>
    </div> -->

    <div v-for="group in settingGroups" :key="group.title" class="mb-5">
      <h2 class="text-subtitle-1 font-weight-bold px-1 mb-2">{{ group.title }}</h2>
      <v-card rounded="lg" class="options-card">
        <v-list lines="two" nav>
          <template v-for="(item, index) in group.items" :key="item.key">
            <v-divider v-if="index" />
            <v-list-item @click="openSetting(item.key)">
              <template #prepend>
                <v-avatar>
                  <v-icon :icon="item.icon" />
                </v-avatar>
              </template>
              <v-list-item-title>{{ item.title }}</v-list-item-title>
              <v-list-item-subtitle v-if="item.key === 'updates' && updateLoading" class="text-primary">
                {{ $t('admin.updateChecking') }}
              </v-list-item-subtitle>
              <v-list-item-subtitle v-else-if="item.key === 'updates' && updateResult"
                :class="updateResult.is_update ? 'text-warning' : 'text-medium-emphasis'">
                <template v-if="!updateResult.source_configured">
                  {{ $t('admin.updateSourceNotConfigured') }}
                  <a class="source-link" href="javascript:void(0)" @click.stop="openSourceDialog">{{ $t('admin.configureSource') }}</a>
                </template>
                <template v-else-if="updateResult.check_failed">
                  {{ $t('admin.updateCheckFailed') }}
                  <a class="source-link" href="javascript:void(0)" @click.stop="openSourceDialog">{{ $t('admin.configureSource') }}</a>
                </template>
                <template v-else>
                  {{ $t('admin.currentVersion') }}: v{{ updateResult.current_version }}
                  <template v-if="updateResult.latest_version">
                    · {{ $t('admin.latestVersion') }}: v{{ updateResult.latest_version }}
                  </template>
                  <template v-if="updateResult.latest_version && !updateResult.is_update">
                    · {{ $t('admin.upToDate') }}
                  </template>
                  <template v-if="updateResult.is_update">
                    ·
                    <a :href="updateResult.download_url || '#'" target="_blank" rel="noopener" @click.stop>
                      {{ $t('admin.downloadLatest') }}
                    </a>
                  </template>
                </template>
              </v-list-item-subtitle>
              <v-list-item-subtitle v-else>{{ item.description }}</v-list-item-subtitle>
              <template #append>
                <v-progress-circular v-if="item.key === 'updates' && updateLoading" indeterminate size="20" width="2" />
                <v-icon v-else icon="mdi-chevron-right" />
              </template>
            </v-list-item>
          </template>
        </v-list>
      </v-card>
    </div>

    <v-dialog v-model="aboutDialog" max-width="520">
      <v-card rounded="lg" >
        <v-card-title class="d-flex align-center">
          <v-icon icon="mdi-information-outline" class="mr-2" color="primary" />
          {{ $t('admin.aboutTitle') }}
        </v-card-title>
        <v-divider />
        <v-card-text>
          <v-skeleton-loader v-if="aboutLoading" type="list-item-three-line" />
          <v-list v-else-if="aboutData" lines="two">
            <v-list-item v-for="row in aboutRows" :key="row.key">
              <v-list-item-title class="text-body-2 text-medium-emphasis">{{ $t(`admin.${row.key}`) }}</v-list-item-title>
              <v-list-item-subtitle class="text-body-1 font-weight-medium">{{ row.value }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <v-alert v-else type="error" variant="tonal">{{ $t('admin.aboutLoadFailed') }}</v-alert>
        </v-card-text>
        <v-card-actions>
          <span class="text-caption text-medium-emphasis px-4">
            {{ $t('admin.appCopyright', { year: aboutData?.year || new Date().getFullYear() }) }}
          </span>
          <v-spacer />
          <v-btn variant="tonal" @click="aboutDialog = false">{{ $t('admin.close') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="sourceDialog" max-width="520">
      <v-card rounded="lg" >
        <v-card-title class="d-flex align-center">
          <v-icon icon="mdi-webhook" class="mr-2" color="primary" />
          {{ $t('admin.updateSource') }}
        </v-card-title>
        <v-divider />
        <v-card-text>
          <v-skeleton-loader v-if="sourceLoading" type="paragraph" />
          <template v-else>
            <v-text-field
              v-model="sourceUrl"
              :label="$t('admin.updateSource')"
              :placeholder="$t('admin.updateSourcePlaceholder')"
              :hint="$t('admin.updateSourceHint')"
              persistent-hint
              type="url"
              density="comfortable"
              clearable
              hide-details="auto"
              class="mb-2"
            />
            <v-alert type="info" variant="tonal" density="compact" class="mb-2">
              {{ $t('admin.updateSourceFormatHint') }}
            </v-alert>
          </template>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="tonal" @click="sourceDialog = false">{{ $t('admin.cancel') }}</v-btn>
          <v-btn
            color="primary"
            :loading="sourceSaving"
            :disabled="sourceLoading"
            @click="saveSource"
          >
            {{ $t('admin.save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.options-page {
  max-width: 830px;
  min-height: 100%;
}

.options-card {
  overflow: hidden;
  /* border-radius: 12px; */
}

.source-link {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
}

.source-link:hover {
  text-decoration: underline;
}
</style>

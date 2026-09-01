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
      { key: 'qqGroup', title: t('admin.joinQQGroup'), description: t('admin.joinQQGroupDescription'), icon: 'mdi-qqchat' },
    ],
  },
])

const LINK_KEYS = ['info', 'theme', 'mail', 'oauth']
const EXTERNAL_LINKS = { qqGroup: 'https://qm.qq.com/q/EjJWTbyudy' }

const aboutDialog = ref(false)
const aboutLoading = ref(false)
const aboutData = ref(null)

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
  if (EXTERNAL_LINKS[key]) {
    window.open(EXTERNAL_LINKS[key], '_blank', 'noopener')
    return
  }
  if (LINK_KEYS.includes(key)) {
    router.push(`/options/${key}`)
    return
  }
  if (key === 'about') {
    openAbout()
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
        <v-list rounded="lg" lines="two" nav>
          <template v-for="(item, index) in group.items" :key="item.key">
            <v-divider v-if="index" />
            <v-list-item @click="openSetting(item.key)">
              <template #prepend>
                <v-avatar>
                  <v-icon :icon="item.icon" />
                </v-avatar>
              </template>
              <v-list-item-title>{{ item.title }}</v-list-item-title>
              <v-list-item-subtitle>{{ item.description }}</v-list-item-subtitle>
              <template #append>
                <v-icon icon="mdi-chevron-right" />
              </template>
            </v-list-item>
          </template>
        </v-list>
      </v-card>
    </div>

    <v-dialog v-model="aboutDialog" max-width="520">
      <v-card rounded="lg">
        <v-toolbar :title="$t('admin.aboutTitle')">
          <template #prepend>
            <v-btn icon variant="text" @click="aboutDialog = false">
              <v-icon>mdi-close</v-icon>
              <v-tooltip activator="parent" location="right">
                {{ $t('common.close') }}
              </v-tooltip>
            </v-btn>
          </template>
        </v-toolbar>

        <v-card-text>
          <v-skeleton-loader v-if="aboutLoading" type="list-item-three-line" />
          <v-list rounded="lg" v-else-if="aboutData" lines="two">
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
            <a href="https://github.com/HaiGeMaster">HaiGeMaster</a>
          </span>
          <!-- <v-spacer />
          <v-btn variant="tonal" @click="aboutDialog = false">{{ $t('admin.close') }}</v-btn> -->
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
</style>

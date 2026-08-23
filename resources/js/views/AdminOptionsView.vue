<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

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

function openSetting(key) {
  if (['info', 'theme', 'mail', 'oauth'].includes(key)) {
    router.push(`/options/${key}`)
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
      <v-card class="options-card" >
        <v-list lines="two" nav>
          <template v-for="(item, index) in group.items" :key="item.key">
            <v-divider v-if="index" />
            <v-list-item :disabled="!['info', 'theme', 'mail', 'oauth'].includes(item.key)" @click="openSetting(item.key)">
              <template #prepend>
                <v-avatar color="primary">
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
  </v-container>
</template>

<style scoped>
.options-page {
  max-width: 830px;
  min-height: 100%;
}

.options-card {
  overflow: hidden;
  border-radius: 12px;
}
</style>

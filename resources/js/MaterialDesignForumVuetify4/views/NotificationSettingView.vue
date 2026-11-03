<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../stores/auth'
import { useNotificationSettingViewStore } from '../stores/views/notificationSetting'
import { useFeedback } from '../composables/useFeedback'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { show } = useFeedback()

const settingStore = useNotificationSettingViewStore()
const { groupedSettings, allWeb, allEmail, saving } = storeToRefs(settingStore)

async function loadSettings() {
  try {
    await settingStore.ensureLoaded()
  } catch (e) {
    show(e.message, 'error')
  }
}

async function saveSettings() {
  try {
    await settingStore.saveSettings()
    show(t('notificationSetting.saved'), 'success')
  } catch (e) {
    show(e.message, 'error')
  }
}

onMounted(() => {
  if (authStore.isLoggedIn) loadSettings()
})
</script>

<template>
  <v-container :class="xs ? 'px-0' : 'px-4'" class=" notification-setting-view" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-card rounded="lg" >
      <v-toolbar :title="$t('notificationSetting.title')">
        <template #prepend>
          <v-btn icon variant="text" @click="router.back()">
            <v-icon>mdi-arrow-left</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.back') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <v-btn
            icon
            variant="text"
            :loading="saving"
            :disabled="!authStore.isLoggedIn"
            @click="saveSettings"
          >
            <v-icon>mdi-check</v-icon>
            <v-tooltip activator="parent" location="left">
              {{ $t('notificationSetting.save') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-divider />

      <!-- 未登录：提示登录 -->
      <v-card-text v-if="!authStore.isLoggedIn" class="pa-6">
        <v-empty-state
          icon="mdi-bell-cog-outline"
          :title="$t('notifications.loginTitle')"
          :text="$t('notifications.loginText')"
        >
          <template #actions>
            <v-btn color="primary" variant="tonal" @click="router.push('/login')">
              {{ $t('common.login') }}
            </v-btn>
          </template>
        </v-empty-state>
      </v-card-text>

      <v-card-text v-else class="pa-0">
        <v-table density="comfortable">
          <thead>
            <tr>
              <th>{{ $t('notificationSetting.item') }}</th>
              <th class="text-center">
                <div class="d-flex flex-column align-center">
                  <span>{{ $t('notificationSetting.siteNotification') }}</span>
                  <v-checkbox
                    v-model="allWeb"
                    density="compact"
                    hide-details
                    color="primary"
                    :title="$t('notificationSetting.toggleAll')"
                  />
                </div>
              </th>
              <th class="text-center">
                <div class="d-flex flex-column align-center">
                  <span>{{ $t('notificationSetting.emailNotification') }}</span>
                  <v-checkbox
                    v-model="allEmail"
                    density="compact"
                    hide-details
                    color="primary"
                    :title="$t('notificationSetting.toggleAll')"
                  />
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <template v-for="group in groupedSettings" :key="group.name">
              <tr class="group-header">
                <td colspan="3">
                  <div class="d-flex align-center ga-2 px-1">
                    <v-icon :icon="group.icon" size="small" />
                    <span>{{ $t(`notificationCategory.${group.name}`) }}</span>
                    <span class="text-caption opacity-70">({{ group.items.length }})</span>
                  </div>
                </td>
              </tr>
              <tr v-for="item in group.items" :key="item.name">
                <td class="text-body-2">{{ $t(`notificationTypeName.${item.name}`) }}</td>
                <td class="text-center">
                  <v-checkbox v-model="item.web_message"
                    color="primary" density="compact" hide-details class="d-inline-flex" />
                </td>
                <td class="text-center">
                  <v-checkbox v-model="item.email_message"
                    color="primary" density="compact" hide-details class="d-inline-flex" />
                </td>
              </tr>
            </template>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<style scoped>
/* 分组标题行：浅色背景 + 主色文字，自动适配明暗主题 */
.group-header td {
  background: rgb(var(--v-theme-surface-light));
  /* color: rgb(var(--v-theme-primary)); */
  font-weight: 600;
  letter-spacing: 0.02em;
}
</style>

<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCookiePreferenceStore } from '../stores/cookiePreference'
import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

import LanguageSwitcher from './LanguageSwitcher.vue'

const { t } = useI18n()
const store = useCookiePreferenceStore()

// 将 store 中的 i18n key 解析为当前语言的文本，切换语言时自动更新
const localizedCategories = computed(() =>
  store.categories.map((cat) => ({
    ...cat,
    name: t(cat.nameKey),
    desc: t(cat.descKey),
    intro: t(cat.introKey),
    legalBasis: t(cat.legalBasisKey),
    note: cat.noteKey ? t(cat.noteKey) : '',
    cookies: cat.cookies.map((ck) => ({
      ...ck,
      provider: t(ck.providerKey),
      purpose: t(ck.purposeKey),
      type: t(ck.typeKey),
      duration: t(ck.durationKey),
    })),
  })),
)

onMounted(() => {
  store.init()
})
</script>

<template>
  <!-- ========== 第一层：底部弹层横幅 ========== -->
  <v-bottom-sheet v-model="store.bannerSheet" :persistent="!store.decided">
    <v-card>
      <v-toolbar :title="t('cookiePref.banner.title')">
        <template #prepend>
          <v-btn icon variant="text">
            <v-icon>mdi-cookie-check</v-icon>
          </v-btn>
        </template>
        <template #append>
            <LanguageSwitcher />
          <v-btn
            v-if="store.decided"
            icon
            color="error"
            variant="tonal"
            @click="store.closeBanner()"
          >
            <v-icon>mdi-close</v-icon>
            <v-tooltip activator="parent" location="left">
              {{ t('cookiePref.banner.close') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-card-text>
        <p class="text-body-2 mb-2">
          {{ t('cookiePref.banner.body') }}<a
            href="/cookie-policy"
            target="_blank"
            rel="noopener noreferrer"
            >{{ t('cookiePref.banner.policyLink') }}</a
          >。
        </p>
        <p class="text-caption text-medium-emphasis">
          {{ t('cookiePref.banner.hint') }}
        </p>
      </v-card-text>

      <v-card-actions class="flex-wrap">
        <v-btn variant="outlined" color="grey-darken-2" @click="store.rejectAll()">
          {{ t('cookiePref.banner.rejectAll') }}
        </v-btn>
        <v-btn
          variant="outlined"
          color="primary"
          prepend-icon="mdi-tune-variant"
          @click="store.openPreferenceCenter()"
        >
          {{ t('cookiePref.banner.customize') }}
        </v-btn>
        <v-btn color="primary" variant="flat" @click="store.acceptAll()">
          {{ t('cookiePref.banner.acceptAll') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-bottom-sheet>

  <!-- ========== 第二层：偏好中心对话框 ========== -->
  <v-dialog v-model="store.prefSheet" :persistent="!store.decided" width="750"
    :fullscreen="xs">
    <v-card class="pref-card">
      <v-toolbar :title="t('cookiePref.pref.title')">
        <template #prepend>
          <v-btn icon variant="text">
            <v-icon>mdi-tune-variant</v-icon>
          </v-btn>
        </template>
        <template #append>
            <LanguageSwitcher v-if="!store.decided"/>
          <v-btn v-if="store.decided" icon variant="text" @click="store.closePreferenceCenter()">
            <v-icon>mdi-close</v-icon>
            <v-tooltip activator="parent" location="left">
              {{ $t('common.close') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <div class="pref-body pa-4">
        <p class="text-body-2 mb-4">
          {{ t('cookiePref.pref.intro') }}<a
            href="/cookie-policy"
            target="_blank"
            rel="noopener noreferrer"
            >{{ t('cookiePref.pref.policyLink') }}</a
          >。
        </p>

        <v-expansion-panels v-model="store.openedPanels" multiple>
          <v-expansion-panel
            v-for="cat in localizedCategories"
            :key="cat.key"
          >
            <v-expansion-panel-title>
              <div class="d-flex panel-head">
                <div class="flex-grow-1">
                  <div class="text-body-2 font-weight-medium mb-2">
                    {{ cat.name }}
                    <v-chip v-if="cat.required" size="x-small" class="ml-2">
                      {{ t('cookiePref.pref.alwaysOn') }}
                    </v-chip>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    {{ cat.desc }} · {{ t('cookiePref.pref.countPrefix') }}{{ cat.cookies.length }}{{ t('cookiePref.pref.countSuffix') }}
                  </div>
                </div>
                <div class="flex-shrink-0" @click.stop>
                  <v-switch
                    :model-value="cat.required ? true : store.prefs[cat.key]"
                    :disabled="cat.required"
                    :color="cat.required ? 'grey' : 'primary'"
                    hide-details
                    inset
                    @update:model-value="(v) => store.toggleCategory(cat, v)"
                  ></v-switch>
                </div>
              </div>
            </v-expansion-panel-title>

            <v-expansion-panel-text>
              <p class="text-body-2 mb-2">{{ cat.intro }}</p>
              <div class="text-caption mb-1">
                {{ t('cookiePref.pref.legalBasisLabel') }}{{ cat.legalBasis }}
              </div>

              <div class="cookie-tbl-wrap">
                <table class="cookie-tbl">
                  <thead>
                    <tr>
                      <th>{{ t('cookiePref.pref.colName') }}</th>
                      <th>{{ t('cookiePref.pref.colProvider') }}</th>
                      <th>{{ t('cookiePref.pref.colPurpose') }}</th>
                      <th>{{ t('cookiePref.pref.colType') }}</th>
                      <th>{{ t('cookiePref.pref.colDuration') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="ck in cat.cookies" :key="ck.name">
                      <td class="cookie-name">{{ ck.name }}</td>
                      <td>{{ ck.provider }}</td>
                      <td>{{ ck.purpose }}</td>
                      <td>{{ ck.type }}</td>
                      <td>{{ ck.duration }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="cat.note" class="text-caption mt-2 text-medium-emphasis">
                {{ cat.note }}
              </div>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>

        <v-alert
          v-if="store.dirty"
          class="mt-4"
          type="info"
          density="compact"
          variant="tonal"
          :text="t('cookiePref.pref.dirtyHint')"
        ></v-alert>

        <p v-if="store.decided" class="text-caption text-medium-emphasis mt-4 mb-0">
          {{ t('cookiePref.pref.lastUpdatedLabel', { time: store.lastUpdated || '-' }) }}。{{ t('cookiePref.pref.lastUpdatedHint') }}
        </p>
      </div>

      <v-divider></v-divider>

      <v-card-actions class="flex-wrap">
        <v-btn variant="text" color="error" @click="store.rejectAll()">
          {{ t('cookiePref.pref.rejectAll') }}
        </v-btn>
        <v-spacer></v-spacer>
        <v-btn
          variant="outlined"
          color="grey-darken-2"
          :disabled="!store.dirty"
          @click="store.revertChanges()"
        >
          {{ t('cookiePref.pref.revert') }}
        </v-btn>
        <v-btn color="primary" variant="flat" @click="store.savePrefs()">
          {{ t('cookiePref.pref.save') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.pref-card {
  max-height: 84vh;
  display: flex;
  flex-direction: column;
}
.pref-body {
  overflow-y: auto;
  overflow-x: hidden;
  flex: 1 1 auto;
  min-height: 0;
  -webkit-overflow-scrolling: touch;
}

.cookie-tbl-wrap {
  overflow-x: auto;
  margin-top: 8px;
}
table.cookie-tbl {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  line-height: 1.5;
}
table.cookie-tbl th,
table.cookie-tbl td {
  padding: 8px 12px;
  text-align: left;
  vertical-align: top;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.12);
}
table.cookie-tbl th {
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.6);
  white-space: nowrap;
  background: rgba(var(--v-theme-on-surface), 0.03);
}
table.cookie-tbl td {
  color: rgba(var(--v-theme-on-surface), 0.87);
}
table.cookie-tbl tbody tr:hover {
  background: rgba(var(--v-theme-on-surface), 0.02);
}
.cookie-name {
  font-family: 'SFMono-Regular', Consolas, monospace;
  font-size: 12px;
  white-space: nowrap;
}

.panel-head {
  width: 100%;
  align-items: center;
}
</style>

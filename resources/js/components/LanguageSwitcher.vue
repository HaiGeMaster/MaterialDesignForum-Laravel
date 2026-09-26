<script setup>
import { useI18n } from 'vue-i18n'
import {
  setLocale,
  // getBaseLanguagePackList
 } from '../i18n'

const { locale,t } = useI18n()

// 语言列表：用各语言的原生名称展示，需要使用 baseLanguagePackInfo.langname 作为label

// const languages = getBaseLanguagePackList()
const languages = [
    // 'de-DE': deDE,
    // 'en-GB': enGB,
    // 'en-US': enUS,
    // 'fr-FR': frFR,
    // 'ja-JP': jaJP,
    // 'ko-KR': koKR,
    // 'ru-RU': ruRU,
    // 'zh-CN': zhCN,
  // 'zh-TW': zhTW,

  { code: 'de-DE', label: "Deutsch (Deutschland)" },
  { code: 'en-GB', label: "English (United Kingdom)" },
  { code: 'en-US', label: "English (United States)" },
  { code: 'fr-FR', label: "Français (France)" },
  { code: 'ja-JP', label: "日本語 (日本)" },
  { code: 'ko-KR', label: "한국어 (대한민국)" },
  { code: 'ru-RU', label: "Русский (Россия)" },
  { code: 'zh-CN', label: "简体中文(中国)" },
  { code: 'zh-TW', label: "繁體中文(中国台灣)" },
]

function switchLanguage(code) {
  setLocale(code)
}
</script>

<template>
  <v-menu  min-width="160">
    <template #activator="{ props }">
      <v-btn
        icon
        variant="text"
        v-bind="props"
        :title="$t('appBar.switchLanguage')"
      >
        <v-icon icon="mdi-translate" />
      </v-btn>
    </template>
    <v-list rounded="lg" density="compact" nav>
      <v-list-item
        v-for="lang in languages"
        :key="lang.code"
        :active="locale === lang.code"
        :prepend-icon="locale === lang.code ? 'mdi-check' : undefined"
        :title="lang.label"
        @click="switchLanguage(lang.code)"
      />
    </v-list>
  </v-menu>
</template>

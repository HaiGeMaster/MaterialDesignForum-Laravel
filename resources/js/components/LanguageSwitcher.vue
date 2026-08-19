<script setup>
import { useI18n } from 'vue-i18n'
import { setLocale } from '../i18n'

const { locale } = useI18n()

// 语言列表：用各语言的原生名称展示，无需翻译
const languages = [
  { code: 'zh', label: '中文' },
  { code: 'en', label: 'English' },
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
    <v-list density="compact" nav>
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

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
    setLocale,
    // getBaseLanguagePackList
} from '../i18n'
import { useAuthStore } from '../stores/auth'
import { useUserStore } from '../stores/user'


const { locale, t } = useI18n()

const authStore = useAuthStore()
const userStore = useUserStore()

const props = defineProps({
    mode: { type: String, default: 'icon' },
})

const mode = computed(() => props.mode)


import { useLocale } from 'vuetify'
const { current } = useLocale()

  function changeVuetifyLocale (locale) {
    current.value = locale
  }


// 语言列表：用各语言的原生名称展示，需要使用 baseLanguagePackInfo.langname 作为label

// const languages = getBaseLanguagePackList()
const languages = [
    { code: 'de-DE', label: "Deutsch (Deutschland)" },
    { code: 'en-GB', label: "English (United Kingdom)" },
    { code: 'en-US', label: "English (United States)" },
    { code: 'fr-FR', label: "Français (France)" },
    { code: 'ja-JP', label: "日本語 (日本)" },
    { code: 'ko-KR', label: "한국어 (대한민국)" },
    { code: 'ru-RU', label: "Русский (Россия)" },
    { code: 'zh-CN', label: "简体中文(中国)" },
    { code: 'zh-TW', label: "繁體中文(中国台灣)" },

    //de-DE/en-GB/en-US/fr-FR/ja-JP/ko-KR/ru-RU/zh-CN/zh-TW
]

async function switchLanguage(code) {
    setLocale(code)
    changeVuetifyLocale(t('baseLanguagePackInfo.vuetifyLangcode'))
    // 已登录用户：把语言标识符（如 zh-CN / en-US）记录到 user 表 language 字段
    if (authStore.isLoggedIn) {
        try {
            await userStore.updateProfile({ language: code })
            if (authStore.user) authStore.user.language = code
        } catch {
            // 保存失败不影响本地语言切换
        }
    }
}
</script>

<template>
    <v-menu min-width="160">
        <template #activator="{ props }">
            <v-btn v-if="mode=='icon'" icon variant="text" v-bind="props">
                <v-icon icon="mdi-translate" />
                <v-tooltip activator="parent" location="bottom">{{ $t('appBar.switchLanguage') }}</v-tooltip>
            </v-btn>
            <v-btn v-if="mode=='btn'" text v-bind="props">
                <v-icon icon="mdi-translate" />
                <v-tooltip activator="parent" location="bottom">{{ $t('appBar.switchLanguage') }}</v-tooltip>
                {{ $t('appBar.switchLanguage') }}
            </v-btn>
        </template>
        <v-list rounded="lg" density="compact" nav>
            <v-list-item v-for="lang in languages" :key="lang.code" :active="locale === lang.code"
                :prepend-icon="locale === lang.code ? 'mdi-check' : undefined" :title="lang.label"
                @click="switchLanguage(lang.code)" />
        </v-list>
    </v-menu>
</template>

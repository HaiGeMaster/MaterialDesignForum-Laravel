<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useTheme } from 'vuetify'
import { useI18n } from 'vue-i18n'
import { BUILTIN_THEMES } from '../plugins/vuetify'
import { availableThemes, defaultTheme, resolveTheme } from '../themeConfig'

const theme = useTheme()
const { t } = useI18n()

const menu = ref(false)

// 当前用户存储的主题偏好（可能是 'system'）
const storedTheme = ref(localStorage.getItem('mdforum-theme') || defaultTheme.value || 'system')

// 当前实际生效是否为深色（system 模式下跟随系统）
const isDark = computed(() => theme.current.value?.dark ?? false)

// 当前主题图标
const currentIcon = computed(() => {
  if (storedTheme.value === 'system') return 'mdi-theme-light-dark'
  return themeMeta(storedTheme.value)?.icon || 'mdi-palette'
})

// 合并内置 + 自定义主题元数据（availableThemes 来自后端配置）
const themeList = computed(() => {
  const available = availableThemes.value.length
    ? availableThemes.value
    : BUILTIN_THEMES.map(th => th.name)

  return available.map(name => {
    const builtin = BUILTIN_THEMES.find(th => th.name === name)
    return builtin || { name, dark: theme.themes.value[name]?.dark ?? false, icon: 'mdi-palette', label: name }
  })
})

function themeMeta(name) {
  return BUILTIN_THEMES.find(th => th.name === name) || null
}

// 选择主题
function selectTheme(name) {
  storedTheme.value = name
  localStorage.setItem('mdforum-theme', name)

  const actual = name === 'system' ? resolveTheme('system') : name
  if (document.startViewTransition) {
    document.startViewTransition(() => {
      theme.change(actual)
    })
  } else {
    theme.change(actual)
  }
  menu.value = false
}

// 监听系统主题变化（仅在 system 模式下生效）
let mql = null
function handleSystemChange() {
  if (storedTheme.value === 'system') {
    theme.change(resolveTheme('system'))
  }
}

onMounted(() => {
  // 初始应用存储的主题
  if (storedTheme.value) {
    const actual = storedTheme.value === 'system' ? resolveTheme('system') : storedTheme.value
    if (theme.themes.value[actual]) {
      theme.change(actual)
    }
  }
  // 监听系统颜色偏好变化
  mql = window.matchMedia('(prefers-color-scheme: dark)')
  mql.addEventListener('change', handleSystemChange)
})

onUnmounted(() => {
  mql?.removeEventListener('change', handleSystemChange)
})
</script>

<template>
  <v-menu min-width="200">
    <template #activator="{ props }">
      <v-btn
        icon
        variant="text"
        v-bind="props"
        :title="$t('appBar.switchTheme')"
      >
        <v-icon :icon="currentIcon" />
      </v-btn>
    </template>
    <v-list rounded="lg" density="compact" nav>
      <!-- System 选项 -->
      <v-list-item
        :active="storedTheme === 'system'"
        :prepend-icon="storedTheme === 'system' ? 'mdi-check' : 'mdi-theme-light-dark'"
        :title="$t('themeDialog.system')"
        @click="selectTheme('system')"
      />
      <!-- 各主题选项 -->
      <v-list-item
        v-for="th in themeList"
        :key="th.name"
        :active="storedTheme === th.name"
        :prepend-icon="storedTheme === th.name ? 'mdi-check' : th.icon"
        :title="th.label"
        @click="selectTheme(th.name)"
      />
    </v-list>
  </v-menu>
</template>

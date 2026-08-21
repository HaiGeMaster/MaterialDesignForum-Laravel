<script setup>
import { computed, onMounted } from 'vue'
import { useTheme } from 'vuetify'

const theme = useTheme()

// 当前实际生效是否为深色（system 模式下跟随系统）
// 注意：theme.current 是 Ref，当前主题对象在其 .value 上
const isDark = computed(() => theme.current.value?.dark ?? false)

// 手动切换浅色/深色，并记住用户选择
function toggleTheme() {
  const next = isDark.value ? 'light' : 'dark'
  theme.change(next)
  localStorage.setItem('mdforum-theme', next)
}

// 圆形扩散过渡切换主题（View Transitions API）
// 设置圆心为点击坐标，动画样式见 resources/css/app.css
function handleThemeClick(event) {
  document.documentElement.style.setProperty('--x', event.clientX + 'px')
  document.documentElement.style.setProperty('--y', event.clientY + 'px')
  if (document.startViewTransition) {
    document.startViewTransition(() => {
      toggleTheme()
    })
  } else {
    toggleTheme()
  }
}

// 恢复用户上次手动选择的主题（未选过则保持跟随系统）
onMounted(() => {
  const saved = localStorage.getItem('mdforum-theme')
  if (saved === 'light' || saved === 'dark') {
    theme.change(saved)
  }
})
</script>

<template>
  <v-btn
    icon
    variant="text"
    :title="isDark ? $t('appBar.switchLight') : $t('appBar.switchDark')"
    @click="handleThemeClick"
  >
    <v-icon :icon="isDark ? 'mdi-white-balance-sunny' : 'mdi-weather-night'" />
  </v-btn>
</template>

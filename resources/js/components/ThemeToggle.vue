<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useTheme } from 'vuetify'
import { useI18n } from 'vue-i18n'
import { BUILTIN_THEMES } from '../plugins/vuetify'
import { availableThemes, defaultTheme, resolveTheme } from '../themeConfig'

const theme = useTheme()
const { t } = useI18n()

const dialog = ref(false)

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
  dialog.value = false
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
  <!-- 触发按钮 -->
  <v-btn
    icon
    variant="text"
    :title="$t('appBar.switchTheme')"
    @click="dialog = true"
  >
    <v-icon :icon="currentIcon" />
  </v-btn>

  <!-- 主题选择对话框 -->
  <v-dialog v-model="dialog" max-width="480" scrollable>
    <v-card rounded="lg" >
      <v-card-title class="d-flex align-center pa-4 pb-2">
        <v-icon icon="mdi-palette-outline" class="me-2" />
        {{ $t('themeDialog.title') }}
      </v-card-title>

      <v-card-text class="pa-3 pt-1">
        <v-row dense>
          <!-- System 选项 -->
          <v-col cols="6" sm="4">
            <v-card
              :variant="storedTheme === 'system' ? 'tonal' : 'outlined'"
              :color="storedTheme === 'system' ? 'primary' : undefined"
              class="theme-card cursor-pointer"
              @click="selectTheme('system')"
            >
              <v-card-text class="text-center pa-4">
                <v-icon size="32" class="mb-2">mdi-theme-light-dark</v-icon>
                <div class="text-caption font-weight-medium">{{ $t('themeDialog.system') }}</div>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- 各主题选项 -->
          <v-col v-for="th in themeList" :key="th.name" cols="6" sm="4">
            <v-theme-provider :theme="th.name" reset>
              <v-card
                :variant="storedTheme === th.name ? 'tonal' : 'outlined'"
                :color="storedTheme === th.name ? 'primary' : undefined"
                class="theme-card cursor-pointer"
                @click="selectTheme(th.name)"
              >
                <v-card-text class="text-center pa-4">
                  <v-icon size="32" class="mb-2">{{ th.icon }}</v-icon>
                  <div class="text-caption font-weight-medium">{{ th.label }}</div>
                </v-card-text>
              </v-card>
            </v-theme-provider>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.theme-card {
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.theme-card:hover {
  transform: translateY(-2px);
}
</style>

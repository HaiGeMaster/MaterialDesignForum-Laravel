<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// 模块级共享：全局 z-index 计数器，支持多个窗口叠加并点击置顶
let globalZ = 1900
function nextZ() {
  return ++globalZ
}

// 移动端断点与 Vuetify 一致（<600px）：手机上窗口默认全屏
const MOBILE_QUERY = '(max-width: 600px)'
// 窗口距视口边缘的默认边距（px）：默认出现在右下角
const MARGIN = 16

const props = defineProps({
  /** 窗口标题 */
  title: { type: String, default: '' },
  /** 标题栏左侧图标 */
  icon: { type: String, default: 'mdi-window' },
  /** 初始宽度（px） */
  width: { type: Number, default: 420 },
  /** 初始高度（px） */
  height: { type: Number, default: 320 },
  /** 初始横坐标（px，默认贴右下角、距边缘 16px） */
  x: {
    type: Number,
    default: (p) => {
      if (typeof window === 'undefined') return MARGIN
      return Math.max(MARGIN, window.innerWidth - p.width - MARGIN)
    },
  },
  /** 初始纵坐标（px，默认贴右下角、距边缘 16px） */
  y: {
    type: Number,
    default: (p) => {
      if (typeof window === 'undefined') return MARGIN
      return Math.max(MARGIN, window.innerHeight - p.height - MARGIN)
    },
  },
})

const emit = defineEmits(['close'])

// 最小化时的标题栏高度
const BAR_HEIGHT = 36
// 最小化时的宽度
const MIN_WIDTH = 240
// 尺寸下限
const MIN_CONTENT_WIDTH = 260
const MIN_CONTENT_HEIGHT = 160

const visible = ref(true)
const minimized = ref(false)
const maximized = ref(false)
const interacting = ref(false)
const isMobile = ref(false)
const x = ref(props.x)
const y = ref(props.y)
const w = ref(props.width)
const h = ref(props.height)
// 初始层级取当前全局值，新窗口自然叠在已有窗口之上
const zIndex = ref(globalZ)

// 监听屏幕尺寸变化：手机上默认全屏，且取消最小化状态
let mql = null
function updateMobile(e) {
  isMobile.value = e.matches
  if (e.matches && minimized.value) {
    minimized.value = false
  }
}

onMounted(() => {
  mql = window.matchMedia(MOBILE_QUERY)
  isMobile.value = mql.matches
  mql.addEventListener('change', updateMobile)
})

// 记录最大化前的位置尺寸，恢复时还原
let prevRect = { x: props.x, y: props.y, w: props.width, h: props.height }

// 拖拽 / 缩放状态
let drag = null

const windowStyle = computed(() => {
  // 手机上打开默认全屏
  if (maximized.value || isMobile.value) {
    return { left: '0px', top: '0px', width: '100vw', height: '100vh', zIndex: zIndex.value }
  }
  return {
    left: `${x.value}px`,
    top: `${y.value}px`,
    width: `${minimized.value ? MIN_WIDTH : w.value}px`,
    height: `${minimized.value ? BAR_HEIGHT : h.value}px`,
    zIndex: zIndex.value,
  }
})

// 点击窗口任意处，将其层级提到全局最高，覆盖其他窗口
function focusWindow() {
  zIndex.value = nextZ()
}

function clamp(v, min, max) {
  return Math.min(Math.max(v, min), max)
}

// —— 拖动 ——
function startDrag(e) {
  // 最大化/移动端全屏状态下不可拖动；最小化时仅剩标题栏，仍可拖动；点击按钮不触发
  if (maximized.value || isMobile.value) return
  if (e.target.closest('button')) return
  drag = { type: 'move', startX: e.clientX, startY: e.clientY, ox: x.value, oy: y.value }
  interacting.value = true
  document.addEventListener('mousemove', onMouseMove)
  document.addEventListener('mouseup', onMouseUp)
}

// —— 缩放（右下角手柄）——
function startResize(e) {
  if (maximized.value || minimized.value || isMobile.value) return
  e.preventDefault()
  drag = { type: 'resize', startX: e.clientX, startY: e.clientY, ow: w.value, oh: h.value }
  interacting.value = true
  document.addEventListener('mousemove', onMouseMove)
  document.addEventListener('mouseup', onMouseUp)
}

function onMouseMove(e) {
  if (!drag) return
  if (drag.type === 'move') {
    // 最小化时按标题栏实际宽度计算右边界，保证能拖到最右侧；各方向保留 16px 边距
    const dragW = minimized.value ? MIN_WIDTH : w.value
    x.value = clamp(drag.ox + (e.clientX - drag.startX), MARGIN, window.innerWidth - dragW - MARGIN)
    y.value = clamp(drag.oy + (e.clientY - drag.startY), MARGIN, window.innerHeight - BAR_HEIGHT - MARGIN)
  } else {
    w.value = clamp(drag.ow + (e.clientX - drag.startX), MIN_CONTENT_WIDTH, window.innerWidth)
    h.value = clamp(drag.oh + (e.clientY - drag.startY), MIN_CONTENT_HEIGHT, window.innerHeight)
  }
}

function onMouseUp() {
  drag = null
  interacting.value = false
  document.removeEventListener('mousemove', onMouseMove)
  document.removeEventListener('mouseup', onMouseUp)
}

onBeforeUnmount(() => {
  mql?.removeEventListener('change', updateMobile)
  document.removeEventListener('mousemove', onMouseMove)
  document.removeEventListener('mouseup', onMouseUp)
})

// —— 窗口控制 ——
function toggleMinimize() {
  minimized.value = !minimized.value
}

function toggleMaximize() {
  if (maximized.value) {
    // 恢复之前的位置与尺寸
    maximized.value = false
    x.value = prevRect.x
    y.value = prevRect.y
    w.value = prevRect.w
    h.value = prevRect.h
  } else {
    prevRect = { x: x.value, y: y.value, w: w.value, h: h.value }
    maximized.value = true
  }
}

function close() {
  visible.value = false
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="window-pop" appear>
    <div
      v-if="visible"
      class="floating-window"
      :class="{
        'floating-window--maximized': maximized || isMobile,
        'floating-window--no-anim': interacting,
      }"
      :style="windowStyle"
      @mousedown="focusWindow"
    >
      <!-- 顶部应用栏：左图标+名称，右最小化/最大化/关闭 -->
      <div class="floating-window__bar" @mousedown.prevent="startDrag">
        <v-icon :icon="icon" size="small" class="text-primary mr-1" />
        <span class="text-subtitle-2 font-weight-medium text-truncate">
          {{ title || $t('floatingWindow.defaultTitle') }}
        </span>
        <v-spacer />
        <!-- 手机上窗口默认全屏，隐藏最小化/最大化按钮 -->
        <v-tooltip v-if="!isMobile" :text="minimized ? $t('floatingWindow.restore') : $t('floatingWindow.minimize')" >
          <template #activator="{ props: tipProps }">
            <v-btn
              v-bind="tipProps"
              size="x-small"
              variant="text"
              icon
              @click.stop="toggleMinimize"
            >
              <v-icon :icon="minimized ? 'mdi-window-restore' : 'mdi-minus'" size="small" />
            </v-btn>
          </template>
        </v-tooltip>
        <v-tooltip v-if="!isMobile" :text="maximized ? $t('floatingWindow.restore') : $t('floatingWindow.maximize')" >
          <template #activator="{ props: tipProps }">
            <v-btn
              v-bind="tipProps"
              size="x-small"
              variant="text"
              icon
              @click.stop="toggleMaximize"
            >
              <v-icon :icon="maximized ? 'mdi-window-restore' : 'mdi-window-maximize'" size="small" />
            </v-btn>
          </template>
        </v-tooltip>
        <v-tooltip :text="$t('floatingWindow.close')" >
          <template #activator="{ props: tipProps }">
            <v-btn v-bind="tipProps" size="x-small" variant="text" icon @click.stop="close">
              <v-icon icon="mdi-window-close" size="small" />
            </v-btn>
          </template>
        </v-tooltip>
      </div>

      <!-- 内容区域（插槽） -->
      <div v-show="!minimized" class="floating-window__content">
        <slot />
      </div>

      <!-- 右下角缩放手柄（手机上全屏不可缩放） -->
      <div
        v-show="!minimized && !maximized && !isMobile"
        class="floating-window__resize"
        @mousedown="startResize"
      />
    </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.floating-window {
  position: fixed;
  z-index: 1900;
  display: flex;
  flex-direction: column;
  min-width: 240px;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
  overflow: hidden;
  /* 最小化 / 最大化 / 还原时平滑过渡 */
  transition: left 0.22s ease, top 0.22s ease, width 0.22s ease, height 0.22s ease;
}

/* 拖拽 / 缩放进行中禁用过渡，避免跟手延迟 */
.floating-window--no-anim {
  transition: none;
}

.floating-window--maximized {
  border-radius: 0;
  border: none;
}

/* 顶部应用栏 */
.floating-window__bar {
  display: flex;
  align-items: center;
  gap: 2px;
  flex: none;
  height: 36px;
  padding: 0 4px 0 12px;
  background: rgb(var(--v-theme-primary), 0.08);
  border-bottom: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
  cursor: move;
  user-select: none;
}

.floating-window__bar :deep(.v-btn) {
  cursor: pointer;
}

/* 内容区 */
.floating-window__content {
  flex: 1;
  min-height: 0;
  overflow: auto;
}

/* 右下角缩放手柄 */
.floating-window__resize {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 16px;
  height: 16px;
  cursor: nwse-resize;
  background: linear-gradient(
    135deg,
    transparent 50%,
    rgb(var(--v-theme-primary), 0.35) 50%
  );
  border-radius: 0 0 10px 0;
}

/* 出现 / 关闭：缩放 + 淡入 */
.window-pop-enter-active {
  transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.2, 0.9, 0.3, 1.15);
}

.window-pop-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.window-pop-enter-from,
.window-pop-leave-to {
  opacity: 0;
  transform: scale(0.85);
}
</style>

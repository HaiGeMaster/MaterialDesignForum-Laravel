<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

/* ============================================================
 * 全局窗口栈（挂在 window 上，而不是模块作用域）
 *
 * 为什么不用模块级变量：Vite HMR、动态 import 重复求值、组件被打包进
 * 多个 chunk 时，模块作用域会各建各的数组 —— 表现为"点了没反应"。
 * 挂到 window 上可保证所有实例共享同一个栈。
 *
 * 数组末尾 = 最顶层。点击任意窗口 → 移到栈顶 → 统一重排 z-index。
 * ============================================================ */
const Z_BASE = 1900

function getStack() {
  if (typeof window === 'undefined') return []
  if (!window.__floatingWindowStack) {
    window.__floatingWindowStack = []
  }
  return window.__floatingWindowStack
}

function restack() {
  const stack = getStack()
  stack.forEach((win, i) => {
    win.z.value = Z_BASE + i + 1
    win.isTop.value = i === stack.length - 1
  })
}

/** 新窗口入栈即置顶，天然叠在已有窗口之上 */
function register(win) {
  const stack = getStack()
  stack.push(win)
  restack()
}

function unregister(win) {
  const stack = getStack()
  const i = stack.indexOf(win)
  if (i > -1) stack.splice(i, 1)
  restack()
}

/** 移到栈顶；已在栈顶则跳过，避免无意义重排 */
function raise(win) {
  const stack = getStack()
  if (stack[stack.length - 1] === win) return
  const i = stack.indexOf(win)
  if (i > -1) stack.splice(i, 1)
  stack.push(win)
  restack()
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
  /** 是否显示侧边导航栏 */
  showDrawer: { type: Boolean, default: false },
  /** 应用栏密度 */
  appbarDensity: { type: String, default: 'default' },
  /** 是否显示应用栏扩展区域 */
  appbarExtended: { type: Boolean, default: false },
  /** 开启后在控制台打印层级变化，用于排查 */
  debug: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

// 最小化时的标题栏高度
const BAR_HEIGHT = 48
// 最小化时的宽度
const MIN_WIDTH = 280
// 尺寸下限
const MIN_CONTENT_WIDTH = 260
const MIN_CONTENT_HEIGHT = 160

const visible = ref(true)
const minimized = ref(false)
const maximized = ref(false)
const interacting = ref(false)
const isMobile = ref(false)
const drawer = ref(false)
const x = ref(props.x)
const y = ref(props.y)
const w = ref(props.width)
const h = ref(props.height)

// 窗口根元素，用于判断点击事件是否落在本窗口内
const rootEl = ref(null)

// 层级：初始占位，实际值由全局栈在 register() 时统一分配
const zIndex = ref(Z_BASE)
const isTop = ref(false)
// 放进全局栈的句柄（持有响应式引用，供 restack 跨实例写入）
const winHandle = { z: zIndex, isTop, debug: () => props.debug }

function log(...args) {
  if (props.debug) {
    console.log('[FloatingWindow]', props.title || '(untitled)', ...args)
  }
}

/* ------------------------------------------------------------
 * 置顶：document 级捕获监听
 *
 * 捕获阶段挂在 document 上是整个文档树里最早能拿到事件的点，
 * 窗口内容里的 stopPropagation / stopImmediatePropagation 都拦不住它。
 * 组件根节点上的监听会被内容里的第三方组件（编辑器、canvas、菜单）
 * 打断，document 上不会。
 * ------------------------------------------------------------ */
function onDocPointerDown(e) {
  const el = rootEl.value
  if (!el || !e.target) return
  // contains 对 Text 节点也有效；iframe 内部点击父文档收不到事件（见文末说明）
  if (el.contains(e.target)) {
    raise(winHandle)
    log('raise →', zIndex.value)
  }
}

// 拖动的那一刻也置顶一次，双保险
function focusWindow() {
  raise(winHandle)
}

// 监听屏幕尺寸变化：手机上默认全屏，且取消最小化状态
let mql = null
function updateMobile(e) {
  isMobile.value = e.matches
  if (e.matches && minimized.value) {
    minimized.value = false
  }
}

onMounted(() => {
  // 挂载即入栈：新开的窗口一定在最上层
  register(winHandle)
  log('register →', zIndex.value, 'stack:', getStack().length)

  // 捕获阶段：pointerdown 覆盖鼠标/触摸/触控笔；mousedown 兜底老环境
  document.addEventListener('pointerdown', onDocPointerDown, true)
  document.addEventListener('mousedown', onDocPointerDown, true)

  mql = window.matchMedia(MOBILE_QUERY)
  isMobile.value = mql.matches
  mql.addEventListener('change', updateMobile)
})

onBeforeUnmount(() => {
  // 出栈，防止 z-index 泄漏（HMR / 路由切换尤其重要）
  unregister(winHandle)
  document.removeEventListener('pointerdown', onDocPointerDown, true)
  document.removeEventListener('mousedown', onDocPointerDown, true)
  mql?.removeEventListener('change', updateMobile)
  detachPointer()
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

function clamp(v, min, max) {
  return Math.min(Math.max(v, min), max)
}

// —— 指针事件统管：鼠标 / 触摸 / 触控笔共用一套逻辑 ——
function attachPointer() {
  document.addEventListener('pointermove', onPointerMove, { passive: false })
  document.addEventListener('pointerup', onPointerUp)
  document.addEventListener('pointercancel', onPointerUp)
}

function detachPointer() {
  document.removeEventListener('pointermove', onPointerMove)
  document.removeEventListener('pointerup', onPointerUp)
  document.removeEventListener('pointercancel', onPointerUp)
}

// —— 拖动 ——
function startDrag(e) {
  // 最大化/移动端全屏状态下不可拖动；最小化时仅剩标题栏，仍可拖动；点击按钮不触发
  if (maximized.value || isMobile.value) return
  if (e.target.closest('button')) return
  drag = { type: 'move', startX: e.clientX, startY: e.clientY, ox: x.value, oy: y.value }
  interacting.value = true
  attachPointer()
}

// —— 缩放（右下角手柄）——
function startResize(e) {
  if (maximized.value || minimized.value || isMobile.value) return
  e.preventDefault()
  drag = { type: 'resize', startX: e.clientX, startY: e.clientY, ow: w.value, oh: h.value }
  interacting.value = true
  attachPointer()
}

function onPointerMove(e) {
  if (!drag) return
  // 触摸拖动时阻止页面跟着滚
  if (e.pointerType !== 'mouse') e.preventDefault()

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

function onPointerUp() {
  if (!drag) return
  drag = null
  interacting.value = false
  detachPointer()
}

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
        ref="rootEl"
        class="floating-window"
        :class="{
          'floating-window--maximized': maximized || isMobile,
          'floating-window--no-anim': interacting,
          'floating-window--active': isTop,
        }"
        :style="windowStyle"
        @pointerdown.capture="focusWindow"
      >
        <v-card rounded="lg" width="100%" height="100%" style="overflow: hidden;">
          <v-layout style="height: inherit">
            <v-app-bar
              class="bg-surface-light floating-window__drag"
              elevation="0"
              :density="appbarDensity"
              @pointerdown="startDrag"
              @dblclick="toggleMaximize"
            >
              <v-app-bar-nav-icon v-if="showDrawer" variant="text" @click.stop="drawer = !drawer" />

              <v-icon v-if="icon" style="margin-left: 16px;">{{ icon }}</v-icon>

              <v-app-bar-title :style="{ marginLeft: icon ? '16px' : '' }">
                {{ title || $t('floatingWindow.defaultTitle') }}
              </v-app-bar-title>

              <slot name="appbar_append" />

              <v-btn icon @click="toggleMinimize">
                <v-icon>{{ minimized ? 'mdi-window-restore' : 'mdi-minus' }}</v-icon>
                <v-tooltip activator="parent" location="left">{{
                  minimized ? $t('floatingWindow.restore') : $t('floatingWindow.minimize')
                }}</v-tooltip>
              </v-btn>

              <v-btn v-show="!isMobile" icon @click="toggleMaximize">
                <v-icon>{{ maximized ? 'mdi-square-rounded-outline' : 'mdi-border-radius' }}</v-icon>
                <v-tooltip activator="parent" location="left">{{
                  maximized ? $t('floatingWindow.restore') : $t('floatingWindow.maximize')
                }}</v-tooltip>
              </v-btn>

              <v-btn icon @click="close">
                <v-icon>mdi-close</v-icon>
                <v-tooltip activator="parent" location="left">{{ $t('floatingWindow.close') }}</v-tooltip>
              </v-btn>

              <template v-if="appbarExtended" #extension>
                <slot name="appbar_extension" />
              </template>
            </v-app-bar>

            <v-navigation-drawer
              v-if="showDrawer"
              v-model="drawer"
              :location="isMobile ? 'bottom' : undefined"
              temporary
            >
              <slot name="drawer_list" />
            </v-navigation-drawer>

            <v-main style="height: 100%;" v-show="!minimized">
              <slot name="content">
                <slot />
              </slot>
            </v-main>
          </v-layout>
        </v-card>

        <!-- 右下角缩放手柄（最小化/全屏/移动端时隐藏） -->
        <div
          v-show="!minimized && !maximized && !isMobile"
          class="floating-window__resize"
          @pointerdown="startResize"
        />
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.floating-window {
  position: fixed;
  /* 不写死 z-index：层级完全由行内 style（全局栈）决定 */
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
  transition: left 0.22s ease, top 0.22s ease, width 0.22s ease, height 0.22s ease,
    box-shadow 0.2s ease, border-color 0.2s ease;
}

/* 当前最顶层窗口：加深阴影 + 主色描边，焦点一眼可辨（同时方便确认置顶是否生效） */
.floating-window--active {
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.28);
  border-color: rgb(var(--v-theme-primary), 0.5);
}

/* 拖拽 / 缩放进行中禁用过渡，避免跟手延迟 */
.floating-window--no-anim {
  transition: none;
}

.floating-window--maximized {
  border-radius: 0;
  border: none;
}

/* 标题栏即拖拽把手：禁用浏览器默认手势，保证触摸拖动不误触页面滚动 */
.floating-window__drag {
  touch-action: none;
}

/* 右下角缩放手柄 */
.floating-window__resize {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 18px;
  height: 18px;
  cursor: nwse-resize;
  z-index: 1;
  touch-action: none;
  background:
    linear-gradient(135deg, transparent 50%, rgb(var(--v-theme-on-surface), 0.35) 50%);
  border-radius: 0 0 10px 0;
}

.floating-window__resize:hover {
  background:
    linear-gradient(135deg, transparent 50%, rgb(var(--v-theme-primary), 0.6) 50%);
}

/* 内容区 */
.floating-window__content {
  flex: 1;
  min-height: 0;
  overflow: auto;
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

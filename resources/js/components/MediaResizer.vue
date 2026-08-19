<script setup>
import { ref, computed, watch } from 'vue'
import { NodeViewWrapper } from '@tiptap/vue-3'

/**
 * 媒体 NodeView：为图片 / 视频节点提供拖拽缩放手柄（角点等比缩放，边点单独调宽/高）。
 */
const props = defineProps({
  node: { type: Object, required: true },
  updateAttributes: { type: Function, required: true },
  editor: { type: Object, required: true },
  getPos: { type: Function, default: () => 0 },
  selected: { type: Boolean, default: false },
})

const wrapper = ref(null)
const hovered = ref(false)
const dragging = ref(false)

const isImage = computed(() => props.node.type.name === 'image')
const isVideo = computed(() => props.node.type.name === 'video')

const attrsWidth = computed(() => Number(props.node.attrs.width) || 0)
const attrsHeight = computed(() => Number(props.node.attrs.height) || 0)

const naturalWidth = ref(0)
const naturalHeight = ref(0)

// 当前实际显示尺寸（拖动时立即更新，不等节点属性回流）
const width = ref(attrsWidth.value || 0)
const height = ref(attrsHeight.value || 0)

// 外部变化（撤销 / 回显 / 重做）时同步回本地
watch([attrsWidth, attrsHeight], ([w, h]) => {
  if (dragging.value) return
  if (w) width.value = w
  if (h) height.value = h
})

const aspectRatio = computed(() => {
  const w = width.value || attrsWidth.value || naturalWidth.value
  const h = height.value || attrsHeight.value || naturalHeight.value
  return w && h ? w / h : null
})

// 只读渲染（ProseRenderer）时不显示缩放手柄
const showHandles = computed(
  () => props.editor?.isEditable && (props.selected || hovered.value) && !dragging.value,
)

const mediaStyle = computed(() => {
  const style = {}
  if (width.value) style.width = `${width.value}px`
  if (height.value) style.height = `${height.value}px`
  return style
})

function selectNode() {
  if (!props.editor?.isEditable) return
  const pos = props.getPos()
  if (typeof pos !== 'number') return
  props.editor.chain().focus().setNodeSelection(pos).run()
}

// 媒体加载完成 / 元数据就绪后，取其原始尺寸；首次插入时超宽则等比缩进编辑区
function onLoad() {
  const el = wrapper.value?.$el
  if (!el) return
  const media = el.querySelector('img, video')
  if (!media) return
  const nw = media.naturalWidth || media.videoWidth || 0
  const nh = media.naturalHeight || media.videoHeight || 0
  if (!nw || !nh) return
  naturalWidth.value = nw
  naturalHeight.value = nh
  if (attrsWidth.value && attrsHeight.value) {
    width.value = attrsWidth.value
    height.value = attrsHeight.value
    return
  }
  let w = nw
  let h = nh
  // 以编辑器内容区宽度为基准，超宽时等比缩进可视范围
  const editorEl = el.closest('.ProseMirror')
  const containerWidth = editorEl?.clientWidth || el.clientWidth || 0
  if (containerWidth && nw > containerWidth) {
    const ratio = containerWidth / nw
    w = Math.round(nw * ratio)
    h = Math.round(nh * ratio)
  }
  width.value = w
  height.value = h
  // 只读渲染时不写回节点属性
  if (props.editor?.isEditable) {
    props.updateAttributes({ width: w, height: h })
  }
}

const MIN_SIZE = 24
const HANDLES = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w']

function startResize(ev, dir) {
  ev.preventDefault()
  ev.stopPropagation()
  const startX = ev.clientX
  const startY = ev.clientY
  const startW = width.value || naturalWidth.value || 100
  const startH = height.value || naturalHeight.value || 100
  const ratio = aspectRatio.value
  dragging.value = true

  const onMove = (e) => {
    let dx = e.clientX - startX
    let dy = e.clientY - startY
    if (dir.includes('w')) dx = -dx
    if (dir.includes('n')) dy = -dy

    let w = dir.includes('e') || dir.includes('w') ? startW + dx : startW
    let h = dir.includes('s') || dir.includes('n') ? startH + dy : startH

    // 角点拖动：保持宽高比
    if (dir.length === 2 && ratio) {
      const r = Math.max(w / startW, h / startH)
      w = startW * r
      h = startH * r
    }

    // 拖动过程中仅更新本地尺寸，获得即时反馈
    width.value = Math.max(MIN_SIZE, Math.round(w))
    height.value = Math.max(MIN_SIZE, Math.round(h))
  }

  const onUp = () => {
    dragging.value = false
    window.removeEventListener('pointermove', onMove)
    window.removeEventListener('pointerup', onUp)
    // 拖动结束一次性写入节点属性，避免产生大量撤销步骤
    props.updateAttributes({ width: width.value, height: height.value })
  }

  window.addEventListener('pointermove', onMove)
  window.addEventListener('pointerup', onUp)
}
</script>

<template>
  <NodeViewWrapper
    ref="wrapper"
    class="media-resizer"
    :class="{ 'media-resizer--selected': selected }"
    @click.stop="selectNode"
    @mouseenter="hovered = true"
    @mouseleave="hovered = false"
  >
    <img
      v-if="isImage"
      :src="node.attrs.src"
      :alt="node.attrs.alt || ''"
      :style="mediaStyle"
      @load="onLoad"
    />
    <video
      v-else
      :src="node.attrs.src"
      :controls="node.attrs.controls"
      :autoplay="node.attrs.autoplay"
      :loop="node.attrs.loop"
      :muted="node.attrs.muted"
      :poster="node.attrs.poster || undefined"
      :style="mediaStyle"
      @loadedmetadata="onLoad"
    />

    <span
      v-for="dir in HANDLES"
      v-show="showHandles"
      :key="dir"
      class="media-resizer__handle"
      :class="`media-resizer__handle--${dir}`"
      @pointerdown="startResize($event, dir)"
    />
  </NodeViewWrapper>
</template>

<style scoped>
.media-resizer {
  position: relative;
  display: inline-block;
  max-width: 100%;
  line-height: 0;
  vertical-align: bottom;
}

.media-resizer--selected {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

.media-resizer img,
.media-resizer video {
  display: block;
  max-width: 100%;
  border-radius: 6px;
  background: #000;
}

.media-resizer__handle {
  position: absolute;
  z-index: 10;
  box-sizing: border-box;
  width: 10px;
  height: 10px;
  border: 1px solid #fff;
  border-radius: 2px;
  background: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 1px rgb(var(--v-theme-primary));
  touch-action: none;
}

.media-resizer__handle--nw { top: -6px; left: -6px; cursor: nwse-resize; }
.media-resizer__handle--n  { top: -6px; left: 50%; margin-left: -6px; cursor: ns-resize; }
.media-resizer__handle--ne { top: -6px; right: -6px; cursor: nesw-resize; }
.media-resizer__handle--e  { top: 50%; right: -6px; margin-top: -6px; cursor: ew-resize; }
.media-resizer__handle--se { bottom: -6px; right: -6px; cursor: nwse-resize; }
.media-resizer__handle--s  { bottom: -6px; left: 50%; margin-left: -6px; cursor: ns-resize; }
.media-resizer__handle--sw { bottom: -6px; left: -6px; cursor: nesw-resize; }
.media-resizer__handle--w  { top: 50%; left: -6px; margin-top: -6px; cursor: ew-resize; }
</style>

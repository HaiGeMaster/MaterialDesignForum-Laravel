<script setup>
import { computed, watch, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import { TextStyle, FontSize, LineHeight } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import Highlight from '@tiptap/extension-highlight'
import Link from '@tiptap/extension-link'
import TextAlign from '@tiptap/extension-text-align'
import { Fancybox } from '@fancyapps/ui'
import '@fancyapps/ui/dist/fancybox/fancybox.css'
import { zh_CN } from '@fancyapps/ui/dist/fancybox/l10n/zh_CN.js'
import { Video } from '../tiptap/Video'
import { ResizableImage } from '../tiptap/ResizableImage'
import { CodeBlock } from '../tiptap/CodeBlock'

defineOptions({ name: 'ProseRenderer' })

const props = defineProps({
  /** 富文本 HTML 内容（content_rendered） */
  content: { type: String, default: '' },
  /** 空内容时的提示文案；为空则不渲染空状态 */
  emptyText: { type: String, default: '' },
  /** 空内容时的图标 */
  emptyIcon: { type: String, default: 'mdi-note-text-outline' },
})

const hasContent = computed(() => {
  const html = props.content?.trim()
  return Boolean(html && html !== '<p></p>')
})

// 只读渲染：与编辑端（RichTextEditor）保持一致的扩展配置，
// editable 固定为 false，实现与编辑器完全相同的排版/高亮效果。
const editor = useEditor({
  content: props.content || '',
  editable: false,
  extensions: [
    StarterKit.configure({
      heading: { levels: [1, 2, 3] },
      codeBlock: false,
    }),
    Underline,
    TextStyle,
    FontSize,
    LineHeight,
    Color,
    Highlight.configure({ multicolor: true }),
    ResizableImage,
    Video,
    CodeBlock,
    Link.configure({
      openOnClick: true,
      autolink: true,
      HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
    }),
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
  ],
})

// —— 富文本图片点击预览（fancyapps/ui Fancybox）——
// 注意：不能用 Fancybox.bind 的容器事件委托 —— ProseMirror 点击图片/视频（atom 节点）
// 时会在冒泡阶段调用 event.preventDefault()，Fancybox 的 fromEvent 检测到
// defaultPrevented 就直接返回，灯箱永远打不开。
// 因此改为直接在图片元素上绑定 click（preventDefault + stopPropagation 拦截冒泡），
// 再手动调用 Fancybox.show() 打开灯箱。
// 视频不接入灯箱：保留原生 controls，支持浏览器全屏播放。
const { locale } = useI18n()

const GALLERY_GROUP = 'prose-media-gallery'
let lightboxObserver = null
const boundImages = new WeakSet()

// 收集同组图片并构造成 Fancybox slide 列表（可在灯箱内左右切换）
function collectSlides() {
  const dom = editor.value?.view?.dom
  if (!dom) return []
  return Array.from(dom.querySelectorAll(`[data-lightbox="${GALLERY_GROUP}"]`)).map((el) => ({
    type: 'image',
    src: el.currentSrc || el.src,
    caption: el.getAttribute('alt') || undefined,
  }))
}

function openLightbox(el) {
  const dom = editor.value?.view?.dom
  if (!dom) return
  const items = Array.from(dom.querySelectorAll(`[data-lightbox="${GALLERY_GROUP}"]`))
  const slides = collectSlides()
  if (!slides.length) return
  Fancybox.show(slides, {
    startIndex: Math.max(0, items.indexOf(el)),
    l10n: locale.value === 'zh' ? zh_CN : undefined,
  })
}

function onImageClick(ev) {
  // 拦截事件，避免冒泡到 ProseMirror 被 preventDefault，也避免它抢走选区
  ev.preventDefault()
  ev.stopPropagation()
  openLightbox(ev.currentTarget)
}

// 给当前 DOM 中的图片打上分组标记并绑定点击（WeakSet 去重，避免重复绑定）
function syncMedia() {
  const dom = editor.value?.view?.dom
  if (!dom) return
  dom.querySelectorAll('img').forEach((el) => {
    el.setAttribute('data-lightbox', GALLERY_GROUP)
    if (!boundImages.has(el)) {
      boundImages.add(el)
      el.addEventListener('click', onImageClick)
    }
  })
}

function refreshLightbox() {
  const dom = editor.value?.view?.dom
  if (!dom) return
  syncMedia()
  // 内容变化会重建 NodeView（img/video 元素被替换），用 MutationObserver 保持绑定
  lightboxObserver?.disconnect()
  lightboxObserver = new MutationObserver(syncMedia)
  lightboxObserver.observe(dom, { childList: true, subtree: true })
}

// 外部内容变化时同步进编辑器（setContent 默认不触发 onUpdate），并刷新媒体预览
watch(
  () => props.content,
  (val) => {
    const ed = editor.value
    if (ed && val !== ed.getHTML()) {
      ed.commands.setContent(val || '', false)
      refreshLightbox()
    }
  },
)

// 编辑器就绪后绑定灯箱；语言切换时更新灯箱文案
watch(editor, (ed) => {
  if (ed) refreshLightbox()
})
watch(locale, () => refreshLightbox())

onBeforeUnmount(() => {
  lightboxObserver?.disconnect()
})
</script>

<template>
  <div>
    <EditorContent v-if="hasContent" class="prose" :editor="editor" />
    <v-empty-state v-else-if="emptyText" :icon="emptyIcon" :title="emptyText" />
  </div>
</template>

<style scoped>
/* 富文本排版（与编辑器渲染效果一致） */
.prose {
  font-size: 15px;
  line-height: 1.8;
  color: rgb(var(--v-theme-on-surface));
  word-break: break-word;
}

.prose :deep(.ProseMirror) {
  outline: none;
}

.prose :deep(h1),
.prose :deep(h2),
.prose :deep(h3) {
  font-weight: 600;
  margin: 1em 0 0.5em;
  line-height: 1.4;
}

.prose :deep(h1) {
  font-size: 1.5em;
}

.prose :deep(h2) {
  font-size: 1.3em;
}

.prose :deep(h3) {
  font-size: 1.15em;
}

.prose :deep(p) {
  margin: 0.5em 0;
}

.prose :deep(ul),
.prose :deep(ol) {
  padding-left: 1.5em;
  margin: 0.5em 0;
}

.prose :deep(ul) {
  list-style: disc;
}

.prose :deep(ol) {
  list-style: decimal;
}

.prose :deep(blockquote) {
  border-left: 3px solid rgb(var(--v-theme-primary));
  margin: 0.75em 0;
  padding: 0.25em 1em;
  /* color: rgb(var(--v-theme-on-surface-variant));
  background: rgb(var(--v-theme-surface-variant)); */
  background: rgb(var(--v-theme-on-surface-variant));
  color: rgb(var(--v-theme-surface-variant));
}

.prose :deep(a) {
  color: rgb(var(--v-theme-primary));
  text-decoration: underline;
}

.prose :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}

/* 富文本内的图片可点击预览（视频保留原生播放/全屏，不接入灯箱） */
.prose :deep(.media-resizer img) {
  cursor: zoom-in;
}

.prose :deep(code) {
  color: rgb(var(--v-theme-on-surface-variant));
  
  background: rgb(var(--v-theme-surface-variant));
  border-radius: 4px;
  padding: 0.1em 0.4em;
  font-size: 0.92em;
}

/* 代码块：与编辑器端一致的深色风格 */
.prose :deep(pre) {
  background: #0d1117;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 12px 14px;
  overflow-x: auto;
}

.prose :deep(pre code) {
  display: block;
  background: transparent;
  padding: 0;
  color: #e6edf3;
  font-family: 'JetBrains Mono', 'Fira Code', Consolas, 'Courier New', monospace;
  font-size: 13px;
  line-height: 1.6;
  tab-size: 4;
}

/* —— 语法高亮 token（GitHub Dark 风格，与编辑器一致）—— */
.prose :deep(.hljs-comment),
.prose :deep(.hljs-quote) {
  color: #8b949e;
  font-style: italic;
}

.prose :deep(.hljs-keyword),
.prose :deep(.hljs-selector-tag),
.prose :deep(.hljs-literal),
.prose :deep(.hljs-section),
.prose :deep(.hljs-link) {
  color: #ff7b72;
}

.prose :deep(.hljs-title),
.prose :deep(.hljs-title.function_),
.prose :deep(.hljs-title.class_),
.prose :deep(.hljs-selector-class) {
  color: #d2a8ff;
}

.prose :deep(.hljs-string),
.prose :deep(.hljs-attr),
.prose :deep(.hljs-attribute),
.prose :deep(.hljs-symbol),
.prose :deep(.hljs-bullet),
.prose :deep(.hljs-addition) {
  color: #a5d6ff;
}

.prose :deep(.hljs-number),
.prose :deep(.hljs-variable),
.prose :deep(.hljs-template-variable),
.prose :deep(.hljs-meta),
.prose :deep(.hljs-type),
.prose :deep(.hljs-selector-id) {
  color: #79c0ff;
}

.prose :deep(.hljs-built_in) {
  color: #ffa657;
}

.prose :deep(.hljs-name),
.prose :deep(.hljs-tag) {
  color: #7ee787;
}

.prose :deep(.hljs-deletion) {
  color: #ffa198;
}

.prose :deep(.hljs-emphasis) {
  font-style: italic;
}

.prose :deep(.hljs-strong) {
  font-weight: 600;
}

.prose :deep(.hljs-params) {
  color: #e6edf3;
}

.prose :deep(table) {
  border-collapse: collapse;
  width: 100%;
  margin: 0.75em 0;
}

.prose :deep(th),
.prose :deep(td) {
  border: 1px solid rgb(var(--v-theme-outline));
  padding: 6px 10px;
}

.prose :deep(hr) {
  border: none;
  border-top: 1px solid rgb(var(--v-theme-outline));
  margin: 1em 0;
}
</style>

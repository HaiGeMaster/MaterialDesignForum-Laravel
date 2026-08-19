<script setup>
import { computed, watch } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import { TextStyle, FontSize, LineHeight } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import Highlight from '@tiptap/extension-highlight'
import Link from '@tiptap/extension-link'
import TextAlign from '@tiptap/extension-text-align'
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

// 外部内容变化时同步进编辑器（setContent 默认不触发 onUpdate）
watch(
  () => props.content,
  (val) => {
    const ed = editor.value
    if (ed && val !== ed.getHTML()) {
      ed.commands.setContent(val || '', false)
    }
  },
)
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
  color: rgb(var(--v-theme-on-surface-variant));
  background: rgb(var(--v-theme-surface-variant));
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

.prose :deep(code) {
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

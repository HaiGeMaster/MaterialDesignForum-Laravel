<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { EditorContent, useEditor, VueRenderer } from '@tiptap/vue-3'
import { BubbleMenu } from '@tiptap/extension-bubble-menu'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Underline from '@tiptap/extension-underline'
import { TextStyle, FontSize, LineHeight } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import Highlight from '@tiptap/extension-highlight'
import Link from '@tiptap/extension-link'
import TextAlign from '@tiptap/extension-text-align'
import RichTextEditorToolbarRow from './RichTextEditorToolbarRow.vue'
import { Video } from '../tiptap/Video'
import { ResizableImage } from '../tiptap/ResizableImage'
import { CodeBlock } from '../tiptap/CodeBlock'
import { Table, TableRow, TableCell, TableHeader } from '@tiptap/extension-table'
import TaskList from '@tiptap/extension-task-list'
import TaskItem from '@tiptap/extension-task-item'
import { Details, DetailsSummary, DetailsContent } from '@tiptap/extension-details'
import Subscript from '@tiptap/extension-subscript'
import Superscript from '@tiptap/extension-superscript'
import Mention from '@tiptap/extension-mention'
import CharacterCount from '@tiptap/extension-character-count'
import MentionSuggestion from './MentionSuggestion.vue'

const props = defineProps({
  /** 编辑器 HTML 内容（v-model 双向绑定） */
  modelValue: { type: String, default: '' },
  /** 空内容时的占位提示 */
  placeholder: { type: String, default: '' },
  /** 内容区高度（数字=px 或 CSS 字符串） */
  height: { type: [Number, String], default: 220 },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

// 内容/选区变化时强制刷新工具栏高亮状态
const refresh = ref(0)

const editor = useEditor({
  content: props.modelValue || '',
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
    Placeholder.configure({ placeholder: props.placeholder || t('editor.placeholder') }),
    Link.configure({
      openOnClick: false,
      autolink: true,
      HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
    }),
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    Table.configure({ resizable: true }),
    TableRow,
    TableCell,
    TableHeader,
    TaskList,
    TaskItem.configure({ nested: true }),
    Details,
    DetailsSummary,
    DetailsContent,
    Subscript,
    Superscript,
    Mention.configure({
      suggestion: {
        render: () => {
          let component
          return {
            onStart: (props) => {
              component = new VueRenderer(MentionSuggestion, { props, editor: props.editor })
              if (!props.clientRect) return
              props.decorationNode?.appendChild(component.element)
            },
            onUpdate(props) { component.updateProps(props) },
            onKeyDown(props) {
              if (props.event.key === 'Escape') { component.destroy(); return true }
              return component.ref?.[props.event.key]?.()
            },
            onExit() { component.destroy() },
          }
        },
      },
    }),
    CharacterCount,
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
  onTransaction: () => {
    refresh.value++
  },
})

// 外部值变化时同步进编辑器（回显 / 清空），setContent 默认不触发 onUpdate
watch(
  () => props.modelValue,
  (val) => {
    const ed = editor.value
    if (ed && val !== ed.getHTML()) {
      ed.commands.setContent(val || '', false)
    }
  },
)

// —— 工具栏 ——
const TEXT_COLORS = computed(() => [
  { label: t('editor.colors.black'), value: '#1f2937' },
  { label: t('editor.colors.red'), value: '#ef4444' },
  { label: t('editor.colors.orange'), value: '#f97316' },
  { label: t('editor.colors.yellow'), value: '#eab308' },
  { label: t('editor.colors.green'), value: '#22c55e' },
  { label: t('editor.colors.cyan'), value: '#14b8a6' },
  { label: t('editor.colors.blue'), value: '#3b82f6' },
  { label: t('editor.colors.purple'), value: '#a855f7' },
  { label: t('editor.colors.gray'), value: '#6b7280' },
])

const HIGHLIGHT_COLORS = computed(() => [
  { label: t('editor.colors.yellow'), value: '#fef08a' },
  { label: t('editor.colors.green'), value: '#bbf7d0' },
  { label: t('editor.colors.cyan'), value: '#a5f3fc' },
  { label: t('editor.colors.blue'), value: '#bfdbfe' },
  { label: 'Pink', value: '#fbcfe8' },
  { label: t('editor.colors.orange'), value: '#fed7aa' },
])

// 上栏：文本样式与常用操作
const toolbarTop = computed(() => [
  { type: 'button', icon: 'mdi-undo', title: t('editor.undo'), action: () => editor.value?.chain().focus().undo().run() },
  { type: 'button', icon: 'mdi-redo', title: t('editor.redo'), action: () => editor.value?.chain().focus().redo().run() },
  { type: 'divider' },
  {
    type: 'menu',
    id: 'textStyle',
    icon: 'mdi-format-text',
    title: t('editor.textStyle'),
    items: [
      {
        icon: 'mdi-format-bold',
        title: t('editor.bold'),
        active: () => editor.value?.isActive('bold'),
        action: () => editor.value?.chain().focus().toggleBold().run(),
      },
      {
        icon: 'mdi-format-italic',
        title: t('editor.italic'),
        active: () => editor.value?.isActive('italic'),
        action: () => editor.value?.chain().focus().toggleItalic().run(),
      },
      {
        icon: 'mdi-format-underline',
        title: t('editor.underline'),
        active: () => editor.value?.isActive('underline'),
        action: () => editor.value?.chain().focus().toggleUnderline().run(),
      },
      {
        icon: 'mdi-format-strikethrough-variant',
        title: t('editor.strike'),
        active: () => editor.value?.isActive('strike'),
        action: () => editor.value?.chain().focus().toggleStrike().run(),
      },
      {
        icon: 'mdi-format-subscript',
        title: t('editor.subscript'),
        active: () => editor.value?.isActive('subscript'),
        action: () => editor.value?.chain().focus().toggleSubscript().run(),
      },
      {
        icon: 'mdi-format-superscript',
        title: t('editor.superscript'),
        active: () => editor.value?.isActive('superscript'),
        action: () => editor.value?.chain().focus().toggleSuperscript().run(),
      },
      {
        icon: 'mdi-code-tags',
        title: t('editor.inlineCode'),
        active: () => editor.value?.isActive('code'),
        action: () => editor.value?.chain().focus().toggleCode().run(),
      },
    ],
  },
  { type: 'divider' },
  {
    type: 'color',
    icon: 'mdi-format-color-text',
    title: t('editor.textColor'),
    colors: TEXT_COLORS.value,
    current: () => editor.value?.getAttributes('textStyle').color,
    apply: (c) => editor.value?.chain().focus().setColor(c).run(),
    clear: () => editor.value?.chain().focus().unsetColor().run(),
  },
  {
    type: 'highlight',
    icon: 'mdi-format-color-highlight',
    title: t('editor.highlight'),
    colors: HIGHLIGHT_COLORS.value,
    current: () => editor.value?.getAttributes('highlight').color,
    apply: (c) => editor.value?.chain().focus().toggleHighlight({ color: c }).run(),
    clear: () => editor.value?.chain().focus().unsetHighlight().run(),
  },
  {
    type: 'select',
    label: t('editor.fontSizeLabel'),
    title: t('editor.fontSize'),
    icon: 'mdi-format-size',
    options: [
      { label: t('editor.default'), value: '' },
      { label: '12px', value: '12px' },
      { label: '14px', value: '14px' },
      { label: '16px', value: '16px' },
      { label: '18px', value: '18px' },
      { label: '20px', value: '20px' },
      { label: '24px', value: '24px' },
      { label: '28px', value: '28px' },
      { label: '32px', value: '32px' },
    ],
    current: () => editor.value?.getAttributes('textStyle').fontSize || '',
    action: (v) =>
      v
        ? editor.value?.chain().focus().setFontSize(v).run()
        : editor.value?.chain().focus().unsetFontSize().run(),
  },
  {
    type: 'select',
    label: t('editor.lineHeightLabel'),
    title: t('editor.lineHeightTitle'),
    icon: 'mdi-format-line-spacing',
    options: [
      { label: t('editor.default'), value: '' },
      { label: t('editor.lineHeight.x1'), value: '1' },
      { label: t('editor.lineHeight.x1_25'), value: '1.25' },
      { label: t('editor.lineHeight.x1_5'), value: '1.5' },
      { label: t('editor.lineHeight.x1_75'), value: '1.75' },
      { label: t('editor.lineHeight.x2'), value: '2' },
    ],
    current: () => editor.value?.getAttributes('textStyle').lineHeight || '',
    action: (v) =>
      v
        ? editor.value?.chain().focus().setLineHeight(v).run()
        : editor.value?.chain().focus().unsetLineHeight().run(),
  },
  {
    type: 'button',
    icon: 'mdi-format-clear',
    title: t('editor.clearFormat'),
    action: () => editor.value?.chain().focus().unsetAllMarks().clearNodes().run(),
  },
])

// 下栏：段落排版与插入
const toolbarBottom = computed(() => [
  {
    type: 'menu',
    id: 'heading',
    icon: 'mdi-format-header-pound',
    title: t('editor.heading'),
    items: [
      {
        icon: 'mdi-format-header-1',
        title: t('editor.h1'),
        active: () => editor.value?.isActive('heading', { level: 1 }),
        action: () => editor.value?.chain().focus().toggleHeading({ level: 1 }).run(),
      },
      {
        icon: 'mdi-format-header-2',
        title: t('editor.h2'),
        active: () => editor.value?.isActive('heading', { level: 2 }),
        action: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run(),
      },
      {
        icon: 'mdi-format-header-3',
        title: t('editor.h3'),
        active: () => editor.value?.isActive('heading', { level: 3 }),
        action: () => editor.value?.chain().focus().toggleHeading({ level: 3 }).run(),
      },
    ],
  },
  {
    type: 'menu',
    id: 'list',
    icon: 'mdi-format-list-bulleted',
    title: t('editor.list'),
    items: [
      {
        icon: 'mdi-format-list-bulleted',
        title: t('editor.bulletList'),
        active: () => editor.value?.isActive('bulletList'),
        action: () => editor.value?.chain().focus().toggleBulletList().run(),
      },
      {
        icon: 'mdi-format-list-numbered',
        title: t('editor.orderedList'),
        active: () => editor.value?.isActive('orderedList'),
        action: () => editor.value?.chain().focus().toggleOrderedList().run(),
      },
      {
        icon: 'mdi-checkbox-marked-outline',
        title: t('editor.taskList'),
        active: () => editor.value?.isActive('taskList'),
        action: () => editor.value?.chain().focus().toggleTaskList().run(),
      },
    ],
  },
  {
    type: 'menu',
    id: 'align',
    icon: 'mdi-format-align-center',
    title: t('editor.align'),
    items: [
      {
        icon: 'mdi-format-align-left',
        title: t('editor.alignLeft'),
        active: () => editor.value?.isActive({ textAlign: 'left' }),
        action: () => editor.value?.chain().focus().setTextAlign('left').run(),
      },
      {
        icon: 'mdi-format-align-center',
        title: t('editor.alignCenter'),
        active: () => editor.value?.isActive({ textAlign: 'center' }),
        action: () => editor.value?.chain().focus().setTextAlign('center').run(),
      },
      {
        icon: 'mdi-format-align-right',
        title: t('editor.alignRight'),
        active: () => editor.value?.isActive({ textAlign: 'right' }),
        action: () => editor.value?.chain().focus().setTextAlign('right').run(),
      },
    ],
  },
  { type: 'divider' },
  {
    type: 'button',
    icon: 'mdi-format-quote-close',
    title: t('editor.quote'),
    active: () => editor.value?.isActive('blockquote'),
    action: () => editor.value?.chain().focus().toggleBlockquote().run(),
  },
  {
    type: 'button',
    icon: 'mdi-code-block-tags',
    title: t('editor.codeBlock'),
    active: () => editor.value?.isActive('codeBlock'),
    action: () => editor.value?.chain().focus().toggleCodeBlock().run(),
  },
  {
    type: 'button',
    icon: 'mdi-minus',
    title: t('editor.horizontalRule'),
    action: () => editor.value?.chain().focus().setHorizontalRule().run(),
  },
  // {
  //   type: 'button',
  //   icon: 'mdi-chevron-down-box-outline',
  //   title: t('editor.details'),
  //   active: () => editor.value?.isActive('details'),
  //   action: () => editor.value?.chain().focus().setDetails().run(),
  // },
  { type: 'divider' },
  {
    type: 'menu',
    id: 'insert',
    icon: 'mdi-plus',
    title: t('editor.insert'),
    items: [
      { type: 'link', icon: 'mdi-link-variant', title: t('editor.link') },
      { type: 'image', icon: 'mdi-image-outline', title: t('editor.image') },
      { type: 'video', icon: 'mdi-video-outline', title: t('editor.video') },
      { type: 'table', icon: 'mdi-table-plus', title: t('editor.table.insert') },
      { type: 'library', icon: 'mdi-image-multiple-outline', title: t('editor.fromLibrary') },
    ],
  },
])

// 表格操作菜单（仅在光标位于表格内时显示）
const tableMenu = computed(() => ({
  type: 'menu',
  id: 'table',
  icon: 'mdi-table',
  title: t('editor.table.menu'),
  visible: () => editor.value?.isActive('table'),
  items: [
    {
      icon: 'mdi-table-row-plus-after',
      title: t('editor.table.insertRowAfter'),
      action: () => editor.value?.chain().focus().addRowAfter().run(),
    },
    {
      icon: 'mdi-table-row-plus-before',
      title: t('editor.table.insertRowBefore'),
      action: () => editor.value?.chain().focus().addRowBefore().run(),
    },
    {
      icon: 'mdi-table-column-plus-after',
      title: t('editor.table.insertColAfter'),
      action: () => editor.value?.chain().focus().addColumnAfter().run(),
    },
    {
      icon: 'mdi-table-column-plus-before',
      title: t('editor.table.insertColBefore'),
      action: () => editor.value?.chain().focus().addColumnBefore().run(),
    },
    {
      icon: 'mdi-table-row-remove',
      title: t('editor.table.deleteRow'),
      action: () => editor.value?.chain().focus().deleteRow().run(),
    },
    {
      icon: 'mdi-table-column-remove',
      title: t('editor.table.deleteCol'),
      action: () => editor.value?.chain().focus().deleteColumn().run(),
    },
    {
      icon: 'mdi-format-header-1',
      title: t('editor.table.toggleHeaderRow'),
      active: () => editor.value?.isActive('table', { withHeaderRow: true }),
      action: () => editor.value?.chain().focus().toggleHeaderRow().run(),
    },
    {
      icon: 'mdi-format-header-pound',
      title: t('editor.table.toggleHeaderCol'),
      active: () => editor.value?.isActive('table', { withHeaderColumn: true }),
      action: () => editor.value?.chain().focus().toggleHeaderColumn().run(),
    },
    {
      icon: 'mdi-table-remove',
      title: t('editor.table.deleteTable'),
      action: () => editor.value?.chain().focus().deleteTable().run(),
    },
  ],
}))
</script>

<template>
  <div
    class="rich-editor"
    :style="{ height: typeof height === 'number' ? `${height}px` : height }"
  >
    <!-- 上栏工具栏：文本样式与常用操作 -->
    <div class="rich-editor__toolbar rich-editor__toolbar--top">
      <RichTextEditorToolbarRow :buttons="toolbarTop" :editor="editor" :refresh="refresh" />
    </div>

    <!-- 编辑区 -->
    <EditorContent class="rich-editor__content" :editor="editor" />

    <!-- 悬浮格式化工具栏：选中文本时出现 -->
    <BubbleMenu
      v-if="editor"
      :editor="editor"
      :tippy-options="{ duration: 100 }"
      :should-show="({ editor: e }) => !e.isActive('image') && !e.isActive('video') && !e.isEmpty"
    >
      <v-card class="d-flex align-center pa-1" density="compact" elevation="4">
        <v-btn size="x-small" icon variant="text" :color="editor.isActive('bold') ? 'primary' : undefined" @click="editor.chain().focus().toggleBold().run()"><v-icon icon="mdi-format-bold" size="small" /></v-btn>
        <v-btn size="x-small" icon variant="text" :color="editor.isActive('italic') ? 'primary' : undefined" @click="editor.chain().focus().toggleItalic().run()"><v-icon icon="mdi-format-italic" size="small" /></v-btn>
        <v-btn size="x-small" icon variant="text" :color="editor.isActive('underline') ? 'primary' : undefined" @click="editor.chain().focus().toggleUnderline().run()"><v-icon icon="mdi-format-underline" size="small" /></v-btn>
        <v-btn size="x-small" icon variant="text" :color="editor.isActive('strike') ? 'primary' : undefined" @click="editor.chain().focus().toggleStrike().run()"><v-icon icon="mdi-format-strikethrough-variant" size="small" /></v-btn>
        <v-btn size="x-small" icon variant="text" :color="editor.isActive('code') ? 'primary' : undefined" @click="editor.chain().focus().toggleCode().run()"><v-icon icon="mdi-code-tags" size="small" /></v-btn>
        <v-divider vertical inset class="mx-1" />
        <v-btn size="x-small" icon variant="text" :color="editor.isActive('blockquote') ? 'primary' : undefined" @click="editor.chain().focus().toggleBlockquote().run()"><v-icon icon="mdi-format-quote-close" size="small" /></v-btn>
      </v-card>
    </BubbleMenu>

    <!-- 下栏工具栏：发布按钮（最左侧）+ 段落排版与插入 -->
    <div class="rich-editor__toolbar rich-editor__toolbar--bottom">
      <div v-if="$slots.publish" class="rich-editor__publish-slot">
        <slot name="publish" />
      </div>
      <RichTextEditorToolbarRow :buttons="toolbarBottom" :editor="editor" :refresh="refresh" />
      <RichTextEditorToolbarRow v-if="editor?.isActive('table')" :buttons="[tableMenu]" :editor="editor" :refresh="refresh" />
    </div>

    <!-- 字数统计 -->
    <!-- <div v-if="editor" class="rich-editor__status">
      {{ t('editor.charCount.characters', { count: editor.storage.characterCount.characters() }) }}
    </div> -->
  </div>
</template>

<style scoped>
.rich-editor {
  display: flex;
  flex-direction: column;
  min-height: 0;
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
  overflow: hidden;
}

.rich-editor__toolbar {
  display: flex;
  flex-direction: column;
  flex: none;
  /* background: rgb(var(--v-theme-surface-variant), 0.3); */
}

.rich-editor__toolbar--top {
  border-bottom: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
}

.rich-editor__toolbar--bottom {
  flex-direction: row;
  align-items: center;
  flex-wrap: wrap;
  gap: 2px;
  padding: 2px 4px;
  border-top: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
}

/* 下栏采用横向布局：发布按钮在最左侧，工具栏按钮紧随其后（取消行容器的盒模型） */
.rich-editor__toolbar--bottom :deep(.rich-editor__toolbar-row) {
  display: contents;
}

.rich-editor__publish-slot {
  display: flex;
  align-items: center;
  flex: none;
  padding-right: 2px;
}

.rich-editor__content {
  flex: 1;
  min-height: 0;
  overflow: auto;
}

/* ProseMirror 编辑区（其 DOM 由 tiptap 动态渲染，需 :deep 才能命中） */
.rich-editor__content :deep(.ProseMirror) {
  height: 100%;
  min-height: 100%;
  outline: none;
  padding: 12px 14px;
  font-size: 14px;
  line-height: 1.7;
}

.rich-editor__content :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  color: rgb(var(--v-theme-on-surface), 0.4);
  float: left;
  height: 0;
  pointer-events: none;
}

.rich-editor__content :deep(.ProseMirror img) {
  max-width: 100%;
  height: auto;
  border-radius: 6px;
}

.rich-editor__content :deep(.ProseMirror video) {
  display: block;
  max-width: 100%;
  height: auto;
  border-radius: 6px;
  background: #000;
}

.rich-editor__content :deep(.ProseMirror a) {
  color: rgb(var(--v-theme-primary));
  text-decoration: underline;
}

.rich-editor__content :deep(.ProseMirror blockquote) {
  margin: 8px 0;
  padding-left: 12px;
  border-left: 3px solid rgb(var(--v-theme-primary));

  /* color: rgb(var(--v-theme-on-surface), 0.7); */
  background: rgb(var(--v-theme-on-surface-variant));
  
  color: rgb(var(--v-theme-surface-variant));
}

/* 代码块的 pre/code 由 CodeBlockView 负责样式，这里排除避免冲突（仅行内 code 生效） */
.rich-editor__content :deep(.ProseMirror pre:not(.code-block__pre)) {
  padding: 10px 12px;
  border-radius: 6px;
  overflow: auto;
  background: rgb(var(--v-theme-surface-variant), 0.5);
}

.rich-editor__content :deep(.ProseMirror code:not(.code-block__code)) {
  
  color: rgb(var(--v-theme-on-surface-variant));
  background: rgb(var(--v-theme-surface-variant));

  padding: 1px 4px;
  border-radius: 4px;
  font-size: 0.9em;
  /* background: rgb(var(--v-theme-surface-variant), 0.5); */
}

.rich-editor__content :deep(.ProseMirror h1) {
  font-size: 1.6em;
}

.rich-editor__content :deep(.ProseMirror h2) {
  font-size: 1.35em;
}

.rich-editor__content :deep(.ProseMirror h3) {
  font-size: 1.15em;
}

.rich-editor__content :deep(.ProseMirror hr) {
  border: none;
  border-top: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
  margin: 12px 0;
}

/* 表格 */
.rich-editor__content :deep(.ProseMirror table) {
  border-collapse: collapse;
  width: 100%;
  margin: 8px 0;
  overflow: visible;
}

.rich-editor__content :deep(.ProseMirror table td),
.rich-editor__content :deep(.ProseMirror table th) {
  border: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
  padding: 6px 10px;
  min-width: 60px;
  vertical-align: top;
  position: relative;
}

.rich-editor__content :deep(.ProseMirror table th) {
  background: rgb(var(--v-theme-surface-variant), 0.3);
  font-weight: 600;
}

.rich-editor__content :deep(.ProseMirror table .selectedCell:after) {
  background: rgb(var(--v-theme-primary), 0.08);
  content: '';
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  pointer-events: none;
  position: absolute;
  z-index: 2;
}

.rich-editor__content :deep(.ProseMirror table .column-resize-handle) {
  position: absolute;
  right: -2px;
  top: 0;
  bottom: -2px;
  width: 4px;
  background-color: rgb(var(--v-theme-primary));
  pointer-events: none;
}

/* 折叠面板 */
.rich-editor__content :deep(.ProseMirror details) {
  border: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  margin: 8px 0;
}

.rich-editor__content :deep(.ProseMirror details summary) {
  padding: 6px 10px;
  cursor: pointer;
  font-weight: 500;
}

.rich-editor__content :deep(.ProseMirror details[open] summary) {
  border-bottom: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
}

.rich-editor__content :deep(.ProseMirror details > :not(summary)) {
  padding: 6px 10px;
}

/* @提及 */
.rich-editor__content :deep(.ProseMirror .mention) {
  color: rgb(var(--v-theme-primary));
  font-weight: 500;
}

/* 任务列表 */
.rich-editor__content :deep(.ProseMirror ul[data-type="taskList"]) {
  list-style: none;
  padding-left: 0;
}

.rich-editor__content :deep(.ProseMirror ul[data-type="taskList"] li) {
  display: flex;
  align-items: flex-start;
  gap: 6px;
}

.rich-editor__content :deep(.ProseMirror ul[data-type="taskList"] li label) {
  margin-top: 3px;
}

.rich-editor__content :deep(.ProseMirror ul[data-type="taskList"] li div) {
  flex: 1;
}

.rich-editor__content :deep(.ProseMirror ul[data-type="taskList"] li[data-checked="true"] div > p) {
  text-decoration: line-through;
  opacity: 0.6;
}

/* 字数统计 */
.rich-editor__status {
  display: flex;
  justify-content: flex-end;
  padding: 2px 8px;
  font-size: 11px;
  color: rgb(var(--v-theme-on-surface), 0.4);
  border-top: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
}
</style>

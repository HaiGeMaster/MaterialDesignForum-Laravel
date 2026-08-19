<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { NodeViewContent, NodeViewWrapper } from '@tiptap/vue-3'

const { t } = useI18n()

const props = defineProps({
  node: { type: Object, required: true },
  updateAttributes: { type: Function, required: true },
  editor: { type: Object, required: true },
})

// 只读渲染（ProseRenderer）时隐藏语言选择下拉，仅展示代码
const isReadOnly = computed(() => !props.editor?.isEditable)

// 语言友好名映射（未在表中的使用原名）
const LANG_LABELS = {
  bash: 'Bash / Shell',
  c: 'C',
  cpp: 'C++',
  csharp: 'C#',
  css: 'CSS',
  diff: 'Diff',
  dockerfile: 'Dockerfile',
  go: 'Go',
  graphql: 'GraphQL',
  html: 'HTML',
  ini: 'INI',
  java: 'Java',
  javascript: 'JavaScript',
  json: 'JSON',
  kotlin: 'Kotlin',
  less: 'Less',
  lua: 'Lua',
  makefile: 'Makefile',
  markdown: 'Markdown',
  objectivec: 'Objective-C',
  perl: 'Perl',
  php: 'PHP',
  python: 'Python',
  r: 'R',
  ruby: 'Ruby',
  rust: 'Rust',
  scss: 'SCSS',
  shell: 'Shell',
  sql: 'SQL',
  swift: 'Swift',
  typescript: 'TypeScript',
  xml: 'XML',
  yaml: 'YAML',
}

// 下拉展示的语言列表：lowlight 实际支持的语言 + 常见补充（不支持的走自动检测兜底）
const RAW_LANGS = [
  'bash', 'shell', 'c', 'cpp', 'csharp', 'css', 'diff', 'dockerfile', 'go',
  'graphql', 'html', 'ini', 'java', 'javascript', 'json', 'kotlin', 'less',
  'lua', 'makefile', 'markdown', 'objectivec', 'perl', 'php', 'plaintext',
  'python', 'r', 'ruby', 'rust', 'scss', 'sql', 'swift', 'typescript', 'xml', 'yaml',
]

const options = computed(() => {
  const list = RAW_LANGS.map((value) => ({
    value,
    label: value === 'plaintext' ? t('codeBlock.plaintext') : LANG_LABELS[value] || value,
  }))
  list.sort((a, b) => a.label.localeCompare(b.label))
  return list
})

const knownValues = new Set(RAW_LANGS)
const language = computed(() => props.node.attrs.language || '')

// 只读模式下代码块栏展示的语言名（未知语言回退到默认文案）
const languageLabel = computed(
  () => LANG_LABELS[language.value] || language.value || t('codeBlock.label'),
)

// 下拉候选项：自动检测 + 全部语言 + 当前自定义语言（保证选中项可显示）
const displayOptions = computed(() => {
  const list = [{ value: '', label: t('codeBlock.autoDetect') }, ...options.value]
  if (language.value && !knownValues.has(language.value)) {
    list.push({ value: language.value, label: language.value })
  }
  return list
})

function onChange(value) {
  props.updateAttributes({ language: value || null })
}
</script>

<template>
  <NodeViewWrapper class="code-block" as="div">
    <div class="code-block__bar" contenteditable="false">
      <span class="code-block__label">
        <v-icon icon="mdi-code-tags" size="small" />
        <span>{{ isReadOnly ? languageLabel : $t('codeBlock.label') }}</span>
      </span>
      <v-select
        v-if="!isReadOnly"
        class="code-block__lang"
        :items="displayOptions"
        :model-value="language"
        item-title="label"
        item-value="value"
        density="compact"
        single-line
        hide-details
        variant="solo"
        :title="$t('codeBlock.selectLang')"
        :menu-props="{ contentClass: 'code-block-lang-menu', maxHeight: 260 }"
        @update:model-value="onChange"
        @mousedown.stop
      />
    </div>
    <pre class="code-block__pre"><NodeViewContent class="code-block__code" /></pre>
  </NodeViewWrapper>
</template>

<style scoped>
.code-block {
  margin: 8px 0;
  border-radius: 8px;
  overflow: hidden;
  background: #0d1117;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

/* 顶部语言栏 */
.code-block__bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 4px 8px;
  background: #161b22;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.code-block__label {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.55);
  user-select: none;
}

.code-block__lang {
  flex: none;
  max-width: 180px;
  font-size: 12px;
}

.code-block__lang :deep(.v-field) {
  min-height: 26px;
  background: #0d1117;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 4px;
  box-shadow: none;
  cursor: pointer;
}

.code-block__lang:hover :deep(.v-field) {
  border-color: rgba(255, 255, 255, 0.35);
}

.code-block__lang :deep(.v-field__input) {
  min-height: 26px;
  padding-top: 2px;
  padding-bottom: 2px;
  font-size: 12px;
  color: #e6edf3;
}

.code-block__lang :deep(.v-field__append-inner) {
  color: rgba(255, 255, 255, 0.55);
}

/* 代码区 */
.code-block__pre {
  margin: 0;
  padding: 12px 14px;
  overflow: auto;
  background: #0d1117;
  border-radius: 0;
  font-size: 13px;
  line-height: 1.6;
}

:deep(.code-block__code) {
  display: block;
  padding: 0;
  background: transparent;
  border-radius: 0;
  font-size: inherit;
  font-family: 'JetBrains Mono', 'Fira Code', Consolas, 'Courier New', monospace;
  color: #e6edf3;
  tab-size: 4;
}

/* —— 语法高亮 token（GitHub Dark 风格）—— */
:deep(.hljs-comment),
:deep(.hljs-quote) {
  color: #8b949e;
  font-style: italic;
}

:deep(.hljs-keyword),
:deep(.hljs-selector-tag),
:deep(.hljs-literal),
:deep(.hljs-section),
:deep(.hljs-link) {
  color: #ff7b72;
}

:deep(.hljs-title),
:deep(.hljs-title.function_),
:deep(.hljs-title.class_),
:deep(.hljs-selector-class) {
  color: #d2a8ff;
}

:deep(.hljs-string),
:deep(.hljs-attr),
:deep(.hljs-attribute),
:deep(.hljs-symbol),
:deep(.hljs-bullet),
:deep(.hljs-addition) {
  color: #a5d6ff;
}

:deep(.hljs-number),
:deep(.hljs-variable),
:deep(.hljs-template-variable),
:deep(.hljs-meta),
:deep(.hljs-type),
:deep(.hljs-selector-id) {
  color: #79c0ff;
}

:deep(.hljs-built_in) {
  color: #ffa657;
}

:deep(.hljs-name),
:deep(.hljs-tag) {
  color: #7ee787;
}

:deep(.hljs-deletion) {
  color: #ffa198;
}

:deep(.hljs-emphasis) {
  font-style: italic;
}

:deep(.hljs-strong) {
  font-weight: 600;
}

:deep(.hljs-params) {
  color: #e6edf3;
}
</style>

<style>
/* v-select 下拉菜单浮层（渲染在 body，需全局样式深色化） */
.code-block-lang-menu {
  background: #0d1117;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 6px;
  overflow: hidden;
}

.code-block-lang-menu .v-list-item {
  min-height: 32px;
}

.code-block-lang-menu .v-list-item__title {
  font-size: 12px;
  color: #e6edf3;
}

.code-block-lang-menu .v-list-item--active {
  background: rgba(255, 255, 255, 0.06);
}

.code-block-lang-menu .v-list-item--active .v-list-item__title {
  color: #79c0ff;
}
</style>

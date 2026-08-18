// 可调语言的代码块扩展：基于 lowlight 实时语法高亮，带语言选择 NodeView
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight'
import { VueNodeViewRenderer } from '@tiptap/vue-3'
import { lowlight } from './lowlight'
import CodeBlockView from '../components/CodeBlockView.vue'

export const CodeBlock = CodeBlockLowlight.extend({
  addNodeView() {
    return VueNodeViewRenderer(CodeBlockView)
  },
}).configure({
  lowlight,
  defaultLanguage: null,
})

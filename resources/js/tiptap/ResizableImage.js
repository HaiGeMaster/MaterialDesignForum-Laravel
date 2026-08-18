import { Node, mergeAttributes, nodeInputRule } from '@tiptap/core'
import { VueNodeViewRenderer } from '@tiptap/vue-3'
import MediaResizer from '../components/MediaResizer.vue'
import { sizeAttributes, withSizeStyle } from './media'

/**
 * 可调整大小的图片节点：解析 / 渲染 <img> 标签，
 * 支持通过编辑器内的拖拽手柄调整宽高（尺寸以 width / height 属性与内联 style 输出）。
 */
export const ResizableImage = Node.create({
  name: 'image',

  group: 'block',

  atom: true,

  selectable: true,

  draggable: true,

  addAttributes() {
    return {
      src: { default: null },
      alt: { default: null },
      title: { default: null },
      ...sizeAttributes,
    }
  },

  parseHTML() {
    return [{ tag: 'img' }]
  },

  renderHTML({ HTMLAttributes }) {
    return ['img', mergeAttributes(withSizeStyle(HTMLAttributes))]
  },

  addCommands() {
    return {
      setImage:
        (options) =>
        ({ commands }) => {
          return commands.insertContent({ type: this.name, attrs: options })
        },
    }
  },

  addInputRules() {
    return [
      nodeInputRule({
        find: /!\[(.+|:?)]\((\S+)(?:(?:\s+)["'](\S+)["'])?\)/,
        type: this.type,
        getAttributes: (match) => ({
          src: match[2],
          alt: match[1],
          title: match[3] || null,
        }),
      }),
    ]
  },

  addNodeView() {
    return VueNodeViewRenderer(MediaResizer)
  },
})

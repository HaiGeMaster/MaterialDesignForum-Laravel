import { Node, mergeAttributes } from '@tiptap/core'
import { VueNodeViewRenderer } from '@tiptap/vue-3'
import MediaResizer from '../components/MediaResizer.vue'
import { sizeAttributes, withSizeStyle } from './media'

/**
 * 可调整大小的视频节点：解析 / 渲染 <video> 标签。
 * 通过命令 `editor.chain().focus().setVideo({ src, ... }).run()` 插入，
 * 支持通过编辑器内的拖拽手柄调整宽高（尺寸以 width / height 属性与内联 style 输出）。
 */
export const Video = Node.create({
  name: 'video',

  group: 'block',

  atom: true,

  selectable: true,

  draggable: false,

  addAttributes() {
    return {
      src: { default: null },
      controls: { default: true },
      autoplay: { default: false },
      loop: { default: false },
      muted: { default: false },
      poster: { default: null },
      ...sizeAttributes,
    }
  },

  parseHTML() {
    return [{ tag: 'video' }]
  },

  renderHTML({ HTMLAttributes }) {
    return ['video', mergeAttributes(withSizeStyle(HTMLAttributes), { controls: 'true' })]
  },

  addCommands() {
    return {
      setVideo:
        (options) =>
        ({ commands }) => {
          return commands.insertContent({ type: this.name, attrs: options })
        },
    }
  },

  addNodeView() {
    return VueNodeViewRenderer(MediaResizer)
  },
})

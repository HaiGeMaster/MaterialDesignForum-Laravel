/**
 * 媒体节点（图片 / 视频）共用工具。
 */

/** 从 DOM 元素读取像素尺寸：优先内联 style，其次 width/height 属性 */
export function pxAttr(el, key) {
  if (!el) return null
  const inline = el.style ? parseFloat(el.style[key]) : NaN
  if (Number.isFinite(inline)) return Math.round(inline)
  const attr = parseFloat(el.getAttribute(key))
  return Number.isFinite(attr) ? Math.round(attr) : null
}

/** 把 width/height 合并进 style，保留属性同时输出内联尺寸，供 renderHTML 使用 */
export function withSizeStyle(attrs) {
  const style = []
  if (attrs.width) style.push(`width: ${attrs.width}px`)
  if (attrs.height) style.push(`height: ${attrs.height}px`)
  const rest = { ...attrs }
  if (style.length) {
    rest.style = rest.style ? `${rest.style}; ${style.join('; ')}` : style.join('; ')
  }
  return rest
}

/** 尺寸属性定义（image / video 节点共用） */
export const sizeAttributes = {
  width: {
    default: null,
    parseHTML: (el) => pxAttr(el, 'width'),
    renderHTML: (attrs) => (attrs.width ? { width: attrs.width } : {}),
  },
  height: {
    default: null,
    parseHTML: (el) => pxAttr(el, 'height'),
    renderHTML: (attrs) => (attrs.height ? { height: attrs.height } : {}),
  },
}

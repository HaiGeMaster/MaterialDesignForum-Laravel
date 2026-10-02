// lowlight 实例：注册 common（约 37 种）语言，供代码块语法高亮使用
import { createLowlight, common } from 'lowlight'

export const lowlight = createLowlight(common)

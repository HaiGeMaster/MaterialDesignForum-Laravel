import { defineStore } from 'pinia'

/**
 * 富文本编辑器悬浮窗口的全局控制器。
 *
 * 任何组件都可以调用 openEditor() 打开窗口：
 * - 写提问 / 写文章 / 写回答
 * - 编辑提问 / 编辑文章 / 编辑回答
 *
 * 窗口标题栏图标与文字（meta）会随「创建 / 编辑」自动切换。
 */
export const useEditorWindowStore = defineStore('editorWindow', {
  state: () => ({
    /** 窗口是否打开 */
    open: false,
    /** 内容类型：question 提问 / article 文章 / answer 回答 */
    mode: 'question',
    /** 操作类型：create 创建 / edit 编辑 */
    action: 'create',
    /** 编辑目标 id（question_id / article_id / answer_id），创建时为 null */
    targetId: null,
    /** 写回答所属的问题 id */
    questionId: null,
    /** 标题（提问 / 文章；回答无标题） */
    title: '',
    /** 富文本 HTML 内容 */
    content: '',
    /** 已选话题对象列表（提问 / 文章；回答不支持话题） */
    topics: [],
    /** 发布请求进行中 */
    loading: false,
    /** API 基础路径：管理员后台传 '/admin/modules/questions' 等，默认空走用户端 */
    apiBase: '',
  }),

  getters: {
    /** 窗口标题栏图标 + 文案 key，随模式与动作切换 */
    meta(state) {
      const map = {
        question_create: {
          icon: 'mdi-forum-plus-outline',
          titleKey: 'editorWindow.createQuestion',
          publishKey: 'editorWindow.publish',
        },
        question_edit: {
          icon: 'mdi-forum',
          titleKey: 'editorWindow.editQuestion',
          publishKey: 'editorWindow.save',
        },
        article_create: {
          icon: 'mdi-file-document-plus-outline',
          titleKey: 'editorWindow.createArticle',
          publishKey: 'editorWindow.publish',
        },
        article_edit: {
          icon: 'mdi-file-document',
          titleKey: 'editorWindow.editArticle',
          publishKey: 'editorWindow.save',
        },
        answer_create: {
          icon: 'mdi-comment-plus-outline',
          titleKey: 'editorWindow.createAnswer',
          publishKey: 'editorWindow.publish',
        },
        answer_edit: {
          icon: 'mdi-comment-plus-outline',
          titleKey: 'editorWindow.editAnswer',
          publishKey: 'editorWindow.save',
        },
      }
      return map[`${state.mode}_${state.action}`] || map.question_create
    },

    /** 提问 / 文章需要标题；回答没有标题字段 */
    hasTitle(state) {
      return state.mode !== 'answer'
    },
  },

  actions: {
    /**
     * 打开编辑器窗口。
     * @param {Object} payload
     * @param {'question'|'article'|'answer'} payload.mode
     * @param {'create'|'edit'} [payload.action='create']
     * @param {number|null} [payload.targetId] 编辑目标 id
     * @param {number|null} [payload.questionId] 写回答所属问题 id
     * @param {string} [payload.title] 初始标题（编辑回显）
     * @param {string} [payload.content] 初始内容（编辑回显）
     * @param {Array} [payload.topics] 已选话题对象列表（编辑回显）
     * @param {string} [payload.apiBase] 管理员后台 API 路径前缀
     */
    openEditor({ mode, action = 'create', targetId = null, questionId = null, title = '', content = '', topics = [], apiBase = '' }) {
      this.mode = mode
      this.action = action
      this.targetId = targetId
      this.questionId = questionId
      this.title = title
      this.content = content
      this.topics = Array.isArray(topics) ? topics.slice() : []
      this.apiBase = apiBase
      this.loading = false
      this.open = true
    },

    /** 关闭窗口并清空状态 */
    close() {
      this.open = false
      this.loading = false
      this.topics = []
      this.apiBase = ''
    },
  },
})

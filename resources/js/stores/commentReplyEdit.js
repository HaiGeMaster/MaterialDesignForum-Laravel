import { defineStore } from 'pinia'

/**
 * 全局评论 / 回复编辑对话框（CommentReplyEditDialog）状态。
 *
 * 更多操作（MoreActions）点击“编辑”时对 comment / reply 类型调用 open()，
 * CommentReplyEditDialog 读取状态并提交 PUT /comments/:id 或 PUT /replies/:id，
 * 提交成功后派发 window CustomEvent('comment:edited' | 'reply:edited')，
 * 由 CommentItem / ReplyItem 监听后更新本地数据。
 */
export const useCommentReplyEditStore = defineStore('commentReplyEdit', {
  state: () => ({
    /** 编辑对话框是否显示 */
    visible: false,
    /** 被编辑对象类型：comment | reply */
    type: 'comment',
    /** 被编辑对象 id：comment_id 或 reply_id */
    id: null,
    /** 当前内容（打开时回显到输入框） */
    content: '',
  }),
  actions: {
    open(payload) {
      Object.assign(this.$state, payload, { visible: true })
    },
    close() {
      this.visible = false
    },
  },
})

import { defineStore } from 'pinia'

/**
 * 全局回复输入框（CommentReplySheet）状态。
 *
 * 评论条目 / 回复条目点击“回复”时调用 open()，
 * CommentReplySheet 读取状态并提交 POST /replies，
 * 提交成功后派发 window CustomEvent('reply:added') 通知所属评论刷新 / 插入回复列表。
 */
export const useReplySheetStore = defineStore('replySheet', {
  state: () => ({
    /** 底部输入框是否显示 */
    visible: false,
    /** 被回复对象 id：comment_id 或 reply_id */
    replyableId: null,
    /** 被回复对象类型：comment | reply（支持回复对回复） */
    replyableType: 'comment',
    /** 所属评论 id（回复最终挂在哪条评论下） */
    replyableCommentId: null,
    /** 被回复的用户 id */
    replyableUserId: null,
    /** 展示用目标信息：{ username, avatar, content } */
    target: null,
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

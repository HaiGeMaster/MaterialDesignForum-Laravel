import { defineStore } from 'pinia'

/**
 * 全局评论列表对话框 store
 *
 * 全局单例，供「问题详情页 / 回答卡片」等任意场景打开同一个评论对话框。
 *
 * 用法：
 *   const commentDialog = useCommentDialogStore()
 *   commentDialog.open({
 *     commentableType: 'answer',
 *     commentableId: answer.answer_id,
 *     onCountChange: (n) => (commentCount.value = n),
 *   })
 *
 * onCountChange 为可选回调：评论总数变化（加载 / 发布 / 删除）时通知调用方，
 * 用于同步按钮上的角标数字。
 */
export const useCommentDialogStore = defineStore('commentDialog', {
  state: () => ({
    model: false,
    commentableType: '',
    commentableId: null,
    onCountChange: null,
  }),

  actions: {
    open({ commentableType, commentableId, onCountChange = null } = {}) {
      this.commentableType = commentableType
      this.commentableId = commentableId
      this.onCountChange = onCountChange
      this.model = true
    },

    close() {
      this.model = false
      this.onCountChange = null
    },

    emitCountChange(count) {
      if (typeof this.onCountChange === 'function') {
        this.onCountChange(count)
      }
    },
  },
})

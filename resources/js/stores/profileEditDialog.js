import { defineStore } from 'pinia'

/**
 * 全局个人资料编辑对话框 store
 *
 * 全局单例，供「个人资料卡 / 任意场景」打开同一个编辑资料对话框。
 *
 * 用法：
 *   const profileEdit = useProfileEditStore()
 *   profileEdit.open(user, { onSaved: (updatedUser) => {} })
 *
 * onSaved 为可选回调：保存成功后携带后端返回的最新用户对象执行（用于刷新调用方状态）。
 */
export const useProfileEditStore = defineStore('profileEditDialog', {
  state: () => ({
    /** 编辑对话框是否显示 */
    model: false,
    /** 被编辑的用户对象 */
    user: null,
    /** 保存成功回调 */
    onSaved: null,
  }),

  actions: {
    open(user, { onSaved = null } = {}) {
      this.user = user
      this.onSaved = onSaved
      this.model = true
    },

    close() {
      this.model = false
      this.user = null
      this.onSaved = null
    },

    emitSaved(user) {
      if (typeof this.onSaved === 'function') {
        this.onSaved(user)
      }
    },
  },
})

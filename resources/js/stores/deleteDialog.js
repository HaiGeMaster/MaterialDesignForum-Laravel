import { defineStore } from 'pinia'

/**
 * 全局删除确认对话框 store
 *
 * 全局单例，供「评论 / 回复 / 回答 / 问题 / 文章 / 话题 / 清空通知」等
 * 任意场景调用同一个删除确认对话框。
 *
 * 用法：
 *   const deleteDialog = useDeleteDialogStore()
 *   deleteDialog.open({
 *     title: t('...'),
 *     text: t('...'),
 *     onConfirm: async () => { await http.delete(...) },
 *   })
 *
 * onConfirm 为异步函数：成功后对话框自动关闭并清除回调；
 * 若抛出异常，对话框保持打开（调用方可自行 toast 错误信息）以便重试。
 */
export const useDeleteDialogStore = defineStore('deleteDialog', {
  state: () => ({
    model: false,
    title: '',
    text: '',
    icon: 'mdi-delete-outline',
    color: 'error',
    confirmText: '',
    cancelText: '',
    loading: false,
    onConfirm: null,
  }),

  actions: {
    open({
      title = '',
      text = '',
      icon = 'mdi-delete-outline',
      color = 'error',
      confirmText = '',
      cancelText = '',
      onConfirm = null,
      onError = null,
    }) {
      this.title = title
      this.text = text
      this.icon = icon
      this.color = color
      this.confirmText = confirmText
      this.cancelText = cancelText
      this.onConfirm = onConfirm
      this.onError = onError
      this.loading = false
      this.model = true
    },

    close() {
      this.model = false
      this.loading = false
      this.onConfirm = null
      this.onError = null
    },

    async confirm() {
      if (typeof this.onConfirm !== 'function') {
        this.close()
        return
      }
      this.loading = true
      try {
        await this.onConfirm()
        this.close()
      } catch (error) {
        if (typeof this.onError === 'function') {
          this.onError(error)
        }
        // 失败时保持对话框打开，允许用户重试；错误消息也会由调用方展示。
      } finally {
        this.loading = false
      }
    },
  },
})

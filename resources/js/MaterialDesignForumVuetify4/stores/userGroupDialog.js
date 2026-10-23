import { defineStore } from 'pinia'

export const useUserGroupDialogStore = defineStore('userGroupDialog', {
  state: () => ({
    model: false,
    userGroup: null,
    apiBase: '/admin/modules/user-groups',
    onSubmit: null,
  }),

  actions: {
    open({
      userGroup = null,
      apiBase = '/admin/modules/user-groups',
      onSubmit = null,
    } = {}) {
      this.userGroup = userGroup
      this.apiBase = apiBase
      this.onSubmit = onSubmit
      this.model = true
    },

    close() {
      this.model = false
      this.userGroup = null
      this.apiBase = '/admin/modules/user-groups'
      this.onSubmit = null
    },

    emitSubmit(userGroup) {
      if (typeof this.onSubmit === 'function') {
        this.onSubmit(userGroup)
      }
    },
  },
})

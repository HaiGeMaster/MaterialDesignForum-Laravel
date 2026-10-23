import { defineStore } from 'pinia'

export const useReportDialogStore = defineStore('reportDialog', {
  state: () => ({
    model: false,
    type: '',
    item: null,
  }),

  actions: {
    open({ type, item }) {
      this.type = type
      this.item = item
      this.model = true
    },

    close() {
      this.model = false
      this.type = ''
      this.item = null
    },
  },
})

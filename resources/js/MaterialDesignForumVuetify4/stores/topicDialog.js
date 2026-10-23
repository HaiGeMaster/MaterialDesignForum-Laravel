import { defineStore } from 'pinia'

export const useTopicDialogStore = defineStore('topicDialog', {
  state: () => ({
    model: false,
    topic: null,
    apiBase: '',
    onSubmit: null,
  }),

  actions: {
    open({ topic = null, apiBase = '', onSubmit = null } = {}) {
      this.topic = topic
      this.apiBase = apiBase
      this.onSubmit = onSubmit
      this.model = true
    },

    close() {
      this.model = false
      this.topic = null
      this.apiBase = ''
      this.onSubmit = null
    },

    emitSubmit(topic) {
      if (typeof this.onSubmit === 'function') {
        this.onSubmit(topic)
      }
    },
  },
})

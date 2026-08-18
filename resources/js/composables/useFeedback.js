import { ref } from 'vue'

// 全局消息提示（App.vue 中的 v-snackbar 绑定同一状态）
const visible = ref(false)
const text = ref('')
const color = ref('info')

export function useFeedback() {
  const show = (message, type = 'info') => {
    text.value = message
    color.value = type
    visible.value = true
  }

  return { visible, text, color, show }
}

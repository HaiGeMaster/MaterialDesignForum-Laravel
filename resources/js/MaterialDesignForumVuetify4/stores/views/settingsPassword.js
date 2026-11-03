import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../../api'

/**
 * 修改密码（SettingsPasswordView）数据 store
 *
 * 负责表单状态与提交（校验规则依赖 i18n，仍保留在视图中）。
 */
export const useSettingsPasswordViewStore = defineStore('settingsPasswordView', () => {
  const form = ref({
    old_password: '',
    password: '',
    password_confirmation: '',
  })

  const loading = ref(false)
  const error = ref('')

  /** 提交修改密码，成功后清空表单并返回后端响应体（含 message） */
  async function updatePassword() {
    loading.value = true
    error.value = ''
    try {
      const { data } = await http.put('/user/password', { ...form.value })
      resetForm()
      return data
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  function resetForm() {
    form.value = {
      old_password: '',
      password: '',
      password_confirmation: '',
    }
  }

  return {
    form,
    loading,
    error,
    updatePassword,
    resetForm,
  }
})

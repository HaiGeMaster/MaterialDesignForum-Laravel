import { defineStore } from 'pinia'
import http from '../api'

export const useUserStore = defineStore('user', {
  state: () => ({
    list: [],
    loading: false,
    uploading: false,
    error: '',
  }),

  actions: {
    async fetchUsers(params = {}) {
      this.loading = true
      this.error = ''

      try {
        const { data } = await http.get('/users', { params })
        const items = Array.isArray(data) ? data : data.data ?? []
        // 第一页替换列表，后续页追加（无限滚动）
        if (!params.page || params.page === 1) {
          this.list = items
        } else {
          this.list = [...this.list, ...items]
        }
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    // 获取单个用户（用户主页）
    async fetchUser(userId) {
      this.loading = true
      this.error = ''

      try {
        const { data } = await http.get(`/users/${userId}`)
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async createUser(payload) {
      this.loading = true
      this.error = ''

      try {
        const { data } = await http.post('/users', payload)
        this.list.unshift(data)
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateUser(userId, payload) {
      this.loading = true
      this.error = ''

      try {
        const { data } = await http.put(`/users/${userId}`, payload)
        this.list = this.list.map((item) => (item.user_id === userId ? data : item))
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteUser(userId) {
      this.loading = true
      this.error = ''

      try {
        await http.delete(`/users/${userId}`)
        this.list = this.list.filter((item) => item.user_id !== userId)
        return true
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    // 编辑当前登录用户的个人资料
    async updateProfile(payload) {
      this.loading = true
      this.error = ''

      try {
        const { data } = await http.put('/user/profile', payload)
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    // 上传图片（avatar / cover），可选 context 和 itemId 区分关联场景
    async uploadImage(type, file, { context, itemId } = {}) {
      this.uploading = true
      this.error = ''

      try {
        const formData = new FormData()
        formData.append('type', type)
        formData.append('file', file)
        if (context) formData.append('context', context)
        if (itemId != null) formData.append('item_id', itemId)
        const { data } = await http.post('/user/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.uploading = false
      }
    },

    // 修改当前登录用户的密码
    async updatePassword(payload) {
      this.loading = true
      this.error = ''

      try {
        const { data } = await http.put('/user/password', payload)
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})

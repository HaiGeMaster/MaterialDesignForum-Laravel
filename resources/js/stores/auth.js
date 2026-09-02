import { defineStore } from 'pinia'
import http from '../api'
import { identifyClarityUser } from '../clarity'
import { useFollowStore } from './follow'

const TOKEN_KEY = 'forum_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem(TOKEN_KEY) || '',
    loading: false,
    error: '',
  }),

  getters: {
    isLoggedIn: (state) => Boolean(state.token),
    userGroupName: (state) => state.user?.user_group?.user_group_name || '',
    canAccessAdmin: (state) => Boolean(
      // state.user?.user_group?.is_admin
      state.user?.user_group?.ability_admin_login,// || state.user?.user_group?.is_admin,
    ),
  },

  actions: {
    // 注册成功后自动保存登录态
    async register(payload) {
      this.loading = true
      this.error = ''

      try {
        const { data } = await http.post('/register', payload)
        this.token = data.token || ''
        this.user = data.user

        if (this.token) {
          localStorage.setItem(TOKEN_KEY, this.token)
        }

        identifyClarityUser(this.user)
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    // 发送邮箱注册验证码
    async sendEmailCode(email) {
      const { data } = await http.post('/email-code', { email })
      return data
    },

    // 发送邮箱找回密码验证码
    async sendResetCode(email) {
      const { data } = await http.post('/email-code/reset', { email })
      return data
    },

    // 邮箱验证码找回密码（成功后自动登录）
    async resetPassword(payload) {
      this.loading = true
      this.error = ''

      try {
        const { data } = await http.post('/auth/reset-password', payload)
        this.token = data.token || ''
        this.user = data.user

        if (this.token) {
          localStorage.setItem(TOKEN_KEY, this.token)
        }

        identifyClarityUser(this.user)
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async login(payload) {
      this.loading = true
      this.error = ''

      try {
        const { data } = await http.post('/login', payload)
        this.token = data.token || ''
        this.user = data.user

        if (this.token) {
          localStorage.setItem(TOKEN_KEY, this.token)
        }

        identifyClarityUser(this.user)
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    // 第三方 OAuth 回调落地：用后端返回的 token 直接落地登录，再拉取用户信息
    async completeOAuthLogin(token) {
      if (!token) return null

      this.token = token
      localStorage.setItem(TOKEN_KEY, token)
      await this.fetchMe()
      return this.user
    },

    // 通过已有 token 拉取当前用户（应用启动时恢复登录态）
    async fetchMe() {
      if (!this.token) return null

      this.loading = true
      this.error = ''

      try {
        const { data } = await http.get('/auth/me')
        this.user = data.user
        identifyClarityUser(this.user)
        return this.user
      } catch (error) {
        this.user = null
        this.token = ''
        localStorage.removeItem(TOKEN_KEY)
        this.error = error.message
        return null
      } finally {
        this.loading = false
      }
    },

    // 退出登录：调用后端使令牌失效，并清理本地状态
    async logout() {
      this.loading = true

      try {
        if (this.token) {
          await http.post('/auth/logout').catch(() => {})
        }
      } finally {
        this.user = null
        this.token = ''
        localStorage.removeItem(TOKEN_KEY)
        // 清空关注状态缓存，避免下一个账号串用
        useFollowStore().reset()
        this.loading = false
      }
    },

    setUser(user) {
      this.user = user
      identifyClarityUser(user)
    },
  },
})

import axios from 'axios'

const TOKEN_KEY = 'forum_token'

const http = axios.create({
  baseURL: '/api',
  timeout: 20000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// 请求拦截器：自动附加 Bearer Token
http.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// 响应拦截器：统一提取错误信息；401 时清理本地登录态
http.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status
    const data = error?.response?.data
    let message = data?.message || error?.message || 'Request failed.'

    // 422 验证错误：优先展示第一个字段错误
    if (status === 422 && data?.errors) {
      const firstKey = Object.keys(data.errors)[0]
      if (firstKey && data.errors[firstKey]?.length) {
        message = data.errors[firstKey][0]
      }
    }

    if (status === 401) {
      localStorage.removeItem(TOKEN_KEY)
      window.dispatchEvent(new CustomEvent('auth:unauthorized'))
    }

    // 保留原始 error 对象（含 response.status 等元信息），仅覆盖 message
    error.message = message
    return Promise.reject(error)
  }
)

export default http

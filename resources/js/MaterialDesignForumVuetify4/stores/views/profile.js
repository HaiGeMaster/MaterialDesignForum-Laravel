import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../../api'

/**
 * 用户主页（ProfileView）数据 store
 *
 * 负责「他人主页」用户信息的拉取与关注状态回写。
 * 「我的主页」数据来自 auth store，不在此重复获取。
 */
export const useProfileViewStore = defineStore('profileView', () => {
  const viewedUser = ref(null)
  const loading = ref(false)
  const error = ref('')

  async function fetchViewedUser(userId) {
    loading.value = true
    error.value = ''
    try {
      const { data } = await http.get(`/users/${userId}`)
      viewedUser.value = data
      return data
    } catch (e) {
      error.value = e.message
      viewedUser.value = null
      throw e
    } finally {
      loading.value = false
    }
  }

  /** 关注状态回写（服务器随用户详情返回 is_following） */
  function updateFollow({ following, followerCount }) {
    if (viewedUser.value && following !== undefined) {
      viewedUser.value.is_following = following
    }
    if (viewedUser.value && followerCount !== undefined) {
      viewedUser.value.follower_count = followerCount
    }
  }

  function clearViewedUser() {
    viewedUser.value = null
    error.value = ''
  }

  return {
    viewedUser,
    loading,
    error,
    fetchViewedUser,
    updateFollow,
    clearViewedUser,
  }
})

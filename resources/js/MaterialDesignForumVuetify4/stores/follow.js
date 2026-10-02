import { defineStore } from 'pinia'
import http from '../api'

/**
 * 关注 store
 *
 * 支持关注目标类型：user 用户、topic 话题、question 提问、article 文章。
 * 注意：关注状态由服务器随各列表/详情接口一并返回（is_following 字段），
 * 前端应以接口数据为准，不要依赖本 store 的旁路缓存作为数据源。
 */
export const useFollowStore = defineStore('follow', {
  state: () => ({
    // `${type}:${id}` -> boolean（旁路缓存，不作为数据源）
    map: {},
  }),

  actions: {
    /**
     * 切换关注状态（关注 / 取消关注）
     * @returns {Promise<{following: boolean, follower_count: number}>}
     */
    async toggle(type, id) {
      const { data } = await http.post('/follows/toggle', { type, id: Number(id) })
      this.map[`${type}:${Number(id)}`] = data.following
      return data
    },

    /** 清除旁路缓存（用于退出登录时） */
    reset() {
      this.map = {}
    },
  },
})

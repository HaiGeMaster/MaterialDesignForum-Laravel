import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../../api'
import { useInfiniteList } from '../../composables/useInfiniteList'

/**
 * 话题列表（TopicsView）数据 store
 *
 * 按 tab（recommend / following）分页加载话题，配合 InfiniteScroll 无限滚动。
 */
export const useTopicsViewStore = defineStore('topicsView', () => {
  // 当前数据所属 tab
  const tab = ref('recommend')

  const { list: topics, loading, error, finished, load, loadNext, retry } = useInfiniteList(
    async (page) => {
      const { data } = await http.get('/topics', { params: { tab: tab.value, page } })
      if (Array.isArray(data)) return { items: data, hasMore: false }
      return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
    },
  )

  /** 切换 tab 并（重新）加载：同一 tab 且有数据时复用缓存 */
  async function loadTab(nextTab) {
    if (nextTab === tab.value && topics.value.length) return
    tab.value = nextTab
    await load(true)
  }

  /** 强制刷新当前 tab 的第一页 */
  function refresh() {
    return load(true)
  }

  /** 关注状态回写（服务器随列表返回 is_following） */
  function updateFollow(topic, { following, followerCount }) {
    topic.is_following = following
    if (followerCount !== undefined) {
      topic.follower_count = followerCount
    }
  }

  return {
    tab,
    topics,
    loading,
    error,
    finished,
    loadTab,
    refresh,
    loadNext,
    retry,
    updateFollow,
  }
})

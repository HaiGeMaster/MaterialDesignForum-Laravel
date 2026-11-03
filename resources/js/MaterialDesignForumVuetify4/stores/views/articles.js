import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../../api'
import { useInfiniteList } from '../../composables/useInfiniteList'

/**
 * 文章列表（ArticlesView）数据 store
 *
 * 按 tab（hot / latest / following）分页加载文章，配合 InfiniteScroll 无限滚动。
 */
export const useArticlesViewStore = defineStore('articlesView', () => {
  // 当前数据所属 tab
  const tab = ref('hot')

  const { list: articles, loading, error, finished, load, loadNext, retry } = useInfiniteList(
    async (page) => {
      const { data } = await http.get('/articles', { params: { tab: tab.value, page } })
      if (Array.isArray(data)) return { items: data, hasMore: false }
      return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
    },
  )

  /** 切换 tab 并（重新）加载：同一 tab 且有数据时复用缓存 */
  async function loadTab(nextTab) {
    if (nextTab === tab.value && articles.value.length) return
    tab.value = nextTab
    await load(true)
  }

  /** 强制刷新当前 tab 的第一页（发布 / 编辑文章后调用） */
  function refresh() {
    return load(true)
  }

  /** 关注状态回写到作者数据 */
  function updateCreatorFollow(item, { following, followerCount }) {
    const creator = item?.creator
    if (!creator) return
    creator.is_following = following
    if (followerCount !== undefined) {
      creator.follower_count = followerCount
    }
  }

  return {
    tab,
    articles,
    loading,
    error,
    finished,
    loadTab,
    refresh,
    loadNext,
    retry,
    updateCreatorFollow,
  }
})

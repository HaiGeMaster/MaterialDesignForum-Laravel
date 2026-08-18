import { ref } from 'vue'

/**
 * 无限滚动列表状态管理。
 *
 * 配合 InfiniteScroll 组件使用：
 * - load(true) 重置并加载第一页（初始 / 切换 tab / 发布后刷新）
 * - loadNext() 加载下一页（由 InfiniteScroll 的 load 事件触发）
 * - retry()   加载失败后重试当前页（由 InfiniteScroll 的 retry 事件触发）
 *
 * @param {(page: number) => Promise<{ items: any[], hasMore: boolean }>} fetcher 分页请求函数
 */
export function useInfiniteList(fetcher) {
  const list = ref([])
  const loading = ref(false)
  const error = ref(false)
  const page = ref(1)
  const finished = ref(false)

  let requestId = 0

  async function load(reset = false) {
    const id = ++requestId

    if (reset) {
      page.value = 1
      list.value = []
      finished.value = false
      error.value = false
    } else if (loading.value || finished.value) {
      return
    }

    loading.value = true
    error.value = false

    try {
      const { items, hasMore } = await fetcher(page.value)
      if (id !== requestId) return // 已发生新的加载，丢弃过期结果
      list.value = reset ? items : [...list.value, ...items]
      finished.value = !hasMore
    } catch (e) {
      if (id !== requestId) return
      error.value = true
    } finally {
      if (id === requestId) loading.value = false
    }
  }

  /** 加载下一页 */
  function loadNext() {
    if (loading.value || finished.value || error.value) return
    page.value += 1
    return load()
  }

  /** 重试当前页 */
  function retry() {
    if (loading.value) return
    return load()
  }

  return { list, loading, error, finished, load, loadNext, retry }
}

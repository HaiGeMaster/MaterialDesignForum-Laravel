import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import http from '../../api'

/**
 * 首页（HomeView）数据 store
 *
 * 汇总问题 / 文章 / 用户三类数据，并在本地派生出「热门 / 最新」面板列表。
 */
export const useHomeViewStore = defineStore('homeView', () => {
  const questions = ref([])
  const articles = ref([])
  const popularUsers = ref([])
  const newUsers = ref([])
  const loading = ref(false)
  // 是否已经成功加载过（用于进入首页时复用缓存）
  const loaded = ref(false)

  // ---- 各面板数据：切片前 5 条 ----

  const hotQuestions = computed(() =>
    [...questions.value]
      .sort((a, b) => b.vote_count + b.answer_count - (a.vote_count + a.answer_count))
      .slice(0, 5),
  )

  const newQuestions = computed(() =>
    [...questions.value]
      .sort((a, b) => new Date(b.create_time) - new Date(a.create_time))
      .slice(0, 5),
  )

  const hotArticles = computed(() =>
    [...articles.value]
      .sort((a, b) => b.vote_count + b.follower_count - (a.vote_count + a.follower_count))
      .slice(0, 5),
  )

  const newArticles = computed(() =>
    [...articles.value]
      .sort((a, b) => new Date(b.create_time) - new Date(a.create_time))
      .slice(0, 5),
  )

  const popularUsersTop = computed(() =>
    [...popularUsers.value]
      .sort((a, b) => (b.follower_count ?? 0) - (a.follower_count ?? 0))
      .slice(0, 5),
  )

  const newUsersTop = computed(() =>
    [...newUsers.value]
      .sort((a, b) => new Date(b.create_time) - new Date(a.create_time))
      .slice(0, 5),
  )

  // ---- 数据获取 ----

  async function fetchData() {
    loading.value = true
    try {
      // 用户列表后端已按 tab 排序：recommend 按粉丝数倒序、new 按注册时间倒序
      const [q, a, pu, nu] = await Promise.all([
        http.get('/questions'),
        http.get('/articles'),
        http.get('/users', { params: { tab: 'recommend' } }),
        http.get('/users', { params: { tab: 'new' } }),
      ])
      questions.value = q.data
      articles.value = a.data
      popularUsers.value = pu.data
      newUsers.value = nu.data
      loaded.value = true
    } catch (error) {
      // 首页加载失败不打断页面
    } finally {
      loading.value = false
    }
  }

  /** 进入首页时调用：已加载过则复用缓存，force 为 true 时强制刷新 */
  async function ensureLoaded(force = false) {
    if (loading.value) return
    if (loaded.value && !force) return
    await fetchData()
  }

  // ---- 数据回写 ----

  /** 关注状态回写到作者数据（与其它视图的回写模式一致） */
  function updateCreatorFollow(item, { following, followerCount }) {
    const creator = item?.creator
    if (!creator) return
    creator.is_following = following
    if (followerCount !== undefined) {
      creator.follower_count = followerCount
    }
  }

  return {
    questions,
    articles,
    popularUsers,
    newUsers,
    loading,
    loaded,
    hotQuestions,
    newQuestions,
    hotArticles,
    newArticles,
    popularUsersTop,
    newUsersTop,
    fetchData,
    ensureLoaded,
    updateCreatorFollow,
  }
})

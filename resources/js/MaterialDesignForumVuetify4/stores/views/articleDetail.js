import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../../api'

/**
 * 文章详情（ArticleDetailView）数据 store
 *
 * 负责文章详情数据、关注回写与删除。
 */
export const useArticleDetailViewStore = defineStore('articleDetailView', () => {
  const article = ref(null)
  const loading = ref(false)
  const error = ref('')

  async function fetchArticle(articleId) {
    loading.value = true
    error.value = ''
    try {
      const { data } = await http.get(`/articles/${articleId}`)
      article.value = data
      return data
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  /** 关注状态回写（服务器随详情返回 is_following） */
  function updateFollow({ following, followerCount }) {
    if (following !== undefined && article.value) {
      article.value.is_following = following
    }
    if (followerCount !== undefined && article.value) {
      article.value.follower_count = followerCount
    }
  }

  /** 删除当前文章（成功后清空本地数据） */
  async function deleteArticle() {
    if (!article.value) return
    await http.delete(`/articles/${article.value.article_id}`)
    reset()
  }

  function reset() {
    article.value = null
    error.value = ''
  }

  return {
    article,
    loading,
    error,
    fetchArticle,
    updateFollow,
    deleteArticle,
    reset,
  }
})

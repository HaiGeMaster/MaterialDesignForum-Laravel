import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../../api'

/**
 * 话题详情（TopicDetailView）数据 store
 *
 * 负责话题本身、创建者以及话题下的文章 / 问题列表的拉取与删除。
 */
export const useTopicDetailViewStore = defineStore('topicDetailView', () => {
  const topic = ref(null)
  const creator = ref(null)
  const articles = ref([])
  const questions = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchTopic(topicId) {
    loading.value = true
    error.value = ''
    try {
      const { data } = await http.get(`/topics/${topicId}`)
      topic.value = data.topic
      creator.value = data.creator || null
      articles.value = data.articles || []
      questions.value = data.questions || []
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
    if (following !== undefined && topic.value) {
      topic.value.is_following = following
    }
    if (followerCount !== undefined && topic.value) {
      topic.value.follower_count = followerCount
    }
  }

  /** 删除当前话题（成功后清空本地数据） */
  async function deleteTopic() {
    if (!topic.value) return
    await http.delete(`/topics/${topic.value.topic_id}`)
    reset()
  }

  function reset() {
    topic.value = null
    creator.value = null
    articles.value = []
    questions.value = []
    error.value = ''
  }

  return {
    topic,
    creator,
    articles,
    questions,
    loading,
    error,
    fetchTopic,
    updateFollow,
    deleteTopic,
    reset,
  }
})

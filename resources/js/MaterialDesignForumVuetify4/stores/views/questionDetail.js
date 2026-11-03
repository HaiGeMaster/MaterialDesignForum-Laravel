import { defineStore } from 'pinia'
import { ref } from 'vue'
import http from '../../api'

/**
 * 问题详情（QuestionDetailView）数据 store
 *
 * 负责问题详情数据、关注 / 评论数回写与删除。
 */
export const useQuestionDetailViewStore = defineStore('questionDetailView', () => {
  const question = ref(null)
  const loading = ref(false)
  const error = ref('')

  async function fetchQuestion(questionId) {
    loading.value = true
    error.value = ''
    try {
      const { data } = await http.get(`/questions/${questionId}`)
      question.value = data
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
    if (following !== undefined && question.value) {
      question.value.is_following = following
    }
    if (followerCount !== undefined && question.value) {
      question.value.follower_count = followerCount
    }
  }

  /** 评论数回写（评论对话框变更后调用） */
  function setCommentCount(count) {
    if (question.value) question.value.comment_count = count
  }

  /** 删除当前问题（成功后清空本地数据） */
  async function deleteQuestion() {
    if (!question.value) return
    await http.delete(`/questions/${question.value.question_id}`)
    reset()
  }

  function reset() {
    question.value = null
    error.value = ''
  }

  return {
    question,
    loading,
    error,
    fetchQuestion,
    updateFollow,
    setCommentCount,
    deleteQuestion,
    reset,
  }
})

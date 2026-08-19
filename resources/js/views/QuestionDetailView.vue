<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'
import AnswersList from '../components/AnswersList.vue'
import CommentButton from '../components/CommentButton.vue'
import FollowButton from '../components/FollowButton.vue'
import MoreActions from '../components/MoreActions.vue'
import ProseRenderer from '../components/ProseRenderer.vue'
import { useAuthStore } from '../stores/auth'
import { useCommentDialogStore } from '../stores/commentDialog'
import { useDeleteDialogStore } from '../stores/deleteDialog'
import { useEditorWindowStore } from '../stores/editorWindow'
import { useFeedback } from '../composables/useFeedback'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const route = useRoute()
const router = useRouter()
const { show } = useFeedback()
const { t } = useI18n()
const authStore = useAuthStore()
const commentDialog = useCommentDialogStore()
const deleteDialog = useDeleteDialogStore()
const editorWindow = useEditorWindowStore()

const question = ref(null)
const loading = ref(false)

// 评论数（操作栏按钮角标显示）
const commentCount = computed(() => Number(question.value?.comment_count || 0))

function handleCommentCount(count) {
  if (question.value) question.value.comment_count = count
}

function openComments() {
  commentDialog.open({
    commentableType: 'question',
    commentableId: question.value?.question_id,
    onCountChange: handleCommentCount,
  })
}

// 作者信息（后端随详情返回 creator）
const creator = computed(() => question.value?.creator || null)
const creatorInitial = computed(() => creator.value?.username?.slice(0, 1).toUpperCase() || '?')

const isFollowing = computed(() => Boolean(question.value?.is_following))

// 当前用户是否为本问题作者
const isQuestionOwner = computed(
  () => authStore.user?.user_id && String(authStore.user.user_id) === String(question.value?.user_id),
)

function dateText(time) {
  return time ? String(time).slice(0, 10) : ''
}

async function fetchQuestion() {
  loading.value = true
  try {
    const { data } = await http.get(`/questions/${route.params.question_id}`)
    question.value = data
  } catch (error) {
    show(error.message, 'error')
  } finally {
    loading.value = false
  }
}

function toggleFollow({ following, followerCount }) {
  if (following !== undefined && question.value) {
    question.value.is_following = following
  }
  if (followerCount !== undefined && question.value) {
    question.value.follower_count = followerCount
  }
}

// ---- 编辑 ----

function editQuestion() {
  editorWindow.openEditor({
    mode: 'question',
    action: 'edit',
    targetId: question.value.question_id,
    title: question.value.title,
    content: question.value.content_markdown,
    topics: question.value.topics || [],
  })
}

// ---- 删除 ----

function askDeleteQuestion() {
  deleteDialog.open({
    title: t('questionDetail.deleteQuestionTitle'),
    text: t('questionDetail.deleteQuestionConfirm'),
    onConfirm: confirmDelete,
  })
}

async function confirmDelete() {
  await http.delete(`/questions/${question.value.question_id}`)
  show(t('questionDetail.deleted'), 'success')
  router.push('/questions')
}

// ---- 发布/编辑完成后刷新 ----

function handleContentPublished(e) {
  const { mode, action, item } = e.detail || {}
  // 回答的发布 / 编辑由 AnswersList 自行刷新，这里只处理问题本身
  if (mode === 'question') {
    fetchQuestion()
    if (action === 'create' && item?.question_id) {
      router.push(`/questions/${item.question_id}`)
    }
  }
}

watch(() => route.params.question_id, fetchQuestion)
onMounted(() => {
  fetchQuestion()
  window.addEventListener('content:published', handleContentPublished)
})
onUnmounted(() => {
  window.removeEventListener('content:published', handleContentPublished)
})
</script>

<template>
  <v-container  :class="xs ? 'px-0' : 'px-4'" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-progress-linear v-if="loading && !question" indeterminate color="primary" class="mb-4" />

    <template v-if="question">
      <!-- 问题主体 -->
      <v-card  class="overflow-hidden mb-4">
        <!-- 应用栏：返回 / 标题 / 关注 -->
        <v-toolbar :title="question.title">
          <template v-slot:prepend>
            <v-btn icon variant="text" :title="$t('common.back')" @click="router.back()">
              <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
          </template>
          <template v-slot:append>
            <FollowButton
              type="question"
              :target-id="question.question_id"
              :following="isFollowing"
              @toggle-follow="toggleFollow"
            />
          </template>
        </v-toolbar>

        <v-card-text class="pa-4">
          <!-- 作者信息：头像 / 姓名 / 个性签名 / 发布时间 -->
          <v-list-item
            class="px-0"
            :to="creator?.user_id ? `/users/${creator.user_id}` : undefined"
          >
            <template v-slot:prepend>
              <v-avatar size="40" color="primary" variant="tonal">
                <v-img v-if="creator?.avatar" :src="creator.avatar" cover />
                <span v-else class="text-body-2 font-weight-bold text-primary">{{ creatorInitial }}</span>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-medium text-primary">
              {{ creator?.username }}
            </v-list-item-title>
            <v-list-item-subtitle v-if="creator?.headline" class="text-medium-emphasis">
              {{ creator.headline }}
            </v-list-item-subtitle>
            <template v-slot:append>
              <span class="text-body-2 text-medium-emphasis text-no-wrap">
                {{ $t('questionDetail.askedAt', { date: dateText(question.create_time) }) }}
              </span>
            </template>
          </v-list-item>

          <!-- 正文 -->
          <ProseRenderer
            :content="question.content_rendered"
            :empty-text="$t('questionDetail.emptyContent')"
            class="mt-4"
          />

          <!-- 话题标签（正文下方） -->
          <div v-if="question.topics?.length" class="d-flex flex-wrap ga-1 mt-4">
            <router-link
              v-for="topic in question.topics"
              :key="topic.topic_id"
              :to="`/topics/${topic.topic_id}`"
              class="text-decoration-none"
            >
              <v-chip size="small" variant="tonal" prepend-icon="mdi-tag-outline">
                {{ topic.name }}
              </v-chip>
            </router-link>
          </div>

        </v-card-text>

        <!-- 底部操作栏：评论在左，更多操作在右 -->
        <v-card-actions class="d-flex align-center px-4 py-2 border-t-thin">
          <CommentButton
            :count="commentCount"
            :tooltip="$t('commentDialog.title')"
            @click="openComments"
          />
          <v-spacer />
          <MoreActions
            type="question"
            :item="question"

            @edit="editQuestion"
            @delete="askDeleteQuestion"
          />
        </v-card-actions>
      </v-card>

      <!-- 回答列表（自取数据：排序 / 分页 / 编辑 / 删除） -->
      <AnswersList :question-id="question.question_id" />
    </template>

    <v-empty-state
      v-else-if="!loading"
      icon="mdi-help-circle-outline"
      :title="$t('questionDetail.notFound')"
      :text="$t('questionDetail.notFoundText')"
    />
  </v-container>
</template>

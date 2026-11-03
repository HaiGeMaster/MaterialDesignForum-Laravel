<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { storeToRefs } from 'pinia'
import CommentSection from '../components/CommentSection.vue'
import FollowButton from '../components/FollowButton.vue'
import MoreActions from '../components/MoreActions.vue'
import ProseRenderer from '../components/ProseRenderer.vue'
import VoteButton from '../components/VoteButton.vue'
import { useAuthStore } from '../stores/auth'
import { useDeleteDialogStore } from '../stores/deleteDialog'
import { useEditorWindowStore } from '../stores/editorWindow'
import { useArticleDetailViewStore } from '../stores/views/articleDetail'
import { useFeedback } from '../composables/useFeedback'
import { useLocalizedDateTime } from '../composables/useLocalizedDateTime'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const route = useRoute()
const router = useRouter()
const { show } = useFeedback()
const { t } = useI18n()
const formatDateTime = useLocalizedDateTime()
const authStore = useAuthStore()
const deleteDialog = useDeleteDialogStore()
const editorWindow = useEditorWindowStore()
const articleStore = useArticleDetailViewStore()
const { article, loading } = storeToRefs(articleStore)

// 底部评论输入框发布成功后递增，强制评论区重新挂载加载最新数据
const commentRefreshKey = ref(0)

const creator = computed(() => article.value?.creator || null)
const creatorInitial = computed(() => creator.value?.username?.slice(0, 1).toUpperCase() || '?')

const isFollowing = computed(() => Boolean(article.value?.is_following))

const isArticleOwner = computed(
  () => authStore.user?.user_id && String(authStore.user.user_id) === String(article.value?.user_id),
)

async function fetchArticle() {
  try {
    await articleStore.fetchArticle(route.params.article_id)
  } catch (error) {
    show(error.message, 'error')
  }
}

function toggleFollow(payload) {
  articleStore.updateFollow(payload)
}

// ---- 编辑 ----

function editArticle() {
  editorWindow.openEditor({
    mode: 'article',
    action: 'edit',
    targetId: article.value.article_id,
    title: article.value.title,
    content: article.value.content_markdown,
    topics: article.value.topics || [],
  })
}

// ---- 删除 ----

function askDelete() {
  deleteDialog.open({
    title: t('articleDetail.deleteTitle'),
    text: t('articleDetail.deleteConfirm', { title: article.value?.title }),
    onConfirm: confirmDelete,
  })
}

async function confirmDelete() {
  await articleStore.deleteArticle()
  show(t('articleDetail.deleted'), 'success')
  router.push('/articles')
}

// ---- 发布/编辑完成后刷新 ----

function handleContentPublished(e) {
  const { mode, action, item } = e.detail || {}
  if (mode !== 'article') return
  if (action === 'create' && item?.article_id) {
    router.push(`/articles/${item.article_id}`)
    return
  }
  fetchArticle()
}

// 底部评论输入框（CommentSheet）发布成功后，刷新本页评论区
function handleCommentAdded(e) {
  const { commentableType, commentableId } = e.detail || {}
  if (commentableType !== 'article') return
  if (commentableId !== undefined && String(commentableId) !== String(article.value?.article_id)) return
  commentRefreshKey.value += 1
}

watch(() => route.params.article_id, fetchArticle)
onMounted(() => {
  fetchArticle()
  window.addEventListener('content:published', handleContentPublished)
  window.addEventListener('comment:added', handleCommentAdded)
})
onUnmounted(() => {
  window.removeEventListener('content:published', handleContentPublished)
  window.removeEventListener('comment:added', handleCommentAdded)
})
</script>

<template>
  <v-container :class="xs ? 'px-0' : 'px-4'"
    :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-progress-linear v-if="loading && !article" indeterminate color="primary" class="mb-4" />

    <template v-if="article">
      <v-card rounded="lg" class="overflow-hidden">
        <!-- 应用栏：返回 / 标题 / 关注 -->
        <v-toolbar :title="article.title">
          <template v-slot:prepend>
            <v-btn icon variant="text" @click="router.back()">
              <v-icon>mdi-arrow-left</v-icon>
              <v-tooltip activator="parent" location="right">
                {{ $t('common.back') }}
              </v-tooltip>
            </v-btn>
          </template>
          <!-- <v-toolbar-title class="font-weight-bold">
            <span class="d-block text-truncate">{{ article.title }}</span>
          </v-toolbar-title> -->
          <template v-slot:append>
            <FollowButton type="article" :target-id="article.article_id" :following="isFollowing"
              @toggle-follow="toggleFollow" size="default" variant="text" />
          </template>
        </v-toolbar>

        <v-card-text class="pa-4 pa-md-6">
          <!-- 作者信息：头像 / 姓名 / 个性签名 / 发布时间 -->
          <v-list-item 
            class="px-2" rounded="lg"
             :to="creator?.user_id ? `/users/${creator.user_id}` : undefined">
            <template v-slot:prepend>
              <v-avatar size="40" :variant="creator?.avatar?'': 'tonal'">
                <v-img v-if="creator?.avatar" :src="creator.avatar" cover />
                <span v-else class="text-body-2 font-weight-bold text-primary">{{ creatorInitial }}</span>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-medium ">
              {{ creator?.username }}
            </v-list-item-title>
            <v-list-item-subtitle v-if="creator?.headline" class="text-medium-emphasis">
              {{ creator.headline }}
            </v-list-item-subtitle>
            <template v-slot:append>
              <span class="text-body-2 text-medium-emphasis text-no-wrap">
                {{ $t('articleDetail.publishedAt', { date: formatDateTime(article.create_time) }) }}
              </span>
            </template>
          </v-list-item>

          <!-- 正文 -->
          <ProseRenderer :content="article.content_rendered" :empty-text="$t('articleDetail.emptyContent')"
            class="mt-4" />

          <!-- 话题标签（正文下方） -->
          <div v-if="article.topics?.length" class="d-flex flex-wrap ga-1 mt-4">
            <router-link v-for="topic in article.topics" :key="topic.topic_id" :to="`/topics/${topic.topic_id}`"
              class="text-decoration-none">
              <v-chip size="small" variant="tonal">
                <v-avatar start>
                  <v-img :src="topic.cover">
                  </v-img>
                </v-avatar>
                {{ topic.name }}
              </v-chip>
            </router-link>
          </div>

        </v-card-text>

        <!-- 底部操作栏：点赞在左，更多操作在右 -->
        <v-card-actions class="d-flex align-center px-4 py-2 border-t-thin">
          <VoteButton type="article" :item-id="article.article_id" :up-count="Number(article.vote_up_count || 0)"
            :down-count="Number(article.vote_down_count || 0)" :my-vote="article.my_vote || null" />
          <v-spacer />
          <MoreActions type="article" :item="article" @edit="editArticle" @delete="askDelete" />
        </v-card-actions>
      </v-card>

      <!-- 评论区 -->
      <CommentSection :key="commentRefreshKey" commentable-type="article" :commentable-id="article.article_id"
        class="mt-4" />
    </template>

    <v-empty-state v-else-if="!loading" icon="mdi-file-document" :title="$t('articleDetail.notFound')"
      :text="$t('articleDetail.notFoundText')" />
  </v-container>
</template>

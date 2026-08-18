<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useFeedback } from '../composables/useFeedback'
import ContentListPanel from '../components/ContentListPanel.vue'
import DeleteDialog from '../components/DeleteDialog.vue'
import FollowButton from '../components/FollowButton.vue'
import MoreActions from '../components/MoreActions.vue'
import TopicDialog from '../components/TopicDialog.vue'

const route = useRoute()
const router = useRouter()
const { show } = useFeedback()
const { t } = useI18n()

const topic = ref(null)
const creator = ref(null)
const articles = ref([])

// 点击列表项进入对应详情页
function handleOpen(item) {
  if (item.question_id) {
    router.push(`/questions/${item.question_id}`)
  } else if (item.article_id) {
    router.push(`/articles/${item.article_id}`)
  }
}
const questions = ref([])
const loading = ref(false)
const activeTab = ref('articles')
const deleteDialog = ref(false)
const deleting = ref(false)
const editDialog = ref(false)

const gradientPalette = [
  'linear-gradient(135deg, #424242 0%, #212121 100%)',
  'linear-gradient(135deg, #616161 0%, #37474F 100%)',
  'linear-gradient(135deg, #757575 0%, #424242 100%)',
  'linear-gradient(135deg, #546E7A 0%, #263238 100%)',
  'linear-gradient(135deg, #9E9E9E 0%, #616161 100%)',
  'linear-gradient(135deg, #455A64 0%, #37474F 100%)',
]

const gradient = computed(() => {
  if (!topic.value) return gradientPalette[0]
  return gradientPalette[topic.value.topic_id % gradientPalette.length]
})

// 关注状态由服务器随详情数据返回（topic.is_following）
const isFollowing = computed(() => Boolean(topic.value?.is_following))

async function fetchTopic() {
  loading.value = true
  try {
    const { data } = await http.get(`/topics/${route.params.topic_id}`)
    topic.value = data.topic
    creator.value = data.creator || null
    articles.value = data.articles || []
    questions.value = data.questions || []
  } catch (error) {
    show(error.message, 'error')
  } finally {
    loading.value = false
  }
}

function toggleFollow({ following, followerCount }) {
  if (following !== undefined && topic.value) {
    topic.value.is_following = following
  }
  if (followerCount !== undefined && topic.value) {
    topic.value.follower_count = followerCount
  }
}

function goCreator() {
  if (creator.value?.user_id) {
    router.push(`/users/${creator.value.user_id}`)
  }
}

const creatorInitial = computed(() =>
  creator.value?.username?.slice(0, 1).toUpperCase() || '?'
)

// 更多操作：编辑 / 删除（举报由 MoreActions 内置对话框处理）
function handleEdit() {
  editDialog.value = true
}

// 话题编辑成功：刷新详情数据
function onTopicSaved() {
  editDialog.value = false
  fetchTopic()
}

function openDeleteDialog() {
  deleteDialog.value = true
}

async function confirmDelete() {
  if (!topic.value) return
  deleting.value = true
  try {
    await http.delete(`/topics/${topic.value.topic_id}`)
    show(t('topicDetail.deleted'), 'success')
    router.push('/topics')
  } catch (error) {
    show(error.message, 'error')
  } finally {
    deleting.value = false
    deleteDialog.value = false
  }
}

// 路由参数变化时重新加载（如同页跳转到其他话题）
watch(() => route.params.topic_id, fetchTopic)
onMounted(fetchTopic)
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <v-btn size="small" variant="tonal" prepend-icon="mdi-arrow-left" to="/topics">
        {{ $t('topicDetail.backToList') }}
      </v-btn>
    </div>

    <v-progress-linear v-if="loading && !topic" indeterminate color="primary" class="mb-4" />

    <template v-if="topic">
      <!-- 话题信息卡片 -->
      <v-card  class="overflow-hidden mb-4">
        <!-- 左右布局：左侧封面 + 右侧信息 -->
        <div class="topic-header">
          <!-- 封面（左侧较小） -->
          <div class="topic-banner">
            <v-img v-if="topic.cover" :src="topic.cover" cover height="100%" />
            <div v-else class="d-flex align-center justify-center fill-height" :style="{ background: gradient }">
              <v-icon icon="mdi-tag" size="56" color="white" />
            </div>
          </div>

          <div class="pa-4 grow">
          <div class="text-h5 font-weight-bold">{{ topic.name }}</div>
          <div v-if="topic.description" class="text-body-2 text-medium-emphasis mt-1">
            {{ topic.description }}
          </div>

          <!-- 创建者 -->
          <div v-if="creator" class="d-flex align-center ga-2 mt-3">
            <v-avatar
              size="28"
              color="primary"
              variant="tonal"
              class="cursor-pointer"
              @click="goCreator"
            >
              <v-img v-if="creator.avatar" :src="creator.avatar" cover />
              <span v-else class="text-body-2 font-weight-bold text-primary">{{ creatorInitial }}</span>
            </v-avatar>
            <span class="text-body-2 text-medium-emphasis">{{ $t('topicDetail.createdBy') }}</span>
            <span class="text-body-2 font-weight-medium text-primary cursor-pointer" @click="goCreator">
              {{ creator.username }}
            </span>
            <span class="text-body-2 text-medium-emphasis">{{ $t('topicDetail.created') }}</span>
          </div>

          <!-- 底部：关注按钮 + 统计 -->
          <div class="d-flex align-center flex-wrap ga-2 mt-3">
            <FollowButton
              type="topic"
              :target-id="topic.topic_id"
              :following="isFollowing"
              @toggle-follow="toggleFollow"
            />

            <v-btn rounded variant="tonal" prepend-icon="mdi-file-document-outline" size="small">
              {{ $t('topicDetail.articleCount', { count: topic.article_count ?? articles.length }) }}
            </v-btn>
            <v-btn rounded variant="tonal" prepend-icon="mdi-help-circle-outline" size="small">
              {{ $t('topicDetail.questionCount', { count: topic.question_count ?? questions.length }) }}
            </v-btn>

            <!-- 更多操作（最右侧）：复制链接 / 分享 / 查看关注者 / 举报 / 编辑 / 删除 -->
            <div class="ml-auto">
              <MoreActions
                type="topic"
                :item="topic"
                location="bottom end"
                @edit="handleEdit"
                @delete="openDeleteDialog"
              />
            </div>
          </div>
          </div>
        </div>
      </v-card>

      <!-- 话题下的文章与问题 -->
      <v-card >
        <v-tabs v-model="activeTab" color="primary" grow>
          <v-tab value="articles">
            <v-icon start icon="mdi-file-document" />
            {{ $t('topicDetail.articleTab', { count: articles.length }) }}
          </v-tab>
          <v-tab value="questions">
            <v-icon start icon="mdi-help-circle" />
            {{ $t('topicDetail.questionTab', { count: questions.length }) }}
          </v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
          <v-window-item value="articles">
            <ContentListPanel
              :title="$t('nav.articles')"
              icon="mdi-file-document"
              item-type="article"
              :items="articles"
              :loading="loading"
              @open="handleOpen"
            />
          </v-window-item>
          <v-window-item value="questions">
            <ContentListPanel
              :title="$t('nav.questions')"
              icon="mdi-help-circle"
              item-type="question"
              :items="questions"
              :loading="loading"
              @open="handleOpen"
            />
          </v-window-item>
        </v-window>
      </v-card>
    </template>

    <v-empty-state
      v-else-if="!loading"
      icon="mdi-tag-off"
      :title="$t('topicDetail.notFound')"
      :text="$t('topicDetail.notFoundText')"
    />

    <!-- 删除话题确认 -->
    <DeleteDialog
      v-model="deleteDialog"
      :title="$t('topicDetail.deleteTitle')"
      :text="$t('topicDetail.deleteConfirm', { name: topic?.name })"
      :loading="deleting"
      @confirm="confirmDelete"
    />

    <!-- 编辑话题对话框（MoreActions 的编辑操作打开） -->
    <TopicDialog v-model="editDialog" :topic="topic" @submit="onTopicSaved" />
  </div>
</template>

<style scoped>
/* 左右布局：封面在左，信息在右 */
.topic-header {
  display: flex;
  align-items: stretch;
}

.topic-banner {
  width: 220px;
  flex-shrink: 0;
}

@media (max-width: 600px) {
  .topic-header {
    flex-direction: column;
  }

  .topic-banner {
    width: 100%;
    height: 150px;
  }
}
</style>

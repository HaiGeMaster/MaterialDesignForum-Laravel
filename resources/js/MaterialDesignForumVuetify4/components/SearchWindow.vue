<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import http from '../api'
import FloatingWindow from './FloatingWindow.vue'
import PersonCard from './PersonCard.vue'
import TopicCard from './TopicCard.vue'
import ContentListItem from './ContentListItem.vue'
import AnswerItem from './AnswerItem.vue'
import CommentItem from './CommentItem.vue'
import ReplyItem from './ReplyItem.vue'
import { useSearchWindowStore } from '../stores/searchWindow'

const props = defineProps({
  /** 窗口初始宽度（px） */
  width: { type: Number, default: 420 },
  /** 窗口初始高度（px） */
  height: { type: Number, default: 520 },
  /** 窗口初始横坐标（px，不传则贴右下角、距边缘 16px） */
  x: { type: Number, default: undefined },
  /** 窗口初始纵坐标（px，不传则贴右下角、距边缘 16px） */
  y: { type: Number, default: undefined },
})

const { t } = useI18n()
const router = useRouter()
const store = useSearchWindowStore()

const searchTabs = [
  { value: 'all', label: t('search.all') },
  { value: 'users', label: t('search.users') },
  { value: 'topics', label: t('search.topics') },
  { value: 'questions', label: t('search.questions') },
  { value: 'articles', label: t('search.articles') },
  { value: 'answers', label: t('search.answers') },
  { value: 'comments', label: t('search.comments') },
  { value: 'replies', label: t('search.replies') },
]

const emptyResults = Object.fromEntries(searchTabs.map((tab) => [tab.value, []]))
const query = ref('')
const activeTab = ref('all')
const results = ref({ ...emptyResults })
const loading = ref(false)
const requestId = ref(0)

function normalizeResults(payload = {}) {
  const next = { ...emptyResults }
  for (const tab of searchTabs) {
    next[tab.value] = Array.isArray(payload[tab.value]) ? payload[tab.value] : []
  }
  return next
}

function flattenAllResults(data) {
  const list = []
  for (const key of ['users', 'topics', 'questions', 'articles', 'answers', 'comments', 'replies']) {
    for (const item of data[key] || []) {
      list.push({ ...item, __type: key })
    }
  }
  return list
}

const currentResults = computed(() => {
  if (activeTab.value === 'all') {
    return flattenAllResults(results.value)
  }
  return results.value[activeTab.value] || []
})

const emptyText = computed(() => {
  if (!query.value.trim()) return t('search.emptyPrompt')
  return t('search.emptyResults')
})

let timer = null
watch(query, (value) => {
  const keyword = value.trim()

  if (!keyword) {
    results.value = { ...emptyResults }
    return
  }

  if (timer) clearTimeout(timer)
  timer = setTimeout(() => loadSearch(keyword), 300)
})

watch(activeTab, () => {
  const keyword = query.value.trim()
  if (keyword) {
    loadSearch(keyword)
  }
})

async function loadSearch(keyword) {
  const currentRequest = ++requestId.value
  loading.value = true

  try {
    const { data } = await http.get('/search', {
      params: {
        q: keyword,
        type: activeTab.value,
      },
    })

    if (currentRequest !== requestId.value) return

    if (activeTab.value === 'all') {
      results.value = normalizeResults(data)
    } else {
      results.value = normalizeResults({
        ...emptyResults,
        [activeTab.value]: Array.isArray(data[activeTab.value]) ? data[activeTab.value] : [],
      })
    }
  } catch (error) {
    if (currentRequest === requestId.value) {
      results.value = { ...emptyResults }
    }
  } finally {
    if (currentRequest === requestId.value) {
      loading.value = false
    }
  }
}

function toggleFollow(item, payload) {
  if (!item) return
  const next = payload?.following ?? false
  const count = payload?.followerCount
  item.is_following = next
  if (count !== undefined) item.follower_count = count
}

function getResultType(item) {
  if (!item) return null
  if (item.__type) return item.__type
  if (item.user_id !== undefined && item.username !== undefined) return 'users'
  if (item.topic_id !== undefined) return 'topics'
  if (item.question_id !== undefined) return 'questions'
  if (item.article_id !== undefined) return 'articles'
  if (item.answer_id !== undefined) return 'answers'
  if (item.comment_id !== undefined) return 'comments'
  if (item.reply_id !== undefined) return 'replies'
  return null
}

function openResult(item) {
  if (!item) return

  const kind = getResultType(item)
  let target = null

  if (kind === 'users') {
    target = `/users/${item.user_id}`
  } else if (kind === 'topics') {
    target = `/topics/${item.topic_id}`
  } else if (kind === 'questions') {
    target = `/questions/${item.question_id}`
  } else if (kind === 'articles') {
    target = `/articles/${item.article_id}`
  } else if (kind === 'answers') {
    if (item.question_id && item.answer_id) {
      target = `/questions/${item.question_id}/answers/${item.answer_id}`
    } else if (item.answer_id) {
      target = `/questions/${item.answer_id}`
    }
  } else if (kind === 'comments') {
    const routeType = item.route_type || item.commentable_type
    const routeId = item.route_id || item.commentable_id
    const questionId = item.question_id || item.route_question_id

    if (routeType === 'article' && routeId) {
      target = `/articles/${routeId}`
    } else if (routeType === 'question' && routeId) {
      target = `/questions/${routeId}`
    } else if (routeType === 'answer' && questionId && routeId) {
      target = `/questions/${questionId}/answers/${routeId}`
    }
  } else if (kind === 'replies') {
    const routeType = item.route_type || item.commentable_type
    const routeId = item.route_id || item.commentable_id || item.replyable_comment_id
    const questionId = item.question_id || item.route_question_id

    if (routeType === 'article' && routeId) {
      target = `/articles/${routeId}`
    } else if (routeType === 'question' && routeId) {
      target = `/questions/${routeId}`
    } else if (routeType === 'answer' && questionId && routeId) {
      target = `/questions/${questionId}/answers/${routeId}`
    }
  }

  if (!target) return

  store.close()
  router.push(target)
}

function closeWindow() {
  store.close()
}
</script>

<template>
  <FloatingWindow
    v-if="store.isOpen"
    appbar-density="compact"
    :title="t('common.search')"
    icon="mdi-magnify"
    :width="width"
    :height="height"
    :x="x"
    :y="y"
    @close="closeWindow"
  >
    <!-- <div class="search-window">才 -->
      <v-text-field
        v-model="query"
        color="primary"
        append-inner-icon="mdi-magnify"
        :label="t('common.search')"
        hide-details
        clearable
        @keydown.enter.prevent
      />

      <v-tabs
        v-model="activeTab"
        color="primary"
        align-tabs="center"
        show-arrows
        class="search-window__tabs"
      >
        <v-tab v-for="tab in searchTabs" :key="tab.value" :value="tab.value">
          {{ tab.label }}
        </v-tab>
      </v-tabs>

      <div class="search-window__panel">
        <div v-if="loading" class="d-flex justify-center align-center py-8">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <template v-else-if="currentResults.length">
          <div v-if="activeTab === 'users' || activeTab === 'all' && currentResults.some((item) => item.__type === 'users')" class="search-window__section">
            <v-row dense>
              <v-col v-for="item in (activeTab === 'all' ? currentResults.filter((i) => i.__type === 'users') : currentResults)" :key="item.user_id || item.id" cols="12">
                <PersonCard
                  :person="item"
                  :following="Boolean(item.is_following)"
                  @click="openResult"
                  @toggle-follow="toggleFollow"
                />
              </v-col>
            </v-row>
          </div>

          <div v-else-if="activeTab === 'topics' || activeTab === 'all' && currentResults.some((item) => item.__type === 'topics')" class="search-window__section">
            <v-row dense>
              <v-col v-for="item in (activeTab === 'all' ? currentResults.filter((i) => i.__type === 'topics') : currentResults)" :key="item.topic_id || item.id" cols="12">
                <TopicCard
                  :topic="item"
                  :following="Boolean(item.is_following)"
                  @click="openResult(item)"
                  @toggle-follow="toggleFollow"
                />
              </v-col>
            </v-row>
          </div>

          <div v-else-if="activeTab === 'questions' || activeTab === 'all' && currentResults.some((item) => item.__type === 'questions')" class="search-window__section">
            <v-list density="compact" nav>
              <ContentListItem
                v-for="item in (activeTab === 'all' ? currentResults.filter((i) => i.__type === 'questions') : currentResults)"
                :key="item.question_id || item.id"
                :item="item"
                item-type="question"
                @open="openResult"
              />
            </v-list>
          </div>

          <div v-else-if="activeTab === 'articles' || activeTab === 'all' && currentResults.some((item) => item.__type === 'articles')" class="search-window__section">
            <v-list density="compact" nav>
              <ContentListItem
                v-for="item in (activeTab === 'all' ? currentResults.filter((i) => i.__type === 'articles') : currentResults)"
                :key="item.article_id || item.id"
                :item="item"
                item-type="article"
                @open="openResult"
              />
            </v-list>
          </div>

          <div v-else-if="activeTab === 'answers' || activeTab === 'all' && currentResults.some((item) => item.__type === 'answers')" class="search-window__section">
            <v-list density="compact" nav>
              <AnswerItem
                v-for="item in (activeTab === 'all' ? currentResults.filter((i) => i.__type === 'answers') : currentResults)"
                :key="item.answer_id || item.id"
                :answer="item"
                :disable-action="true"
                @open="openResult"
              />
            </v-list>
          </div>

          <div v-else-if="activeTab === 'comments' || activeTab === 'all' && currentResults.some((item) => item.__type === 'comments')" class="search-window__section">
            <v-list density="compact" nav>
              <CommentItem
                v-for="item in (activeTab === 'all' ? currentResults.filter((i) => i.__type === 'comments') : currentResults)"
                :key="item.comment_id || item.id"
                :comment="item"
                :disable-action="true"
                @open="openResult"
              />
            </v-list>
          </div>

          <div v-else-if="activeTab === 'replies' || activeTab === 'all' && currentResults.some((item) => item.__type === 'replies')" class="search-window__section">
            <v-list density="compact" nav>
              <ReplyItem
                v-for="item in (activeTab === 'all' ? currentResults.filter((i) => i.__type === 'replies') : currentResults)"
                :key="item.reply_id || item.id"
                :reply="item"
                :comment-id="item.comment_id || item.replyable_comment_id || 0"
                :disable-action="true"
                @open="openResult"
              />
            </v-list>
          </div>
        </template>

        <v-empty-state
          v-else
          icon="mdi-magnify"
          :title="emptyText"
          :text="query.trim() ? t('search.emptyHint') : t('search.emptyDefault')"
        />
      </div>
    <!-- </div> -->
  </FloatingWindow>
</template>

<style scoped>
.search-window {
  display: flex;
  flex-direction: column;
  gap: 8px;
  height: 100%;
  min-height: 0;
  padding: 10px 12px 12px;
  box-sizing: border-box;
}

.search-window__tabs {
  flex: none;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.search-window__panel {
  flex: 1;
  min-height: 0;
  overflow: auto;
  padding-bottom: 8px;

  height: 100%;
  max-height: fit-content;
}

.search-window__section {
  display: block;

  /* height: 100%; */
}

@media (max-width: 600px) {
  .search-window {
    padding: 8px;
    gap: 6px;
  }

  .search-window__tabs :deep(.v-tab) {
    min-width: auto;
    padding-inline: 10px;
    font-size: 0.78rem;
  }
}
</style>

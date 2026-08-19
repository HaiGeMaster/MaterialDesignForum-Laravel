<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'
import ContentListPanel from '../components/ContentListPanel.vue'
import TopicCarousel from '../components/TopicCarousel.vue'
import { useFeedback } from '../composables/useFeedback'

const { show } = useFeedback()
const { t } = useI18n()
const router = useRouter()

const questions = ref([])
const articles = ref([])
const loading = ref(true)

// 各面板数据：切片前 5 条
const hotQuestions = computed(() =>
  [...questions.value]
    .sort((a, b) => b.vote_count + b.answer_count - (a.vote_count + a.answer_count))
    .slice(0, 5)
)

const newQuestions = computed(() =>
  [...questions.value]
    .sort((a, b) => new Date(b.create_time) - new Date(a.create_time))
    .slice(0, 5)
)

const hotArticles = computed(() =>
  [...articles.value]
    .sort((a, b) => b.vote_count + b.follower_count - (a.vote_count + a.follower_count))
    .slice(0, 5)
)

const newArticles = computed(() =>
  [...articles.value]
    .sort((a, b) => new Date(b.create_time) - new Date(a.create_time))
    .slice(0, 5)
)

const panels = computed(() => [
  { title: t('home.hotQuestions'), icon: 'mdi-fire', to: '/questions', itemType: 'question', items: hotQuestions.value },
  { title: t('home.newQuestions'), icon: 'mdi-clock-fast', to: '/questions', itemType: 'question', items: newQuestions.value },
  { title: t('home.hotArticles'), icon: 'mdi-fire', to: '/articles', itemType: 'article', items: hotArticles.value },
  { title: t('home.newArticles'), icon: 'mdi-clock-fast', to: '/articles', itemType: 'article', items: newArticles.value },
])

// 关注状态回写到作者数据（与其它视图的回写模式一致）
function handleToggleFollow(item, { following, followerCount }) {
  const creator = item?.creator
  if (!creator) return
  creator.is_following = following
  if (followerCount !== undefined) {
    creator.follower_count = followerCount
  }
}

// 小卡片里的更多操作（私信 / 举报）暂未实现
function handleMore(item, action) {
  show(t('common.developing'), 'info')
}

// 点击列表项进入对应详情页
function handleOpen(item) {
  if (item.question_id) {
    router.push(`/questions/${item.question_id}`)
  } else if (item.article_id) {
    router.push(`/articles/${item.article_id}`)
  }
}

async function fetchData() {
  loading.value = true
  try {
    const [q, a] = await Promise.all([http.get('/questions'), http.get('/articles')])
    questions.value = q.data
    articles.value = a.data
  } catch (error) {
    // 首页加载失败不打断页面
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>

<template>
  <v-container   :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`,padding:'0'} : {}">
    <!-- 话题转轴 -->
    <div class="d-flex align-center mb-3 pa-4">
      <v-icon icon="mdi-tag-multiple" class="mr-2" />
      <span class="text-h6 font-weight-bold">{{ $t('home.hotTopics') }}</span>
      <v-spacer />
      <v-btn size="small" variant="text" to="/topics" append-icon="mdi-chevron-right">
        {{ $t('content.viewAll') }}
      </v-btn>
    </div>
    <TopicCarousel />

    <!-- 内容四栏：最多两列排布 -->
    <v-row class="mt-2 px-4" >
      <v-col v-for="panel in panels" :key="panel.title" cols="12" md="6" >
        <ContentListPanel
          :title="panel.title"
          :icon="panel.icon"
          :to="panel.to"
          :item-type="panel.itemType"
          :items="panel.items"
          :loading="loading"
          @toggle-follow="handleToggleFollow"
          @more="handleMore"
          @open="handleOpen"
        />
      </v-col>
    </v-row>
  </v-container>
</template>

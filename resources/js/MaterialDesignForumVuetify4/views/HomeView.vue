<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import ContentListPanel from '../components/ContentListPanel.vue'
import TopicCarousel from '../components/TopicCarousel.vue'
import { useFeedback } from '../composables/useFeedback'
import { useHomeViewStore } from '../stores/views/home'
import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const { show } = useFeedback()
const { t } = useI18n()
const router = useRouter()
const homeStore = useHomeViewStore()

const loading = computed(() => homeStore.loading)

const panels = computed(() => [
  { title: t('home.hotQuestions'), icon: 'mdi-fire', to: '/questions', itemType: 'question', items: homeStore.hotQuestions },
  { title: t('home.newQuestions'), icon: 'mdi-clock-fast', to: '/questions', itemType: 'question', items: homeStore.newQuestions },
  { title: t('home.hotArticles'), icon: 'mdi-fire', to: '/articles', itemType: 'article', items: homeStore.hotArticles },
  { title: t('home.newArticles'), icon: 'mdi-clock-fast', to: '/articles', itemType: 'article', items: homeStore.newArticles },
  { title: t('home.popularUsers'), icon: 'mdi-fire', to: '/users', itemType: 'user', items: homeStore.popularUsersTop },
  { title: t('home.newUsers'), icon: 'mdi-clock-fast', to: '/users', itemType: 'user', items: homeStore.newUsersTop },
])

// 关注状态回写到作者数据（与其它视图的回写模式一致）
function handleToggleFollow(item, payload) {
  homeStore.updateCreatorFollow(item, payload)
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
  } else if (item.user_id) {
    router.push(`/users/${item.user_id}`)
  }
}

onMounted(() => homeStore.ensureLoaded())
</script>

<template>
  <v-container   :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`,padding: `${xs?'0': '10px'}` } : {}" >
    <!-- style="overflow-x: hidden;" -->
    <!-- 话题转轴 -->
    <div class="d-flex align-center pa-4">
      <!-- <v-icon icon="mdi-tag-multiple" class="mr-2" /> -->
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

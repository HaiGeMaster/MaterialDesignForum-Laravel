<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useInfiniteList } from '../composables/useInfiniteList'
import InfiniteScroll from './InfiniteScroll.vue'
import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const props = defineProps({
    /** 用户 ID */
    userId: { type: [Number, String], required: true },
})

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

// 点击问题 / 文章 / 回答进入对应详情页
function goDetail(item, type) {
    if (type === 'question' && item.question_id) {
        router.push(`/questions/${item.question_id}`)
    } else if (type === 'article' && item.article_id) {
        router.push(`/articles/${item.article_id}`)
    } else if (type === 'answer' && item.question_id) {
        router.push(`/questions/${item.question_id}`)
    }
}

const TABS = ['questions', 'articles', 'answers']

// 从路由哈希解析 tab（如 #articles），无效值回退到 questions
function hashToTab(hash) {
    const value = String(hash || '').replace(/^#/, '')
    return TABS.includes(value) ? value : 'questions'
}

// tab 与路由哈希双向绑定（刷新 / 前进后退 / 分享链接可恢复）
const tab = ref(hashToTab(route.hash))

// 路由哈希变化 → 同步 tab 并懒加载（浏览器前进/后退、手动改地址）
watch(() => route.hash, (hash) => {
    const next = hashToTab(hash)
    if (next !== tab.value) {
        tab.value = next
        handleTabChange(next)
    }
})

// tab 切换 → 写回哈希（replace 避免产生多余历史记录）
watch(tab, (value) => {
    if (route.hash !== `#${value}`) {
        router.replace({ hash: `#${value}` })
    }
})

// 提问 / 文章 / 回答：无限滚动分页加载
const {
    list: questions,
    loading: questionsLoading,
    error: questionsError,
    finished: questionsFinished,
    load: loadQuestions,
    loadNext: loadQuestionsNext,
    retry: retryQuestions,
} = useInfiniteList(async (page) => {
    const { data } = await http.get(`/users/${props.userId}/questions`, { params: { page } })
    return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
})

const {
    list: articles,
    loading: articlesLoading,
    error: articlesError,
    finished: articlesFinished,
    load: loadArticles,
    loadNext: loadArticlesNext,
    retry: retryArticles,
} = useInfiniteList(async (page) => {
    const { data } = await http.get(`/users/${props.userId}/articles`, { params: { page } })
    return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
})

const {
    list: answers,
    loading: answersLoading,
    error: answersError,
    finished: answersFinished,
    load: loadAnswers,
    loadNext: loadAnswersNext,
    retry: retryAnswers,
} = useInfiniteList(async (page) => {
    const { data } = await http.get(`/users/${props.userId}/answers`, { params: { page } })
    return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
})

const loading = computed(
    () =>
        questionsLoading.value ||
        articlesLoading.value ||
        answersLoading.value,
)

// 每个 tab 是否已加载过（懒加载）
const loaded = ref({ questions: false, articles: false, answers: false })

// tab 切换时懒加载对应列表
async function handleTabChange(value) {
    if (loaded.value[value]) return
    loaded.value[value] = true
    await loadTab(value)
}

async function loadTab(name) {
    if (name === 'questions') return loadQuestions(true)
    if (name === 'articles') return loadArticles(true)
    if (name === 'answers') return loadAnswers(true)
}

// 用户切换时重置并重新加载（tab 依据当前哈希恢复）
watch(
    () => props.userId,
    async (userId) => {
        if (!userId) return
        loaded.value = { questions: false, articles: false, answers: false }
        tab.value = hashToTab(route.hash)
        await loadTab(tab.value)
    },
    { immediate: true },
)

// 元信息：问题显示回答数，文章显示评论数
function metaText(item, type) {
    const time = item.create_time ? String(item.create_time).slice(0, 10) : ''
    if (type === 'article') return t('content.commentsSuffix', { count: item.comment_count ?? 0, time })
    if (type === 'answer') return t('userContent.answeredAt', { time })
    return t('content.answersSuffix', { count: item.answer_count ?? 0, time })
}

// 回答没有标题，展示内容摘要
function answerTitle(item) {
    if (item.question_title) return t('userContent.answerPrefix', { title: item.question_title })
    const content = (item.content_markdown || item.content_rendered || '').trim()
    return content
        ? t('userContent.answerPrefix', { title: `${content.slice(0, 40)}...` })
        : t('userContent.answer')
}
</script>

<template>
    <v-card rounded="lg" class="overflow-hidden">
        <v-tabs v-model="tab" color="primary" :grow="xs" @update:model-value="handleTabChange">
            <v-tab value="questions">{{ $t('userContent.questions') }}</v-tab>
            <v-tab value="articles">{{ $t('userContent.articles') }}</v-tab>
            <v-tab value="answers">{{ $t('userContent.answers') }}</v-tab>
        </v-tabs>

        <v-progress-linear v-if="loading" indeterminate color="primary" />

        <v-window v-model="tab">
            <!-- 提问 -->
            <v-window-item value="questions">
                <v-list rounded="lg" style="padding:0;" v-if="questions.length" lines="two">
                    <v-list-item v-for="item in questions" :key="item.question_id" :title="item.title"
                        class="border-b-thin" @click="goDetail(item, 'question')">
                        <template #subtitle>
                            <span class="d-inline-flex align-center">
                                <v-icon icon="mdi-message-outline" size="x-small" class="mr-1" />
                                {{ metaText(item, 'question') }}
                            </span>
                        </template>
                    </v-list-item>
                </v-list>
                <InfiniteScroll
                    v-if="questions.length"
                    :loading="questionsLoading"
                    :error="questionsError"
                    :finished="questionsFinished"
                    @load="loadQuestionsNext"
                    @retry="retryQuestions"
                />
                <v-empty-state v-else-if="!questionsLoading" icon="mdi-forum"
                    :title="$t('userContent.emptyQuestionsTitle')" :text="$t('userContent.emptyQuestionsText')"
                    class="pa-4" />
            </v-window-item>

            <!-- 文章 -->
            <v-window-item value="articles">
                <v-list rounded="lg" style="padding:0;" v-if="articles.length" lines="two">
                    <v-list-item v-for="item in articles" :key="item.article_id" :title="item.title"
                        class="border-b-thin" @click="goDetail(item, 'article')">
                        <template #subtitle>
                            <span class="d-inline-flex align-center">
                                <v-icon icon="mdi-comment-outline" size="x-small" class="mr-1" />
                                {{ metaText(item, 'article') }}
                            </span>
                        </template>
                    </v-list-item>
                </v-list>
                <InfiniteScroll
                    v-if="articles.length"
                    :loading="articlesLoading"
                    :error="articlesError"
                    :finished="articlesFinished"
                    @load="loadArticlesNext"
                    @retry="retryArticles"
                />
                <v-empty-state v-else-if="!articlesLoading" icon="mdi-file-document"
                    :title="$t('userContent.emptyArticlesTitle')" :text="$t('userContent.emptyArticlesText')"
                    class="pa-4" />
            </v-window-item>

            <!-- 回答 -->
            <v-window-item value="answers">
                <v-list rounded="lg" style="padding:0;" v-if="answers.length" lines="two">
                    <v-list-item v-for="item in answers" :key="item.answer_id" :title="answerTitle(item)"
                        class="border-b-thin" @click="goDetail(item, 'answer')">
                        <template #subtitle>
                            <span class="d-inline-flex align-center">
                                <v-icon icon="mdi-comment-text-outline" size="x-small" class="mr-1" />
                                {{ metaText(item, 'answer') }}
                            </span>
                        </template>
                    </v-list-item>
                </v-list>
                <InfiniteScroll
                    v-if="answers.length"
                    :loading="answersLoading"
                    :error="answersError"
                    :finished="answersFinished"
                    @load="loadAnswersNext"
                    @retry="retryAnswers"
                />
                <v-empty-state v-else-if="!answersLoading" icon="mdi-message-reply"
                    :title="$t('userContent.emptyAnswersTitle')" :text="$t('userContent.emptyAnswersText')"
                    class="pa-4" />
            </v-window-item>
        </v-window>
    </v-card>
</template>

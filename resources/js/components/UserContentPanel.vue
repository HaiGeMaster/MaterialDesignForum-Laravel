<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'

const props = defineProps({
    /** 用户 ID */
    userId: { type: [Number, String], required: true },
})

const { show: feedback } = useFeedback()
const { t } = useI18n()
const authStore = useAuthStore()
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

// 媒体库仅本人可见：浏览他人主页时隐藏该标签
const isSelf = computed(() => {
    const me = authStore.user
    return Boolean(me) && String(me.user_id) === String(props.userId)
})

const tab = ref('questions')
const questions = ref([])
const articles = ref([])
const answers = ref([])
const media = ref([])
const loading = ref(false)

// 每个 tab 是否已加载过（懒加载）
const loaded = ref({ questions: false, articles: false, answers: false, media: false })

async function loadQuestions() {
    const { data } = await http.get(`/users/${props.userId}/questions`)
    questions.value = data
}

async function loadArticles() {
    const { data } = await http.get(`/users/${props.userId}/articles`)
    articles.value = data
}

async function loadAnswers() {
    const { data } = await http.get(`/users/${props.userId}/answers`)
    answers.value = data
}

async function loadMedia() {
    const { data } = await http.get(`/users/${props.userId}/media`)
    media.value = Array.isArray(data) ? data : []
}

// tab 切换时懒加载对应列表
async function handleTabChange(value) {
    if (loaded.value[value]) return
    await loadTab(value)
}

async function loadTab(name) {
    loading.value = true
    try {
        if (name === 'questions') await loadQuestions()
        else if (name === 'articles') await loadArticles()
        else if (name === 'answers') await loadAnswers()
        else if (name === 'media') await loadMedia()
        loaded.value[name] = true
    } catch (error) {
        feedback(error.message, 'error')
    } finally {
        loading.value = false
    }
}

// 用户切换时重置并重新加载
watch(
    () => props.userId,
    async (userId) => {
        if (!userId) return
        loaded.value = { questions: false, articles: false, answers: false, media: false }
        questions.value = []
        articles.value = []
        answers.value = []
        media.value = []
        tab.value = 'questions'
        await loadTab('questions')
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

// —— 媒体库 ——
const preview = ref(null)
const previewOpen = ref(false)

function openPreview(item) {
    preview.value = item
    previewOpen.value = true
}

function formatSize(bytes) {
    if (!bytes) return ''
    if (bytes < 1024) return `${bytes} B`
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function mediaDate(item) {
    return item.create_time ? String(item.create_time).slice(0, 10) : ''
}
</script>

<template>
    <v-card class="overflow-hidden">
        <v-tabs v-model="tab" color="primary" grow @update:model-value="handleTabChange">
            <v-tab value="questions">{{ $t('userContent.questions') }}</v-tab>
            <v-tab value="articles">{{ $t('userContent.articles') }}</v-tab>
            <v-tab value="answers">{{ $t('userContent.answers') }}</v-tab>
            <v-tab v-if="isSelf" value="media">
                <span class="d-inline-flex align-center">
                    {{ $t('userContent.media') }}
                    <v-tooltip :text="$t('userContent.mediaPrivate')" location="top">
                        <template #activator="{ props: tipProps }">
                            <v-icon v-bind="tipProps" size="small" class="ml-1">mdi-lock-outline</v-icon>
                        </template>
                    </v-tooltip>
                </span>
            </v-tab>
        </v-tabs>

        <v-progress-linear v-if="loading" indeterminate color="primary" />

        <v-window v-model="tab">
            <!-- 提问 -->
            <v-window-item value="questions">
                <v-list v-if="questions.length" lines="two">
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
                <v-empty-state v-else-if="!loading" icon="mdi-comment-question-outline"
                    :title="$t('userContent.emptyQuestionsTitle')" :text="$t('userContent.emptyQuestionsText')"
                    class="pa-4" />
            </v-window-item>

            <!-- 文章 -->
            <v-window-item value="articles">
                <v-list v-if="articles.length" lines="two">
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
                <v-empty-state v-else-if="!loading" icon="mdi-file-document-outline"
                    :title="$t('userContent.emptyArticlesTitle')" :text="$t('userContent.emptyArticlesText')"
                    class="pa-4" />
            </v-window-item>

            <!-- 回答 -->
            <v-window-item value="answers">
                <v-list v-if="answers.length" lines="two">
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
                <v-empty-state v-else-if="!loading" icon="mdi-message-processing-outline"
                    :title="$t('userContent.emptyAnswersTitle')" :text="$t('userContent.emptyAnswersText')"
                    class="pa-4" />
            </v-window-item>

            <!-- 媒体库（仅本人可见） -->
            <v-window-item v-if="isSelf" value="media">
                <div v-if="media.length" class="media-grid pa-4">
                    <v-hover v-for="item in media" :key="item.media_id" v-slot="{ isHovering, props }">
                        <v-card v-bind="props" class="media-card cursor-pointer" :elevation="isHovering ? 6 : 1"
                            @click="openPreview(item)">
                            <v-img v-if="item.type === 'image'" :src="item.url" height="110" cover
                                class="media-thumb" />
                            <div v-else class="media-thumb media-thumb-video">
                                <video :src="item.url" preload="metadata" muted playsinline />
                                <div class="media-play">
                                    <v-icon icon="mdi-play-circle" size="40" color="white" />
                                </div>
                            </div>
                            <v-card-text class="pa-2">
                                <div class="text-body-2 text-truncate" :title="item.filename">{{ item.filename }}</div>
                                <div class="text-caption text-medium-emphasis">
                                    {{ item.type === 'image' ? $t('userContent.image') : $t('userContent.video') }} · {{
                                    formatSize(item.size) }} · {{ mediaDate(item) }}
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-hover>
                </div>
                <v-empty-state v-else-if="!loading" icon="mdi-image-multiple-outline"
                    :title="$t('userContent.emptyMediaTitle')" :text="$t('userContent.emptyMediaText')" class="pa-4" />
            </v-window-item>
        </v-window>

        <!-- 媒体预览 -->
        <v-dialog v-model="previewOpen" max-width="880" scrollable>
            <v-card style="margin-top: 40px;">

                <v-toolbar>
                    <template #prepend>
                        <!-- <v-icon icon="mdi-image-outline" /> -->
                        <v-btn icon="mdi-image-outline"></v-btn>
                    </template>
                    <v-toolbar-title>
                        {{ preview?.filename }}
                    </v-toolbar-title>
                    <template #append>
                        <v-btn icon="mdi-close" variant="text"  @click="previewOpen = false" />
                    </template>
                </v-toolbar>

                <!-- <v-card-title>
                    <v-icon icon="mdi-image-outline" size="small" class="mr-2 text-medium-emphasis" />
                    <span class="text-truncate text-subtitle-1">{{ preview?.filename }}</span>
                    <v-spacer />
                    <v-btn icon="mdi-close" variant="text" size="small" @click="previewOpen = false" />
                </v-card-title> -->
                <v-card-text class="pa-0 bg-black">
                    <v-img v-if="preview?.type === 'image'" :src="preview.url" max-height="70vh" contain />
                    <video v-else :src="preview?.url" controls autoplay class="d-block w-100 mx-auto"
                        style="max-height: 70vh" />
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-card>
</template>

<style scoped>
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}

.media-card {
    width: 100%;
}

.media-thumb {
    position: relative;
    background: rgb(var(--v-theme-surface-variant));
}

.media-thumb-video {
    height: 110px;
    background: #000;
    overflow: hidden;
}

.media-thumb-video video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.media-play {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.25);
}
</style>

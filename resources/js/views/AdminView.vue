<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import AdminNewDataStats from '../components/AdminNewDataStats.vue'
import AdminServerInfoStats from '../components/AdminServerInfoStats.vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const { locale } = useI18n()
const loading = ref(true)
const hasError = ref(false)
const generatedAt = ref('')
const counts = ref({})
const recent = ref({})
const server = ref({})
const chartRanges = ref({})
const chartLoading = ref({})

const dashboardItems = computed(() => [
    { key: 'topic', label: 'topics', countKey: 'topic_count', permission: 'ability_admin_manage_topic', icon: 'mdi-book-variant', color: 'primary', chartColor: '#1867C0', areaColor: 'rgba(24, 103, 192, 0.22)', to: '/topics' },
    { key: 'question', label: 'questions', countKey: 'question_count', permission: 'ability_admin_manage_question', icon: 'mdi-forum', color: 'info', chartColor: '#2196F3', areaColor: 'rgba(33, 150, 243, 0.22)', to: '/questions' },
    { key: 'answer', label: 'answers', countKey: 'answer_count', permission: 'ability_admin_manage_answer', icon: 'mdi-message-reply', color: 'cyan', chartColor: '#00ACC1', areaColor: 'rgba(0, 172, 193, 0.22)', to: '/answers' },
    { key: 'article', label: 'articles', countKey: 'article_count', permission: 'ability_admin_manage_article', icon: 'mdi-file-document', color: 'success', chartColor: '#4CAF50', areaColor: 'rgba(76, 175, 80, 0.22)', to: '/articles' },
    { key: 'comment', label: 'comments', countKey: 'comment_count', permission: 'ability_admin_manage_comment', icon: 'mdi-message-reply-text', color: 'orange', chartColor: '#FB8C00', areaColor: 'rgba(251, 140, 0, 0.22)', to: '/comments' },
    { key: 'reply', label: 'replies', countKey: 'reply_count', permission: 'ability_admin_manage_reply', icon: 'mdi-reply-all', color: 'deep-purple', chartColor: '#673AB7', areaColor: 'rgba(103, 58, 183, 0.22)', to: '/replies' },
    { key: 'user_group', label: 'userGroups', countKey: 'user_group_count', permission: 'ability_admin_manage_user_group', icon: 'mdi-account-group', color: 'teal', chartColor: '#00897B', areaColor: 'rgba(0, 137, 123, 0.22)', to: '/user-groups' },
    { key: 'user', label: 'users', countKey: 'user_count', permission: 'ability_admin_manage_user', icon: 'mdi-account-multiple', color: 'blue-grey', chartColor: '#546E7A', areaColor: 'rgba(84, 110, 122, 0.22)', to: '/users' },
    { key: 'report', label: 'reports', countKey: 'report_count', permission: 'ability_admin_manage_report', icon: 'mdi-flag', color: 'error', chartColor: '#B00020', areaColor: 'rgba(176, 0, 32, 0.22)', to: '/reports' },
])

const visibleItems = computed(() => dashboardItems.value.filter((item) => {
    const group = authStore.user?.user_group || {}
    return Boolean(group.is_admin || group[item.permission])
}))

function formatCount(value) {
    return Number(value || 0).toLocaleString(locale.value)
}

function formatGeneratedAt() {
    return generatedAt.value
        ? new Intl.DateTimeFormat(locale.value, { dateStyle: 'short', timeStyle: 'short' }).format(new Date(generatedAt.value))
        : ''
}

async function fetchOverview(range = 'last_7_days') {
    loading.value = true
    hasError.value = false
    try {
        const { data } = await http.get('/admin/overview', { params: { range } })
        counts.value = data.counts || {}
        recent.value = data.recent || {}
        server.value = data.server || {}
        generatedAt.value = data.generated_at || ''
    } catch (error) {
        hasError.value = true
    } finally {
        loading.value = false
    }
}

function openModule(item) {
    window.location.assign(item.to)
}

async function updateChartRange(item, range) {
    chartRanges.value[item.key] = range
    chartLoading.value[item.key] = true
    try {
        const { data } = await http.get('/admin/overview', { params: { range } })
        recent.value = { ...recent.value, [item.key]: data.recent?.[item.key] || [] }
    } catch (error) {
        hasError.value = true
    } finally {
        chartLoading.value[item.key] = false
    }
}

onMounted(fetchOverview)
</script>

<template>
    <v-container class="admin-page py-6 px-4 px-md-8">
        <section class="admin-hero mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">MDForum / ADMIN</div>
                <h1 class="text-h4 text-md-h3 font-weight-bold mt-1">{{ $t('admin.title') }}</h1>
                <p class="text-body-1 text-medium-emphasis mt-2">{{ $t('admin.welcome', {
                    username:
                        authStore.user?.username }) }}</p>
                <p class="text-body-2 text-medium-emphasis mt-1">{{ $t('admin.subtitle') }}</p>
            </div>
            <v-btn variant="tonal" prepend-icon="mdi-refresh" :loading="loading" @click="fetchOverview()">{{
                $t('admin.refresh') }}</v-btn>
        </section>

        <v-alert v-if="hasError" type="warning" variant="tonal" class="mb-6" closable>{{ $t('admin.loadFailed')
            }}</v-alert>

        <div class="section-heading">
            <h2 class="text-h6 font-weight-bold">{{ $t('admin.overview') }}</h2>
            <span v-if="generatedAt" class="text-caption text-medium-emphasis">{{ $t('admin.lastUpdated', {
                time:
                formatGeneratedAt() }) }}</span>
        </div>

        <v-row class="mb-6">
            <v-col v-for="item in visibleItems" :key="item.key" cols="12" sm="6" md="4" lg="3">
                <v-card class="count-card h-100" variant="tonal" :color="item.color" @click="openModule(item)">
                    <v-card-text class="d-flex align-center pa-5">
                        <v-avatar :color="item.chartColor" size="46"><v-icon :icon="item.icon" /></v-avatar>
                        <div class="ml-4">
                            <div class="text-body-2">{{ $t(`admin.${item.label}`) }}</div>
                            <v-skeleton-loader v-if="loading" type="text" width="74" class="mt-2" />
                            <div v-else class="text-h4 font-weight-bold mt-1">{{ formatCount(counts[item.countKey]) }}
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <div class="section-heading">
            <h2 class="text-h6 font-weight-bold">{{ $t('admin.recentActivity') }}</h2>
            <span class="text-caption text-medium-emphasis">{{ $t('admin.chartHint') }}</span>
        </div>

        <v-row class="mb-6">
            <v-col cols="12" md="6">
                <AdminServerInfoStats :data="server" :loading="loading" />
            </v-col>
            <v-col v-for="item in visibleItems" :key="`chart-${item.key}`" cols="12" md="6">
                <AdminNewDataStats :item="item" :data="recent[item.key]" :range="chartRanges[item.key] || 'last_7_days'"
                    :loading="loading || chartLoading[item.key]"
                    @update:range="(range) => updateChartRange(item, range)" @open="openModule" />
            </v-col>
        </v-row>


    </v-container>
</template>

<style scoped>
.admin-page {
    min-height: 100%;
    width: 1080px;
}

.admin-hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    padding: 28px;
    border: 1px solid rgba(var(--v-theme-primary), 0.16);
    border-radius: 16px;
    background: linear-gradient(120deg, rgba(var(--v-theme-primary), 0.1), rgba(var(--v-theme-secondary), 0.06));
}

.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}

.count-card,
.panel-card {
    border-radius: 12px;
}

@media (max-width: 600px) {
    .admin-hero {
        align-items: stretch;
        flex-direction: column;
        padding: 22px;
    }

    .section-heading {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

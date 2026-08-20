<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import { useDeleteDialogStore } from '../stores/deleteDialog'
import { useCommentReplyEditStore } from '../stores/commentReplyEditDialog'
import { useEditorWindowStore } from '../stores/editorWindow'
import { useProfileEditStore } from '../stores/profileEditDialog'
import http from '../api'
import TopicDialog from '../components/TopicDialog.vue'
import UserGroupDialog from '../components/UserGroupDialog.vue'
import ReportHandleDialog from '../components/ReportHandleDialog.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { show: feedback } = useFeedback()
const { t, locale } = useI18n()
const deleteDialog = useDeleteDialogStore()
const commentReplyEdit = useCommentReplyEditStore()
const editorWindow = useEditorWindowStore()
const profileEdit = useProfileEditStore()

// ================== 模块元信息 ==================
const MODULE_META = {
  topics: {
    titleKey: 'admin.manageTopics',
    icon: 'mdi-book-variant',
    apiPath: '/admin/modules/topics',
    idKey: 'topic_id',
    canView: (g) => g.is_admin || g.ability_admin_manage_topic,
    itemTo: (row) => `/topics/${row.topic_id}`,
  },
  questions: {
    titleKey: 'admin.manageQuestions',
    icon: 'mdi-forum',
    apiPath: '/admin/modules/questions',
    idKey: 'question_id',
    canView: (g) => g.is_admin || g.ability_admin_manage_question,
    itemTo: (row) => `/questions/${row.question_id}`,
  },
  answers: {
    titleKey: 'admin.manageAnswers',
    icon: 'mdi-message-reply',
    apiPath: '/admin/modules/answers',
    idKey: 'answer_id',
    canView: (g) => g.is_admin || g.ability_admin_manage_answer,
    itemTo: (row) => `/questions/${row.question_id}`,
  },
  articles: {
    titleKey: 'admin.manageArticles',
    icon: 'mdi-file-document',
    apiPath: '/admin/modules/articles',
    idKey: 'article_id',
    canView: (g) => g.is_admin || g.ability_admin_manage_article,
    itemTo: (row) => `/articles/${row.article_id}`,
  },
  comments: {
    titleKey: 'admin.manageComments',
    icon: 'mdi-message-reply-text',
    apiPath: '/admin/modules/comments',
    idKey: 'comment_id',
    canView: (g) => g.is_admin || g.ability_admin_manage_comment,
    itemTo: () => null,
  },
  replies: {
    titleKey: 'admin.manageReplies',
    icon: 'mdi-reply-all',
    apiPath: '/admin/modules/replies',
    idKey: 'reply_id',
    canView: (g) => g.is_admin || g.ability_admin_manage_reply,
    itemTo: () => null,
  },
  'user-groups': {
    titleKey: 'admin.manageUserGroups',
    icon: 'mdi-account-group',
    apiPath: '/admin/modules/user-groups',
    idKey: 'user_group_id',
    canView: (g) => g.is_admin || g.ability_admin_manage_user_group,
    itemTo: () => null,
  },
  users: {
    titleKey: 'admin.manageUsers',
    icon: 'mdi-account-multiple',
    apiPath: '/admin/modules/users',
    idKey: 'user_id',
    canView: (g) => g.is_admin || g.ability_admin_manage_user,
    itemTo: (row) => `/users/${row.user_id}`,
  },
  reports: {
    titleKey: 'admin.manageReports',
    icon: 'mdi-flag',
    apiPath: '/admin/modules/reports',
    idKey: 'report_id',
    canView: (g) => g.is_admin || g.ability_admin_manage_report,
    itemTo: () => null,
  },
}

const moduleName = computed(() => route.params.module)
const moduleMeta = computed(() => MODULE_META[moduleName.value] || null)

// 权限不足时回到仪表盘
watch(
  () => moduleName.value,
  (name) => {
    if (!name || !moduleMeta.value) {
      router.replace('/admin/')
      return
    }
    const group = authStore.user?.user_group || {}
    if (!moduleMeta.value.canView(group)) {
      feedback(t('admin.noPermission'), 'error')
      router.replace('/admin/')
    }
  },
  { immediate: true },
)

// ================== 每个模块的列定义 ==================
// header.key 必须与后端返回字段名一致；header.sortKey 用于服务端排序，对应后端 sortable 白名单
const COLUMN_PRESETS = {
  id: { key: 'id', title: 'ID', align: 'start', sortable: false, width: '80px' },
  actions: { key: 'actions', title: 'admin.fields.actions', align: 'end', sortable: false, width: '120px' },
}

// 把 i18n key 转成最终 title：vuetify 的 headers[].title 是直接渲染的字符串，
// 不会自动调用 $t()，所以这里在生成 headers 时就调 t() 把 key 解析成最终文本。
// 注意：i18n locale 切换时 computed 会自动重算（t() 内部跟踪 locale）。
function makeHeaders() {
  const m = moduleName.value
  if (!m) return []
  const def = ({
    topics: [
      { key: 'topic_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'name', title: 'admin.fields.topicName', align: 'start', sortable: false },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'follower_count', title: 'admin.fields.followers', align: 'end', sortable: true, width: '100px' },
      { key: 'article_count', title: 'admin.fields.articles', align: 'end', sortable: true, width: '100px' },
      { key: 'question_count', title: 'admin.fields.questions', align: 'end', sortable: true, width: '100px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '170px' },
      COLUMN_PRESETS.actions,
    ],
    questions: [
      { key: 'question_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'title', title: 'admin.fields.title', align: 'start', sortable: false },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'answer_count', title: 'admin.fields.answers', align: 'end', sortable: true, width: '100px' },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '100px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '170px' },
      COLUMN_PRESETS.actions,
    ],
    answers: [
      { key: 'answer_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'question_id', title: 'admin.fields.questionId', align: 'start', sortable: true, width: '110px' },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '100px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '170px' },
      COLUMN_PRESETS.actions,
    ],
    articles: [
      { key: 'article_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'title', title: 'admin.fields.title', align: 'start', sortable: false },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'comment_count', title: 'admin.fields.comments', align: 'end', sortable: true, width: '100px' },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '100px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '170px' },
      COLUMN_PRESETS.actions,
    ],
    comments: [
      { key: 'comment_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'commentable_type', title: 'admin.fields.targetType', align: 'start', sortable: false, width: '110px' },
      { key: 'commentable_id', title: 'admin.fields.targetId', align: 'start', sortable: false, width: '110px' },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'content', title: 'admin.fields.content', align: 'start', sortable: false },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '100px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '170px' },
      COLUMN_PRESETS.actions,
    ],
    replies: [
      { key: 'reply_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'replyable_type', title: 'admin.fields.targetType', align: 'start', sortable: false, width: '110px' },
      { key: 'replyable_id', title: 'admin.fields.targetId', align: 'start', sortable: false, width: '110px' },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'content', title: 'admin.fields.content', align: 'start', sortable: false },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '100px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '170px' },
      COLUMN_PRESETS.actions,
    ],
    'user-groups': [
      { key: 'user_group_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'user_group_name', title: 'admin.fields.userGroupName', align: 'start', sortable: false },
      { key: 'user_group_user_count', title: 'admin.fields.userCount', align: 'end', sortable: true, width: '120px' },
      { key: 'is_admin', title: 'admin.fields.isAdmin', align: 'center', sortable: true, width: '100px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '170px' },
      COLUMN_PRESETS.actions,
    ],
    users: [
      { key: 'user_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'username', title: 'admin.fields.username', align: 'start', sortable: true },
      { key: 'email', title: 'admin.fields.email', align: 'start', sortable: false },
      { key: 'user_group_name', title: 'admin.fields.userGroup', align: 'start', sortable: false, width: '160px' },
      { key: 'follower_count', title: 'admin.fields.followers', align: 'end', sortable: true, width: '100px' },
      { key: 'last_login_time', title: 'admin.fields.lastLogin', align: 'end', sortable: true, width: '170px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '170px' },
      COLUMN_PRESETS.actions,
    ],
    reports: [
      { key: 'report_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'reportable_type', title: 'admin.fields.targetType', align: 'start', sortable: false, width: '110px' },
      { key: 'reportable_id', title: 'admin.fields.targetId', align: 'start', sortable: false, width: '110px' },
      { key: 'username', title: 'admin.fields.reporter', align: 'start', sortable: false },
      { key: 'reason', title: 'admin.fields.reason', align: 'start', sortable: false },
      { key: 'report_handle_state', title: 'admin.fields.handleState', align: 'center', sortable: true, width: '120px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '170px' },
      COLUMN_PRESETS.actions,
    ],
  })
  // 在返回前把每个 title 的 i18n key 解析成最终文本（vuetify 不会自动 $t）
  return (def[m] || []).map((h) => ({ ...h, title: h.title ? t(h.title) : h.title }))
}

const headers = computed(() => makeHeaders())

// ================== 表格状态 ==================
const items = ref([])
const total = ref(0)
const loading = ref(true)
const error = ref(null)

const page = ref(1)
const itemsPerPage = ref(15)
// sort-by 必须是数组：空数组表示无排序（让后端走默认主键 desc）。
// 之前用 [{ key: '', order: 'desc' }] 会让 vuetify 内部解析出"有排序但 key 为空"的异常状态。
const sortBy = ref([])
const search = ref('')
const searchInput = ref('')

let searchDebounce = null

function applySearch() {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    search.value = searchInput.value.trim()
    page.value = 1
    loadItems()
  }, 400)
}

function formatDate(value) {
  if (!value) return '—'
  try {
    return new Intl.DateTimeFormat(locale.value, {
      year: 'numeric', month: '2-digit', day: '2-digit',
      hour: '2-digit', minute: '2-digit',
    }).format(new Date(value))
  } catch {
    return String(value)
  }
}

function truncate(text, len = 80) {
  if (!text) return '—'
  const str = String(text).replace(/<[^>]+>/g, '').trim()
  return str.length > len ? str.slice(0, len) + '…' : str
}

const TARGET_TYPE_LABELS = {
  question: 'admin.fields.question',
  article: 'admin.fields.article',
  answer: 'admin.fields.answer',
  comment: 'admin.fields.comment',
  reply: 'admin.fields.reply',
  topic: 'admin.fields.topicName',
  user: 'admin.fields.username',
}

function targetTypeLabel(type) {
  if (!type) return '—'
  const key = TARGET_TYPE_LABELS[type]
  return key ? t(key) : type
}

const REPORT_STATES = {
  0: { label: 'admin.reportState.pending', color: 'error' },
  1: { label: 'admin.reportState.deleted', color: 'grey' },
  2: { label: 'admin.reportState.dismissed', color: 'success' },
}

function reportState(state) {
  return REPORT_STATES[state] || { label: 'admin.reportState.pending', color: 'error' }
}

async function loadItems() {
  if (!moduleMeta.value) return
  loading.value = true
  error.value = null
  try {
    const sort = sortBy.value[0] || {}
    const params = {
      page: page.value,
      per_page: itemsPerPage.value,
    }
    if (search.value) params.search = search.value
    if (sort.key) {
      params.order_by = sort.key
      params.order_dir = sort.order || 'desc'
    }
    const { data } = await http.get(moduleMeta.value.apiPath, { params })
    items.value = Array.isArray(data?.data) ? data.data : []
    total.value = Number(data?.total || 0)
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || t('admin.loadFailed')
    items.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

// 监听 module 变化重置状态并重新加载
watch(
  () => moduleName.value,
  () => {
    if (!moduleMeta.value) return
    page.value = 1
    itemsPerPage.value = 15
    sortBy.value = []
    search.value = ''
    searchInput.value = ''
    loadItems()
  },
  { immediate: true },
)

function onRowClick(_event, { item }) {
  // vuetify 4: item slot 解构里的 item 直接就是原始数据（不再需要 .raw）
  if (!item) return
  const to = moduleMeta.value?.itemTo?.(item)
  if (to) openInNewTab(to)
}

function openInNewTab(url) {
  if (url) window.open(url, '_blank')
}

function refresh() {
  loadItems()
}

// ================== 编辑 / 删除（复用已有全局组件） ==================
// topics → TopicDialog；questions/answers/articles → RichTextEditorWindow
// comments/replies → CommentReplyEditDialog；users → ProfileEditDialog
// user-groups → UserGroupDialog；reports → ReportHandleDialog
const EDITABLE_MODULES = ['topics', 'questions', 'answers', 'articles', 'comments', 'replies', 'users', 'user-groups', 'reports']
const moduleEditable = computed(() => EDITABLE_MODULES.includes(moduleName.value))
// reports 只能处理不能删除
const moduleDeletable = computed(() => moduleEditable.value && moduleName.value !== 'reports')

// TopicDialog 状态
const topicDialogVisible = ref(false)
const topicEditItem = ref(null)

// UserGroupDialog 状态
const userGroupDialogVisible = ref(false)
const userGroupEditItem = ref(null)

// ReportHandleDialog 状态
const reportDialogVisible = ref(false)
const reportEditItem = ref(null)

// 编辑分发：根据模块打开对应的已有组件
async function openEditDialog(item) {
  const m = moduleName.value
  const row = item || {}

  if (m === 'topics') {
    topicEditItem.value = row
    topicDialogVisible.value = true
    return
  }

  if (m === 'questions' || m === 'answers' || m === 'articles') {
    const mode = m === 'questions' ? 'question' : m === 'articles' ? 'article' : 'answer'
    const id = row[MODULE_META[m].idKey]

    // 提问 / 文章有话题关联，列表接口不含 topics，需拉取完整详情
    let topics = []
    let title = row.title ?? ''
    let content = row.content_rendered || row.content_markdown || ''
    if (m === 'questions' || m === 'articles') {
      try {
        const { data } = await http.get(`/${m}/${id}`)
        if (data.topics) topics = data.topics
        if (data.title) title = data.title
        if (data.content_rendered || data.content_markdown) {
          content = data.content_rendered || data.content_markdown
        }
      } catch {
        // 拉取失败则用列表数据兜底
      }
    }

    editorWindow.openEditor({
      mode,
      action: 'edit',
      targetId: id,
      questionId: row.question_id ?? null,
      title,
      content,
      topics,
      apiBase: `/admin/modules/${m}`,
    })
    return
  }

  if (m === 'comments' || m === 'replies') {
    commentReplyEdit.open({
      type: m === 'replies' ? 'reply' : 'comment',
      id: row[MODULE_META[m].idKey],
      content: row.content || '',
      apiBase: `/admin/modules/${m}`,
      onSaved: (data) => onItemEdited(data),
    })
    return
  }

  if (m === 'users') {
    profileEdit.open(row, {
      apiBase: '/admin/modules/users',
      onSaved: (data) => onItemEdited(data),
    })
    return
  }

  if (m === 'user-groups') {
    userGroupEditItem.value = row
    userGroupDialogVisible.value = true
    return
  }

  if (m === 'reports') {
    reportEditItem.value = row
    reportDialogVisible.value = true
    return
  }
}

// 通用：编辑完成后合并更新行数据
function onItemEdited(updatedItem) {
  if (!updatedItem) {
    loadItems()
    return
  }
  const m = moduleName.value
  const idKey = MODULE_META[m]?.idKey || `${m.replace(/s$/, '')}_id`
  const list = items.value
  const idx = list.findIndex((row) => row[idKey] === updatedItem[idKey])
  if (idx >= 0) {
    list[idx] = { ...list[idx], ...updatedItem }
  } else {
    loadItems()
  }
}

// TopicDialog 提交回调
function onTopicSaved(data) {
  onItemEdited(data)
}

// UserGroupDialog 提交回调
function onUserGroupSaved(data) {
  onItemEdited(data)
}

// ReportHandleDialog 提交回调
function onReportHandled(data) {
  onItemEdited(data)
}

// 删除：复用全局 DeleteDialog
function openDeleteDialog(item) {
  const m = moduleName.value
  const idKey = MODULE_META[m]?.idKey || `${m.replace(/s$/, '')}_id`
  const id = item?.[idKey]
  if (!id) return

  const label = item?.name || item?.title || item?.username || item?.user_group_name || `#${id}`

  deleteDialog.open({
    title: t('admin.deleteTitle'),
    text: t('admin.deleteConfirmPrefix') + label + t('admin.deleteConfirmSuffix'),
    onConfirm: async () => {
      await http.delete(`/admin/modules/${m}/${id}`)
      items.value = items.value.filter((row) => row[idKey] !== id)
      total.value = Math.max(0, total.value - 1)
      feedback(t('admin.deleteSuccess'), 'success')
    },
  })
}

// 监听 RichTextEditorWindow 发布成功事件（App.vue 广播的 content:published）
function onContentPublished() {
  // 管理员编辑后刷新当前列表
  loadItems()
}
window.addEventListener('content:published', onContentPublished)

// 监听 FloatingActionButton 创建用户组成功事件
function onUserGroupCreated() {
  loadItems()
}
window.addEventListener('user-group:created', onUserGroupCreated)

onBeforeUnmount(() => {
  window.removeEventListener('content:published', onContentPublished)
  window.removeEventListener('user-group:created', onUserGroupCreated)
})
</script>

<template>
  <v-container class="admin-module-page py-6 px-4 px-md-8" fluid>
    <!-- 标题区 -->
    <v-row class="mb-2" align="center" no-gutters>
      <v-col cols="12" md="6" class="d-flex align-center">
        <!-- <v-avatar col哈哈 -->
        <div>
          <!-- <div class="text-overline text-primary font-weight-bold">MDForum / ADMIN</div> -->
          <h1 class="text-h5 font-weight-bold">
            {{ moduleMeta ? $t(moduleMeta.titleKey) : $t('common.developing') }}
          </h1>
        </div>
      </v-col>
      <v-col cols="12" md="6" class="d-flex align-center justify-end mt-4 mt-md-0" style="gap:12px;">
        <v-text-field
          v-model="searchInput"
          density="compact"
          variant="outlined"
          prepend-inner-icon="mdi-magnify"
          :placeholder="$t('admin.searchPlaceholder')"
          hide-details
          clearable
          style="max-width: 320px;"
          @update:model-value="applySearch"
          @click:clear="() => { search = ''; searchInput = ''; page = 1; loadItems() }"
        />
        <v-btn variant="tonal" color="primary" prepend-icon="mdi-refresh" :loading="loading" @click="refresh">
          {{ $t('admin.refresh') }}
        </v-btn>
      </v-col>
    </v-row>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4" closable @click:close="error = null">
      {{ error }}
    </v-alert>

    <v-card class="data-table-card">
      <v-data-table-server
        v-if="moduleMeta"
        v-model:page="page"
        v-model:items-per-page="itemsPerPage"
        v-model:sort-by="sortBy"
        :headers="headers"
        :items="items"
        :items-length="total"
        :loading="loading"
        :search="search"
        hover
        density="comfortable"
        @update:options="loadItems"
        @click:row="onRowClick"
      >
        <template #[`item.name`]="{ item }">
          <div class="text-truncate" style="max-width: 280px;">
            {{ item?.name || '—' }}
          </div>
        </template>
        <template #[`item.title`]="{ item }">
          <div class="text-truncate" style="max-width: 280px;">
            {{ item?.title || '—' }}
          </div>
        </template>
        <template #[`item.content`]="{ item }">
          <div class="text-truncate" style="max-width: 320px;">
            {{ truncate(item?.content || item?.content_markdown, 80) }}
          </div>
        </template>
        <template #[`item.reason`]="{ item }">
          <div class="text-truncate" style="max-width: 240px;">
            {{ item?.reason || '—' }}
          </div>
        </template>
        <template #[`item.username`]="{ item }">
          <span v-if="item?.username" class="text-primary font-weight-medium">
            {{ item.username }}
          </span>
          <span v-else>—</span>
        </template>
        <template #[`item.commentable_type`]="{ item }">
          {{ targetTypeLabel(item?.commentable_type) }}
        </template>
        <template #[`item.replyable_type`]="{ item }">
          {{ targetTypeLabel(item?.replyable_type) }}
        </template>
        <template #[`item.reportable_type`]="{ item }">
          {{ targetTypeLabel(item?.reportable_type) }}
        </template>
        <template #[`item.is_admin`]="{ item }">
          <v-chip v-if="item?.is_admin" color="error" size="small" label>{{ $t('admin.yes') }}</v-chip>
          <v-chip v-else color="grey" size="small" label variant="outlined">{{ $t('admin.no') }}</v-chip>
        </template>
        <template #[`item.report_handle_state`]="{ item }">
          <v-chip :color="reportState(item?.report_handle_state).color" size="small" label>
            {{ $t(reportState(item?.report_handle_state).label) }}
          </v-chip>
        </template>
        <template #[`item.create_time`]="{ item }">
          <span class="text-no-wrap">{{ formatDate(item?.create_time) }}</span>
        </template>
        <template #[`item.last_login_time`]="{ item }">
          <span class="text-no-wrap">{{ formatDate(item?.last_login_time) }}</span>
        </template>
        <template #[`item.actions`]="{ item }">
          <div class="d-flex justify-end" style="gap: 4px;">
            <v-btn
              v-if="moduleMeta?.itemTo && moduleMeta.itemTo(item || {})"
              icon="mdi-open-in-new"
              size="small"
              variant="text"
              :title="$t('admin.actions.viewOnSite')"
              @click.stop="openInNewTab(moduleMeta.itemTo(item || {}))"
            />
            <v-btn
              v-if="moduleEditable"
              icon="mdi-pencil"
              size="small"
              variant="text"
              color="primary"
              :title="$t('common.edit')"
              @click.stop="openEditDialog(item)"
            />
            <v-btn
              v-if="moduleDeletable"
              icon="mdi-delete"
              size="small"
              variant="text"
              color="error"
              :title="$t('common.delete')"
              @click.stop="openDeleteDialog(item)"
            />
          </div>
        </template>
        <template #loading>
          <v-skeleton-loader type="table-row@5" />
        </template>
        <template #no-data>
          <div class="text-center pa-8 text-medium-emphasis">
            <v-icon size="48" class="mb-2">mdi-database-off-outline</v-icon>
            <div>{{ $t('admin.noData') }}</div>
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- 话题编辑对话框（topics 模块专用；其他模块的编辑对话框均为全局组件，已在 App.vue 挂载） -->
    <TopicDialog
      v-model="topicDialogVisible"
      :topic="topicEditItem"
      api-base="/admin/modules/topics"
      @submit="onTopicSaved"
    />

    <!-- 用户组编辑对话框（user-groups 模块专用） -->
    <UserGroupDialog
      v-model="userGroupDialogVisible"
      :user-group="userGroupEditItem"
      @submit="onUserGroupSaved"
    />

    <!-- 举报处理对话框（reports 模块专用） -->
    <ReportHandleDialog
      v-model="reportDialogVisible"
      :report="reportEditItem"
      @submit="onReportHandled"
    />
  </v-container>
</template>

<style scoped>
.admin-module-page {
  min-height: 100%;
}

.data-table-card {
  border-radius: 12px;
  overflow: hidden;
}
</style>

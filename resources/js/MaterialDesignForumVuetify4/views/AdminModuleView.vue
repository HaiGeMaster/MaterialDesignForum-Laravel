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
import { useDisplay } from 'vuetify'
import http from '../api'
import TopicDialog from '../components/TopicDialog.vue'
import UserGroupDialog from '../components/UserGroupDialog.vue'
import ReportHandleDialog from '../components/ReportHandleDialog.vue'

const { mobile } = useDisplay()
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
  // 用户组权限列：标题复用 UserGroupDialog 的权限翻译 key（userGroupDialog.fields.*）
  const ability = (key, title) => ({ key, title, align: 'center', sortable: false, width: '88px' })
  const timeLimit = (key, title) => ({ key, title, align: 'end', sortable: false, width: '96px' })

  const def = ({
    topics: [
      { key: 'topic_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'name', title: 'admin.fields.topicName', align: 'start', sortable: false },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'description', title: 'admin.fields.description', align: 'start', sortable: false },
      { key: 'article_count', title: 'admin.fields.articles', align: 'end', sortable: true, width: '90px' },
      { key: 'question_count', title: 'admin.fields.questions', align: 'end', sortable: true, width: '90px' },
      { key: 'follower_count', title: 'admin.fields.followers', align: 'end', sortable: true, width: '90px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '160px' },
      { key: 'update_time', title: 'admin.fields.updatedAt', align: 'end', sortable: true, width: '160px' },
      COLUMN_PRESETS.actions,
    ],
    questions: [
      { key: 'question_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'title', title: 'admin.fields.title', align: 'start', sortable: false },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'comment_count', title: 'admin.fields.comments', align: 'end', sortable: true, width: '90px' },
      { key: 'answer_count', title: 'admin.fields.answers', align: 'end', sortable: true, width: '90px' },
      { key: 'follower_count', title: 'admin.fields.followers', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_up_count', title: 'admin.fields.voteUps', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_down_count', title: 'admin.fields.voteDowns', align: 'end', sortable: true, width: '90px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '160px' },
      { key: 'update_time', title: 'admin.fields.updatedAt', align: 'end', sortable: true, width: '160px' },
      COLUMN_PRESETS.actions,
    ],
    answers: [
      { key: 'answer_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'question_id', title: 'admin.fields.questionId', align: 'start', sortable: true, width: '90px' },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'content_markdown', title: 'admin.fields.content', align: 'start', sortable: false },
      { key: 'comment_count', title: 'admin.fields.comments', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_up_count', title: 'admin.fields.voteUps', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_down_count', title: 'admin.fields.voteDowns', align: 'end', sortable: true, width: '90px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '160px' },
      { key: 'update_time', title: 'admin.fields.updatedAt', align: 'end', sortable: true, width: '160px' },
      COLUMN_PRESETS.actions,
    ],
    articles: [
      { key: 'article_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'title', title: 'admin.fields.title', align: 'start', sortable: false },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'comment_count', title: 'admin.fields.comments', align: 'end', sortable: true, width: '90px' },
      { key: 'follower_count', title: 'admin.fields.followers', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_up_count', title: 'admin.fields.voteUps', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_down_count', title: 'admin.fields.voteDowns', align: 'end', sortable: true, width: '90px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '160px' },
      { key: 'update_time', title: 'admin.fields.updatedAt', align: 'end', sortable: true, width: '160px' },
      COLUMN_PRESETS.actions,
    ],
    comments: [
      { key: 'comment_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'commentable_type', title: 'admin.fields.targetType', align: 'start', sortable: false, width: '100px' },
      { key: 'commentable_id', title: 'admin.fields.targetId', align: 'start', sortable: false, width: '90px' },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'content', title: 'admin.fields.content', align: 'start', sortable: false },
      { key: 'reply_count', title: 'admin.fields.replies', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_up_count', title: 'admin.fields.voteUps', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_down_count', title: 'admin.fields.voteDowns', align: 'end', sortable: true, width: '90px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '160px' },
      { key: 'update_time', title: 'admin.fields.updatedAt', align: 'end', sortable: true, width: '160px' },
      COLUMN_PRESETS.actions,
    ],
    replies: [
      { key: 'reply_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'replyable_type', title: 'admin.fields.targetType', align: 'start', sortable: false, width: '100px' },
      { key: 'replyable_id', title: 'admin.fields.targetId', align: 'start', sortable: false, width: '90px' },
      { key: 'replyable_username', title: 'admin.fields.replyTargetUser', align: 'start', sortable: false },
      { key: 'replyable_comment_id', title: 'admin.fields.parentCommentId', align: 'start', sortable: false, width: '100px' },
      { key: 'username', title: 'admin.fields.creator', align: 'start', sortable: false },
      { key: 'content', title: 'admin.fields.content', align: 'start', sortable: false },
      { key: 'reply_count', title: 'admin.fields.replies', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_count', title: 'admin.fields.votes', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_up_count', title: 'admin.fields.voteUps', align: 'end', sortable: true, width: '90px' },
      { key: 'vote_down_count', title: 'admin.fields.voteDowns', align: 'end', sortable: true, width: '90px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '160px' },
      { key: 'update_time', title: 'admin.fields.updatedAt', align: 'end', sortable: true, width: '160px' },
      COLUMN_PRESETS.actions,
    ],
    'user-groups': [
      { key: 'user_group_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'user_group_name', title: 'admin.fields.userGroupName', align: 'start', sortable: false },
      { key: 'user_group_description', title: 'admin.fields.description', align: 'start', sortable: false },
      { key: 'user_group_icon', title: 'admin.fields.icon', align: 'center', sortable: false, width: '80px' },
      { key: 'user_group_icon_show', title: 'admin.fields.iconShow', align: 'center', sortable: false, width: '88px' },
      { key: 'user_group_color', title: 'admin.fields.color', align: 'center', sortable: false, width: '88px' },
      { key: 'user_group_user_count', title: 'admin.fields.userCount', align: 'end', sortable: true, width: '90px' },
      { key: 'is_admin', title: 'admin.fields.isAdmin', align: 'center', sortable: true, width: '88px' },
      ability('ability_normal_login', 'userGroupDialog.fields.normalLogin'),
      ability('ability_admin_login', 'userGroupDialog.fields.adminLogin'),
      ability('ability_admin_manage_user_group', 'userGroupDialog.fields.manageUserGroup'),
      ability('ability_admin_manage_user', 'userGroupDialog.fields.manageUser'),
      ability('ability_admin_manage_topic', 'userGroupDialog.fields.manageTopic'),
      ability('ability_admin_manage_question', 'userGroupDialog.fields.manageQuestion'),
      ability('ability_admin_manage_article', 'userGroupDialog.fields.manageArticle'),
      ability('ability_admin_manage_comment', 'userGroupDialog.fields.manageComment'),
      ability('ability_admin_manage_answer', 'userGroupDialog.fields.manageAnswer'),
      ability('ability_admin_manage_reply', 'userGroupDialog.fields.manageReply'),
      ability('ability_admin_manage_report', 'userGroupDialog.fields.manageReport'),
      ability('ability_admin_manage_option', 'userGroupDialog.fields.manageOption'),
      ability('ability_create_article', 'userGroupDialog.fields.createArticle'),
      ability('ability_create_question', 'userGroupDialog.fields.createQuestion'),
      ability('ability_create_answer', 'userGroupDialog.fields.createAnswer'),
      ability('ability_create_comment', 'userGroupDialog.fields.createComment'),
      ability('ability_create_reply', 'userGroupDialog.fields.createReply'),
      ability('ability_create_topic', 'userGroupDialog.fields.createTopic'),
      ability('ability_edit_own_article', 'userGroupDialog.fields.editOwnArticle'),
      ability('ability_edit_own_question', 'userGroupDialog.fields.editOwnQuestion'),
      ability('ability_edit_own_answer', 'userGroupDialog.fields.editOwnAnswer'),
      ability('ability_edit_own_comment', 'userGroupDialog.fields.editOwnComment'),
      ability('ability_edit_own_reply', 'userGroupDialog.fields.editOwnReply'),
      ability('ability_edit_own_topic', 'userGroupDialog.fields.editOwnTopic'),
      ability('ability_delete_own_article', 'userGroupDialog.fields.deleteOwnArticle'),
      ability('ability_delete_own_question', 'userGroupDialog.fields.deleteOwnQuestion'),
      ability('ability_delete_own_answer', 'userGroupDialog.fields.deleteOwnAnswer'),
      ability('ability_delete_own_comment', 'userGroupDialog.fields.deleteOwnComment'),
      ability('ability_delete_own_reply', 'userGroupDialog.fields.deleteOwnReply'),
      ability('ability_delete_own_topic', 'userGroupDialog.fields.deleteOwnTopic'),
      timeLimit('time_before_edit_article', 'userGroupDialog.fields.editArticle'),
      timeLimit('time_before_edit_question', 'userGroupDialog.fields.editQuestion'),
      timeLimit('time_before_edit_answer', 'userGroupDialog.fields.editAnswer'),
      timeLimit('time_before_edit_comment', 'userGroupDialog.fields.editComment'),
      timeLimit('time_before_edit_reply', 'userGroupDialog.fields.editReply'),
      timeLimit('time_before_edit_topic', 'userGroupDialog.fields.editTopic'),
      timeLimit('time_before_delete_article', 'userGroupDialog.fields.deleteArticle'),
      timeLimit('time_before_delete_question', 'userGroupDialog.fields.deleteQuestion'),
      timeLimit('time_before_delete_answer', 'userGroupDialog.fields.deleteAnswer'),
      timeLimit('time_before_delete_comment', 'userGroupDialog.fields.deleteComment'),
      timeLimit('time_before_delete_reply', 'userGroupDialog.fields.deleteReply'),
      timeLimit('time_before_delete_topic', 'userGroupDialog.fields.deleteTopic'),
      ability('ability_edit_article_only_no_comment', 'userGroupDialog.fields.editArticleNoComment'),
      ability('ability_edit_question_only_no_answer', 'userGroupDialog.fields.editQuestionNoAnswer'),
      ability('ability_edit_answer_only_no_comment', 'userGroupDialog.fields.editAnswerNoComment'),
      ability('ability_edit_question_only_no_comment', 'userGroupDialog.fields.editQuestionNoComment'),
      ability('ability_edit_comment_only_no_reply', 'userGroupDialog.fields.editCommentNoReply'),
      ability('ability_edit_reply_only_no_reply', 'userGroupDialog.fields.editReplyNoReply'),
      ability('ability_edit_topic_only_no_article_or_question', 'userGroupDialog.fields.editTopicNoContent'),
      ability('ability_delete_article_only_no_comment', 'userGroupDialog.fields.deleteArticleNoComment'),
      ability('ability_delete_question_only_no_answer', 'userGroupDialog.fields.deleteQuestionNoAnswer'),
      ability('ability_delete_answer_only_no_comment', 'userGroupDialog.fields.deleteAnswerNoComment'),
      ability('ability_delete_question_only_no_comment', 'userGroupDialog.fields.deleteQuestionNoComment'),
      ability('ability_delete_comment_only_no_reply', 'userGroupDialog.fields.deleteCommentNoReply'),
      ability('ability_delete_reply_only_no_reply', 'userGroupDialog.fields.deleteReplyNoReply'),
      ability('ability_delete_topic_only_no_article_or_question', 'userGroupDialog.fields.deleteTopicNoContent'),
      ability('ability_edit_own_info', 'userGroupDialog.fields.editOwnInfo'),
      ability('ability_vote', 'userGroupDialog.fields.vote'),
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '160px' },
      { key: 'update_time', title: 'admin.fields.updatedAt', align: 'end', sortable: true, width: '160px' },
      COLUMN_PRESETS.actions,
    ],
    users: [
      { key: 'user_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'username', title: 'admin.fields.username', align: 'start', sortable: true },
      { key: 'email', title: 'admin.fields.email', align: 'start', sortable: false },
      { key: 'user_group_name', title: 'admin.fields.userGroup', align: 'start', sortable: false, width: '140px' },
      { key: 'create_ip', title: 'admin.fields.createIp', align: 'start', sortable: false, width: '110px' },
      { key: 'create_location', title: 'admin.fields.createLocation', align: 'start', sortable: false, width: '130px' },
      { key: 'last_login_time', title: 'admin.fields.lastLogin', align: 'end', sortable: true, width: '160px' },
      { key: 'last_login_ip', title: 'admin.fields.lastLoginIp', align: 'start', sortable: false, width: '110px' },
      { key: 'last_login_location', title: 'admin.fields.lastLoginLocation', align: 'start', sortable: false, width: '130px' },
      { key: 'follower_count', title: 'admin.fields.followers', align: 'end', sortable: true, width: '90px' },
      { key: 'followee_count', title: 'admin.fields.followees', align: 'end', sortable: true, width: '90px' },
      { key: 'following_topic_count', title: 'admin.fields.followingTopics', align: 'end', sortable: true, width: '100px' },
      { key: 'following_article_count', title: 'admin.fields.followingArticles', align: 'end', sortable: true, width: '100px' },
      { key: 'following_question_count', title: 'admin.fields.followingQuestions', align: 'end', sortable: true, width: '100px' },
      { key: 'article_count', title: 'admin.fields.articles', align: 'end', sortable: true, width: '90px' },
      { key: 'question_count', title: 'admin.fields.questions', align: 'end', sortable: true, width: '90px' },
      { key: 'answer_count', title: 'admin.fields.answers', align: 'end', sortable: true, width: '90px' },
      { key: 'comment_count', title: 'admin.fields.comments', align: 'end', sortable: true, width: '90px' },
      { key: 'reply_count', title: 'admin.fields.replies', align: 'end', sortable: true, width: '90px' },
      { key: 'notification_unread', title: 'admin.fields.unreadNotifications', align: 'end', sortable: true, width: '100px' },
      { key: 'headline', title: 'admin.fields.headline', align: 'start', sortable: false },
      { key: 'bio', title: 'admin.fields.bio', align: 'start', sortable: false },
      { key: 'blog', title: 'admin.fields.blog', align: 'start', sortable: false },
      { key: 'company', title: 'admin.fields.company', align: 'start', sortable: false },
      { key: 'location', title: 'admin.fields.location', align: 'start', sortable: false },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '160px' },
      { key: 'update_time', title: 'admin.fields.updatedAt', align: 'end', sortable: true, width: '160px' },
      { key: 'disable_time', title: 'admin.fields.disabledAt', align: 'end', sortable: true, width: '160px' },
      { key: 'disable_until', title: 'admin.fields.disabledUntil', align: 'end', sortable: true, width: '160px' },
      COLUMN_PRESETS.actions,
    ],
    reports: [
      { key: 'report_id', title: 'admin.fields.id', align: 'start', sortable: true, width: '80px' },
      { key: 'username', title: 'admin.fields.reporter', align: 'start', sortable: false },
      { key: 'reportable_type', title: 'admin.fields.targetType', align: 'start', sortable: false, width: '100px' },
      { key: 'reportable_id', title: 'admin.fields.targetId', align: 'start', sortable: false, width: '90px' },
      { key: 'reason', title: 'admin.fields.reason', align: 'start', sortable: false },
      { key: 'report_handle_state', title: 'admin.fields.handleState', align: 'center', sortable: true, width: '110px' },
      { key: 'create_time', title: 'admin.fields.createdAt', align: 'end', sortable: true, width: '160px' },
      COLUMN_PRESETS.actions,
    ],
  })
  // 在返回前把每个 title 的 i18n key 解析成最终文本（vuetify 不会自动 $t）
  return (def[m] || []).map((h) => ({ ...h, title: h.title ? t(h.title) : h.title }))
}

const headers = computed(() => makeHeaders())

// 布尔型列（is_admin / user_group_icon_show / ability_*）：统一渲染为“是/否”芯片
const boolHeaders = computed(() =>
  headers.value.filter(
    (h) => h.key === 'is_admin' || h.key === 'user_group_icon_show' || h.key.startsWith('ability_'),
  ),
)

// 需要截断显示的长文本列（统一渲染为单行省略）
const truncateTextKeys = ['content_markdown', 'user_group_description', 'headline', 'bio', 'blog', 'company', 'location']

// 布尔列 / 长文本列对应的动态插槽名（供模板 v-for 渲染 item.<key> 插槽）
const boolSlotNames = computed(() => boolHeaders.value.map((h) => `item.${h.key}`))
const truncateSlotNames = computed(() => truncateTextKeys.map((k) => `item.${k}`))
// 由插槽名（item.<key>）反解出字段 key
const slotKey = (name) => String(name).replace(/^item\./, '')

// ================== 表格状态 ==================
const items = ref([])
const total = ref(0)
const loading = ref(true)
const error = ref(null)
const selected = ref([])
const selectedUserGroupId = ref(null)
const bulkUserGroupDialog = ref(false)

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

function itemValue(item) {
  if (!item) return null
  const key = moduleMeta.value?.idKey || 'id'
  return item[key] ?? item.id ?? null
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
    clearSelection()
    page.value = 1
    itemsPerPage.value = 15
    sortBy.value = []
    search.value = ''
    searchInput.value = ''
    loadItems()
    if (moduleName.value === 'users') {
      loadUserGroups()
    }
  },
  { immediate: true },
)

function onRowClick(_event, { item }) {
  // vuetify 4: item slot 解构里的 item 直接就是原始数据（不再需要 .raw）
  if (!item) return
  // 点击行默认打开对应编辑对话框
  if (moduleEditable.value) {
    openEditDialog(item)
    return
  }
  // 不可编辑模块：回退为打开前台详情
  const to = moduleMeta.value?.itemTo?.(item)
  if (to) openInNewTab(to)
}

function openInNewTab(url) {
  if (url) window.open(url, '_blank')
}

function refresh() {
  loadItems()
}

const selectedIds = computed(() => {
  if (!moduleName.value || !moduleMeta.value) return []
  const key = moduleMeta.value.idKey
  return selected.value.map((row) => row?.[key]).filter((id) => id !== undefined && id !== null)
})

const selectedUsers = computed(() => selected.value.filter((item) => moduleName.value === 'users'))
const selectedReports = computed(() => selected.value.filter((item) => moduleName.value === 'reports'))
const hasSelection = computed(() => selected.value.length > 0)

async function loadUserGroups() {
  try {
    const { data } = await http.get('/user-groups')
    userGroupOptions.value = Array.isArray(data) ? data : []
  } catch {
    userGroupOptions.value = []
  }
}

const userGroupOptions = ref([])

function clearSelection() {
  selected.value = []
}

async function bulkDeleteSelected() {
  if (!hasSelection.value) return
  if (moduleName.value === 'reports') return

  const count = selected.value.length
  const label = moduleName.value === 'users' ? '用户' : '选中的条目'

  deleteDialog.open({
    title: t('admin.bulkDeleteTitle'),
    text: t('admin.bulkDeleteText', { count, label }),
    confirmText: t('common.delete'),
    onConfirm: async () => {
      const module = moduleName.value
      await Promise.all(
        selected.value.map((item) => {
          const idKey = MODULE_META[module]?.idKey || `${module.replace(/s$/, '')}_id`
          const id = item?.[idKey]
          if (!id) return Promise.resolve()
          return http.delete(`/admin/modules/${module}/${id}`)
        }),
      )
      clearSelection()
      await loadItems()
      feedback(t('admin.bulkDeleteSuccess', { count }), 'success')
    },
    onError: (error) => {
      const message = error?.response?.data?.message || error?.message || t('admin.deleteFailed') || '删除失败'
      feedback(message, 'error')
    },
  })
}

async function bulkDisableSelectedUsers() {
  if (moduleName.value !== 'users' || !selectedUsers.value.length) return

  const count = selectedUsers.value.length
  const today = new Date()
  const localDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

  deleteDialog.open({
    title: t('admin.bulkDisableTitle'),
    text: t('admin.bulkDisableText', { count }),
    confirmText: t('admin.bulkDisableAction'),
    icon: 'mdi-account-cancel',
    color: 'warning',
    showDatePicker: true,
    datePickerLabel: t('admin.disableUntil'),
    minDate: localDate,
    selectedDate: localDate,
    allowPermanent: true,
    permanentLabel: t('admin.permanentDisable'),
    onConfirm: async () => {
      const now = new Date().toISOString()
      const selectedDate = deleteDialog.selectedDate
      const disableUntil = selectedDate instanceof Date
        ? `${selectedDate.getFullYear()}-${String(selectedDate.getMonth() + 1).padStart(2, '0')}-${String(selectedDate.getDate()).padStart(2, '0')}`
        : String(selectedDate || '').slice(0, 10)
      await Promise.all(
        selectedUsers.value.map((item) => {
          const id = item?.user_id
          if (!id) return Promise.resolve()
          return http.put(`/admin/modules/users/${id}`, {
            disable_time: now,
            disable_until: deleteDialog.permanent ? null : disableUntil,
          })
        }),
      )
      clearSelection()
      await loadItems()
      feedback(t('admin.bulkDisableSuccess', { count }), 'success')
    },
    onError: (error) => {
      const message = error?.response?.data?.message || error?.message || t('admin.editFailed')
      feedback(message, 'error')
    },
  })
}

function openBulkUserGroupDialog() {
  if (moduleName.value !== 'users' || !selectedUsers.value.length) return
  selectedUserGroupId.value = null
  bulkUserGroupDialog.value = true
}

async function applyBulkUserGroup() {
  if (!selectedUserGroupId.value || moduleName.value !== 'users' || !selectedUsers.value.length) return

  // console.log('selectedUsers.value.length', selectedUsers.value.length)
  const selectedUsersCount = selectedUsers.value.length

  const targetGroupId = Number(selectedUserGroupId.value)
  await Promise.all(
    selectedUsers.value.map((item) => {
      const id = item?.user_id
      if (!id) return Promise.resolve()
      return http.put(`/admin/modules/users/${id}`, { user_group_id: targetGroupId })
    }),
  )
  bulkUserGroupDialog.value = false
  clearSelection()
  await loadItems()
  feedback(t('admin.bulkUserGroupSuccess', { count: selectedUsersCount }), 'success')
}

async function bulkSetReportState(state) {
  if (moduleName.value !== 'reports' || !selectedReports.value.length) return

  const selectedReportsCount = selectedReports.value.length
  await Promise.all(
    selectedReports.value.map((item) => {
      const id = item?.report_id
      if (!id) return Promise.resolve()
      return http.put(`/admin/modules/reports/${id}`, { report_handle_state: state })
    }),
  )
  clearSelection()
  await loadItems()
  feedback(t('admin.bulkReportStateSuccess', { count: selectedReportsCount, state: t(`reportDialog.state${state === 0 ? 'Pending' : state === 1 ? 'Handled' : 'Dismissed'}`) }), 'success')
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

  console.log('openDeleteDialog', (m === 'user-groups' && Number(item?.user_group_user_count ?? 0) > 0))
  if (m === 'user-groups' && Number(item?.user_group_user_count ?? 0) > 0) {
    feedback(t('admin.userGroupDeleteBlocked'), 'error')
    return
  }

  deleteDialog.open({
    title: t('admin.deleteTitle'),
    text: t('admin.deleteConfirmPrefix') + label + t('admin.deleteConfirmSuffix'),
    onConfirm: async () => {
      await http.delete(`/admin/modules/${m}/${id}`)
      items.value = items.value.filter((row) => row[idKey] !== id)
      total.value = Math.max(0, total.value - 1)
      feedback(t('admin.deleteSuccess'), 'success')
    },
    onError: (error) => {
      const message = error?.response?.data?.message || error?.message || t('admin.deleteFailed') || '删除失败'
      feedback(message, 'error')
    },
  })
}

function toggleUserDisabled(item) {
  const id = item?.user_id
  if (!id) return

  const isDisabled = Boolean(item.is_disabled ?? item.disable_time)
  const action = isDisabled ? 'enable' : 'disable'
  const username = item.username || `#${id}`
  const today = new Date()
  const localDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

  deleteDialog.open({
    title: t(`admin.${action}UserTitle`),
    text: t(`admin.${action}UserText`, { username }),
    confirmText: t(`admin.${action}UserAction`),
    icon: isDisabled ? 'mdi-account-check' : 'mdi-account-cancel',
    color: isDisabled ? 'success' : 'warning',
    showDatePicker: !isDisabled,
    datePickerLabel: t('admin.disableUntil'),
    minDate: localDate,
    selectedDate: localDate,
    allowPermanent: !isDisabled,
    permanentLabel: t('admin.permanentDisable'),
    onConfirm: async () => {
      const selectedDate = deleteDialog.selectedDate
      const disableUntil = selectedDate instanceof Date
        ? `${selectedDate.getFullYear()}-${String(selectedDate.getMonth() + 1).padStart(2, '0')}-${String(selectedDate.getDate()).padStart(2, '0')}`
        : String(selectedDate || '').slice(0, 10)
      const { data } = await http.put(`/admin/modules/users/${id}`, {
        disable_time: isDisabled ? null : new Date().toISOString(),
        disable_until: isDisabled || deleteDialog.permanent ? null : disableUntil,
      })
      onItemEdited(data)
      feedback(t(`admin.${action}UserSuccess`, { username }), 'success')
    },
    onError: (error) => {
      const message = error?.response?.data?.message || error?.message || t('admin.editFailed')
      feedback(message, 'error')
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
    <!-- <v-row class="mb-2" align="center" no-gutters>
      <v-col cols="12" md="6" class="d-flex align-center">
        <div>
          <h1 class="text-h5 font-weight-bold">
            {{ moduleMeta ? $t(moduleMeta.titleKey) : $t('common.developing') }}
          </h1>
        </div>
      </v-col>
      <v-col cols="12" md="6" class="d-flex align-center justify-end mt-4 mt-md-0" style="gap:12px;">
        <v-text-field color="primary" 
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
    </v-row> -->

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4" closable @click:close="error = null">
      {{ error }}
    </v-alert>

    <v-card rounded="lg" class="data-table-card">

      <v-toolbar :title="moduleMeta ? $t(moduleMeta.titleKey) : $t('common.developing')">
        <template #prepend>
          <div v-if="hasSelection" class="d-flex align-center ga-2">
            <v-btn
              v-if="moduleName !== 'reports' && moduleName !== 'users'"
              color="error"
              variant="flat"
              size="small"
              icon
              @click="bulkDeleteSelected"
            >
              <v-icon>mdi-delete-outline</v-icon>
              <v-tooltip activator="parent" >
                {{ $t('admin.bulkDelete') }} ({{ selected.length }})
              </v-tooltip>
            </v-btn>

            <v-btn
              v-if="moduleName === 'users'"
              color="warning"
              variant="flat"
              size="small"
              icon
              @click="bulkDisableSelectedUsers"
            >
              <v-icon>mdi-account-cancel</v-icon>
              <v-tooltip activator="parent" >
                {{ $t('admin.bulkDisable') }} ({{ selected.length }})
              </v-tooltip>
            </v-btn>

            <v-btn
              v-if="moduleName === 'users'"
              color="primary"
              variant="flat"
              size="small"
              icon
              @click="openBulkUserGroupDialog"
            >
              <v-icon>mdi-account-switch</v-icon>
              <v-tooltip activator="parent" >
                {{ $t('admin.bulkChangeUserGroup') }}
              </v-tooltip>
            </v-btn>

            <v-btn
              v-if="moduleName === 'reports'"
              color="error"
              variant="flat"
              size="small"
              icon
              @click="bulkSetReportState(0)"
            >
              <v-icon>mdi-clock-outline</v-icon>
              <v-tooltip activator="parent" >
                {{ $t('reportDialog.statePending') }}
              </v-tooltip>
            </v-btn>
            <v-btn
              v-if="moduleName === 'reports'"
              color="grey"
              variant="flat"
              size="small"
              icon
              @click="bulkSetReportState(1)"
            >
              <v-icon>mdi-check-circle-outline</v-icon>
              <v-tooltip activator="parent" >
                {{ $t('reportDialog.stateHandled') }}
              </v-tooltip>
            </v-btn>
            <v-btn
              v-if="moduleName === 'reports'"
              color="success"
              variant="flat"
              size="small"
              icon
              @click="bulkSetReportState(2)"
            >
              <v-icon>mdi-close-circle-outline</v-icon>
              <v-tooltip activator="parent" >
                {{ $t('reportDialog.stateDismissed') }}
              </v-tooltip>
            </v-btn>
          </div>
        </template>
        <template #append>
          <div class="d-flex align-center" style="gap: 12px;max-width: 600px;width: 100%;">
            <v-text-field color="primary"  v-model="searchInput" density="compact" variant="outlined" prepend-inner-icon="mdi-magnify"
              :placeholder="$t('admin.searchPlaceholder')" hide-details clearable style="max-width: 320px;"
              @update:model-value="applySearch"
              @click:clear="() => { search = ''; searchInput = ''; page = 1; loadItems() }" />

            <v-btn icon variant="text" :loading="loading" @click="refresh">
              <v-icon>mdi-refresh</v-icon>
              <v-tooltip activator="parent" >
                {{ $t('admin.refresh') }}
              </v-tooltip>
            </v-btn>
          </div>
        </template>
      </v-toolbar>
        <!-- :items-per-page-options="[
  {value: 10, title: '10'},
  {value: 25, title: '25'},
  {value: 50, title: '50'},
  {value: 100, title: '100'},
  {value: 500, title: '500'},
  {value: 1000, title: '1000'},
  {value: 10000, title: '10000'},
]" -->
      <v-data-table-server color="primary" :mobile="mobile" v-if="moduleMeta" v-model="selected" v-model:page="page" v-model:items-per-page="itemsPerPage"

        v-model:sort-by="sortBy" :headers="headers" :items="items" :items-length="total" :loading="loading"
        :search="search" :item-value="itemValue" :return-object="true" show-select hover density="comfortable"
        @update:options="loadItems" @click:row="onRowClick">
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
          <!-- <span v-if="item?.username" class="text-primary font-weight-medium">
            {{ item.username }}
          </span>
          <span v-else>—</span> -->

          <v-chip
            variant="tonal"
          >
            <!-- <v-avatar start>
              <v-img :src="item.avatar"></v-img>
            </v-avatar> -->
            <v-avatar start :variant="item?.avatar?'': 'tonal'">
                <v-img v-if="item.avatar" :src="item.avatar" cover />
                <span v-else class="text-body-2 font-weight-bold text-primary">{{ item.username.charAt(0).toUpperCase() }}</span>
            </v-avatar>

            {{ item.username }}
          </v-chip>

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
        <template v-for="name in boolSlotNames" :key="name" v-slot:[name]="{ item }">
          <div class="d-flex justify-center">
            <v-chip v-if="item[slotKey(name)]" color="success" size="small" label>{{ $t('admin.yes')
            }}</v-chip>
            <v-chip v-else color="grey" size="small" label variant="outlined">{{ $t('admin.no') }}</v-chip>
          </div>
        </template>
        <template #[`item.replyable_username`]="{ item }">
          <span v-if="item?.replyable_username" class="text-primary font-weight-medium">
            {{ item.replyable_username }}
          </span>
          <span v-else>—</span>
        </template>
        <template #[`item.report_handle_state`]="{ item }">
          <v-chip :color="reportState(item?.report_handle_state).color" size="small" label>
            {{ $t(reportState(item?.report_handle_state).label) }}
          </v-chip>
        </template>


        <template #[`item.create_location`]="{ item }">
          <span class="text-no-wrap">{{ item?.create_location }}</span>
        </template>
        <template #[`item.last_login_location`]="{ item }">
          <span class="text-no-wrap">{{ item?.last_login_location }}</span>
        </template>
        <template #[`item.location`]="{ item }">
          <span class="text-no-wrap">{{ item?.location }}</span>
        </template>

        <template #[`item.create_time`]="{ item }">
          <span class="text-no-wrap">{{ formatDate(item?.create_time) }}</span>
        </template>
        <template #[`item.update_time`]="{ item }">
          <span class="text-no-wrap">{{ formatDate(item?.update_time) }}</span>
        </template>
        <template #[`item.disable_time`]="{ item }">
          <span class="text-no-wrap">{{ formatDate(item?.disable_time) }}</span>
        </template>
        <template #[`item.disable_until`]="{ item }">
          <span class="text-no-wrap">{{ formatDate(item?.disable_until) }}</span>
        </template>
        <template #[`item.last_login_time`]="{ item }">
          <span class="text-no-wrap">{{ formatDate(item?.last_login_time) }}</span>
        </template>
        <template #[`item.user_group_icon`]="{ item }">
          <v-icon v-if="item?.user_group_icon?.startsWith('mdi-')">{{ item.user_group_icon }}</v-icon>
          <span v-else>{{ item?.user_group_icon || '—' }}</span>
        </template>
        <template #[`item.user_group_color`]="{ item }">
          <div class="d-flex align-center justify-center" style="gap: 6px;">
            <v-avatar v-if="item?.user_group_color" :color="item.user_group_color" size="18" class="color-swatch" />
            <span v-if="item?.user_group_color" class="text-caption text-medium-emphasis">{{
              item.user_group_color }}</span>
            <span v-else>—</span>
          </div>
        </template>
        <template #[`item.description`]="{ item }">
          <div class="text-truncate" style="max-width: 240px;">
            {{ item?.description || '—' }}
          </div>
        </template>
        <template v-for="name in truncateSlotNames" :key="name" v-slot:[name]="{ item }">
          <div class="text-truncate" style="max-width: 220px;">
            {{ item[slotKey(name)] || '—' }}
          </div>
        </template>
        <template #[`item.actions`]="{ item }">
          <div class="d-flex justify-end" style="gap: 4px;">
            <v-btn v-if="moduleMeta?.itemTo && moduleMeta.itemTo(item || {})" icon size="small"
              variant="text"
              @click.stop="openInNewTab(moduleMeta.itemTo(item || {}))">
              <v-icon>mdi-open-in-new</v-icon>
            <v-tooltip
        activator="parent"
      >{{$t('admin.actions.viewOnSite')}}</v-tooltip>
            </v-btn>
            <v-btn v-if="moduleEditable" icon="mdi-pencil" size="small" variant="text" color="primary"
              :title="$t('common.edit')" @click.stop="openEditDialog(item)">
              <v-icon>mdi-pencil</v-icon>
            <v-tooltip
        activator="parent"
      >{{$t('common.edit')}}</v-tooltip>
            </v-btn>
            <v-btn v-if="moduleName === 'users'" icon
              size="small" variant="text" :color="item?.is_disabled ? 'success' : 'warning'"
              :title="$t(item?.is_disabled ? 'admin.enableUserAction' : 'admin.disableUserAction')"
              @click.stop="toggleUserDisabled(item)">
              <v-icon>{{ item?.is_disabled ? 'mdi-account-check' : 'mdi-account-cancel' }}</v-icon>
            <v-tooltip
        activator="parent"
      >{{$t(item?.is_disabled ? 'admin.enableUserAction' : 'admin.disableUserAction')}}</v-tooltip>
            </v-btn>
            <v-btn v-else-if="moduleDeletable" size="small" variant="text" color="error"
              :title="$t('common.delete')" @click.stop="openDeleteDialog(item)"  >
              <v-icon>mdi-delete</v-icon>
            <v-tooltip
        activator="parent"
      >{{$t('common.delete')}}</v-tooltip>
            </v-btn>
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
    <TopicDialog v-model="topicDialogVisible" :topic="topicEditItem" api-base="/admin/modules/topics"
      @submit="onTopicSaved" />

    <!-- 用户组编辑对话框（user-groups 模块专用） -->
    <UserGroupDialog v-model="userGroupDialogVisible" :user-group="userGroupEditItem" @submit="onUserGroupSaved" />

    <!-- 举报处理对话框（reports 模块专用） -->
    <ReportHandleDialog v-model="reportDialogVisible" :report="reportEditItem" @submit="onReportHandled" />

    <v-dialog v-model="bulkUserGroupDialog" max-width="420">
      <v-card rounded="lg">
        <!-- <v-card-title class="d-flex align-center justify-space-between">
          <span>{{ $t('admin.bulkChangeUserGroup') }}</span>
          <v-btn icon variant="text" @click="bulkUserGroupDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title> -->

        <v-toolbar :title="$t('admin.bulkChangeUserGroup')">
          <template v-slot:prepend>
            <v-btn icon variant="text" @click="bulkUserGroupDialog = false" >
              <v-icon>
                mdi-close
              </v-icon>
              <v-tooltip activator="parent" location="right">
                {{ $t('common.cancel') }}
              </v-tooltip>
            </v-btn>
          </template>
          <template #append>
            <v-btn icon variant="text" @click="applyBulkUserGroup" :disabled="!selectedUserGroupId" >
              <v-icon>
                mdi-check
              </v-icon>
              <v-tooltip activator="parent" location="left">
                {{ $t('common.submit') }}
              </v-tooltip>
            </v-btn>
          </template>
        </v-toolbar>

        <v-card-text>
          <v-select
            v-model="selectedUserGroupId"
            :items="userGroupOptions"
            item-title="user_group_name"
            item-value="user_group_id"
            :label="$t('admin.userGroup')"
            variant="outlined"
            density="comfortable"
          />
        </v-card-text>
        <!-- <v-card-actions class="justify-end">
          <v-btn variant="text" @click="bulkUserGroupDialog = false">{{ $t('common.cancel') }}</v-btn>
          <v-btn color="primary" :disabled="!selectedUserGroupId" @click="applyBulkUserGroup">
            {{ $t('common.confirm') }}
          </v-btn>
        </v-card-actions> -->
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.admin-module-page {
  min-height: 100%;
}

.data-table-card {
  /* border-radius: 12px; */
  overflow: hidden;
}

.color-swatch {
  border: 1px solid rgba(127, 127, 127, 0.4);
}
</style>

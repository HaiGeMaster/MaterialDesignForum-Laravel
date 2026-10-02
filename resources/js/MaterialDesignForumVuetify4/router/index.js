import { watch } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { i18n } from '../i18n'

import { siteName } from '../themeConfig'

const routes = [
  { path: '/', name: 'home', component: () => import('../views/HomeView.vue'), meta: { titleKey: 'route.home', maxWidth: 1080 } },
  { path: '/topics', name: 'topics', component: () => import('../views/TopicsView.vue'), meta: { titleKey: 'route.topics', maxWidth: 1080 } },
  { path: '/topics/:topic_id', name: 'topic-detail', component: () => import('../views/TopicDetailView.vue'), meta: { titleKey: 'route.topicDetail', maxWidth: 860 } },
  { path: '/questions', name: 'questions', component: () => import('../views/QuestionsView.vue'), meta: { titleKey: 'route.questions', maxWidth: 860 } },
  { path: '/questions/:question_id', name: 'question-detail', component: () => import('../views/QuestionDetailView.vue'), meta: { titleKey: 'route.questionDetail', maxWidth: 860 } },
  
  { path: '/questions/:question_id/answers/:answer_id', name: 'question-answer-detail', component: () => import('../views/QuestionDetailView.vue'), meta: { titleKey: 'route.questionDetail', maxWidth: 860 } },
  
  { path: '/articles', name: 'articles', component: () => import('../views/ArticlesView.vue'), meta: { titleKey: 'route.articles', maxWidth: 860 } },
  { path: '/articles/:article_id', name: 'article-detail', component: () => import('../views/ArticleDetailView.vue'), meta: { titleKey: 'route.articleDetail', maxWidth: 860 } },
  { path: '/login', name: 'login', component: () => import('../views/LoginView.vue'), meta: { titleKey: 'route.login' } },
  { path: '/users/:user_id', name: 'user', component: () => import('../views/ProfileView.vue'), meta: { titleKey: 'route.user', maxWidth: 860 } },
  { path: '/users', name: 'users', component: () => import('../views/PeopleView.vue'), meta: { titleKey: 'route.users', maxWidth: 1080 } },
  { path: '/notifications', name: 'notifications', component: () => import('../views/NotificationsView.vue'), meta: { titleKey: 'route.notifications', maxWidth: 860 } },
  { path: '/notifications/setting', name: 'notification-setting', component: () => import('../views/NotificationSettingView.vue'), meta: { titleKey: 'route.notificationSetting', maxWidth: 860 } },
  { path: '/oauth/callback', name: 'oauth-callback', component: () => import('../views/OAuthCallbackView.vue'), meta: { titleKey: 'route.oauthCallback' } },
  { path: '/install', name: 'install', component: () => import('../views/InstallView.vue'), meta: { titleKey: 'route.install' } },
  { path: '/settings', name: 'settings', component: () => import('../views/SettingsView.vue'), meta: { titleKey: 'route.settings', requiresAuth: true, maxWidth: 860 } },
  { path: '/media', name: 'media-library', component: () => import('../views/MediaLibraryView.vue'), meta: { titleKey: 'route.mediaLibrary', maxWidth: 1080, requiresAuth: true } },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../views/NotFoundView.vue'), meta: { titleKey: 'route.notFound', maxWidth: 860 } },
  // { path: '/sign-in', name: 'sign-in', component: () => import('../views/SignInView.vue'), meta: { titleKey: 'route.signIn', requiresAuth: true, maxWidth: 860 } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 站内路径校验：仅接受以 / 开头且非 // 的路径（避免开放重定向）
function safePath(value) {
  return typeof value === 'string' && value.startsWith('/') && !value.startsWith('//') ? value : '/'
}

// 存储 loading 回调函数
let loadingCallback = null

// 暴露注册回调的方法
router.setLoadingCallback = (callback) => {
  loadingCallback = callback
}

// 登录守卫：requiresAuth 路由未登录时重定向到独立登录页
router.beforeEach(async (to) => {
  if (loadingCallback) loadingCallback(true)

  const authStore = useAuthStore()

  // 已登录用户访问登录页：跳回原目标或首页
  if (to.name === 'login' && authStore.isLoggedIn) {
    return { path: safePath(to.query.redirect) }
  }

  // 需要登录的路由（如 /settings）
  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  // 个人主页 /users/me 需要登录（他人主页 /users/:id 未登录仍可查看）
  if (to.params.user_id === 'me' && !authStore.isLoggedIn) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
})

// 当前路由的标题键（供 siteName / 语言切换后重刷标题使用）
let currentTitleKey = null

function applyDocumentTitle() {
  const title = currentTitleKey ? i18n.global.t(currentTitleKey) : ''
  document.title = title ? `${title} · ${siteName.value}` : siteName.value
}

router.afterEach((to) => {
  currentTitleKey = to.meta.titleKey || null
  applyDocumentTitle()

  
  if (loadingCallback) loadingCallback(false)
})

// siteName 由 /theme 接口异步加载，首次导航可能早于其就绪；到位后重刷当前页标题
watch(siteName, applyDocumentTitle)

// 切换语言时同步刷新标题
watch(i18n.global.locale, applyDocumentTitle)


export default router

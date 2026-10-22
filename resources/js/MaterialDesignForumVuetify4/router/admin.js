import { watch } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AdminView from '../views/AdminView.vue'
import AdminModuleView from '../views/AdminModuleView.vue'
import AdminOptionsView from '../views/AdminOptionsView.vue'
import AdminOptionInfoView from '../views/AdminOptionInfoView.vue'
import AdminOptionThemeView from '../views/AdminOptionThemeView.vue'
import AdminOptionMailView from '../views/AdminOptionMailView.vue'
import AdminOptionOAuthView from '../views/AdminOptionOAuthView.vue'
import { siteName } from '../themeConfig'
import { i18n } from '../i18n'

const adminRouter = createRouter({
  history: createWebHistory('/admin/'),
  routes: [
    { path: '/', name: 'admin-dashboard', component: AdminView, meta: { titleKey: 'route.admin' } },
    { path: '/options', name: 'admin-options', component: AdminOptionsView, meta: { titleKey: 'route.admin' } },
    { path: '/options/info', name: 'admin-option-info', component: AdminOptionInfoView, meta: { titleKey: 'route.admin' } },
    { path: '/options/theme', name: 'admin-option-theme', component: AdminOptionThemeView, meta: { titleKey: 'route.admin' } },
    { path: '/options/mail', name: 'admin-option-mail', component: AdminOptionMailView, meta: { titleKey: 'route.admin' } },
    { path: '/options/oauth', name: 'admin-option-oauth', component: AdminOptionOAuthView, meta: { titleKey: 'route.admin' } },
    { path: '/:module(topics|questions|answers|articles|comments|replies|user-groups|users|reports|options)', name: 'admin-module', component: AdminModuleView, meta: { titleKey: 'route.admin' } },
  ],
})

const modulePermissions = {
  topics: 'ability_admin_manage_topic',
  questions: 'ability_admin_manage_question',
  answers: 'ability_admin_manage_answer',
  articles: 'ability_admin_manage_article',
  comments: 'ability_admin_manage_comment',
  replies: 'ability_admin_manage_reply',
  'user-groups': 'ability_admin_manage_user_group',
  users: 'ability_admin_manage_user',
  reports: 'ability_admin_manage_report',
  options: 'ability_admin_manage_option',
}

// 存储 loading 回调函数
let loadingCallback = null

// 暴露注册回调的方法
adminRouter.setLoadingCallback = (callback) => {
  loadingCallback = callback
}
adminRouter.beforeEach(async (to) => {
  if (loadingCallback) loadingCallback(true)
  const authStore = useAuthStore()

  if (!authStore.isLoggedIn) {
    window.location.assign('/login?redirect=%2Fadmin%2F')
    return false
  }

  if (!authStore.user) {
    await authStore.fetchMe()
  }

  if (!authStore.canAccessAdmin) {
    window.location.assign('/')
    return false
  }

  const permission = to.path.startsWith('/options')
    ? 'ability_admin_manage_option'
    : modulePermissions[to.params.module]
  const userGroup = authStore.user?.user_group || {}
  if (permission && !userGroup.is_admin && userGroup[permission] !== true) {
    return { name: 'admin-dashboard' }
  }

  return true
})

// 当前路由的标题键（供 siteName / 语言切换后重刷标题使用）
let currentTitleKey = null

function applyDocumentTitle() {
  const title = currentTitleKey ? i18n.global.t(currentTitleKey) : ''
  document.title = title ? `${title} · ${siteName.value}` : (siteName.value || 'Admin')
}

adminRouter.afterEach((to) => {
  currentTitleKey = to.meta.titleKey || null
  applyDocumentTitle()


  if (loadingCallback) loadingCallback(false)
})

// siteName 由 /theme 接口异步加载，首次导航可能早于其就绪；到位后重刷当前页标题
watch(siteName, applyDocumentTitle)

// 切换语言时同步刷新标题
watch(i18n.global.locale, applyDocumentTitle)

export default adminRouter

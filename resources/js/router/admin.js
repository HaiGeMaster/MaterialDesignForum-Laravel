import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AdminView from '../views/AdminView.vue'
import AdminModuleView from '../views/AdminModuleView.vue'
import AdminOptionsView from '../views/AdminOptionsView.vue'
import AdminOptionDetailView from '../views/AdminOptionDetailView.vue'

const adminRouter = createRouter({
  history: createWebHistory('/admin/'),
  routes: [
    { path: '/', name: 'admin-dashboard', component: AdminView },
    { path: '/options', name: 'admin-options', component: AdminOptionsView },
    { path: '/options/:setting(info|theme|mail|oauth)', name: 'admin-option-detail', component: AdminOptionDetailView },
    { path: '/:module(topics|questions|answers|articles|comments|replies|user-groups|users|reports|options)', name: 'admin-module', component: AdminModuleView },
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

adminRouter.beforeEach(async (to) => {
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

adminRouter.afterEach(() => {
  document.title = 'Admin · MDForum'
})

export default adminRouter

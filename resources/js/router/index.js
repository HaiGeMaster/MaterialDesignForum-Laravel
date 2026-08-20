import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { i18n } from '../i18n'

const routes = [
  { path: '/', name: 'home', component: () => import('../views/HomeView.vue'), meta: { titleKey: 'route.home', maxWidth: 1080 } },
  { path: '/topics', name: 'topics', component: () => import('../views/TopicsView.vue'), meta: { titleKey: 'route.topics', maxWidth: 1080 } },
  { path: '/topics/:topic_id', name: 'topic-detail', component: () => import('../views/TopicDetailView.vue'), meta: { titleKey: 'route.topicDetail', maxWidth: 860 } },
  { path: '/questions', name: 'questions', component: () => import('../views/QuestionsView.vue'), meta: { titleKey: 'route.questions', maxWidth: 860 } },
  { path: '/questions/:question_id', name: 'question-detail', component: () => import('../views/QuestionDetailView.vue'), meta: { titleKey: 'route.questionDetail', maxWidth: 860 } },
  { path: '/articles', name: 'articles', component: () => import('../views/ArticlesView.vue'), meta: { titleKey: 'route.articles', maxWidth: 860 } },
  { path: '/articles/:article_id', name: 'article-detail', component: () => import('../views/ArticleDetailView.vue'), meta: { titleKey: 'route.articleDetail', maxWidth: 860 } },
  { path: '/users/:user_id', name: 'user', component: () => import('../views/ProfileView.vue'), meta: { titleKey: 'route.user', maxWidth: 860 } },
  { path: '/users', name: 'users', component: () => import('../views/PeopleView.vue'), meta: { titleKey: 'route.users', maxWidth: 1080 } },
  { path: '/notifications', name: 'notifications', component: () => import('../views/NotificationsView.vue'), meta: { titleKey: 'route.notifications', maxWidth: 1080 } },
  { path: '/notifications/setting', name: 'notification-setting', component: () => import('../views/NotificationSettingView.vue'), meta: { titleKey: 'route.notificationSetting', maxWidth: 1080 } },
  { path: '/oauth/callback', name: 'oauth-callback', component: () => import('../views/OAuthCallbackView.vue'), meta: { titleKey: 'route.oauthCallback' } },
  { path: '/settings', name: 'settings', component: () => import('../views/SettingsView.vue'), meta: { titleKey: 'route.settings', requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 登录守卫：requiresAuth 路由未登录时重定向到登录页
router.beforeEach(async (to) => {
  if (to.meta.requiresAuth) {
    const authStore = useAuthStore()
    if (!authStore.isLoggedIn) {
      return { path: '/users/me', query: { redirect: to.fullPath } }
    }
  }

})

router.afterEach((to) => {
  const title = to.meta.titleKey ? i18n.global.t(to.meta.titleKey) : ''
  document.title = title ? `${title} · MDForum` : 'MDForum'
})

export default router

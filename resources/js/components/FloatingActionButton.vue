<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import CommentSheet from './CommentSheet.vue'
import TopicDialog from './TopicDialog.vue'
import UserGroupDialog from './UserGroupDialog.vue'
import { useEditorWindowStore } from '../stores/editorWindow'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { show } = useFeedback()
const { t } = useI18n()

// 移动端断点与 Vuetify 一致（<600px）：手机上仅显示圆形图标按钮
const MOBILE_QUERY = '(max-width: 600px)'
const isMobile = ref(false)
let mql = null
function updateMobile(e) {
  isMobile.value = e.matches
}
onMounted(() => {
  mql = window.matchMedia(MOBILE_QUERY)
  isMobile.value = mql.matches
  mql.addEventListener('change', updateMobile)
})
onBeforeUnmount(() => {
  mql?.removeEventListener('change', updateMobile)
})

const topicDialog = ref(false)
const userGroupDialog = ref(false)
const commentSheet = ref(false)
const editorWindow = useEditorWindowStore()

// 各页面匹配的文字 / 图标 / 功能接入状态
const pageConfig = {
  topics: {
    labelKey: 'floatingAction.newTopic',
    icon: 'mdi-tag-plus-outline',
  },
  questions: {
    labelKey: 'floatingAction.newQuestion',
    icon: 'mdi-comment-question-outline',
  },
  articles: {
    labelKey: 'floatingAction.newArticle',
    icon: 'mdi-file-document-plus-outline',
  },
  // 问题详情页：悬浮按钮变为「写回答」
  'question-detail': {
    labelKey: 'questionDetail.writeAnswer',
    icon: 'mdi-message-reply-outline',
  },
  // 文章详情页：悬浮按钮变为「写评论」（底部输入框）
  'article-detail': {
    labelKey: 'floatingAction.writeComment',
    icon: 'mdi-comment-plus-outline',
  },
  // 管理员用户组管理页：悬浮按钮变为「新建用户组」
  'admin-user-groups': {
    labelKey: 'admin.newUserGroup',
    icon: 'mdi-account-plus-outline',
  },
}

// 话题 / 问题 / 文章列表页、问题详情页、管理员用户组页显示
const current = computed(() => {
  if (route.name === 'admin-module' && route.params.module === 'user-groups') {
    return pageConfig['admin-user-groups']
  }
  return pageConfig[route.name]
})
const visible = computed(() => Boolean(current.value))

// 点击：未登录先引导登录；按页面打开对应创建入口
function handleClick() {
  if (!authStore.isLoggedIn) {
    show(t('floatingAction.loginFirst'), 'info')
    router.push('/users/me')
    return
  }
  if (route.name === 'topics') {
    topicDialog.value = true
  } else if (route.name === 'questions') {
    editorWindow.openEditor({ mode: 'question', action: 'create' })
  } else if (route.name === 'articles') {
    editorWindow.openEditor({ mode: 'article', action: 'create' })
  } else if (route.name === 'question-detail') {
    // 问题详情页：打开编辑器写回答
    editorWindow.openEditor({
      mode: 'answer',
      action: 'create',
      questionId: Number(route.params.question_id),
    })
  } else if (route.name === 'article-detail') {
    // 文章详情页：打开底部评论输入框
    commentSheet.value = true
  } else if (route.name === 'admin-module' && route.params.module === 'user-groups') {
    // 管理员用户组管理页：打开新建用户组对话框
    userGroupDialog.value = true
  }
}

// 话题创建成功：跳转到新话题详情页
function onTopicCreated(data) {
  topicDialog.value = false
  router.push(`/topics/${data.topic_id}`)
}

// 用户组创建成功：刷新当前页数据（AdminModuleView 监听 window 事件）
function onUserGroupCreated(data) {
  userGroupDialog.value = false
  window.dispatchEvent(new CustomEvent('user-group:created', { detail: data }))
}
</script>

<template>
  <!-- Teleport 到 body：脱离 v-app / v-main__scroller 的层叠上下文，
       避免全屏滚动容器覆盖按钮导致无法点击 -->
  <Teleport to="body">
    <div v-if="visible" class="fab">
      <v-btn
        class="fab__main"
        color="primary"
        elevation="4"
        size="x-large"
        rounded="pill"
        :icon="isMobile"
        :title="$t(current.labelKey)"
        @click="handleClick"
      >
        <v-icon :icon="current.icon" />
        <span v-if="!isMobile" class="fab__label">{{ $t(current.labelKey) }}</span>
      </v-btn>

      <!-- 新建话题对话框（仅话题页使用） -->
      <TopicDialog v-model="topicDialog" @submit="onTopicCreated" />

      <!-- 新建用户组对话框（管理员用户组管理页使用） -->
      <UserGroupDialog v-model="userGroupDialog" @submit="onUserGroupCreated" />

      <!-- 文章详情页：底部评论输入框 -->
      <CommentSheet
        v-model="commentSheet"
        commentable-type="article"
        :commentable-id="Number(route.params.article_id)"
      />
    </div>
  </Teleport>
</template>

<style scoped>
/* 固定在视口右下角，边距 16px；层级高于应用栏（1004）但低于悬浮窗口（1900+） */
.fab {
  position: fixed;
  right: 16px;
  bottom: 16px;
  z-index: 1050;
}

/* .fab__main {
  transition: transform 0.15s ease;
}

.fab__main:hover {
  transform: scale(1.05);
} */

.fab__label {
  margin-left: 8px;
  font-weight: 600;
}
</style>

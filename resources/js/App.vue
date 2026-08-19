<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from './stores/auth'
import { useFeedback } from './composables/useFeedback'
import AppBar from './components/AppBar.vue'
import AppDrawer from './components/AppDrawer.vue'
import RichTextEditorWindow from './components/RichTextEditorWindow.vue'
import FloatingActionButton from './components/FloatingActionButton.vue'
import UsersDialog from './components/UsersDialog.vue'
import CommentReplySheet from './components/CommentReplySheet.vue'
import CommentReplyEditDialog from './components/CommentReplyEditDialog.vue'
import CommentDialog from './components/CommentDialog.vue'
import DeleteDialog from './components/DeleteDialog.vue'
import ProfileEditDialog from './components/ProfileEditDialog.vue'

const authStore = useAuthStore()
const { visible, text, color } = useFeedback()
const { t } = useI18n()

const drawer = ref(true)

// 发布成功后广播全局事件，列表页 / 详情页可监听刷新
function handleContentPublished(payload) {
  window.dispatchEvent(new CustomEvent('content:published', { detail: payload }))
}

function handleAuthUnauthorized() {
  authStore.setUser(null)
  useFeedback().show(t('app.sessionExpired'), 'warning')
}

onMounted(() => {
  authStore.fetchMe()
  window.addEventListener('auth:unauthorized', handleAuthUnauthorized)
})

onBeforeUnmount(() => {
  window.removeEventListener('auth:unauthorized', handleAuthUnauthorized)
})
</script>

<style scoped>
/* 应用锁定为视口高度，内容超高时由 v-main 内部滚动，避免整页被撑高 */
.app-shell {
  height: 100vh;
  height: 100dvh;
  overflow: hidden;
}
</style>

<template>
  <v-app class="app-shell">
    <!-- 锁定视口高度，滚动只发生在 v-main 内容区 -->
    <v-layout class="v-layout--full-height">
      <AppBar @toggle-drawer="drawer = !drawer" />

      <AppDrawer v-model="drawer" />

      <v-main scrollable>
        <router-view />
      </v-main>
    </v-layout>

    <v-snackbar v-model="visible" :color="color"  timeout="3000">
      {{ text }}
    </v-snackbar>

    <!-- 悬浮窗口：富文本编辑器（写文章 / 写提问 / 写回答，及编辑） -->
    <RichTextEditorWindow
      :width="420"
      :height="520"
      @published="handleContentPublished"
    />

    <!-- 右下角悬浮发布按钮 -->
    <FloatingActionButton />

    <!-- 用户浏览对话框：查看某对象粉丝 / 某用户关注的人 -->
    <UsersDialog />

    <!-- 全局回复底部输入框：评论 / 回复均可回复，支持回复对回复 -->
    <CommentReplySheet />

    <!-- 全局评论 / 回复编辑对话框：更多操作中的“编辑”打开 -->
    <CommentReplyEditDialog />

    <!-- 全局删除确认对话框：任意删除操作统一调用 -->
    <DeleteDialog />

    <!-- 全局评论列表对话框：问题 / 回答等任意场景统一调用 -->
    <CommentDialog />

    <!-- 全局个人资料编辑对话框：任意场景统一调用 -->
    <ProfileEditDialog />
  </v-app>
</template>

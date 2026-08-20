<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useAuthStore } from './stores/auth'
import { useFeedback } from './composables/useFeedback'
import AdminAppBar from './components/AdminAppBar.vue'
import AdminDrawer from './components/AdminDrawer.vue'
import RichTextEditorWindow from './components/RichTextEditorWindow.vue'
import CommentReplyEditDialog from './components/CommentReplyEditDialog.vue'
import DeleteDialog from './components/DeleteDialog.vue'
import ProfileEditDialog from './components/ProfileEditDialog.vue'
import FloatingActionButton from './components/FloatingActionButton.vue'

const authStore = useAuthStore()
const { visible, text, color } = useFeedback()
const drawer = ref(true)

function toggleDrawer() {
  drawer.value = !drawer.value
}

// 富文本编辑器发布成功后广播事件，AdminModuleView 监听后刷新表格
function handleContentPublished(payload) {
  window.dispatchEvent(new CustomEvent('content:published', { detail: payload }))
}

function handleUnauthorized() {
  authStore.setUser(null)
  window.location.assign('/users/me?redirect=%2Fadmin%2F')
}

onMounted(() => {
  window.addEventListener('auth:unauthorized', handleUnauthorized)
})

onBeforeUnmount(() => {
  window.removeEventListener('auth:unauthorized', handleUnauthorized)
})
</script>

<template>
  <v-app>
    <v-layout class="admin-shell">
      <AdminAppBar @toggle-drawer="drawer = !drawer" />
      <AdminDrawer v-model="drawer" />

      <v-main scrollable>
        <router-view />
      </v-main>
    </v-layout>

    <v-snackbar v-model="visible" :color="color" timeout="3000">
      {{ text }}
    </v-snackbar>

    <!-- 全局对话框：管理员后台编辑/删除各模块数据时调用 -->
    <!-- 富文本编辑悬浮窗：编辑提问 / 回答 / 文章 -->
    <RichTextEditorWindow :width="420" :height="520" @published="handleContentPublished" />

    <!-- 评论 / 回复编辑对话框 -->
    <CommentReplyEditDialog />

    <!-- 删除确认对话框 -->
    <DeleteDialog />

    <!-- 用户资料编辑对话框 -->
    <ProfileEditDialog />

    <!-- 右下角悬浮按钮：管理员用户组页创建用户组 -->
    <FloatingActionButton />
  </v-app>
</template>

<style scoped>
.admin-shell {
  height: 100vh;
  height: 100dvh;
  overflow: hidden;
}
</style>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useFeedback } from './composables/useFeedback'
import http from './api'
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

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { visible, text, color } = useFeedback()
const { t } = useI18n()

const drawer = ref(true)

// 安装向导页：隐藏左侧抽屉、顶部应用栏等非必要外壳，让向导占满整屏
const isInstallPage = computed(() => route.name === 'install')

// 发布成功后广播全局事件，列表页 / 详情页可监听刷新
function handleContentPublished(payload) {
  window.dispatchEvent(new CustomEvent('content:published', { detail: payload }))
}

function handleAuthUnauthorized() {
  authStore.setUser(null)
  useFeedback().show(t('app.sessionExpired'), 'warning')
}

onMounted(async () => {
  // 首次加载检查安装状态：未安装则跳转 /install；访问 /install 路由时不跳转避免循环
    try {
        // setWatermark()

    const { data } = await http.get('/install/status')
    if (!data.installed && route.name !== 'install') {
      router.replace('/install')
      return
    }
    // 已安装：如果用户当前在 /install 路由上，跳回首页
    if (data.installed && route.name === 'install') {
      router.replace('/')
      return
    }
  } catch {
    // status 接口失败时不阻塞，继续走下面的 fetchMe（让用户看到正常界面或后续报错）
  }

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

<style>
/* Poppins 字体：全局引入并作为默认字体。
   通过覆盖 Vuetify 的 --font-family 变量全局生效，
   避免用 * !important 误伤 MDI 图标字体和代码块等专用字体。 */
@font-face {
  font-family: 'Poppins';
  src: url('../css/Poppins-Regular.ttf') format('truetype');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}

:root {
  --font-family: 'Poppins', 'PingFang SC', 'Microsoft YaHei', 'Helvetica Neue', Arial,
    sans-serif;
}

body,
.v-application {
  font-family: var(--font-family);
}
</style>

<template>
  <v-app class="app-shell">
    <!-- 锁定视口高度，滚动只发生在 v-main 内容区 -->
    <v-layout class="v-layout--full-height">
      <AppBar v-if="!isInstallPage" @toggle-drawer="drawer = !drawer" />

      <AppDrawer v-if="!isInstallPage" v-model="drawer" />

      <v-main scrollable>
        <router-view />
      </v-main>
    </v-layout>

    <v-snackbar v-model="visible" :color="color"  timeout="3000">
      {{ text }}
    </v-snackbar>

    <!-- 安装向导页：隐藏所有内容创作 / 互动相关浮窗，保持页面纯净 -->
    <template v-if="!isInstallPage">
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
    </template>
  </v-app>
</template>

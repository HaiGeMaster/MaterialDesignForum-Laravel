<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import MoreActions from './MoreActions.vue'
import UserMiniCard from './UserMiniCard.vue'
import VoteButton from './VoteButton.vue'
import { useAuthStore } from '../stores/auth'
import { useReplySheetStore } from '../stores/replySheet'
import { useDeleteDialogStore } from '../stores/deleteDialog'
import { useFeedback } from '../composables/useFeedback'

const props = defineProps({
  reply: { type: Object, required: true },
  /** 所属评论 id（用于打开回复框 / 删除后由父组件移除并更新计数） */
  commentId: { type: [Number, String], required: true },
})
const emit = defineEmits(['deleted'])

const { t } = useI18n()
const authStore = useAuthStore()
const replySheet = useReplySheetStore()
const deleteDialog = useDeleteDialogStore()
const { show } = useFeedback()

const author = computed(() => props.reply.creator || {})
const authorInitial = computed(() => (author.value.username || '?').slice(0, 1).toUpperCase())
const targetUser = computed(() => props.reply.replyable_user || null)
// 回复对回复（replyable_type === 'reply'）时展示"回复 @对方"
const showReplyTarget = computed(() => props.reply.replyable_type === 'reply' && targetUser.value)

// 旧布局：时间旁显示用户上次登录位置（有则显示）
const locationText = computed(() => {
  const loc = author.value.last_login_location
  return loc && String(loc).trim() !== '' ? ` · ${loc}` : ''
})

function timeText(time) {
  return time ? String(time).slice(0, 16).replace('T', ' ') : ''
}

function openReply() {
  if (!authStore.isLoggedIn) {
    show(t('commentSheet.loginFirst'), 'info')
    return
  }
  replySheet.open({
    replyableId: props.reply.reply_id,
    replyableType: 'reply',
    replyableCommentId: props.commentId,
    replyableUserId: props.reply.user_id,
    target: {
      username: author.value.username,
      avatar: author.value.avatar,
      content: props.reply.content,
    },
  })
}

function askDelete() {
  deleteDialog.open({
    title: t('commentSection.deleteReplyTitle'),
    text: t('commentSection.deleteReplyConfirm'),
    onConfirm: confirmDelete,
  })
}

async function confirmDelete() {
  await http.delete(`/replies/${props.reply.reply_id}`)
  show(t('commentSection.replyDeleted'), 'success')
  emit('deleted')
}

// 编辑完成：用后端返回的最新数据覆盖本地对象（保持引用，触发响应式更新）
function handleReplyEdited(e) {
  const updated = e.detail
  if (!updated || String(updated.reply_id) !== String(props.reply.reply_id)) return
  Object.assign(props.reply, updated)
}

onMounted(() => window.addEventListener('reply:edited', handleReplyEdited))
onBeforeUnmount(() => window.removeEventListener('reply:edited', handleReplyEdited))
</script>

<template>
  <!-- 参考旧项目布局：v-list-item 结构（头像 / 用户名(+回复对象) / 内容 / 操作区） -->
  <v-list-item
    rounded="lg"
    active-class="text-primary"
    class="py-3 reply-item"
    @click="openReply"
  >
    <!-- 头像：点击弹出用户小卡片 -->
    <template #prepend>
      <v-menu
        v-if="author.user_id"
        :close-on-content-click="false"
        offset="16"
      >
        <template #activator="{ props: menuProps }">
          <v-avatar
            size="36"
            color="primary"
            
            class="cursor-pointer"
            v-bind="menuProps"
            @click.stop="menuProps.onClick?.($event)"
          >
            <v-img v-if="author.avatar" :src="author.avatar" cover />
            <span v-else class="text-body-2 font-weight-bold text-primary">{{ authorInitial }}</span>
          </v-avatar>
        </template>
        <UserMiniCard :user="author" />
      </v-menu>
      <v-avatar v-else size="36" color="grey" >
        <span class="text-body-2 font-weight-bold">?</span>
      </v-avatar>
    </template>

    <!-- 标题：用户名（回复对回复时附带"回复 @对方"） -->
    <v-list-item-title>
      {{ author.username }}
      <template v-if="showReplyTarget">
        <span class="text-grey"> {{ t('replyItem.replyTo') }} </span>
        <router-link
          :to="targetUser.user_id ? `/users/${targetUser.user_id}` : undefined"
          class="text-primary font-weight-medium text-decoration-none"
          @click.stop
        >
          @{{ targetUser.username }}
        </router-link>
      </template>
    </v-list-item-title>

    <!-- 内容 -->
    <v-list-item-subtitle class="mb-1 text-high-emphasis opacity-100">
      <div
        class="text-body-2 reply-item__content"
        style="white-space: pre-wrap; word-break: break-word"
      >
        {{ reply.content }}
      </div>
    </v-list-item-subtitle>

    <!-- 操作区：时间 / 投票 / 更多 -->
    <v-list-item-action>
      <v-list-item-subtitle class="text-high-emphasis">
        {{ timeText(reply.create_time) }}{{ locationText }}
      </v-list-item-subtitle>

      <v-spacer />

      <VoteButton
        type="reply"
        :item-id="reply.reply_id"
        :up-count="Number(reply.vote_up_count || 0)"
        :down-count="Number(reply.vote_down_count || 0)"
        :my-vote="reply.my_vote || null"
      />
      <MoreActions type="reply" :item="reply" @delete="askDelete" />
    </v-list-item-action>
  </v-list-item>
</template>

<style scoped>
.reply-item {
  cursor: pointer;
  transition: background-color 0.15s ease;
}
.reply-item:hover {
  background-color: rgba(128, 128, 128, 0.06);
}
.reply-item__content {
  min-width: 0;
}
</style>

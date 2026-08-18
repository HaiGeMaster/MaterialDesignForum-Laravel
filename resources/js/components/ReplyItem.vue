<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import DeleteDialog from './DeleteDialog.vue'
import MoreActions from './MoreActions.vue'
import VoteButton from './VoteButton.vue'
import { useAuthStore } from '../stores/auth'
import { useReplySheetStore } from '../stores/replySheet'
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
const { show } = useFeedback()

const deleteDialog = ref(false)
const deleting = ref(false)

const author = computed(() => props.reply.creator || {})
const authorInitial = computed(() => (author.value.username || '?').slice(0, 1).toUpperCase())
const targetUser = computed(() => props.reply.replyable_user || null)
// 回复对回复（replyable_type === 'reply'）时展示“回复 @对方”
const showReplyTarget = computed(() => props.reply.replyable_type === 'reply' && targetUser.value)

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

async function confirmDelete() {
  deleting.value = true
  try {
    await http.delete(`/replies/${props.reply.reply_id}`)
    show(t('commentSection.replyDeleted'), 'success')
    emit('deleted')
  } catch (error) {
    show(error.message, 'error')
  } finally {
    deleting.value = false
    deleteDialog.value = false
  }
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
  <div class="d-flex ga-2 px-2 py-1" @click.stop="openReply">
    <v-avatar
      size="28"
      color="primary"
      variant="tonal"
      :to="author.user_id ? `/users/${author.user_id}` : undefined"
      @click.stop
    >
      <v-img v-if="author.avatar" :src="author.avatar" cover />
      <span v-else class="text-caption font-weight-bold text-primary">{{ authorInitial }}</span>
    </v-avatar>

    <div class="grow min-w-0">
      <!-- 头部：用户名 / 回复 @对方 / 时间 -->
      <div class="d-flex align-center ga-2 flex-wrap">
        <router-link
          :to="author.user_id ? `/users/${author.user_id}` : undefined"
          class="text-subtitle-2 font-weight-medium text-primary text-decoration-none"
          @click.stop
        >
          {{ author.username }}
        </router-link>
        <span v-if="showReplyTarget" class="text-body-2 text-medium-emphasis">
          {{ $t('replyItem.replyTo') }}
          <router-link
            :to="targetUser.user_id ? `/users/${targetUser.user_id}` : undefined"
            class="text-primary font-weight-medium text-decoration-none"
            @click.stop
          >
            @{{ targetUser.username }}
          </router-link>
        </span>
        <span class="text-caption text-medium-emphasis">{{ timeText(reply.create_time) }}</span>
      </div>

      <!-- 内容 -->
      <div class="text-body-2 reply-item__content">
        <span v-if="showReplyTarget" class="text-medium-emphasis">@{{ targetUser.username }} </span>
        <span style="white-space: pre-wrap; word-break: break-word;">{{ reply.content }}</span>
      </div>

      <!-- 操作：回复 / 赞同 / 更多 -->
      <div class="d-flex align-center ga-1 mt-0.5">
        <!-- <v-btn size="x-small" variant="text" class="text-none" density="comfortable" @click.stop="openReply">
          <v-icon size="small" start>mdi-reply</v-icon>
          {{ $t('replyItem.reply') }}
        </v-btn> -->
        <VoteButton
          type="reply"
          :item-id="reply.reply_id"
          :up-count="Number(reply.vote_up_count || 0)"
          :down-count="Number(reply.vote_down_count || 0)"
          :my-vote="reply.my_vote || null"
        />
        <v-spacer />
        <MoreActions
          type="reply"
          :item="reply"
          location="top end"
          @delete="deleteDialog = true"
        />
      </div>
    </div>

    <!-- 删除确认 -->
    <DeleteDialog
      v-model="deleteDialog"
      :title="$t('commentSection.deleteReplyTitle')"
      :text="$t('commentSection.deleteReplyConfirm')"
      :loading="deleting"
      @confirm="confirmDelete"
    />
  </div>
</template>

<style scoped>
.reply-item__content {
  min-width: 0;
}
</style>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import DeleteDialog from './DeleteDialog.vue'
import InfiniteScroll from './InfiniteScroll.vue'
import MoreActions from './MoreActions.vue'
import ReplyItem from './ReplyItem.vue'
import VoteButton from './VoteButton.vue'
import { useAuthStore } from '../stores/auth'
import { useReplySheetStore } from '../stores/replySheet'
import { useFeedback } from '../composables/useFeedback'

const props = defineProps({
  comment: { type: Object, required: true },
})
const emit = defineEmits(['deleted'])

const { t } = useI18n()
const authStore = useAuthStore()
const replySheet = useReplySheetStore()
const { show } = useFeedback()

// ---- 评论主体 ----
const author = computed(() => props.comment.creator || {})
const authorInitial = computed(() => (author.value.username || '?').slice(0, 1).toUpperCase())

const deleteDialog = ref(false)
const deleting = ref(false)

function timeText(time) {
  return time ? String(time).slice(0, 16).replace('T', ' ') : ''
}

// ---- 回复列表 ----
const expanded = ref(false)
const repliesLoaded = ref(false)
const replies = ref([])
const repliesLoading = ref(false)
const repliesError = ref(false)
const repliesFinished = ref(false)
const repliesPage = ref(1)
const replyCount = ref(Number(props.comment.reply_count || 0))

// 父组件重载后同步后端计数
watch(
  () => props.comment.reply_count,
  (v) => {
    replyCount.value = Number(v || 0)
  },
)

function toggleReplies() {
  expanded.value = !expanded.value
  if (expanded.value && !repliesLoaded.value) {
    loadReplies(true)
  }
}

async function loadReplies(reset = false) {
  if (reset) {
    repliesPage.value = 1
    replies.value = []
    repliesError.value = false
    repliesFinished.value = false
  }
  repliesLoading.value = true
  try {
    const { data } = await http.get('/replies', {
      params: {
        replyable_comment_id: props.comment.comment_id,
        page: repliesPage.value,
      },
    })
    const list = data.data || []
    replies.value = reset ? list : [...replies.value, ...list]
    repliesLoaded.value = true
    repliesFinished.value = !(data.current_page < data.last_page)
    repliesPage.value += 1
  } catch {
    repliesError.value = true
  } finally {
    repliesLoading.value = false
  }
}

function handleReplyAdded(e) {
  const { reply, commentId } = e.detail || {}
  if (commentId === undefined || String(commentId) !== String(props.comment.comment_id)) return
  expanded.value = true
  if (repliesLoaded.value) {
    replies.value.unshift(reply)
  } else {
    loadReplies(true)
  }
  replyCount.value += 1
}

function handleReplyDeleted(reply) {
  const i = replies.value.findIndex((r) => r.reply_id === reply.reply_id)
  if (i !== -1) replies.value.splice(i, 1)
  replyCount.value = Math.max(0, replyCount.value - 1)
}

// 编辑完成：用后端返回的最新数据覆盖本地对象（保持引用，触发响应式更新）
function handleCommentEdited(e) {
  const updated = e.detail
  if (!updated || String(updated.comment_id) !== String(props.comment.comment_id)) return
  Object.assign(props.comment, updated)
}

// ---- 回复评论 / 删除评论 ----
function openReply() {
  if (!authStore.isLoggedIn) {
    show(t('commentSheet.loginFirst'), 'info')
    return
  }
  replySheet.open({
    replyableId: props.comment.comment_id,
    replyableType: 'comment',
    replyableCommentId: props.comment.comment_id,
    replyableUserId: props.comment.user_id,
    target: {
      username: author.value.username,
      avatar: author.value.avatar,
      content: props.comment.content,
    },
  })
}

async function confirmDeleteComment() {
  deleting.value = true
  try {
    await http.delete(`/comments/${props.comment.comment_id}`)
    show(t('commentSection.deleted'), 'success')
    emit('deleted')
  } catch (error) {
    show(error.message, 'error')
  } finally {
    deleting.value = false
    deleteDialog.value = false
  }
}

onMounted(() => {
  window.addEventListener('reply:added', handleReplyAdded)
  window.addEventListener('comment:edited', handleCommentEdited)
})
onBeforeUnmount(() => {
  window.removeEventListener('reply:added', handleReplyAdded)
  window.removeEventListener('comment:edited', handleCommentEdited)
})
</script>

<template>
  <v-card variant="text" class="comment-item mb-2" @click="openReply">
    <v-card-text class="pa-4">
      <div class="d-flex ga-3">
        <v-avatar
          size="36"
          color="primary"
          variant="tonal"
          :to="author.user_id ? `/users/${author.user_id}` : undefined"
          @click.stop
        >
          <v-img v-if="author.avatar" :src="author.avatar" cover />
          <span v-else class="text-body-2 font-weight-bold text-primary">{{ authorInitial }}</span>
        </v-avatar>

        <div class="grow min-w-0">
          <!-- 头部：用户名 / 时间 -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <router-link
              :to="author.user_id ? `/users/${author.user_id}` : undefined"
              class="text-subtitle-2 font-weight-medium text-primary text-decoration-none"
              @click.stop
            >
              {{ author.username }}
            </router-link>
            <span class="text-caption text-medium-emphasis">{{ timeText(comment.create_time) }}</span>
          </div>

          <!-- 评论内容 -->
          <div class="text-body-2 mt-0.5 comment-item__content" style="white-space: pre-wrap; word-break: break-word;">
            {{ comment.content }}
          </div>

          <!-- 操作：回复 / 赞同 / 更多 -->
          <div class="d-flex align-center ga-1 mt-1" @click.stop>
            <!-- <v-btn size="x-small" variant="text" class="text-none" density="comfortable" @click.stop="openReply">
              <v-icon size="small" start>mdi-reply</v-icon>
              {{ $t('commentItem.reply') }}
            </v-btn> -->
            <VoteButton
              type="comment"
              :item-id="comment.comment_id"
              :up-count="Number(comment.vote_up_count || 0)"
              :down-count="Number(comment.vote_down_count || 0)"
              :my-vote="comment.my_vote || null"
            />
            <v-spacer />
            <MoreActions
              type="comment"
              :item="comment"
              location="top end"
              @delete="deleteDialog = true"
            />
          </div>

          <!-- 查看 / 收起回复 -->
          <div v-if="replyCount > 0 || expanded" class="mt-1">
            <v-btn
              variant="text"
              size="x-small"
              density="comfortable"
              class="text-none"
              color="primary"
              @click.stop="toggleReplies"
            >
              <v-icon size="small" start>{{ expanded ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
              {{
                expanded
                  ? $t('commentItem.hideReplies')
                  : $t('commentItem.viewReplies', { count: replyCount })
              }}
            </v-btn>
          </div>

          <!-- 回复列表（展开时显示） -->
          <div v-if="expanded" class="mt-1" @click.stop>
            <template v-for="(reply, i) in replies" :key="reply.reply_id">
              <ReplyItem
                :reply="reply"
                :comment-id="comment.comment_id"
                @deleted="handleReplyDeleted(reply)"
              />
              <v-divider v-if="i < replies.length - 1" :thickness="1" class="opacity-50" />
            </template>
            <InfiniteScroll
              :loading="repliesLoading"
              :error="repliesError"
              :finished="repliesFinished"
              @load="loadReplies(false)"
              @retry="loadReplies(false)"
            />
          </div>
        </div>
      </div>
    </v-card-text>

    <!-- 删除评论确认 -->
    <DeleteDialog
      v-model="deleteDialog"
      :title="$t('commentSection.deleteTitle')"
      :text="$t('commentSection.deleteConfirm')"
      :loading="deleting"
      @confirm="confirmDeleteComment"
    />
  </v-card>
</template>

<style scoped>
.comment-item {
  cursor: pointer;
  transition: background-color 0.15s ease;
}
.comment-item:hover {
  background-color: rgba(128, 128, 128, 0.06);
}
.comment-item__content {
  min-width: 0;
}
</style>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import CommentButton from './CommentButton.vue'
import CommentDialog from './CommentDialog.vue'
import MoreActions from './MoreActions.vue'
import ProseRenderer from './ProseRenderer.vue'
import VoteButton from './VoteButton.vue'

const props = defineProps({
  answer: { type: Object, required: true },
})
const emit = defineEmits(['edit', 'delete'])

const { t } = useI18n()

const commentDialog = ref(false)
const commentCount = ref(Number(props.answer.comment_count || 0))

const author = computed(() => props.answer.creator || {})
const authorInitial = computed(() => (author.value.username || '?').slice(0, 1).toUpperCase())

watch(
  () => props.answer.comment_count,
  (v) => {
    commentCount.value = Number(v || 0)
  },
)

function timeText(time) {
  return time ? String(time).slice(0, 16).replace('T', ' ') : ''
}

function openComments() {
  commentDialog.value = true
}
</script>

<template>
  <v-card variant="text" class="answer-item mb-3" @click="openComments">
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
            <span class="text-caption text-medium-emphasis">{{ timeText(answer.create_time) }}</span>
          </div>

          <!-- 回答正文 -->
          <ProseRenderer :content="answer.content_rendered" class="mt-1" />

          <!-- 操作：评论 / 赞同 / 更多 -->
          <div class="d-flex align-center ga-2 mt-2">
            <CommentButton
              :count="commentCount"
              :tooltip="$t('answerItem.comments')"
              @click.stop="openComments"
            />
            <VoteButton
              type="answer"
              :item-id="answer.answer_id"
              :up-count="Number(answer.vote_up_count || 0)"
              :down-count="Number(answer.vote_down_count || 0)"
              :my-vote="answer.my_vote || null"
            />
            <v-spacer />
            <MoreActions
              type="answer"
              :item="answer"
              location="top end"
              @edit="emit('edit', answer)"
              @delete="emit('delete', answer)"
            />
          </div>
        </div>
      </div>
    </v-card-text>

    <!-- 该回答下的评论对话框 -->
    <CommentDialog
      v-model="commentDialog"
      commentable-type="answer"
      :commentable-id="answer.answer_id"
      @count-change="(n) => (commentCount = n)"
    />
  </v-card>
</template>

<style scoped>
.answer-item {
  cursor: pointer;
  transition: background-color 0.15s ease;
}
.answer-item:hover {
  background-color: rgba(128, 128, 128, 0.06);
}
</style>

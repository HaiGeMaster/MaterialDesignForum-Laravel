<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import CommentButton from './CommentButton.vue'
import MoreActions from './MoreActions.vue'
import ProseRenderer from './ProseRenderer.vue'
import UserMiniCard from './UserMiniCard.vue'
import VoteButton from './VoteButton.vue'
import { useCommentDialogStore } from '../stores/commentDialog'
import { useLocalizedDateTime } from '../composables/useLocalizedDateTime'

const props = defineProps({
  answer: { type: Object, required: true },
  disableAction: { type: Boolean, default: false },
})
const emit = defineEmits(['edit', 'delete', 'open'])

const { t } = useI18n()
const formatDateTime = useLocalizedDateTime()
const commentDialog = useCommentDialogStore()

const commentCount = ref(Number(props.answer.comment_count || 0))

const author = computed(() => props.answer.creator || {})
const authorInitial = computed(() => (author.value.username || '?').slice(0, 1).toUpperCase())

// 旧布局：时间旁显示用户上次登录位置（有则显示）
const locationText = computed(() => {
  const loc = author.value.last_login_location
  return loc && String(loc).trim() !== '' ? ` · ${loc}` : ''
})

watch(
  () => props.answer.comment_count,
  (v) => {
    commentCount.value = Number(v || 0)
  },
)

function openComments() {
  // 防止加载时评论按钮点击，然后错误的拉取全部数据
  if (props.answer.answer_id == null || props.answer.answer_id === 0 || props.answer.answer_id === '0') {
    return
  }
  commentDialog.open({
    commentableType: 'answer',
    commentableId: props.answer.answer_id,
    onCountChange: (n) => (commentCount.value = n),
  })
}

function handlePrimaryClick() {
  if (props.disableAction) {
    emit('open', props.answer)
    return
  }
  openComments()
}
</script>

<template>
  <!-- 参考旧项目布局：v-list-item 结构（头像 / 用户名 / 内容 / 操作区） -->
  <v-list-item rounded="lg"

    active-class="text-primary"
    class="py-3 answer-item"
    @click="handlePrimaryClick"
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
            class="cursor-pointer"
            :variant="author?.avatar?'': 'tonal'"
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

    <!-- 用户名 -->
    <v-list-item-title>{{ author.username }}</v-list-item-title>

    <!-- 回答正文 -->
    <v-list-item-subtitle class="mb-1 text-high-emphasis opacity-100">
      <ProseRenderer :content="answer.content_rendered" />
    </v-list-item-subtitle>

    <!-- 操作区：时间 / 评论 / 赞同 / 更多 -->
    <v-list-item-action>

      <v-list-item-subtitle class="text-high-emphasis">
        {{ formatDateTime(answer.create_time) }}{{ locationText }}
      </v-list-item-subtitle>

      <!-- <div class="d-flex align-center ga-1 flex-wrap">
        <span class="text-caption text-medium-emphasis">
          {{ formatDateTime(answer.create_time) }}{{ locationText }}
        </span> -->

        <v-spacer />

        <CommentButton
          :count="commentCount"
          :tooltip="t('answerItem.comments')"
          @click.stop="openComments"
        />
        <VoteButton
          type="answer"
          :item-id="answer.answer_id"
          :up-count="Number(answer.vote_up_count || 0)"
          :down-count="Number(answer.vote_down_count || 0)"
          :my-vote="answer.my_vote || null"
        />
        <MoreActions
          type="answer"
          :item="answer"
          @edit="emit('edit', answer)"
          @delete="emit('delete', answer)"
        />
      <!-- </div> -->
    </v-list-item-action>
  </v-list-item>
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

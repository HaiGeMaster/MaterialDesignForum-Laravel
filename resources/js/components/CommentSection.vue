<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import CommentItem from './CommentItem.vue'
import InfiniteScroll from './InfiniteScroll.vue'
import ListHeader from './ListHeader.vue'
import { useInfiniteList } from '../composables/useInfiniteList'

const props = defineProps({
  commentableType: { type: String, required: true },
  commentableId: { type: [Number, String], required: true },
})
const emit = defineEmits(['count-change'])

const { t } = useI18n()

const sort = ref('-create_time')
const total = ref(0)

const sortItems = computed(() => [
  { value: '-create_time', label: t('commentSection.sortNewest') },
  { value: '+create_time', label: t('commentSection.sortOldest') },
  { value: '-vote_count', label: t('commentSection.sortVotes') },
])

const title = computed(() => `${t('commentSection.title')} (${total.value})`)

const { list: items, loading, error, finished, load, loadNext, retry } = useInfiniteList(fetchComments)

async function fetchComments(page) {
  if (!props.commentableType || props.commentableId == null) {
    return { items: [], hasMore: false }
  }
  const { data } = await http.get('/comments', {
    params: {
      commentable_type: props.commentableType,
      commentable_id: props.commentableId,
      order: sort.value,
      page,
      per_page: 20,
    },
  })
  if (Array.isArray(data)) {
    total.value = data.length
    emit('count-change', total.value)
    return { items: data, hasMore: false }
  }
  total.value = data.total ?? 0
  emit('count-change', total.value)
  return { items: data.data || [], hasMore: data.current_page < data.last_page }
}

function handleDeleted(i) {
  items.value.splice(i, 1)
  total.value = Math.max(0, total.value - 1)
  emit('count-change', total.value)
}

function handleCommentAdded(e) {
  const detail = e.detail || {}
  if (detail.commentableType && detail.commentableId) {
    if (
      detail.commentableType !== props.commentableType ||
      Number(detail.commentableId) !== Number(props.commentableId)
    ) {
      return
    }
  }
  load(true)
}

watch(
  () => [props.commentableType, props.commentableId, sort.value],
  () => load(true),
)

onMounted(() => {
  load(true)
  window.addEventListener('comment:added', handleCommentAdded)
})
onBeforeUnmount(() => window.removeEventListener('comment:added', handleCommentAdded))
</script>

<template>
  <v-card id="comments" class="mt-4">
    <ListHeader :title="title" :sort="sort" :sort-items="sortItems" @update:sort="(v) => (sort = v)" />

    <v-divider />

    <!-- 加载中 -->
    <div v-if="loading && !items.length" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <!-- 空状态 -->
    <div v-else-if="!items.length" class="text-center py-8 text-medium-emphasis">
      {{ $t('commentSection.empty') }}
    </div>

    <!-- 评论列表 -->
    <template v-else>
      <template v-for="(comment, i) in items" :key="comment.comment_id">
        <CommentItem :comment="comment" @deleted="handleDeleted(i)" />
        <v-divider v-if="i < items.length - 1" />
      </template>
      <InfiniteScroll :loading="loading" :error="error" :finished="finished" @load="loadNext" @retry="retry" />
    </template>
  </v-card>
</template>

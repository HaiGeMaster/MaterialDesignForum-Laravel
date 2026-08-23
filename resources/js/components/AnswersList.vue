<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import AnswerItem from './AnswerItem.vue'
import InfiniteScroll from './InfiniteScroll.vue'
import ListHeader from './ListHeader.vue'
import { useAuthStore } from '../stores/auth'
import { useEditorWindowStore } from '../stores/editorWindow'
import { useDeleteDialogStore } from '../stores/deleteDialog'
import { useFeedback } from '../composables/useFeedback'
import { useInfiniteList } from '../composables/useInfiniteList'

const props = defineProps({
  questionId: { type: [Number, String], required: true },
})

const { t } = useI18n()
const authStore = useAuthStore()
const editorWindow = useEditorWindowStore()
const deleteDialog = useDeleteDialogStore()
const { show } = useFeedback()

const sort = ref('-create_time')
const total = ref(0)

const sortItems = computed(() => [
  { value: '-create_time', label: t('answersList.sortNewest') },
  { value: '+create_time', label: t('answersList.sortOldest') },
  { value: '-vote_count', label: t('answersList.sortVotes') },
])

const title = computed(() => `${t('answersList.title')} (${total.value})`)

const { list: items, loading, error, finished, load, loadNext, retry } = useInfiniteList(fetchAnswers)

async function fetchAnswers(page) {
  const { data } = await http.get('/answers', {
    params: {
      question_id: props.questionId,
      order: sort.value,
      page,
      per_page: 10,
    },
  })
  if (Array.isArray(data)) {
    total.value = data.length
    return { items: data, hasMore: false }
  }
  total.value = data.total ?? 0
  return { items: data.data || [], hasMore: data.current_page < data.last_page }
}

// ---- 编辑回答 ----
function editAnswer(answer) {
  editorWindow.openEditor({
    mode: 'answer',
    action: 'edit',
    targetId: answer.answer_id,
    questionId: props.questionId,
    content: answer.content_markdown,
  })
}

// ---- 删除回答 ----
const deletingAnswer = ref(null)

function askDelete(answer) {
  deletingAnswer.value = answer
  deleteDialog.open({
    title: t('answersList.deleteTitle'),
    text: t('answersList.deleteConfirm'),
    onConfirm: confirmDelete,
  })
}

async function confirmDelete() {
  if (!deletingAnswer.value) return
  await http.delete(`/answers/${deletingAnswer.value.answer_id}`)
  const i = items.value.findIndex((a) => a.answer_id === deletingAnswer.value.answer_id)
  if (i !== -1) items.value.splice(i, 1)
  total.value = Math.max(0, total.value - 1)
  show(t('answersList.deleted'), 'success')
}

// ---- 发布 / 编辑回答后刷新 ----
function handleContentPublished(e) {
  const { mode } = e.detail || {}
  if (mode === 'answer') {
    load(true)
  }
}

watch(
  () => [props.questionId, sort.value],
  () => load(true),
)

onMounted(() => {
  load(true)
  window.addEventListener('content:published', handleContentPublished)
})
onBeforeUnmount(() => window.removeEventListener('content:published', handleContentPublished))
</script>

<template>
  <v-card id="answers" class="mt-4">
    <ListHeader :title="title" :sort="sort" :sort-items="sortItems" @update:sort="(v) => (sort = v)" />

    <v-divider />

    <!-- 加载中 -->
    <div v-if="loading && !items.length" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <!-- 空状态 -->
    <div v-else-if="!items.length" class="text-center py-8 text-medium-emphasis">
      {{ $t('answersList.empty') }}
    </div>

    <!-- 回答列表 -->
    <template v-else>
      <div class="pa-3">
        <AnswerItem
          v-for="answer in items"
          :key="answer.answer_id"
          :answer="answer"
          @edit="editAnswer"
          @delete="askDelete"
        />
      </div>
      <InfiniteScroll :loading="loading" :error="error" :finished="finished" @load="loadNext" @retry="retry" />
    </template>
  </v-card>
</template>

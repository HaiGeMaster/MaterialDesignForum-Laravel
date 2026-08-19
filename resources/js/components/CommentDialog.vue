<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDisplay } from 'vuetify'
import http from '../api'
import CommentItem from './CommentItem.vue'
import InfiniteScroll from './InfiniteScroll.vue'
import { useAuthStore } from '../stores/auth'
import { useCommentDialogStore } from '../stores/commentDialog'
import { useFeedback } from '../composables/useFeedback'
import { useInfiniteList } from '../composables/useInfiniteList'

const store = useCommentDialogStore()

const { mobile } = useDisplay()

const { t } = useI18n()
const authStore = useAuthStore()
const router = useRouter()
const { show } = useFeedback()

const sort = ref('-create_time')
const total = ref(0)
const content = ref('')
const submitting = ref(false)

const sortItems = computed(() => [
  { value: '-create_time', label: t('commentDialog.sortNewest') },
  { value: '+create_time', label: t('commentDialog.sortOldest') },
  { value: '-vote_count', label: t('commentDialog.sortVotes') },
])

const { list: items, loading, error, finished, load, loadNext, retry } = useInfiniteList(fetchComments)

async function fetchComments(page) {
  const { data } = await http.get('/comments', {
    params: {
      commentable_type: store.commentableType,
      commentable_id: store.commentableId,
      order: sort.value,
      page,
      per_page: 20,
    },
  })
  total.value = data.total ?? 0
  store.emitCountChange(total.value)
  return { items: data.data || [], hasMore: data.current_page < data.last_page }
}

function handleDeleted(i) {
  items.value.splice(i, 1)
  total.value = Math.max(0, total.value - 1)
  store.emitCountChange(total.value)
}

async function submit() {
  const text = content.value.trim()
  if (!text || submitting.value) return
  if (!authStore.isLoggedIn) {
    show(t('commentSheet.loginFirst'), 'info')
    router.push('/users/me')
    return
  }
  submitting.value = true
  try {
    await http.post('/comments', {
      commentable_type: store.commentableType,
      commentable_id: store.commentableId,
      content: text,
    })
    content.value = ''
    show(t('commentSheet.success'), 'success')
    load(true)
  } catch (e) {
    show(e.message, 'error')
  } finally {
    submitting.value = false
  }
}

watch(
  () => store.model,
  (v) => {
    if (v) load(true)
  },
)

watch(
  () => [store.commentableType, store.commentableId, sort.value],
  () => {
    if (store.model) load(true)
  },
)
</script>

<template>
  <v-dialog
    :model-value="store.model"
    @update:model-value="(v) => { if (!v) store.close() }"
    max-width="760"
    :fullscreen="mobile"
    scrollable
  >
    <v-card rounded="lg">
      <!-- 头部：标题 + 排序 + 关闭 -->
      <v-toolbar >
        <v-toolbar-title >
          {{ $t('commentDialog.title') }} ({{ total }})
        </v-toolbar-title>
        <template v-slot:append>
          <v-menu :close-on-content-click="true" >
            <template v-slot:activator="{ props: menuProps }">
              <v-btn v-bind="menuProps" variant="text"  class="text-none">
                <v-icon  start>mdi-sort</v-icon>
                <span class="text-body-2">
                  {{ sortItems.find((i) => i.value === sort)?.label || sortItems[0]?.label }}
                </span>
                <v-icon >mdi-chevron-down</v-icon>
              </v-btn>
            </template>
            <v-list density="compact" min-width="160">
              <v-list-item
                v-for="item in sortItems"
                :key="item.value"
                :active="sort === item.value"
                @click="sort = item.value"
              >
                <v-list-item-title class="text-body-2">{{ item.label }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
          <v-btn icon  variant="text" @click="store.close()">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </template>
      </v-toolbar>

      <v-divider />

      <!-- 评论列表（可滚动） -->
      <div class="comment-dialog__list">
        <div v-if="loading && !items.length" class="text-center py-8">
          <v-progress-circular indeterminate color="primary" />
        </div>
        <div v-else-if="!items.length" class="text-center py-8 text-medium-emphasis">
          {{ $t('commentDialog.empty') }}
        </div>
        <template v-else>
          <template v-for="(comment, i) in items" :key="comment.comment_id">
            <CommentItem :comment="comment" @deleted="handleDeleted(i)" />
            <v-divider v-if="i < items.length - 1" />
          </template>
          <InfiniteScroll
            :loading="loading"
            :error="error"
            :finished="finished"
            @load="loadNext"
            @retry="retry"
          />
        </template>
      </div>

      <v-divider />

      <!-- 底部：发布评论输入框 -->
      <v-card-actions class="pa-3">
        <v-text-field
          v-model="content"
          color="primary"
          rounded="lg"
          variant="outlined"
          :placeholder="$t('commentSheet.placeholder')"
          hide-details="auto"
          :disabled="submitting"
          @keydown.enter.exact.prevent="submit"
        >
          <template v-slot:append-inner>
            <v-btn
              rounded="lg"
              variant="flat"
              color="primary"
              :loading="submitting"
              :disabled="!content.trim()"
              @click="submit"
            >
              {{ $t('commentSheet.publish') }}
            </v-btn>
          </template>
        </v-text-field>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.comment-dialog__list {
  /* max-height: 55vh; */
  height: 100%;
  overflow-y: auto;
}
</style>

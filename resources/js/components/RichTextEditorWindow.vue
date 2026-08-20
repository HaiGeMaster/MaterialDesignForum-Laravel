<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import FloatingWindow from './FloatingWindow.vue'
import RichTextEditor from './RichTextEditor.vue'
import { useEditorWindowStore } from '../stores/editorWindow'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'

const props = defineProps({
  /** 窗口初始宽度（px） */
  width: { type: Number, default: 400 },
  /** 窗口初始高度（px） */
  height: { type: Number, default: 520 },
  /** 窗口初始横坐标（px，不传则贴右下角、距边缘 16px） */
  x: { type: Number, default: undefined },
  /** 窗口初始纵坐标（px，不传则贴右下角、距边缘 16px） */
  y: { type: Number, default: undefined },
})

const emit = defineEmits(['published'])

const { t } = useI18n()
const { show } = useFeedback()
const store = useEditorWindowStore()
const authStore = useAuthStore()

/** 窗口标题栏图标与文案（随创建/编辑切换） */
const meta = computed(() => store.meta)
const isEditing = computed(() => store.action === 'edit')

/** 可写双向绑定 */
const titleModel = computed({
  get: () => store.title,
  set: (v) => (store.title = v),
})
const contentModel = computed({
  get: () => store.content,
  set: (v) => (store.content = v),
})

/** 话题最多可选数量 */
const MAX_TOPICS = 5

/** 全部话题选项（用于 v-select 多选） */
const topicOptions = ref([])

async function loadTopics() {
  try {
    const { data } = await http.get('/topics')
    topicOptions.value = data || []
  } catch {
    topicOptions.value = []
  }
}

onMounted(() => {
  if (store.open) loadTopics()
})
watch(
  () => store.open,
  (open) => {
    if (open) loadTopics()
  },
)

/** v-select 双向绑定：store.topics（对象数组）<-> topic_id 数组 */
const topicsModel = computed({
  get: () => store.topics.map((t) => t.topic_id),
  set: (ids) => {
    // v-select 内部会先选中再触发 setter，直接 return 无法回滚 UI，
    // 必须把数组截断回写，多选的那个 chip 才会被移除
    if (ids.length > MAX_TOPICS) {
      show(t('editorWindow.maxTopicsReached', { max: MAX_TOPICS }), 'warning')
      ids = ids.slice(0, MAX_TOPICS)
    }
    store.topics = ids
      .map((id) => topicOptions.value.find((topic) => topic.topic_id === id))
      .filter(Boolean)
  },
})

/** 发布 / 保存：创建走 POST，编辑走 PUT */
async function handlePublish() {
  if (!authStore.isLoggedIn) {
    show(t('editorWindow.loginFirst'), 'info')
    return
  }
  if (store.hasTitle && !store.title.trim()) {
    show(t('editorWindow.needTitle'), 'warning')
    return
  }
  const html = store.content.trim()
  if (!html || html === '<p></p>') {
    show(t('editorWindow.needContent'), 'warning')
    return
  }

  store.loading = true
  try {
    const common = { content_markdown: html, content_rendered: html }
    let url, method, payload

    // 管理员后台模式：apiBase 指向 '/admin/modules/questions' 等
    const base = store.apiBase || { question: '/questions', article: '/articles', answer: '/answers' }[store.mode]

    if (store.mode === 'question') {
      url = isEditing.value ? `${base}/${store.targetId}` : base
      method = isEditing.value ? 'put' : 'post'
      payload = { title: store.title.trim(), topic_ids: store.topics.map((t) => t.topic_id), ...common }
    } else if (store.mode === 'article') {
      url = isEditing.value ? `${base}/${store.targetId}` : base
      method = isEditing.value ? 'put' : 'post'
      payload = { title: store.title.trim(), topic_ids: store.topics.map((t) => t.topic_id), ...common }
    } else {
      url = isEditing.value ? `${base}/${store.targetId}` : base
      method = isEditing.value ? 'put' : 'post'
      payload = { question_id: store.questionId, ...common }
    }

    const { data } = await http[method](url, payload)

    show(isEditing.value ? t('editorWindow.updateSuccess') : t('editorWindow.createSuccess'), 'success')
    emit('published', { mode: store.mode, action: store.action, item: data })
    store.close()
  } catch (error) {
    show(error.message || t('editorWindow.submitError'), 'error')
  } finally {
    store.loading = false
  }
}

function handleClose() {
  store.close()
}
</script>

<template>
  <FloatingWindow
    v-if="store.open"
    :title="t(meta.titleKey)"
    :icon="meta.icon"
    :width="width"
    :height="height"
    :x="x"
    :y="y"
    @close="handleClose"
  >
    <div class="editor-window">
      <!-- 标题输入：提问 / 文章；回答无标题 -->
      <div v-if="store.hasTitle" class="editor-window__title">
        <v-text-field
          v-model="titleModel"
          :label="$t('editorWindow.titleLabel')"
          :placeholder="$t('editorWindow.titlePlaceholder')"
          density="compact"
          variant="outlined"
          maxlength="80"
          hide-details
        />
      </div>

      <!-- 话题选择：提问 / 文章（v-select 多选） -->
      <v-select
        v-if="store.hasTitle"
        v-model="topicsModel"
        :items="topicOptions"
        :label="$t('editorWindow.selectTopics')"
        item-title="name"
        item-value="topic_id"
        chips
        multiple
        closable-chips
        density="compact"
        variant="outlined"
        hide-details
        :menu-props="{ maxHeight: 280 }"
        class="editor-window__topics"
      />

      <!-- 富文本编辑器 + 下栏左侧发布按钮 -->
      <div class="editor-window__body">
        <RichTextEditor
          v-model="contentModel"
          :placeholder="$t('editorWindow.contentPlaceholder')"
          height="100%"
        >
          <template #publish>
            <v-btn
              :color="isEditing ? 'secondary' : 'primary'"
              :loading="store.loading"
              variant="flat"
              size="small"
              class="text-none"
              @click="handlePublish"
            >
              <v-icon :icon="isEditing ? 'mdi-content-save-outline' : 'mdi-send'" start size="small" />
              {{ $t(meta.publishKey) }}
            </v-btn>
          </template>
        </RichTextEditor>
      </div>
    </div>
  </FloatingWindow>
</template>

<style scoped>
/* 窗口内容：标题 + 编辑器纵向排布，编辑器占满剩余空间 */
.editor-window {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  padding: 8px 10px 10px;
  gap: 8px;
}

.editor-window__title {
  flex: none;
}

.editor-window__topics {
  flex: none;
}

.editor-window__body {
  flex: 1;
  min-height: 0;
}
</style>

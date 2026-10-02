<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useFeedback } from '../composables/useFeedback'
import ProseRenderer from './ProseRenderer.vue'

const props = defineProps({
  /** 对话框显隐 */
  modelValue: { type: Boolean, default: false },
  /** 举报数据对象 */
  report: { type: Object, default: null },
  /** API 基础路径 */
  apiBase: { type: String, default: '/admin/modules/reports' },
})

const emit = defineEmits(['update:modelValue', 'submit'])

const { show } = useFeedback()
const { t } = useI18n()

const submitting = ref(false)
/** 处理状态：0=未处理 1=已处理(删除违规内容) 2=无违规/不予处理 */
const handleState = ref(0)

/** 举报对象内容加载状态 */
const contentLoading = ref(false)
const reportedContent = ref(null)

const dialogOpen = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// 状态选项
const stateOptions = computed(() => [
  { value: 0, label: t('reportDialog.statePending'), color: 'error', icon: 'mdi-clock-outline' },
  { value: 1, label: t('reportDialog.stateHandled'), color: 'grey', icon: 'mdi-check-circle-outline' },
  { value: 2, label: t('reportDialog.stateDismissed'), color: 'success', icon: 'mdi-close-circle-outline' },
])

// 将 reportable_type（如 App\Models\Question）映射为 admin modules API 中的模块名
const reportableModule = computed(() => {
  const type = props.report?.reportable_type || ''
  return type.toLowerCase().replace(/^.*\\/, '')
})

// 举报目标类型显示名称
const targetTypeName = computed(() => {
  const map = {
    question: t('reportDialog.targetQuestion'),
    article: t('reportDialog.targetArticle'),
    answer: t('reportDialog.targetAnswer'),
    comment: t('reportDialog.targetComment'),
    reply: t('reportDialog.targetReply'),
    user: t('reportDialog.targetUser'),
    topic: t('reportDialog.targetTopic'),
  }
  return map[reportableModule.value] || reportableModule.value
})

const currentState = computed(() => {
  return stateOptions.value.find((s) => s.value === (props.report?.report_handle_state ?? 0))
})

// 举报对象的预览内容（标题 / 正文摘要）
const contentPreview = computed(() => {
  if (!reportedContent.value) return null
  const item = reportedContent.value
  const type = reportableModule.value

  switch (type) {
    case 'question':
    case 'article':
      return {
        title: item.title || '',
        content: item.content_rendered || item.content_markdown || '',
      }
    case 'answer':
      return {
        title: '',
        content: item.content_rendered || item.content_markdown || '',
      }
    case 'comment':
    case 'reply':
      return {
        title: '',
        content: item.content || '',
      }
    case 'topic':
      return {
        title: item.name || '',
        content: item.description || '',
      }
    case 'user':
      return {
        title: item.username || '',
        content: item.signature || item.introduction || '',
      }
    default:
      return null
  }
})

// 将 reportable_type 的单数短名映射为 admin modules API 路径中的复数模块名
const modulePathMap = {
  question: 'questions',
  article: 'articles',
  answer: 'answers',
  comment: 'comments',
  reply: 'replies',
  topic: 'topics',
  user: 'users',
}

async function loadReportedContent() {
  const type = reportableModule.value
  const id = props.report?.reportable_id
  if (!type || !id) {
    reportedContent.value = null
    return
  }

  const modulePath = modulePathMap[type] || `${type}s`
  contentLoading.value = true
  try {
    const { data } = await http.get(`/admin/modules/${modulePath}/${id}`)
    reportedContent.value = data
  } catch {
    reportedContent.value = null
  } finally {
    contentLoading.value = false
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    handleState.value = props.report?.report_handle_state ?? 0
    reportedContent.value = null
    loadReportedContent()
  },
)

async function submit() {
  submitting.value = true
  try {
    const id = props.report?.report_id
    if (!id) return

    const { data } = await http.put(`${props.apiBase}/${id}`, {
      report_handle_state: handleState.value,
    })

    show(t('reportDialog.updateSuccess'), 'success')
    emit('submit', data)
    dialogOpen.value = false
  } catch (error) {
    show(error.message || t('reportDialog.submitError'), 'error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <v-dialog v-model="dialogOpen" max-width="520">
    <v-card rounded="lg" >
      <v-toolbar :title="$t('reportDialog.title')">
        <template #prepend>
          <v-btn icon variant="text" @click="dialogOpen = false">
            <v-icon>
              mdi-close
            </v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.cancel') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <v-btn icon variant="text" :loading="submitting" @click="submit">
            <v-icon>
              mdi-check
            </v-icon>
            <v-tooltip activator="parent" location="left">
              {{ $t('common.save') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>
      <v-divider />

      <v-card-text class="pt-4">
        <!-- 举报详情 -->
        <div v-if="report" class="mb-4">
          <div class="text-subtitle-2 text-medium-emphasis mb-1">{{ $t('reportDialog.targetType') }}</div>
          <div class="text-body-1 mb-3">
            {{ targetTypeName }}
            <span v-if="report.reportable_id" class="text-medium-emphasis">#{{ report.reportable_id }}</span>
          </div>

          <div class="text-subtitle-2 text-medium-emphasis mb-1">{{ $t('reportDialog.reporter') }}</div>
          <div class="text-body-1 mb-3">{{ report.username || `#${report.user_id}` }}</div>

          <div class="text-subtitle-2 text-medium-emphasis mb-1">{{ $t('reportDialog.reason') }}</div>
          <div class="text-body-1 mb-3">{{ report.reason || '—' }}</div>

          <!-- 举报对象内容预览 -->
          <div class="text-subtitle-2 text-medium-emphasis mb-1">{{ $t('reportDialog.reportedContent') }}</div>
          <v-card rounded="lg" v-if="contentLoading" variant="outlined" class="mb-3" flat>
            <v-card-text class="d-flex align-center justify-center py-6">
              <v-progress-circular indeterminate size="small" />
            </v-card-text>
          </v-card>
          <v-card rounded="lg" v-else-if="contentPreview" variant="outlined" class="mb-3" flat>
            <v-card-text>
              <div v-if="contentPreview.title" class="text-subtitle-1 font-weight-bold mb-2">
                {{ contentPreview.title }}
              </div>
              <div v-if="contentPreview.content" class="report-content" style="max-height: 200px; overflow-y: auto;">
                <ProseRenderer :content="contentPreview.content" />
              </div>
              <div v-else class="text-medium-emphasis">—</div>
            </v-card-text>
          </v-card>
          <div v-else class="text-body-2 text-medium-emphasis mb-3">—</div>

          <div class="text-subtitle-2 text-medium-emphasis mb-1">{{ $t('reportDialog.currentState') }}</div>
          <v-chip v-if="currentState" :color="currentState.color" size="small" variant="tonal" class="mb-3">
            <v-icon :icon="currentState.icon" start size="x-small" />
            {{ currentState.label }}
          </v-chip>
        </div>

        <v-divider class="mb-4" />

        <!-- 处理状态选择 -->
        <div class="text-subtitle-1 font-weight-bold mb-3">{{ $t('reportDialog.selectState') }}</div>
        <v-radio-group v-model="handleState" class="mt-0">
          <v-radio
            v-for="opt in stateOptions"
            :key="opt.value"
            :value="opt.value"
            color="primary"
          >
            <template #label>
              <div class="d-flex align-center" style="gap: 8px;">
                <v-icon :icon="opt.icon" :color="opt.color" size="small" />
                <span>{{ opt.label }}</span>
              </div>
            </template>
          </v-radio>
        </v-radio-group>
      </v-card-text>

    </v-card>
  </v-dialog>
</template>

<style scoped>
.report-content {
  /* 限制内嵌富文本高度，超出滚动 */
  max-height: 200px;
}
</style>

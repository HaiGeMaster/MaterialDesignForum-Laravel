<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import http from '../api'

const props = defineProps({
  /** v-model 控制对话框显隐 */
  modelValue: { type: Boolean, default: false },
  /** 举报目标类型：question / article / answer / comment / user / reply / topic */
  type: { type: String, default: '' },
  /** 举报目标对象（需含对应 *_id 字段） */
  item: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'reported'])

const router = useRouter()
const authStore = useAuthStore()
const { show } = useFeedback()
const { t } = useI18n()

// 举报原因（参考旧项目 report-dialog）
const REASONS_KEY = [
  'report.reasons.spam',
  'report.reasons.abuse',
  'report.reasons.harmful',
  'report.reasons.copyright',
  'report.reasons.engagement',
  'report.reasons.other',
]
const reasons = computed(() => REASONS_KEY.map((key) => t(key)))
const OTHER_KEY = 'report.reasons.other'

const reason = ref('')
const otherText = ref('')
const submitting = ref(false)

const showOther = computed(() => reason.value === t(OTHER_KEY))
// 未选择原因，或选择"其他原因"但未填写内容时不允许提交
const canSubmit = computed(() => {
  if (!reason.value) return false
  if (showOther.value) return otherText.value.trim().length > 0
  return true
})

// 按类型取举报目标 ID
const targetId = computed(() => {
  if (!props.item) return null
  const key = {
    user: 'user_id',
    topic: 'topic_id',
    question: 'question_id',
    article: 'article_id',
    answer: 'answer_id',
    comment: 'comment_id',
    reply: 'reply_id',
  }[props.type]
  return key ? props.item[key] : null
})

// 每次打开时重置表单
watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      reason.value = ''
      otherText.value = ''
    }
  }
)

async function submit() {
  if (!canSubmit.value || submitting.value) return
  // 未登录时引导到登录页（个人资料页）
  if (!authStore.isLoggedIn) {
    show(t('report.loginFirst'), 'info')
    router.push('/login')
    return
  }
  submitting.value = true
  try {
    await http.post('/reports', {
      user_id: authStore.user.user_id,
      reportable_type: props.type,
      reportable_id: targetId.value,
      reason: showOther.value ? otherText.value.trim() : reason.value,
    })
    show(t('report.submitted'), 'success')
    emit('reported')
    emit('update:modelValue', false)
  } catch (error) {
    show(error.message, 'error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <v-dialog :model-value="modelValue" max-width="480" @update:model-value="emit('update:modelValue', $event)">
    <v-card rounded="lg" :title="$t('report.title')" prepend-icon="mdi-flag-outline" :subtitle="$t('report.subtitle')">
      <v-card-subtitle>{{ $t('report.subtitle') }}</v-card-subtitle>

      <v-card-text>
        <v-radio-group v-model="reason" color="error">
          <v-radio v-for="r in reasons" :key="r" :label="r" :value="r" />
        </v-radio-group>

        <v-textarea
          v-if="showOther"
          v-model="otherText"
          :label="$t('report.describe')"
          variant="outlined"
          rows="3"
          maxlength="200"
          counter
          :disabled="submitting"
        />
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" :disabled="submitting" @click="emit('update:modelValue', false)">
          {{ $t('report.cancel') }}
        </v-btn>
        <v-btn
          color="error"
          variant="tonal"
          :disabled="!canSubmit"
          :loading="submitting"
          @click="submit"
        >
          {{ $t('report.submit') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

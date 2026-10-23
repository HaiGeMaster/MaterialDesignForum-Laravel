<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useReportDialogStore } from '../stores/reportDialog'
import { useFeedback } from '../composables/useFeedback'
import http from '../api'

const router = useRouter()
const authStore = useAuthStore()
const store = useReportDialogStore()
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
  if (!store.item) return null
  const key = {
    user: 'user_id',
    topic: 'topic_id',
    question: 'question_id',
    article: 'article_id',
    answer: 'answer_id',
    comment: 'comment_id',
    reply: 'reply_id',
  }[store.type]
  return key ? store.item[key] : null
})

// 每次打开时重置表单
watch(
  () => store.model,
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
      reportable_type: store.type,
      reportable_id: targetId.value,
      reason: showOther.value ? otherText.value.trim() : reason.value,
    })
    show(t('report.submitted'), 'success')
    store.close()
  } catch (error) {
    show(error.message, 'error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <v-dialog :model-value="store.model" max-width="480" @update:model-value="store.close">
    <v-card rounded="lg">
      <v-toolbar :title="$t('report.title')">
        <template v-slot:prepend>
          <v-btn icon variant="text" @click="store.close">
            <v-icon>
              mdi-close
            </v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('report.cancel') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <v-btn icon variant="text" color="error" :disabled="!canSubmit" :loading="submitting" @click="submit">
            <v-icon>
              mdi-check
            </v-icon>
            <v-tooltip activator="parent" location="left">
              {{ $t('report.submit') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-card-text>
        <div class="text-body-2 text-medium-emphasis mb-4">
          {{ $t('report.subtitle') }}
        </div>

        <v-radio-group v-model="reason" color="error">
          <v-radio v-for="r in reasons" :key="r" :label="r" :value="r" />
        </v-radio-group>

        <v-textarea color="primary" 
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
    </v-card>
  </v-dialog>
</template>

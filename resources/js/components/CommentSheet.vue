<template>
  <v-bottom-sheet v-model="sheet" inset max-width="750">
    <v-card rounded="t-lg">
      <v-card-text class="pt-4 pb-2">
        <v-text-field
          v-model="content"
          color="primary"

          variant="outlined"
          :label="label || $t('commentSheet.placeholder')"
          hide-details="auto"
          :disabled="submitting"
          @keydown.enter.exact.prevent="submit"
        >
          <template v-slot:append-inner>
            <v-btn

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
      </v-card-text>
    </v-card>
  </v-bottom-sheet>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  /** 被评论对象类型：article / question */
  commentableType: { type: String, required: true },
  /** 被评论对象 ID */
  commentableId: { type: [Number, String], required: true },
  /** 输入框占位文案（可选，默认取 commentSheet.placeholder） */
  label: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const authStore = useAuthStore()
const router = useRouter()
const { show } = useFeedback()
const { t } = useI18n()

const content = ref('')
const submitting = ref(false)

const sheet = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

async function submit() {
  const text = content.value.trim()
  if (!text || submitting.value) return
  if (!authStore.isLoggedIn) {
    show(t('commentSheet.loginFirst'), 'info')
    router.push('/login')
    return
  }
  submitting.value = true
  try {
    const body = new URLSearchParams({
      commentable_type: props.commentableType,
      commentable_id: String(props.commentableId),
      content: text,
    })
    const { data } = await http.post('/comments', body, {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    })
    // 通知详情页刷新评论区（与 content:published 事件风格一致）
    window.dispatchEvent(
      new CustomEvent('comment:added', {
        detail: {
          commentableType: props.commentableType,
          commentableId: props.commentableId,
          comment: data,
        },
      }),
    )
    content.value = ''
    sheet.value = false
    show(t('commentSheet.success'), 'success')
  } catch (e) {
    show(e.message, 'error')
  } finally {
    submitting.value = false
  }
}
</script>

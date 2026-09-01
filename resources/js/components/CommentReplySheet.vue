<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useAuthStore } from '../stores/auth'
import { useReplySheetStore } from '../stores/replySheet'
import { useFeedback } from '../composables/useFeedback'

const authStore = useAuthStore()
const router = useRouter()
const { show } = useFeedback()
const { t } = useI18n()
const replySheet = useReplySheetStore()

const content = ref('')
const submitting = ref(false)

const target = computed(() => replySheet.target || {})
const targetInitial = computed(() => (target.value.username || '?').slice(0, 1).toUpperCase())

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
    const { data } = await http.post('/replies', {
      replyable_id: replySheet.replyableId,
      replyable_type: replySheet.replyableType,
      replyable_comment_id: replySheet.replyableCommentId,
      replyable_user_id: replySheet.replyableUserId ?? null,
      content: text,
    })
    // 通知所属评论刷新 / 插入回复列表
    window.dispatchEvent(
      new CustomEvent('reply:added', {
        detail: { reply: data, commentId: replySheet.replyableCommentId },
      }),
    )
    content.value = ''
    replySheet.close()
    show(t('replySheet.success'), 'success')
  } catch (e) {
    show(e.message, 'error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <v-bottom-sheet v-model="replySheet.visible" inset max-width="760">
    <v-card rounded="t-lg">
      <v-card-text class="pt-4 pb-1">
        <!-- 被回复目标信息：头像 / 用户名 / 内容预览 -->
        <div class="d-flex align-start ga-3">
          <v-avatar size="36">
            <v-img v-if="target.avatar" :src="target.avatar" cover />
            <span v-else class="text-body-2 font-weight-bold text-primary">{{ targetInitial }}</span>
          </v-avatar>
          <div class="min-w-0">
            <div class="text-subtitle-2 font-weight-medium text-primary">{{ target.username }}</div>
            <div class="text-body-2 text-medium-emphasis text-truncate">{{ target.content }}</div>
          </div>
        </div>
      </v-card-text>

      <v-card-actions class="pt-1">
        <v-text-field color="primary" 
          v-model="content"
          variant="outlined"
          :placeholder="t('replySheet.placeholder')"
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
              {{ $t('replySheet.publish') }}
            </v-btn>
          </template>
        </v-text-field>
      </v-card-actions>
    </v-card>
  </v-bottom-sheet>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useCommentReplyEditStore } from '../stores/commentReplyEditDialog'
import { useFeedback } from '../composables/useFeedback'

const store = useCommentReplyEditStore()
const { t } = useI18n()
const { show } = useFeedback()

const content = ref('')
const saving = ref(false)
const textareaRef = ref(null)

// 打开时把 store 里的内容同步到输入框并聚焦
watch(
  () => store.visible,
  (visible) => {
    if (visible) {
      content.value = store.content
      nextTick(() => textareaRef.value?.focus())
    }
  },
)

const title = computed(() =>
  store.type === 'reply'
    ? t('commentReplyEditDialog.editReplyTitle')
    : t('commentReplyEditDialog.editCommentTitle'),
)

async function save() {
  const text = content.value.trim()
  if (!text) {
    show(t('commentReplyEditDialog.emptyContent'), 'warning')
    return
  }
  saving.value = true
  try {
    const url = store.apiBase
      ? `${store.apiBase}/${store.id}`
      : store.type === 'reply' ? `/replies/${store.id}` : `/comments/${store.id}`
    const { data } = await http.put(url, { content: text })
    show(t('commentReplyEditDialog.success'), 'success')
    store.emitSaved(data)
    store.close()
    // 广播编辑完成事件，CommentItem / ReplyItem 监听后更新本地数据
    const eventName = store.type === 'reply' ? 'reply:edited' : 'comment:edited'
    window.dispatchEvent(new CustomEvent(eventName, { detail: data }))
  } catch (error) {
    show(error.message, 'error')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <v-dialog v-model="store.visible" max-width="520">
    <v-card rounded="lg" >
      <v-toolbar :title="title">
        <template #prepend>
          <v-btn icon variant="text" @click="store.close()">
            <v-icon>mdi-close</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ t('common.cancel') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <v-btn icon variant="text" :loading="saving" @click="save">
            <v-icon>mdi-check</v-icon>
            <v-tooltip activator="parent" location="left">
              {{ t('commentReplyEditDialog.save') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-divider />

      <v-card-text>
        <v-textarea
          ref="textareaRef"
          v-model="content"
          :placeholder="t('commentReplyEditDialog.placeholder')"
          maxlength="1000"
          counter
          auto-grow
          rows="3"
          variant="outlined"
          hide-details="auto"
        />
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

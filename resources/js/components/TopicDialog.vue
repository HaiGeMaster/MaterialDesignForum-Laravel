<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'
import ImageUploadField from './ImageUploadField.vue'

const props = defineProps({
  /** 对话框显隐 */
  modelValue: { type: Boolean, default: false },
  /** 编辑模式传入话题对象；新建模式传 null */
  topic: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'submit'])

const authStore = useAuthStore()
const { show } = useFeedback()
const { t } = useI18n()

const formRef = ref(null)
const submitting = ref(false)
const name = ref('')
const description = ref('')
const cover = ref('')

// 编辑模式（传入话题对象）与新建模式
const isEdit = computed(() => Boolean(props.topic))
const dialogOpen = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// 每次打开时按模式初始化表单
watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    name.value = props.topic?.name || ''
    description.value = props.topic?.description || ''
    cover.value = props.topic?.cover || ''
    formRef.value?.resetValidation()
  },
)

// 名称：必填，最多 20 字（与表结构 name varchar(20) 一致）
const nameRules = [
  (v) => (v?.trim() ? true : t('topicDialog.nameRequired')),
  (v) => (v?.trim().length <= 20 ? true : t('topicDialog.nameMax')),
]

// 描述：必填，最多 1000 字（与表结构 description varchar(1000) 一致）
const descriptionRules = [
  (v) => (v?.trim() ? true : t('topicDialog.descriptionRequired')),
  (v) => (v?.trim().length <= 1000 ? true : t('topicDialog.descriptionMax')),
]

async function submit() {
  const { valid } = await formRef.value?.validate()
  if (!valid) return

  submitting.value = true
  try {
    const payload = {
      name: name.value.trim(),
      description: description.value.trim(),
      cover: cover.value,
    }

    let data
    if (isEdit.value) {
      const res = await http.put(`/topics/${props.topic.topic_id}`, payload)
      data = res.data
      show(t('topicDialog.updateSuccess'), 'success')
    } else {
      payload.user_id = authStore.user?.user_id
      const res = await http.post('/topics', payload)
      data = res.data
      show(t('topicDialog.createSuccess'), 'success')
    }

    emit('submit', data)
    dialogOpen.value = false
  } catch (error) {
    show(error.message || t('topicDialog.submitError'), 'error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <v-dialog v-model="dialogOpen" max-width="560">
    <v-card >
      <v-card-title class="d-flex align-center pr-2">
        <v-icon :icon="isEdit ? 'mdi-pencil-outline' : 'mdi-tag-plus-outline'" class="mr-2" />
        {{ isEdit ? $t('topicDialog.titleEdit') : $t('topicDialog.titleNew') }}
      </v-card-title>

      <v-card-text>
        <v-form ref="formRef" @submit.prevent="submit">
          <v-text-field
            v-model="name"
            :label="$t('topicDialog.name')"
            :rules="nameRules"
            counter="20"
            variant="outlined"
            hide-details="auto"
            class="mb-4"
          />

          <v-textarea
            v-model="description"
            :label="$t('topicDialog.description')"
            :rules="descriptionRules"
            counter="1000"
            variant="outlined"
            rows="3"
            auto-grow
            hide-details="auto"
            class="mb-4"
          />

          <div class="text-subtitle-2 text-medium-emphasis mb-1">
            {{ $t('topicDialog.cover') }}
          </div>
          <ImageUploadField
            v-model="cover"
            type="cover"
            preview-ratio="16 / 6"
            :label="$t('topicDialog.cover')"
          />
        </v-form>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="dialogOpen = false">{{ $t('common.cancel') }}</v-btn>
        <v-btn color="primary" variant="tonal" :loading="submitting" @click="submit">
          {{ isEdit ? $t('common.save') : $t('common.submit') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

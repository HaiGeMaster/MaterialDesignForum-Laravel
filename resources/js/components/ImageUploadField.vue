<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '../stores/user'
import { useFeedback } from '../composables/useFeedback'

const props = defineProps({
  modelValue: { type: String, default: '' },
  type: {
    type: String,
    required: true,
    validator: (v) => ['avatar', 'cover'].includes(v),
  },
  label: { type: String, default: '' },
  // 预览区域宽高比，如头像 '1'，封面 '16 / 6'
  previewRatio: { type: String, default: '1' },
  // 关联场景（如 'topic'），透传给上传接口
  context: { type: String, default: '' },
  // 关联 ID（如话题 ID），透传给上传接口
  itemId: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue'])

const userStore = useUserStore()
const { show: feedback } = useFeedback()
const { t } = useI18n()

const fileInput = ref(null)
// 选中文件后的本地临时预览（未上传成功前）
const localPreview = ref('')

const hasImage = computed(() => Boolean(props.modelValue || localPreview.value))
const previewSrc = computed(() => localPreview.value || props.modelValue || '')

const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
const MAX_SIZE = 2 * 1024 * 1024 // 2MB，与后端 max:2048 保持一致

async function onFileChange(event) {
  const file = event.target.files?.[0]
  // 允许用户再次选择同一个文件
  event.target.value = ''
  if (!file) return

  if (!ALLOWED_TYPES.includes(file.type)) {
    feedback(t('imageUpload.typeError'), 'error')
    return
  }
  if (file.size > MAX_SIZE) {
    feedback(t('imageUpload.sizeError'), 'error')
    return
  }

  // 先本地预览，再上传
  localPreview.value = URL.createObjectURL(file)
  try {
    const result = await userStore.uploadImage(props.type, file, {
      context: props.context || undefined,
      itemId: props.itemId != null ? props.itemId : undefined,
    })
    emit('update:modelValue', result.url)
    feedback(result.message || t('imageUpload.uploaded'), 'success')
  } catch (error) {
    feedback(error.message, 'error')
  } finally {
    URL.revokeObjectURL(localPreview.value)
    localPreview.value = ''
  }
}

function pick() {
  fileInput.value?.click()
}

function clear() {
  emit('update:modelValue', '')
}
</script>

<template>
  <div>
    <!-- 预览区，点击即可选择图片 -->
    <div
      class="image-upload-preview rounded-xl"
      :style="{ aspectRatio: previewRatio }"
      @click="pick"
    >
      <img
        v-if="hasImage"
        :src="previewSrc"
        class="w-100 h-100 image-upload-object-cover"
        :alt="$t('imageUpload.previewAlt')"
      />
      <div
        v-else
        class="d-flex flex-column align-center justify-center h-100 text-medium-emphasis"
      >
        <v-icon
          size="40"
          :icon="type === 'avatar' ? 'mdi-account-circle-outline' : 'mdi-image-outline'"
        />
        <span class="text-caption mt-2">
          {{ $t('imageUpload.clickToSelect', { label: label || $t('imageUpload.select') }) }}
        </span>
      </div>

      <div v-if="userStore.uploading" class="image-upload-loading rounded-xl">
        <v-progress-circular indeterminate size="24" color="primary" />
      </div>
    </div>

    <input
      ref="fileInput"
      type="file"
      accept="image/jpeg,image/png,image/webp,image/gif"
      class="d-none"
      @change="onFileChange"
    />

    <!-- <div class="d-flex ga-2 mt-2">
      <v-btn
        size="small"
        variant="tonal"
        prepend-icon="mdi-upload"
        :loading="userStore.uploading"
        @click="pick"
      >
        {{ $t('imageUpload.select') }}
      </v-btn>
      <v-btn
        v-if="modelValue"
        size="small"
        variant="text"
        color="error"
        prepend-icon="mdi-close-circle-outline"
        @click="clear"
      >
        {{ $t('imageUpload.clear') }}
      </v-btn>
    </div> -->
  </div>
</template>

<style scoped>
.image-upload-preview {
  position: relative;
  overflow: hidden;
  cursor: pointer;
  /* background-color: rgb(var(--v-theme-surface-variant)); */
}

.image-upload-loading {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  /* background-color: rgba(0, 0, 0, 0.45); */
}

.image-upload-object-cover {
  object-fit: cover;
  display: block;
}
</style>

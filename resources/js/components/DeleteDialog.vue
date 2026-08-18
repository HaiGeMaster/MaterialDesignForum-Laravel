<template>
  <v-dialog
    :model-value="modelValue"
    max-width="420"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card >
      <v-card-title class="d-flex align-center pr-2">
        <v-icon :icon="icon" :color="color" class="mr-2" />
        {{ title }}
      </v-card-title>
      <v-card-text>
        {{ text }}
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="$emit('update:modelValue', false)">
          {{ cancelText || t('common.cancel') }}
        </v-btn>
        <v-btn :color="color" variant="tonal" :loading="loading" @click="$emit('confirm')">
          {{ confirmText || t('common.delete') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  /** 是否显示 */
  modelValue: { type: Boolean, default: false },
  /** 对话框标题 */
  title: { type: String, default: '' },
  /** 确认文案 */
  text: { type: String, default: '' },
  /** 标题图标 */
  icon: { type: String, default: 'mdi-delete-outline' },
  /** 图标 / 确认按钮颜色 */
  color: { type: String, default: 'error' },
  /** 删除请求进行中 */
  loading: { type: Boolean, default: false },
  /** 确认按钮文字（默认 common.delete） */
  confirmText: { type: String, default: '' },
  /** 取消按钮文字（默认 common.cancel） */
  cancelText: { type: String, default: '' },
})

defineEmits(['update:modelValue', 'confirm'])

const { t } = useI18n()
</script>

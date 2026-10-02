<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

// 仅在值是合法 hex 颜色时传给选择器，避免输入到一半的非法字符串触发告警
const pickerValue = computed(() =>
  /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(props.modelValue || '') ? props.modelValue : undefined
)
</script>

<template>
  <v-text-field
    v-bind="$attrs"
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <template #append-inner>
      <v-menu
        :close-on-content-click="false"
        :close-on-back="false"
        location="end"
        scrim
      >
        <template #activator="{ props: menuProps }">
          <span
            v-bind="menuProps"
            style="display:inline-block;width:20px;height:20px;border-radius:4px;border:1px solid rgba(0,0,0,.12);flex-shrink:0;cursor:pointer;"
            :style="{ backgroundColor: modelValue || 'transparent' }"
          />
        </template>
        <v-color-picker
          :model-value="pickerValue"
          mode="hex"
          hide-inputs
          elevation="8"
          @update:model-value="emit('update:modelValue', $event)"
        />
      </v-menu>
    </template>
  </v-text-field>
</template>

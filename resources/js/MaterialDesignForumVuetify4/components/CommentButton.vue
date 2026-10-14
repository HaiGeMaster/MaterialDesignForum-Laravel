<script setup>
defineProps({
  /** 评论数量（角标） */
  count: { type: [Number, String], default: 0 },
  /** 按钮提示文案 */
  tooltip: { type: String, default: '' },
})
const emit = defineEmits(['click'])
</script>

<template>
  <v-tooltip :text="tooltip">
    <template v-slot:activator="{ props: tipProps }">
      <v-badge bordered offset-x="8" offset-y="8" color="primary" :content="count" :model-value="Number(count) > 0">
        <v-btn v-bind="tipProps" icon size="small" variant="plain" @click="emit('click')">
          <v-icon size="small">mdi-comment-multiple-outline</v-icon>
        </v-btn>
      </v-badge>
    </template>
  </v-tooltip>
</template>

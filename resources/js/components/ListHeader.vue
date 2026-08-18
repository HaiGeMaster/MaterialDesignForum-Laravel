<script setup>
defineProps({
  /** 标题文案（父组件负责拼好数量，如“评论 (12)”） */
  title: { type: String, default: '' },
  /** 当前排序值 */
  sort: { type: String, default: '' },
  /** 排序项：[{ value, label }] */
  sortItems: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:sort'])
</script>

<template>
  <div class="d-flex align-center justify-space-between px-4 py-2">
    <span class="text-subtitle-1 font-weight-bold text-no-wrap">{{ title }}</span>

    <v-menu
      v-if="sortItems.length"
      :close-on-content-click="true"
      location="bottom end"
    >
      <template v-slot:activator="{ props: menuProps }">
        <v-btn v-bind="menuProps" variant="text" size="small" class="text-none">
          <v-icon size="small" start>mdi-sort</v-icon>
          <span class="text-body-2">
            {{ sortItems.find((i) => i.value === sort)?.label || sortItems[0]?.label }}
          </span>
          <v-icon size="small">mdi-chevron-down</v-icon>
        </v-btn>
      </template>
      <v-list density="compact" min-width="160">
        <v-list-item
          v-for="item in sortItems"
          :key="item.value"
          :active="sort === item.value"
          @click="emit('update:sort', item.value)"
        >
          <v-list-item-title class="text-body-2">{{ item.label }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import ContentListItem from './ContentListItem.vue'

const props = defineProps({
  /** 面板标题，如「热门问题」 */
  title: { type: String, required: true },
  /** 标题栏图标 */
  icon: { type: String, default: 'mdi-list-box-outline' },
  /** 「查看全部」跳转路由 */
  to: { type: String, default: '' },
  /** 列表数据 */
  items: { type: Array, default: () => [] },
  /** 条目类型：question 问题 / article 文章 */
  itemType: { type: String, default: 'question' },
  /** 是否加载中 */
  loading: { type: Boolean, default: false },
  /** 空状态文本（可选，未传时使用通用文案） */
  emptyText: { type: String, default: '' },
})

const emit = defineEmits(['toggle-follow', 'more', 'open'])

// 当前打开头像卡片菜单的列表项 id（同时只开一个）
const activeAvatar = ref(null)

// 问题/文章主键字段不同，统一取 id
function itemId(item) {
  return item.question_id ?? item.article_id
}

const showEmpty = computed(() => !props.loading && props.items.length === 0)
</script>

<template>
  <v-card  class="overflow-hidden fill-height">
    <!-- 顶部标题栏 -->
    <div class="d-flex align-center px-4 py-3 border-b-thin">
      <v-icon :icon="icon" color="primary" size="small" class="mr-2" />
      <span >{{ title }}</span>
      <v-spacer />
      <v-btn v-if="to" size="small" variant="text" :to="to" append-icon="mdi-chevron-right">
        {{ $t('content.viewAll') }}
      </v-btn>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" />

    <!-- 列表 -->
    <v-list v-else-if="items.length" lines="two" style="padding: 0;">
      <ContentListItem
        v-for="item in items"
        :key="itemId(item)"
        :item="item"
        :item-type="itemType"
        :active="activeAvatar === itemId(item)"
        @update:active="(v) => (activeAvatar = v ? itemId(item) : null)"
        @toggle-follow="(...args) => emit('toggle-follow', ...args)"
        @more="(...args) => emit('more', ...args)"
        @open="(item) => emit('open', item)"
      />
    </v-list>

    <!-- 空状态 -->
    <v-empty-state
      v-else
      icon="mdi-inbox-outline"
      :title="$t('content.emptyTitle', { title })"
      :text="emptyText || $t('content.emptyText')"
      class="pa-4"
    />
  </v-card>
</template>

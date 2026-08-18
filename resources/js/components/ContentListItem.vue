<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import UserMiniCard from './UserMiniCard.vue'

const props = defineProps({
  /** 列表数据条目（问题或文章） */
  item: { type: Object, required: true },
  /** 条目类型：question 问题 / article 文章 */
  itemType: { type: String, default: 'question' },
  /** 是否打开用户小卡片菜单（由父组件控制同一时间只开一个） */
  active: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle-follow', 'more', 'update:active', 'open'])
const { t } = useI18n()

// 元信息：问题显示回答数，文章显示评论数
const metaIcon = computed(() =>
  props.itemType === 'article' ? 'mdi-comment-outline' : 'mdi-message-outline'
)

function metaText(item) {
  const time = item.create_time ? String(item.create_time).slice(0, 10) : ''
  if (props.itemType === 'article') {
    return t('content.commentsSuffix', { count: item.comment_count ?? 0, time })
  }
  return t('content.answersSuffix', { count: item.answer_count ?? 0, time })
}

// 作者头像：有头像图用图，否则用用户名首字母
function authorInitial(item) {
  const name = item.creator?.username
  return name ? name.slice(0, 1).toUpperCase() : '?'
}
</script>

<template>
  <v-list-item
    :title="item.title"
    class="border-b-thin"
    @click="emit('open', item)"
  >
    <template #prepend>
      <!-- 有作者信息：头像点击弹出用户小卡片 -->
      <v-menu
        v-if="item.creator"
        :model-value="active"
        :close-on-content-click="false"
        location="start"
        offset="16"
        @update:model-value="(v) => emit('update:active', v)"
      >
        <template #activator="{ props: menuProps }">
          <v-avatar
            size="36"
            color="primary"
            variant="tonal"
            class="cursor-pointer"
            v-bind="menuProps"
            @click.stop="menuProps.onClick?.($event)"
          >
            <v-img v-if="item.creator.avatar" :src="item.creator.avatar" cover />
            <span v-else class="text-body-2 font-weight-bold text-primary">
              {{ authorInitial(item) }}
            </span>
          </v-avatar>
        </template>

        <UserMiniCard
          :user="item.creator"
          @toggle-follow="(payload) => emit('toggle-follow', item, payload)"
          @more="(action) => emit('more', item, action)"
        />
      </v-menu>

      <!-- 无作者信息：静态占位头像 -->
      <v-avatar v-else size="36" color="grey" variant="tonal">
        <span class="text-body-2 font-weight-bold">?</span>
      </v-avatar>
    </template>

    <template #subtitle>
      <span class="d-inline-flex align-center">
        <v-icon :icon="metaIcon" size="x-small" class="mr-1" />
        {{ metaText(item) }}
      </span>
    </template>
  </v-list-item>
</template>

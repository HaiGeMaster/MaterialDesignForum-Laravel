<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useUsersDialogStore } from '../stores/usersDialog'
import { useCommentReplyEditStore } from '../stores/commentReplyEdit'
import { useFeedback } from '../composables/useFeedback'
import ReportDialog from './ReportDialog.vue'

const props = defineProps({
  /** 自定义菜单项：[{ title, icon, color?, divider?, disabled? }] */
  items: { type: Array, default: () => [] },
  /** 菜单弹出位置 */
  location: { type: String, default: 'top end' },
  /** 按钮尺寸 */
  size: { type: String, default: 'small' },
  /**
   * 内容类型：user / topic / question / article / answer / comment / reply
   * 传入 type + item 后会自动生成内置操作（复制链接 / 分享 / 举报 / 编辑 / 删除）
   */
  type: { type: String, default: '' },
  /** 当前数据对象（需含对应 *_id 与 user_id 字段） */
  item: { type: Object, default: null },
  /** 是否管理员（管理员不显示举报） */
  isAdmin: { type: Boolean, default: false },
  /** 是否启用内置操作 */
  withBuiltin: { type: Boolean, default: true },
})

const emit = defineEmits(['select', 'copy-link', 'share', 'report', 'edit', 'delete'])

const authStore = useAuthStore()
const usersDialog = useUsersDialogStore()
const commentReplyEdit = useCommentReplyEditStore()
const { show: feedback } = useFeedback()
const { t } = useI18n()

// 内置举报对话框（点击"举报"时打开）
const reportDialog = ref(false)

const currentUserId = computed(() => authStore.user?.user_id ?? null)

// 当前数据是否为登录用户本人的内容（编辑/删除仅本人可见，举报仅他人可见）
const isSelf = computed(() => {
  if (!props.item) return false
  return String(props.item.user_id) === String(currentUserId.value)
})

// 按类型取数据对象的 ID
const itemId = computed(() => {
  if (!props.item) return null
  const key = {
    user: 'user_id',
    topic: 'topic_id',
    question: 'question_id',
    article: 'article_id',
    answer: 'answer_id',
    comment: 'comment_id',
    reply: 'reply_id',
  }[props.type]
  return key ? props.item[key] : null
})

// 详情页链接（复制链接 / 分享使用）
const detailUrl = computed(() => {
  if (!props.type || itemId.value == null) return ''
  const origin = window.location.origin
  switch (props.type) {
    case 'user':
      return `${origin}/users/${itemId.value}`
    case 'topic':
      return `${origin}/topics/${itemId.value}`
    case 'question':
      return `${origin}/questions/${itemId.value}`
    case 'article':
      return `${origin}/articles/${itemId.value}`
    default:
      return ''
  }
})

// 内置操作：按权限规则自动生成
const builtinItems = computed(() => {
  if (!props.withBuiltin || !props.item) return []
  const list = []

  // 复制链接 / 分享：有详情页时可用
  if (detailUrl.value) {
    list.push({ title: t('moreActions.copyLink'), icon: 'mdi-content-copy', action: 'copy-link' })
    list.push({ title: t('moreActions.share'), icon: 'mdi-share-variant', action: 'share' })
  }

  // 查看关注者：话题 / 问题 / 文章（打开用户浏览对话框）
  if (['topic', 'question', 'article'].includes(props.type)) {
    list.push({
      title: t('moreActions.viewFollowers', { count: props.item?.follower_count ?? 0 }),
      icon: 'mdi-account-multiple-outline',
      action: 'view-followers',
    })
  }

  // 举报：非管理员且非本人
  if (!props.isAdmin && !isSelf.value) {
    list.push({ title: t('moreActions.report'), icon: 'mdi-flag-outline', color: 'error', action: 'report' })
  }

  // 编辑 / 删除：仅本人（用户资料走独立编辑入口，不在此列）
  if (isSelf.value && props.type !== 'user') {
    list.push({ title: t('moreActions.edit'), icon: 'mdi-pencil', action: 'edit' })
    list.push({ title: t('moreActions.delete'), icon: 'mdi-delete-outline', color: 'error', action: 'delete' })
  }

  return list
})

// 合并内置操作与自定义项：内置在前，自定义在后，中间用分隔线隔开
const menuItems = computed(() => {
  const builtin = builtinItems.value
  const custom = props.items ?? []
  if (!builtin.length) return custom
  if (!custom.length) return builtin
  return [...builtin, { divider: true }, ...custom]
})

// 按钮是否可用（至少存在一个可点击项，参考旧项目 LookButtonShowRule）
const hasMenu = computed(() => menuItems.value.some((i) => !i.divider && !i.disabled))

async function handleSelect(item) {
  if (item.divider || item.disabled) return

  // 内置操作：内部处理（复制/分享/举报）或走专用事件，不再触发 select，
  // 避免被父组件当作自定义操作（如"功能开发中"提示）
  switch (item.action) {
    case 'copy-link':
      await copyLink()
      emit('copy-link', props.item)
      return
    case 'share':
      share()
      emit('share', props.item)
      return
    case 'view-followers':
      // 打开用户浏览对话框，展示关注该对象的所有用户
      usersDialog.open({ modes: 'followers', type: props.type, id: itemId.value })
      return
    case 'report':
      // 内置打开举报对话框（未启用内置操作时回退为事件透出）
      if (props.withBuiltin) {
        reportDialog.value = true
        return
      }
      emit('report', props.item)
      return
    case 'edit':
      // 评论 / 回复：内置打开全局编辑对话框（参照旧项目 comment-reply-edit-dialog）
      if (props.type === 'comment' || props.type === 'reply') {
        commentReplyEdit.open({
          type: props.type,
          id: itemId.value,
          content: props.item.content,
        })
        return
      }
      emit('edit', props.item)
      return
    case 'delete':
      emit('delete', props.item)
      return
  }

  // 自定义项照旧
  emit('select', item)
}

async function copyLink() {
  try {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(detailUrl.value)
    } else {
      // 兼容非安全上下文
      const ta = document.createElement('textarea')
      ta.value = detailUrl.value
      ta.style.position = 'fixed'
      ta.style.opacity = '0'
      document.body.appendChild(ta)
      ta.select()
      document.execCommand('copy')
      document.body.removeChild(ta)
    }
    feedback(t('moreActions.copied'), 'success')
  } catch {
    feedback(t('moreActions.copyFailed'), 'error')
  }
}

function share() {
  if (navigator.share) {
    navigator.share({ url: detailUrl.value }).catch(() => {})
  } else {
    copyLink()
  }
}
</script>

<template>
  <v-menu :location="location">
    <template #activator="{ props: menuProps }">
      <v-btn
        :size="size"
        variant="text"
        icon="mdi-dots-vertical"
        v-bind="menuProps"
        :disabled="!hasMenu"
        @click.stop="menuProps.onClick?.($event)"
      />
    </template>

    <v-list density="compact" min-width="160">
      <template v-for="(item, index) in menuItems" :key="index">
        <v-divider v-if="item.divider" />
        <v-list-item
          v-else
          :prepend-icon="item.icon"
          :title="item.title"
          :color="item.color"
          :disabled="item.disabled"
          @click="handleSelect(item)"
        />
      </template>
    </v-list>
  </v-menu>

  <!-- 举报对话框（内置操作） -->
  <ReportDialog v-model="reportDialog" :type="props.type" :item="props.item" />
</template>

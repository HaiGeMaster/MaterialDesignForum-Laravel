<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useUsersDialogStore } from '../stores/usersDialog'
import { useCommentReplyEditStore } from '../stores/commentReplyEditDialog'
import { useReportDialogStore } from '../stores/reportDialog'
import { useUserStore } from '../stores/user'
import { useFeedback } from '../composables/useFeedback'
import { useRoute } from 'vue-router'

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
  // isAdmin: { type: Boolean, default: false },
  /** 是否启用内置操作 */
  withBuiltin: { type: Boolean, default: true },
})

const emit = defineEmits(['select', 'copy-link', 'share', 'report', 'edit', 'delete'])

const authStore = useAuthStore()
const userStore = useUserStore()
const usersDialog = useUsersDialogStore()
const commentReplyEditDialog = useCommentReplyEditStore()
const reportDialog = useReportDialogStore()
const { show: feedback } = useFeedback()
const { t } = useI18n()
const route = useRoute()

const resettingProfileAsset = ref(false)

const currentUserId = computed(() => authStore.user?.user_id ?? null)

// 当前数据是否为登录用户本人的内容（编辑/删除仅本人可见，举报仅他人可见）
const isSelf = computed(() => {
  if (!props.item) return false
  return String(props.item.user_id) === String(currentUserId.value)
})

// 当前用户用户组权限
const userGroup = computed(() => authStore.user?.user_group || {})
const isAdminUser = computed(() => Boolean(userGroup.value.is_admin))
const isOwnUserProfileRoute = computed(() =>
  route.name === 'user'
  && (route.params.user_id === 'me'
    || String(route.params.user_id) === String(props.item?.user_id)),
)
const canResetProfileAsset = computed(() =>
  props.type === 'user'
  && props.item?.user_id != null
  && currentUserId.value != null
  && (isAdminUser.value || (isSelf.value && isOwnUserProfileRoute.value)),
)

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
    case 'answer':
      // 回答链接：/questions/{question_id}/answers/{answer_id}
      if (props.item?.question_id) {
        return `${origin}/questions/${props.item.question_id}/answers/${itemId.value}`
      }
      return ''
    default:
      return ''
  }
})

// ================== 权限检查：时间限制 ==================
/**
 * 将 Laravel datetime 序列化后的 ISO 8601 字符串安全转为毫秒时间戳。
 * Laravel 输出格式如 "2026-08-20T22:07:10.000000Z"（6 位微秒），
 * 部分浏览器引擎对超过 3 位的小数秒解析异常，这里截断到毫秒。
 */
function safeParseTime(value) {
  if (!value) return NaN
  if (typeof value === 'number') return value
  // 截断微秒为毫秒：把 .000000Z → .000Z
  const normalized = String(value).replace(/\.(\d{3})\d*(Z|[+-]\d{2}:\d{2})?$/, '.$1$2')
  return Date.parse(normalized)
}

/** 检查编辑/删除时限是否已过，true=已过期不可操作 */
function isTimeExpired(createTime, timeLimitMinutes) {
  const limit = Number(timeLimitMinutes)
  if (!limit || limit === 0) return false // 0 = 无限期
  if (!createTime) return false
  const created = safeParseTime(createTime)
  if (Number.isNaN(created)) return false
  const limitMs = limit * 60 * 1000
  return Date.now() > created + limitMs
}

// ================== 权限检查：前置条件 ==================
/** 检查是否存在阻碍编辑/删除的子内容（评论/回答/回复等），true=有阻碍不可操作 */
function hasBlockingSubContent(type, item, isDelete = false) {
  const prefix = isDelete ? 'ability_delete' : 'ability_edit'
  const group = userGroup.value

  switch (type) {
    case 'topic': {
      if (!group[`${prefix}_topic_only_no_article_or_question`]) return false
      return (item.article_count > 0 || item.question_count > 0)
    }
    case 'question': {
      if (group[`${prefix}_question_only_no_answer`] && (item.answer_count ?? 0) > 0) return true
      if (group[`${prefix}_question_only_no_comment`] && (item.comment_count ?? 0) > 0) return true
      return false
    }
    case 'article': {
      if (!group[`${prefix}_article_only_no_comment`]) return false
      return (item.comment_count ?? 0) > 0
    }
    case 'answer': {
      if (!group[`${prefix}_answer_only_no_comment`]) return false
      return (item.comment_count ?? 0) > 0
    }
    case 'comment': {
      if (!group[`${prefix}_comment_only_no_reply`]) return false
      return (item.reply_count ?? 0) > 0
    }
    case 'reply': {
      if (!group[`${prefix}_reply_only_no_reply`]) return false
      return (item.reply_count ?? 0) > 0
    }
  }
  return false
}

// ================== 类型 → 权限字段映射 ==================
const TYPE_PERM_MAP = {
  topic:    { editAbility: 'ability_edit_own_topic',    deleteAbility: 'ability_delete_own_topic',    editTime: 'time_before_edit_topic',    deleteTime: 'time_before_delete_topic' },
  question: { editAbility: 'ability_edit_own_question', deleteAbility: 'ability_delete_own_question', editTime: 'time_before_edit_question', deleteTime: 'time_before_delete_question' },
  article:  { editAbility: 'ability_edit_own_article',  deleteAbility: 'ability_delete_own_article',  editTime: 'time_before_edit_article',  deleteTime: 'time_before_delete_article' },
  answer:   { editAbility: 'ability_edit_own_answer',   deleteAbility: 'ability_delete_own_answer',   editTime: 'time_before_edit_answer',   deleteTime: 'time_before_delete_answer' },
  comment:  { editAbility: 'ability_edit_own_comment',  deleteAbility: 'ability_delete_own_comment',  editTime: 'time_before_edit_comment',  deleteTime: 'time_before_delete_comment' },
  reply:    { editAbility: 'ability_edit_own_reply',    deleteAbility: 'ability_delete_own_reply',    editTime: 'time_before_edit_reply',    deleteTime: 'time_before_delete_reply' },
}

// ================== 编辑权限 ==================
// 注意：本组件仅处理本人内容的编辑/删除，管理员编辑他人内容请走后台模块。
const canEdit = computed(() => {
  if (!props.item || !props.type || props.type === 'user') return false
  // 仅本人 + 有权限 + 时限未过 + 前置条件满足（管理员亦同）
  if (!isSelf.value) return false
  const perm = TYPE_PERM_MAP[props.type]
  if (!perm) return false
  if (!userGroup.value[perm.editAbility]) return false
  if (isTimeExpired(props.item.create_time, userGroup.value[perm.editTime])) return false
  if (hasBlockingSubContent(props.type, props.item, false)) return false
  return true
})

// ================== 删除权限 ==================
const canDelete = computed(() => {
  if (!props.item || !props.type || props.type === 'user') return false
  if (!isSelf.value) return false
  const perm = TYPE_PERM_MAP[props.type]
  if (!perm) return false
  if (!userGroup.value[perm.deleteAbility]) return false
  if (isTimeExpired(props.item.create_time, userGroup.value[perm.deleteTime])) return false
  if (hasBlockingSubContent(props.type, props.item, true)) return false
  return true
})

// ================== 举报权限 ==================
const canReport = computed(() => {
  if (!props.item || !props.type) return false
//   if (isAdminUser.value) return false // 管理员不举报，直接后台处理
  if (isSelf.value) return false // 不能举报自己
  return true
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

  if (canResetProfileAsset.value) {
    list.push(
      { title: t('moreActions.resetAvatar'), icon: 'mdi-account-circle-outline', action: 'reset-avatar', disabled: resettingProfileAsset.value },
      { title: t('moreActions.resetCover'), icon: 'mdi-image-refresh-outline', action: 'reset-cover', disabled: resettingProfileAsset.value },
    )
  }

  // 举报：非管理员且非本人
  if (canReport.value) {
    list.push({ title: t('moreActions.report'), icon: 'mdi-flag-outline', color: 'error', action: 'report' })
  }

  // 编辑 / 删除：基于权限检查（用户资料走独立编辑入口，不在此列）
  if (canEdit.value) {
    list.push({ title: t('moreActions.edit'), icon: 'mdi-pencil', action: 'edit' })
  }
  if (canDelete.value) {
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
    case 'reset-avatar':
      await resetProfileAsset('avatar')
      return
    case 'reset-cover':
      await resetProfileAsset('cover')
      return
    case 'report':
      // 内置打开举报对话框（未启用内置操作时回退为事件透出）
      if (props.withBuiltin) {
        reportDialog.open({ type: props.type, item: props.item })
        return
      }
      emit('report', props.item)
      return
    case 'edit':
      // 评论 / 回复：内置打开全局编辑对话框
      if (props.type === 'comment' || props.type === 'reply') {
        const plural = props.type === 'comment' ? 'comments' : 'replies'
        commentReplyEditDialog.open({
          type: props.type,
          id: itemId.value,
          content: props.item.content,
          // 管理员编辑他人内容时走管理端 API
          apiBase: isAdminUser.value && !isSelf.value
            ? `/admin/modules/${plural}`
            : undefined,
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

async function resetProfileAsset(type) {
  if (!canResetProfileAsset.value || resettingProfileAsset.value) return

  resettingProfileAsset.value = true
  try {
    const data = await userStore.resetProfileAsset(props.item.user_id, type)
    const value = data?.user?.[type]
    if (typeof value !== 'string' || !value) {
      throw new Error(t('moreActions.resetFailed'))
    }

    props.item[type] = value
    if (isSelf.value && authStore.user) {
      authStore.setUser({ ...authStore.user, [type]: value })
    }
    feedback(t(type === 'avatar' ? 'moreActions.resetAvatarSuccess' : 'moreActions.resetCoverSuccess'), 'success')
  } catch (error) {
    feedback(error.message || t('moreActions.resetFailed'), 'error')
  } finally {
    resettingProfileAsset.value = false
  }
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

    <v-list rounded="lg" density="compact" >
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
</template>

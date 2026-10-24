<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useLocalizedDateTime } from '../composables/useLocalizedDateTime'

const props = defineProps({
    item: { type: Object, required: true },
})

const { t } = useI18n()
const formatDateTime = useLocalizedDateTime()

// 发送者信息（后端 index 已附加 sender_user）
const sender = computed(() => props.item.sender_user || {})
const senderInitial = computed(() => (sender.value.username || '?').slice(0, 1).toUpperCase())

// 相对时间显示：与评论等组件一致
const timeText = computed(() => {
    return formatDateTime(props.item.create_time)
})

// 通知类型文案
const typeText = computed(() => t(`notificationType.${props.item.type}`) || props.item.type)
const contentText = computed(() => {
    const resultKeys = {
        report_handled_action_taken: 'notificationResult.reportHandled.actionTaken',
        report_handled_no_action: 'notificationResult.reportHandled.noAction',
    }
    const resultKey = resultKeys[props.item.content_rendered]

    return resultKey ? t(resultKey) : props.item.content_rendered
})

// 点击跳转链接（参照旧项目 SpawnLink 逻辑）
function notificationLink(item) {
    if (!item || !item.type) return null
    switch (item.type) {
        case 'user_follow':
            return sender.value.user_id ? { path: `/users/${sender.value.user_id}` } : null
        case 'topic_follow':
        case 'topic_delete':
            return item.topic_id ? { path: `/topics/${item.topic_id}` } : null
        case 'question_follow':
        case 'question_comment':
        case 'question_answer':
        case 'question_delete':
            return item.question_id ? { path: `/questions/${item.question_id}` } : null
        case 'article_follow':
        case 'article_comment':
        case 'article_like':
        case 'article_delete':
            return item.article_id ? { path: `/articles/${item.article_id}` } : null
        case 'answer_comment':
        case 'answer_like':
        case 'answer_delete':
            return item.question_id ? { path: `/questions/${item.question_id}` } : null
        case 'comment_like':
        case 'comment_reply':
        case 'comment_delete':
        case 'reply_like':
        case 'reply_reply':
        case 'reply_delete':
            if (item.question_id) return { path: `/questions/${item.question_id}` }
            if (item.article_id) return { path: `/articles/${item.article_id}` }
            return null
        case 'follow_user_update':
            if (item.article_id) return { path: `/articles/${item.article_id}` }
            if (item.question_id) return { path: `/questions/${item.question_id}` }
            return sender.value.user_id ? { path: `/users/${sender.value.user_id}` } : null
        case 'follow_topic_update':
            if (item.article_id) return { path: `/articles/${item.article_id}` }
            if (item.question_id) return { path: `/questions/${item.question_id}` }
            return item.topic_id ? { path: `/topics/${item.topic_id}` } : null
        case 'follow_question_update':
            return item.question_id ? { path: `/questions/${item.question_id}` } : null
        case 'follow_article_update':
            return item.article_id ? { path: `/articles/${item.article_id}` } : null
        case 'report_handled':
            if (item.question_id) return { path: `/questions/${item.question_id}` }
            if (item.article_id) return { path: `/articles/${item.article_id}` }
            if (item.topic_id) return { path: `/topics/${item.topic_id}` }
            return item.user_id ? { path: `/users/${item.user_id}` } : null
        default:
            return null
    }
}

// 类型图标（参照旧项目 SpawnIcon 逻辑）
function notificationIcon(item) {
    if (!item || !item.type) return 'mdi-bell-outline'
    switch (item.type) {
        case 'user_follow':
            return 'mdi-account'
        case 'topic_follow':
        case 'topic_delete':
            return 'mdi-book-variant'
        case 'question_follow':
        case 'question_comment':
        case 'question_answer':
        case 'question_delete':
            return 'mdi-forum'
        case 'article_follow':
        case 'article_comment':
        case 'article_delete':
            return 'mdi-file-document'
        case 'article_like':
        case 'answer_like':
        case 'comment_like':
        case 'reply_like':
            return 'mdi-thumb-up'
        case 'answer_comment':
        case 'answer_delete':
            return 'mdi-message-reply'
        case 'comment_reply':
        case 'comment_delete':
            return 'mdi-message-reply-text'
        case 'reply_reply':
        case 'reply_delete':
            return 'mdi-reply-all'
        case 'follow_user_update':
        case 'follow_topic_update':
        case 'follow_question_update':
        case 'follow_article_update':
            return 'mdi-star'
        case 'report_handled':
            return 'mdi-flag'
        default:
            return 'mdi-bell-outline'
    }
}
</script>

<template>

    <!-- <v-card class="ma-4" rounded="lg" :to="notificationLink(item)">
        <v-card-item>
            <v-card-title class="text-body-medium d-flex align-center">
                <v-icon color="#949cf7" start>
                    {{ notificationIcon(item) }}
                </v-icon>

                <span class="text-medium-emphasis font-weight-bold">{{ timeText }}</span>

                <v-spacer></v-spacer>

                <v-avatar :image="sender.avatar" size="x-small"
                    :to="sender.user_id ? `/users/${sender.user_id}` : undefined" @click.stop></v-avatar>

                <v-chip class="ms-2 text-medium-emphasis" color="grey-darken-4" prepend-icon="mdi-account-multiple"
                    size="small" :text="sender.username" variant="flat"></v-chip>
            </v-card-title>

            <div class="py-2">
                <div class="text-title-large">{{ item.content_rendered }}</div>

                <div class="font-weight-light text-medium-emphasis">
                    {{ typeText }}
                </div>
            </div>
        </v-card-item>

        <v-divider></v-divider>

        <div class="pa-4 d-flex align-center">
            <v-spacer></v-spacer>

            <v-btn class="text-none" prepend-icon="mdi-check" variant="text" border>
                Interested
            </v-btn>
        </div>
    </v-card> -->

    <v-list-item rounded="lg" :to="notificationLink(item)" class="notification-item px-2" lines="two">
        <template #prepend>
            <v-avatar size="40" :variant="sender?.avatar?'': 'tonal'" :to="sender.user_id ? `/users/${sender.user_id}` : undefined" @click.stop>
                <v-img v-if="sender.avatar" :src="sender.avatar" cover />
                <span v-else class="text-body-2 font-weight-bold text-primary">{{ senderInitial }}</span>
            </v-avatar>
        </template>

        <v-list-item-title class="d-flex align-center ga-1 text-subtitle-2">
            <span class="font-weight-medium">{{ sender.username }}</span>
            <v-icon size="small" color="medium-emphasis">{{ notificationIcon(item) }}</v-icon>
        </v-list-item-title>

        <v-list-item-subtitle class="text-body-2">
            <div class="text-medium-emphasis">{{ typeText }}</div>
            <div v-if="contentText" class="text-truncate text-medium-emphasis" style="max-width: 320px;">
                {{ contentText }}
            </div>
        </v-list-item-subtitle>

        <template #append>
            <span class="text-caption text-medium-emphasis">{{ timeText }}</span>
        </template>
    </v-list-item>
</template>

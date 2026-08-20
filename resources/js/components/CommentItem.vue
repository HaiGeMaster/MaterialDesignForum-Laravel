<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import http from "../api";
import InfiniteScroll from "./InfiniteScroll.vue";
import MoreActions from "./MoreActions.vue";
import ReplyItem from "./ReplyItem.vue";
import UserMiniCard from "./UserMiniCard.vue";
import VoteButton from "./VoteButton.vue";
import { useAuthStore } from "../stores/auth";
import { useReplySheetStore } from "../stores/replySheet";
import { useDeleteDialogStore } from "../stores/deleteDialog";
import { useFeedback } from "../composables/useFeedback";

const props = defineProps({
    comment: { type: Object, required: true },
});
const emit = defineEmits(["deleted"]);

const { t } = useI18n();
const authStore = useAuthStore();
const replySheet = useReplySheetStore();
const deleteDialog = useDeleteDialogStore();
const { show } = useFeedback();

// ---- 评论主体 ----
const author = computed(() => props.comment.creator || {});
const authorInitial = computed(() =>
    (author.value.username || "?").slice(0, 1).toUpperCase(),
);

function timeText(time) {
    return time ? String(time).slice(0, 16).replace("T", " ") : "";
}

// 旧布局：时间旁显示用户上次登录位置（有则显示）
const locationText = computed(() => {
    const loc = author.value.last_login_location;
    return loc && String(loc).trim() !== "" ? ` · ${loc}` : "";
});

// ---- 回复列表 ----
const expanded = ref(false);
const repliesLoaded = ref(false);
const replies = ref([]);
const repliesLoading = ref(false);
const repliesError = ref(false);
const repliesFinished = ref(false);
const repliesPage = ref(1);
const replyCount = ref(Number(props.comment.reply_count || 0));

// 父组件重载后同步后端计数
watch(
    () => props.comment.reply_count,
    (v) => {
        replyCount.value = Number(v || 0);
    },
);

function toggleReplies() {
    expanded.value = !expanded.value;
    if (expanded.value && !repliesLoaded.value) {
        loadReplies(true);
    }
}

async function loadReplies(reset = false) {
    if (reset) {
        repliesPage.value = 1;
        replies.value = [];
        repliesError.value = false;
        repliesFinished.value = false;
    }
    repliesLoading.value = true;
    try {
        const { data } = await http.get("/replies", {
            params: {
                replyable_comment_id: props.comment.comment_id,
                page: repliesPage.value,
            },
        });
        const list = data.data || [];
        replies.value = reset ? list : [...replies.value, ...list];
        repliesLoaded.value = true;
        repliesFinished.value = !(data.current_page < data.last_page);
        repliesPage.value += 1;
    } catch {
        repliesError.value = true;
    } finally {
        repliesLoading.value = false;
    }
}

function handleReplyAdded(e) {
    const { reply, commentId } = e.detail || {};
    if (
        commentId === undefined ||
        String(commentId) !== String(props.comment.comment_id)
    )
        return;
    expanded.value = true;
    if (repliesLoaded.value) {
        replies.value.unshift(reply);
    } else {
        loadReplies(true);
    }
    replyCount.value += 1;
}

function handleReplyDeleted(reply) {
    const i = replies.value.findIndex((r) => r.reply_id === reply.reply_id);
    if (i !== -1) replies.value.splice(i, 1);
    replyCount.value = Math.max(0, replyCount.value - 1);
}

// 编辑完成：用后端返回的最新数据覆盖本地对象（保持引用，触发响应式更新）
function handleCommentEdited(e) {
    const updated = e.detail;
    if (
        !updated ||
        String(updated.comment_id) !== String(props.comment.comment_id)
    )
        return;
    Object.assign(props.comment, updated);
}

// ---- 回复评论 / 删除评论 ----
function openReply() {
    if (!authStore.isLoggedIn) {
        show(t("commentSheet.loginFirst"), "info");
        return;
    }
    replySheet.open({
        replyableId: props.comment.comment_id,
        replyableType: "comment",
        replyableCommentId: props.comment.comment_id,
        replyableUserId: props.comment.user_id,
        target: {
            username: author.value.username,
            avatar: author.value.avatar,
            content: props.comment.content,
        },
    });
}

function askDelete() {
    deleteDialog.open({
        title: t("commentSection.deleteTitle"),
        text: t("commentSection.deleteConfirm"),
        onConfirm: confirmDeleteComment,
    });
}

async function confirmDeleteComment() {
    await http.delete(`/comments/${props.comment.comment_id}`);
    show(t("commentSection.deleted"), "success");
    emit("deleted");
}

onMounted(() => {
    window.addEventListener("reply:added", handleReplyAdded);
    window.addEventListener("comment:edited", handleCommentEdited);
});
onBeforeUnmount(() => {
    window.removeEventListener("reply:added", handleReplyAdded);
    window.removeEventListener("comment:edited", handleCommentEdited);
});
</script>

<template>
    <!-- 参考旧项目布局：v-list-item 结构（头像 / 用户名 / 内容+回复 / 操作区） -->
    <v-list-item
        rounded="lg"
        active-class="text-primary"
        class="py-3 comment-item"
        @click="openReply"
    >
        <!-- 头像：点击弹出用户小卡片 -->
        <template #prepend>
            <v-menu
                v-if="author.user_id"
                :close-on-content-click="false"
                offset="16"
            >
                <template #activator="{ props: menuProps }">
                    <v-avatar
                        size="36"
                        color="primary"
                        
                        class="cursor-pointer"
                        v-bind="menuProps"
                        @click.stop="menuProps.onClick?.($event)"
                    >
                        <v-img
                            v-if="author.avatar"
                            :src="author.avatar"
                            cover
                        />
                        <span
                            v-else
                            class="text-body-2 font-weight-bold text-primary"
                            >{{ authorInitial }}</span
                        >
                    </v-avatar>
                </template>
                <UserMiniCard :user="author" />
            </v-menu>
            <v-avatar v-else size="36" color="grey" >
                <span class="text-body-2 font-weight-bold">?</span>
            </v-avatar>
        </template>

        <!-- 用户名 -->
        <v-list-item-title>{{ author.username }}</v-list-item-title>

        <!-- 内容 + 回复列表 -->
        <v-list-item-subtitle
            class="mb-1 text-high-emphasis opacity-100"
        >
            <div
                class="text-body-2 comment-item__content"
                style="white-space: pre-wrap; word-break: break-word"
            >
                {{ comment.content }}
            </div>

            <v-expand-transition>
                <v-card
                    v-if="expanded"
                    rounded="lg"
                    variant="flat"
                    class="mt-1"
                    @click.stop
                >
                    <v-card-text v-show="replies.length" class="pa-2">
                        <template
                            v-for="(reply, i) in replies"
                            :key="reply.reply_id"
                        >
                            <ReplyItem
                                :reply="reply"
                                :comment-id="comment.comment_id"
                                @deleted="handleReplyDeleted(reply)"
                            />
                            <v-divider
                                v-if="i < replies.length - 1"
                            />
                        </template>
                        <InfiniteScroll
                            :loading="repliesLoading"
                            :error="repliesError"
                            :finished="repliesFinished"
                            @load="loadReplies(false)"
                            @retry="loadReplies(false)"
                        />
                    </v-card-text>
                </v-card>
            </v-expand-transition>
        </v-list-item-subtitle>

        <!-- 操作区：时间 / 查看回复 / 加载更多 / 投票 / 更多 -->
        <v-list-item-action>
            <v-list-item-subtitle class="text-high-emphasis">
        {{ timeText(comment.create_time)
                    }}{{ locationText }}
      </v-list-item-subtitle>
                <!-- 查看 / 收起回复 -->
                <v-btn
                    v-show="replyCount > 0"
                    rounded="lg"
                    size="small"
                    variant="plain"
                    class="text-high-emphasis"
                    style="margin-left: 8px"
                    @click.stop="toggleReplies"
                    :loading="repliesLoading"
                >
                    <v-icon>{{
                        expanded ? "mdi-chevron-up" : "mdi-chevron-down"
                    }}</v-icon>
                    {{
                        expanded
                            ? t("commentItem.hideReplies")
                            : t("commentItem.viewReplies", {
                                  count: replyCount,
                              })
                    }}
                </v-btn>

                <!-- 加载更多回复 -->
                <v-btn
                    v-if="repliesLoaded"
                    v-show="!repliesFinished && replyCount > 0 && expanded"
                    rounded="lg"
                    variant="text"
                    color="primary"
                    size="small"
                    @click.stop="loadReplies(false)"
                >
                    <v-icon>mdi-subdirectory-arrow-right</v-icon>
                    {{ t("commentItem.showMoreReplies") }}
                    ({{ replies.length }}/{{ replyCount }})
                </v-btn>

                <v-spacer />
                <VoteButton
                    type="comment"
                    :item-id="comment.comment_id"
                    :up-count="Number(comment.vote_up_count || 0)"
                    :down-count="Number(comment.vote_down_count || 0)"
                    :my-vote="comment.my_vote || null"
                />
                <MoreActions type="comment" :item="comment" @delete="askDelete" />
            <!-- </div> -->
        </v-list-item-action>
    </v-list-item>
</template>

<style scoped>
.comment-item {
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.comment-item:hover {
    background-color: rgba(128, 128, 128, 0.06);
}
.comment-item__content {
    min-width: 0;
}
</style>

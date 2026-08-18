<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useUsersDialogStore } from "../stores/usersDialog";
import FollowButton from "./FollowButton.vue";
import MoreActions from "./MoreActions.vue";
import ProfileEditDialog from "./ProfileEditDialog.vue";

const props = defineProps({
    user: { type: Object, required: true },
    // 展示模式：self=当前用户（显示邮箱），other=他人主页（显示关注/更多操作）
    mode: { type: String, default: "self" },
    following: { type: Boolean, default: false },
});

const emit = defineEmits(["toggle-follow", "more"]);

const { t } = useI18n();
const usersDialog = useUsersDialogStore();
const groupName = computed(() => props.user?.user_group?.name || t("userProfile.noGroup"));
const initial = computed(
    () => props.user?.username?.slice(0, 1).toUpperCase() || "?",
);

// 编辑个人资料对话框开关（self 模式）
const editDialog = ref(false);

// 自定义更多项（内置的复制链接/分享/举报由 MoreActions 按权限自动生成）
const moreItems = [{ title: t("userProfile.message"), icon: "mdi-message-outline" }];
</script>

<template>
    <v-card >
        <!-- 封面与头像：头像绝对定位，中心点正好落在封面底边 -->
        <div class="cover-wrap">
            <v-img v-if="user?.cover" :src="user.cover" height="240" cover />
            <div v-else class="bg-primary" style="height: 240px"></div>
            <v-avatar size="96" color="secondary" class="border avatar-center">
                <v-img v-if="user?.avatar" :src="user.avatar" cover />
                <span v-else class="text-h4">{{ initial }}</span>
            </v-avatar>
        </div>

        <v-card-text class="text-center pt-16">
            <div class="text-h6 mt-3">{{ user?.username }}</div>
            <div class="text-body-2 text-medium-emphasis mb-2">
                {{ user?.headline || $t("userProfile.noHeadline") }}
            </div>
            <v-chip size="small" color="primary" variant="tonal">
                {{ groupName }}
            </v-chip>

            <v-list v-if="user" lines="one" density="compact" class="mt-2">
                <v-list-item
                    v-if="user.blog"
                    prepend-icon="mdi-link"
                    :title="user.blog"
                />
                <v-list-item
                    v-if="user.company"
                    prepend-icon="mdi-city"
                    :title="user.company"
                />
                <v-list-item
                    v-if="user.location"
                    prepend-icon="mdi-map-marker"
                    :title="user.location"
                />
                <v-list-item
                    v-if="user.bio"
                    prepend-icon="mdi-file-document"
                    :title="user.bio"
                />
            </v-list>

            <v-divider class="my-3" />

            <!-- 操作区：自己显示编辑资料按钮，他人显示关注 + 统计 + 更多操作 -->
            <div class="d-flex align-center mt-4 px-1">
                <div class="d-flex align-center ga-3">
                    <v-btn
                        v-if="mode === 'self'"
                        color="primary"
                        size="small"
                        @click="editDialog = true"
                        icon="mdi-pencil"
                    >
                    </v-btn>
                    <FollowButton
                        v-else
                        type="user"
                        :target-id="user.user_id"
                        :following="following"
                        @toggle-follow="emit('toggle-follow', $event)"
                    />

                    <v-btn
                        variant="tonal"
                        size="small"
                        prepend-icon="mdi-account-arrow-right-outline"
                        @click="
                            usersDialog.open({
                                modes: 'followees',
                                type: 'user',
                                id: user.user_id,
                            })
                        "
                    >
                        {{ $t("userProfile.following", { count: user?.followee_count ?? 0 }) }}
                    </v-btn>
                    <v-btn
                        variant="tonal"
                        size="small"
                        prepend-icon="mdi-account-heart-outline"
                        @click="
                            usersDialog.open({
                                modes: 'followers',
                                type: 'user',
                                id: user.user_id,
                            })
                        "
                    >
                        {{ $t("userProfile.followers", { count: user?.follower_count ?? 0 }) }}
                    </v-btn>
                </div>

                <v-spacer />

                <MoreActions
                    type="user"
                    :item="user"
                    :items="moreItems"
                    @select="(item) => emit('more', item.title)"
                />
            </div>
        </v-card-text>

        <ProfileEditDialog v-model="editDialog" :user="user" />
    </v-card>
</template>

<style scoped>
.cover-wrap {
    position: relative;
}

.avatar-center {
    /* 头像 96px，bottom 负半高使其中心点恰好对齐封面底边 */
    position: absolute;
    left: 50%;
    bottom: -48px;
    transform: translateX(-50%);
    z-index: 1;
}
</style>

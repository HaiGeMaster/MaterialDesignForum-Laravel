<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useDisplay } from "vuetify";
import { useAuthStore } from "../stores/auth";
import { useUserStore } from "../stores/user";
import { useFeedback } from "../composables/useFeedback";
import { useUsersDialogStore } from "../stores/usersDialog";
import { useProfileEditStore } from "../stores/profileEditDialog";
import FollowButton from "./FollowButton.vue";
import MoreActions from "./MoreActions.vue";

const props = defineProps({
    user: { type: Object, required: true },
    // 展示模式：self=当前用户（显示邮箱），other=他人主页（显示关注/更多操作）
    mode: { type: String, default: "self" },
    following: { type: Boolean, default: false },
});

const emit = defineEmits(["toggle-follow", "saved"]);

const { t } = useI18n();
const { xs } = useDisplay();
const authStore = useAuthStore();
const userStore = useUserStore();
const { show: feedback } = useFeedback();
const usersDialog = useUsersDialogStore();
const profileEdit = useProfileEditStore();
const groupName = computed(
    () => props.user?.user_group?.user_group_name || t("userProfile.noGroup"),
);
const groupIcon = computed(
    () => props.user?.user_group?.user_group_icon || "mdi-account",
);
const groupColor = computed(() => props.user?.user_group?.user_group_color || "");
const initial = computed(
    () => props.user?.username?.slice(0, 1).toUpperCase() || "?",
);

// ---------- 封面 / 头像快捷上传（self 模式，悬浮显示） ----------
const ALLOWED_TYPES = ["image/jpeg", "image/png", "image/webp", "image/gif"];
const MAX_SIZE = 2 * 1024 * 1024; // 2MB，与后端 max:2048 保持一致

const fileInput = ref(null);
const pendingType = ref("cover");
const uploadingType = ref("");

const fmtCount = (n) =>
    new Intl.NumberFormat(undefined, {
        notation: "compact",
        maximumFractionDigits: 1,
    }).format(Number(n ?? 0));

function pick(type) {
    pendingType.value = type;
    fileInput.value?.click();
}

async function onFileChange(event) {
    const file = event.target.files?.[0];
    // 允许用户再次选择同一个文件
    event.target.value = "";
    if (!file) return;

    if (!ALLOWED_TYPES.includes(file.type)) {
        feedback(t("imageUpload.typeError"), "error");
        return;
    }
    if (file.size > MAX_SIZE) {
        feedback(t("imageUpload.sizeError"), "error");
        return;
    }

    const type = pendingType.value;
    uploadingType.value = type;
    try {
        const result = await userStore.uploadImage(type, file);
        // 上传成功后立即保存并同步当前登录用户信息
        const saved = await userStore.updateProfile({ [type]: result.url });
        authStore.setUser(saved.user);
        feedback(result.message || t("imageUpload.uploaded"), "success");
        emit("saved", saved.user);
    } catch (error) {
        feedback(error.message, "error");
    } finally {
        uploadingType.value = "";
    }
}

</script>

<template>
    <v-card rounded="lg">
        <!-- 封面：头像+名字行整体以封面底边为锚 -->
        <div class="cover-wrap">
            <v-img v-if="user?.cover" :src="user.cover" alt="" height="440" cover />
            <div v-else class="bg-primary" style="height: 440px"></div>

            <!--
                头像+名字行：bottom: -48px（行高 96px），行中心点精确落在封面底边；
                xs 居中 / 非 xs 靠左，名字垂直居中对齐头像中心
            -->
            <div class="profile-row" :class="xs ? 'profile-row--xs' : 'profile-row--left'">
                <v-avatar border size="96" class=" avatar-wrap elevation-2">
                    <v-img v-if="user?.avatar" :src="user.avatar" :alt="user?.username" cover />
                    <span v-else class="text-h4">{{ initial }}</span>
                    <!-- 头像上传按钮：悬浮头像时显示，居中对齐头像 -->
                    <v-btn v-if="mode === 'self'" class="avatar-upload-btn" icon size="x-small" color="warning"
                        :aria-label="$t('userProfile.uploadAvatar')" :loading="uploadingType === 'avatar'"
                        @click.stop="pick('avatar')">
                        <v-icon icon="mdi-camera" />
                        <v-tooltip activator="parent">{{ $t('userProfile.uploadAvatar') }}</v-tooltip>
                    </v-btn>
                </v-avatar>

                <!-- 非 xs：名字与一句话在头像右侧 -->
                <div v-if="!xs" class="text-start">
                    <div class="profile-name">{{ user?.username }}</div>
                    <div class="text-body-2 text-medium-emphasis">
                        {{ user?.headline  }}
                    </div>
                </div>
            </div>

            <!-- 封面上传按钮：悬浮封面时显示，位于右上角 -->
            <v-btn v-if="mode === 'self'" class="cover-upload-btn" icon size="small" color="warning"
                :aria-label="$t('userProfile.uploadCover')" :loading="uploadingType === 'cover'"
                @click.stop="pick('cover')">

                <v-icon icon="mdi-camera" />
                <v-tooltip activator="parent">{{ $t('userProfile.uploadCover') }}</v-tooltip>
            </v-btn>
            <v-chip :color="groupColor || 'primary'" variant="tonal" class="group-chip">
                <v-icon v-if="groupIcon" :icon="groupIcon" class="mr-1" />
                {{ groupName }}
            </v-chip>
        </div>

        <v-card-text :class="xs ? 'text-center pt-16' : 'pt-16'">
            <!-- xs：名字与一句话居中显示在头像下方 -->
            <div v-if="xs">
                <div class="profile-name">{{ user?.username }}</div>
                <div class="text-body-2 text-medium-emphasis mb-2">
                    {{ user?.headline || $t("userProfile.noHeadline") }}
                </div>
            </div>

            <v-list v-if="user" lines="one" density="compact" class="mt-2"
                :style="xs ? { margin: 'auto', width: 'auto' } : {}">
                <v-list-item v-if="user.blog" prepend-icon="mdi-link" :title="user.blog" />
                <v-list-item v-if="user.company" prepend-icon="mdi-city" :title="user.company" />
                <v-list-item v-if="user.location" prepend-icon="mdi-map-marker" :title="user.location" />
                <v-list-item v-if="user.bio" prepend-icon="mdi-file-document" :title="user.bio" />
            </v-list>

            <v-divider class="my-3" />

            <!-- 操作区：自己显示编辑资料按钮，他人显示关注 + 统计 + 更多操作 -->
            <div class="d-flex align-center mt-4 px-1">
                <div class="d-flex align-center ga-3">
                    <v-btn v-if="mode === 'self'" variant="tonal" size="small"
                        @click="profileEdit.open(user, { onSaved: (u) => emit('saved', u) })" icon>
                        <v-icon icon="mdi-pencil" />
                        <v-tooltip activator="parent">{{ $t('profileEdit.title') }}</v-tooltip>
                    </v-btn>
                    <FollowButton v-else type="user" :target-id="user.user_id" :following="following"
                        @toggle-follow="emit('toggle-follow', $event)" />
                    <v-btn variant="text" prepend-icon="mdi-account-arrow-right-outline" @click="
                        usersDialog.open({
                            modes: 'followees',
                            type: 'user',
                            id: user.user_id,
                        })
                        ">
                        {{ $t("userProfile.following", { count: user?.followee_count ?? 0 }) }}
                    </v-btn>
                    <v-btn variant="text" prepend-icon="mdi-account-heart-outline" @click="
                        usersDialog.open({
                            modes: 'followers',
                            type: 'user',
                            id: user.user_id,
                        })
                        ">
                        {{ $t("userProfile.followers", { count: user?.follower_count ?? 0 }) }}
                    </v-btn>
                </div>

                <v-spacer />

                <MoreActions type="user" :item="user" />
            </div>
        </v-card-text>

        <!-- 封面/头像上传用的隐藏文件选择 -->
        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="d-none"
            @change="onFileChange" />
    </v-card>
</template>

<style scoped>
.cover-wrap {
    position: relative;
}

/* 头像+名字行：absolute 锚定封面底边。
   bottom: -48px 且行高由 96px 头像决定，
   行中心（头像中心）精确落在封面底边线上。 */
.profile-row {
    position: absolute;
    bottom: -48px;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 16px;
}

/* xs：水平居中 */
.profile-row--xs {
    left: 50%;
    transform: translateX(-50%);
}

/* 其他：靠左 */
.profile-row--left {
    left: 24px;
}

/* 用户名：显式加大加粗（不依赖 Vuetify typography 类） */
.profile-name {
    font-size: 28px !important;
    font-weight: 700 !important;
    line-height: 1.4;
}

.profile-row .profile-name {
    color: #fff;
    -webkit-text-stroke: 1px rgba(0, 0, 0, 0.75);
    paint-order: stroke fill;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);
}

.group-chip {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 1;
}

/* 封面上传按钮：悬浮封面区时显示，右上角 */
.cover-upload-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 2;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.cover-wrap:hover .cover-upload-btn {
    opacity: 1;
}

/* 头像上传按钮：悬浮头像时显示，居中对齐头像 */
.avatar-upload-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.avatar-wrap:hover .avatar-upload-btn {
    opacity: 1;
}

/* 触摸设备：没有 hover 状态，上传按钮常驻显示 */
/* @media (hover: none) {
    .cover-upload-btn,
    .avatar-upload-btn {
        opacity: 1;
    }
} */
</style>






































<!-- <script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useDisplay } from "vuetify";
import { useAuthStore } from "../stores/auth";
import { useUserStore } from "../stores/user";
import { useFeedback } from "../composables/useFeedback";
import { useUsersDialogStore } from "../stores/usersDialog";
import { useProfileEditStore } from "../stores/profileEditDialog";
import FollowButton from "./FollowButton.vue";
import MoreActions from "./MoreActions.vue";

const props = defineProps({
    user: { type: Object, required: true },
    // 展示模式：self=当前用户（显示邮箱），other=他人主页（显示关注/更多操作）
    mode: { type: String, default: "self" },
    following: { type: Boolean, default: false },
});

const emit = defineEmits(["toggle-follow", "saved"]);

const { t } = useI18n();
const { xs } = useDisplay();
const authStore = useAuthStore();
const userStore = useUserStore();
const { show: feedback } = useFeedback();
const usersDialog = useUsersDialogStore();
const profileEdit = useProfileEditStore();
const groupName = computed(
    () => props.user?.user_group?.user_group_name || t("userProfile.noGroup"),
);
const groupIcon = computed(
    () => props.user?.user_group?.user_group_icon || "mdi-account",
);
const groupColor = computed(() => props.user?.user_group?.user_group_color || "");
const initial = computed(
    () => props.user?.username?.slice(0, 1).toUpperCase() || "?",
);
const hasExtraDetails = computed(() =>
    Boolean(props.user?.blog || props.user?.company || props.user?.location || props.user?.bio),
);
const detailsExpanded = ref(false);

// ---------- 封面 / 头像快捷上传（self 模式，悬浮显示） ----------
const ALLOWED_TYPES = ["image/jpeg", "image/png", "image/webp", "image/gif"];
const MAX_SIZE = 2 * 1024 * 1024; // 2MB，与后端 max:2048 保持一致

const fileInput = ref(null);
const pendingType = ref("cover");
const uploadingType = ref("");

const fmtCount = (n) =>
    new Intl.NumberFormat(undefined, {
        notation: "compact",
        maximumFractionDigits: 1,
    }).format(Number(n ?? 0));

function pick(type) {
    pendingType.value = type;
    fileInput.value?.click();
}

async function onFileChange(event) {
    const file = event.target.files?.[0];
    // 允许用户再次选择同一个文件
    event.target.value = "";
    if (!file) return;

    if (!ALLOWED_TYPES.includes(file.type)) {
        feedback(t("imageUpload.typeError"), "error");
        return;
    }
    if (file.size > MAX_SIZE) {
        feedback(t("imageUpload.sizeError"), "error");
        return;
    }

    const type = pendingType.value;
    uploadingType.value = type;
    try {
        const result = await userStore.uploadImage(type, file);
        // 上传成功后立即保存并同步当前登录用户信息
        const saved = await userStore.updateProfile({ [type]: result.url });
        authStore.setUser(saved.user);
        feedback(result.message || t("imageUpload.uploaded"), "success");
        emit("saved", saved.user);
    } catch (error) {
        feedback(error.message, "error");
    } finally {
        uploadingType.value = "";
    }
}

</script>

<template>
    <v-card rounded="lg">
        <div class="cover-wrap">
            <v-img v-if="user?.cover" :src="user.cover" alt="" class="cover-image" cover />
            <div v-else class="cover-placeholder bg-primary"></div>

            <!-- 封面上传按钮：悬浮封面时显示，位于右上角 -->
            <v-btn v-if="mode === 'self'" class="cover-upload-btn" icon size="small" color="warning"
                :aria-label="$t('userProfile.uploadCover')" :loading="uploadingType === 'cover'"
                @click.stop="pick('cover')">
                <v-icon icon="mdi-camera" />
                <v-tooltip activator="parent">{{ $t('userProfile.uploadCover') }}</v-tooltip>
            </v-btn>
            <v-chip :color="groupColor || 'primary'" variant="tonal" class="group-chip">
                <v-icon v-if="groupIcon" :icon="groupIcon" class="mr-1" />
                {{ groupName }}
            </v-chip>
        </div>

        <div class="profile-info">
            <v-avatar size="110" class="profile-avatar avatar-wrap elevation-2">
                <v-img v-if="user?.avatar" :src="user.avatar" :alt="user?.username" cover />
                <span v-else class="text-h4">{{ initial }}</span>
                <v-btn v-if="mode === 'self'" class="avatar-upload-btn" icon size="x-small" color="warning"
                    :aria-label="$t('userProfile.uploadAvatar')" :loading="uploadingType === 'avatar'"
                    @click.stop="pick('avatar')">
                    <v-icon icon="mdi-camera" />
                    <v-tooltip activator="parent">{{ $t('userProfile.uploadAvatar') }}</v-tooltip>
                </v-btn>
            </v-avatar>

            <div class="profile-details" :class="{ 'profile-details--center': xs }">
                <h1 class="profile-name">{{ user?.username }}</h1>
                <div v-if="user?.headline" class="profile-headline">
                    <v-icon icon="mdi-card-account-details-outline" size="small" />
                    <span>{{ user.headline }}</span>
                </div>

                <v-expand-transition>
                    <div v-show="detailsExpanded" class="profile-extra-details">
                        <div v-if="user?.blog" class="profile-meta-item">
                            <v-icon icon="mdi-link" size="small" />
                            <span>{{ user.blog }}</span>
                        </div>
                        <div v-if="user?.company" class="profile-meta-item">
                            <v-icon icon="mdi-city" size="small" />
                            <span>{{ user.company }}</span>
                        </div>
                        <div v-if="user?.location" class="profile-meta-item">
                            <v-icon icon="mdi-map-marker" size="small" />
                            <span>{{ user.location }}</span>
                        </div>
                        <div v-if="user?.bio" class="profile-meta-item profile-meta-item--bio">
                            <v-icon icon="mdi-file-document-outline" size="small" />
                            <span>{{ user.bio }}</span>
                        </div>
                    </div>
                </v-expand-transition>

                <v-btn v-if="hasExtraDetails" class="details-toggle" variant="text" size="small"
                    :aria-expanded="detailsExpanded" @click="detailsExpanded = !detailsExpanded">
                    <v-icon :icon="detailsExpanded ? 'mdi-chevron-up' : 'mdi-chevron-down'" start />
                    {{ $t(detailsExpanded ? 'userProfile.hideDetails' : 'userProfile.showDetails') }}
                </v-btn>
            </div>
        </div>

        <v-divider class="profile-actions-divider" />

        <div class="profile-actions">
            <div class="profile-primary-action">
                <v-btn v-if="mode === 'self'" variant="tonal" icon
                    @click="profileEdit.open(user, { onSaved: (u) => emit('saved', u) })">
                    <v-icon icon="mdi-pencil" />
                    <v-tooltip activator="parent">{{ $t('profileEdit.title') }}</v-tooltip>
                </v-btn>
                <FollowButton v-else type="user" :target-id="user.user_id" :following="following"
                    @toggle-follow="emit('toggle-follow', $event)" />
            </div>

            <div class="profile-stats">
                <v-btn variant="text" prepend-icon="mdi-account-heart-outline" @click="
                    usersDialog.open({
                        modes: 'followers',
                        type: 'user',
                        id: user.user_id,
                    })
                    ">
                    {{ $t("userProfile.followers", { count: user?.follower_count ?? 0 }) }}
                </v-btn>
                <span class="profile-stats-divider" />
                <v-btn variant="text" prepend-icon="mdi-account-arrow-right-outline" @click="
                    usersDialog.open({
                        modes: 'followees',
                        type: 'user',
                        id: user.user_id,
                    })
                    ">
                    {{ $t("userProfile.following", { count: user?.followee_count ?? 0 }) }}
                </v-btn>
            </div>

            <v-spacer />
            <MoreActions type="user" :item="user" />
        </div>

        <!-- 封面/头像上传用的隐藏文件选择 -->
        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="d-none"
            @change="onFileChange" />
    </v-card>
</template>

<style scoped>
.cover-wrap {
    position: relative;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: rgb(var(--v-theme-surface-variant));
}

.cover-image,
.cover-placeholder {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.cover-image :deep(.v-img__img) {
    object-position: center;
}

.group-chip {
    position: absolute;
    top: 16px;
    left: 16px;
    z-index: 1;
}

.cover-upload-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 1;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.cover-wrap:hover .cover-upload-btn,
.cover-upload-btn:focus-visible {
    opacity: 1;
}

.profile-info {
    display: flex;
    align-items: flex-start;
    gap: 32px;
    padding: 20px 24px;
}

.profile-avatar {
    flex: 0 0 110px;
    border: 4px solid rgb(var(--v-theme-surface));
    background: rgb(var(--v-theme-surface-variant));
}

.profile-details {
    min-width: 0;
    flex: 1;
}

.profile-name {
    margin: 4px 0 6px;
    color: rgb(var(--v-theme-on-surface));
    font-size: 32px;
    font-weight: 600;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.profile-headline,
.profile-meta-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    color: rgb(var(--v-theme-on-surface-variant));
    line-height: 1.5;
}

.profile-headline .v-icon,
.profile-meta-item .v-icon {
    flex: 0 0 auto;
    margin-top: 3px;
}

.profile-headline span,
.profile-meta-item span {
    min-width: 0;
    overflow-wrap: anywhere;
}

.profile-extra-details {
    display: grid;
    gap: 8px;
    margin-top: 10px;
}

.profile-meta-item--bio span {
    white-space: pre-line;
}

.details-toggle {
    margin-top: 4px;
    margin-left: -8px;
}

.avatar-upload-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    z-index: 2;
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.avatar-wrap:hover .avatar-upload-btn,
.avatar-upload-btn:focus-visible {
    opacity: 1;
}

.profile-actions {
    display: flex;
    min-height: 72px;
    align-items: center;
    padding: 8px 24px 16px 57px;
}

.profile-primary-action {
    flex: 0 0 auto;
    margin-right: 40px;
}

.profile-stats {
    display: flex;
    min-width: 0;
    align-items: center;
}

.profile-stats .v-btn {
    color: rgb(var(--v-theme-secondary));
}

.profile-stats-divider {
    width: 1px;
    height: 20px;
    flex: 0 0 1px;
    margin: 0 8px;
    background: rgba(var(--v-border-color), var(--v-border-opacity));
}

@media (hover: none) {
    .cover-upload-btn,
    .avatar-upload-btn {
        opacity: 1;
    }
}

@media (max-width: 599.98px) {
    .cover-wrap {
        border-radius: 0;
    }

    .group-chip {
        top: 12px;
        left: 12px;
    }

    .profile-info {
        flex-direction: column;
        align-items: center;
        gap: 0;
        padding: 0 16px 20px;
    }

    .profile-avatar {
        margin-top: -53px;
    }

    .profile-details--center {
        width: 100%;
        text-align: center;
    }

    .profile-name {
        margin-top: 10px;
        font-size: 24px;
    }

    .profile-headline,
    .profile-meta-item {
        justify-content: center;
        text-align: start;
    }

    .profile-extra-details {
        width: 100%;
        max-width: 480px;
        margin: 10px auto 0;
        text-align: start;
    }

    .details-toggle {
        margin-left: 0;
    }

    .profile-actions {
        position: relative;
        min-height: 64px;
        margin-top: 54px;
        padding: 8px 16px 16px;
    }

    .profile-actions-divider {
        display: none;
    }

    .profile-primary-action {
        margin-right: 0;
    }

    .profile-stats {
        position: absolute;
        top: -54px;
        right: 0;
        left: 0;
        height: 54px;
        justify-content: stretch;
        border-top: 1px solid rgba(var(--v-border-color), 0.12);
        border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
    }

    .profile-stats .v-btn {
        width: 50%;
        min-width: 0;
        height: 100%;
        flex: 1 1 50%;
        padding: 0 6px;
        border-radius: 0;
        white-space: normal;
    }

    .profile-stats-divider {
        margin: 0;
    }
}
</style> -->

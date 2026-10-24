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
    <v-card rounded="lg" class="mx-auto">
        <div class="cover-wrap">
            <v-parallax v-if="user?.cover" :src="user.cover" height="280" />
            <div v-else class="bg-primary" style="height: 280px"></div>

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

        <v-list>
            <v-list-item>
                <template #prepend>
                    <v-avatar size="72" class="avatar-wrap">
                        <v-img v-if="user?.avatar" :src="user.avatar" :alt="user?.username" cover />
                        <span v-else class="text-h5">{{ initial }}</span>
                        <v-btn v-if="mode === 'self'" class="avatar-upload-btn" icon size="x-small" color="warning"
                            :aria-label="$t('userProfile.uploadAvatar')" :loading="uploadingType === 'avatar'"
                            @click.stop="pick('avatar')">
                            <v-icon icon="mdi-camera" />
                            <v-tooltip activator="parent">{{ $t('userProfile.uploadAvatar') }}</v-tooltip>
                        </v-btn>
                    </v-avatar>
                </template>

                <v-list-item-title style="font-size: 1.8rem; font-weight: 900;">
                    <!-- <strong > -->
                        <!-- <h1> -->
                        {{ user?.username }}
                        <!-- </h1> -->
                    <!-- </strong> -->
                </v-list-item-title>
                <v-list-item-subtitle>
                    {{ user?.headline }}
                </v-list-item-subtitle>

                <template #append>
                    
            <v-btn v-if="mode === 'self'" variant="tonal" size="small" icon
                @click="profileEdit.open(user, { onSaved: (u) => emit('saved', u) })">
                <v-icon icon="mdi-pencil" />
                <v-tooltip activator="parent">{{ $t('profileEdit.title') }}</v-tooltip>
            </v-btn>
            <FollowButton v-else type="user" :target-id="user.user_id" :following="following"
                @toggle-follow="emit('toggle-follow', $event)" />

                </template>
            </v-list-item>
        </v-list>

        <v-divider v-if="user.blog||user.company||user.location||user.bio"/>

        <v-card-text v-if="user.blog||user.company||user.location||user.bio" :class="xs ? 'text-center' : ''">
            <v-list v-if="user" lines="one" class="pa-2" density="compact" :style="xs ? { margin: 'auto', width: 'auto' } : {}">
                <v-list-item v-if="user.blog" prepend-icon="mdi-link" :title="user.blog" />
                <v-list-item v-if="user.company" prepend-icon="mdi-city" :title="user.company" />
                <v-list-item v-if="user.location" prepend-icon="mdi-map-marker" :title="user.location" />
                <v-list-item v-if="user.bio" prepend-icon="mdi-file-document" :title="user.bio" />
            </v-list>
        </v-card-text>

        <v-divider />

        <v-card-actions class="d-flex align-center flex-wrap ga-2 pa-4">
            <!-- <v-btn v-if="mode === 'self'" variant="tonal" size="small" icon
                @click="profileEdit.open(user, { onSaved: (u) => emit('saved', u) })">
                <v-icon icon="mdi-pencil" />
                <v-tooltip activator="parent">{{ $t('profileEdit.title') }}</v-tooltip>
            </v-btn>
            <FollowButton v-else type="user" :target-id="user.user_id" :following="following"
                @toggle-follow="emit('toggle-follow', $event)" /> -->
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
            <v-spacer />
            <MoreActions type="user" :item="user" />
        </v-card-actions>

        <!-- 封面/头像上传用的隐藏文件选择 -->
        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="d-none"
            @change="onFileChange" />
    </v-card>
</template>

<style scoped>
.cover-wrap {
    position: relative;
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

.avatar-wrap {
    position: relative;
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

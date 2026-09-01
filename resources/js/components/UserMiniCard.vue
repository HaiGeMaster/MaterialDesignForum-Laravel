<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useUsersDialogStore } from '../stores/usersDialog'
import FollowButton from './FollowButton.vue'
import MoreActions from './MoreActions.vue'

const props = defineProps({
  /** 用户对象（来自列表数据的 creator 字段） */
  user: { type: Object, required: true },
})

const emit = defineEmits(['toggle-follow', 'more'])

const router = useRouter()
const authStore = useAuthStore()
const usersDialog = useUsersDialogStore()
const { t } = useI18n()

const initial = computed(() => props.user?.username?.slice(0, 1).toUpperCase() || '?')

// 当前登录用户本人不显示关注按钮
const isSelf = computed(
  () => Boolean(authStore.user) && Number(authStore.user.user_id) === Number(props.user?.user_id),
)

const isFollowing = computed(() => Boolean(props.user?.is_following))

// 跳转到用户个人资料页
function goProfile() {
  router.push(`/users/${props.user?.user_id}`)
}

// 自定义更多项（内置的复制链接/分享/举报由 MoreActions 按权限自动生成）
const moreItems = [{ title: t('userCard.message'), icon: 'mdi-message-outline' }]
</script>

<template>
  <v-card rounded="lg" width="320"  class="overflow-hidden">
    <!-- 封面 -->
    <v-img v-if="user?.cover" :src="user.cover" height="160" cover />
    <div v-else class="bg-secondary" style="height: 160px"></div>

    <v-card-text class="pt-3">
      <!-- 头像 + 名称 + 个性签名 -->
      <v-list-item
        class="px-0 mb-2 cursor-pointer"
        density="comfortable"
        @click="goProfile"
      >
        <template #prepend>
          <v-avatar size="48" color="primary"  class="mr-2">
            <v-img v-if="user?.avatar" :src="user.avatar" cover />
            <span v-else class="text-h6 font-weight-bold text-primary">{{ initial }}</span>
          </v-avatar>
        </template>

        <v-list-item-title class="font-weight-bold text-truncate">
          {{ user?.username }}
        </v-list-item-title>

        <v-list-item-subtitle class="text-medium-emphasis text-truncate">
          {{ user?.bio || $t('userCard.noBio') }}
        </v-list-item-subtitle>
      </v-list-item>

      <!-- 统计：关注量 / 粉丝量 / 回答数量 -->
      <div class="d-flex justify-center mb-3">
        <v-btn-group>
          <v-btn
            prepend-icon="mdi-account-arrow-right-outline"
            @click="usersDialog.open({ modes: 'followees', type: 'user', id: user.user_id })"
          >
            {{ $t('userCard.following', { count: user?.followee_count ?? 0 }) }}
          </v-btn>
          <v-btn
            prepend-icon="mdi-account-heart-outline"
            @click="usersDialog.open({ modes: 'followers', type: 'user', id: user.user_id })"
          >
            {{ $t('userCard.followers', { count: user?.follower_count ?? 0 }) }}
          </v-btn>
          <v-btn prepend-icon="mdi-message-text-outline">
            {{ $t('userCard.answers', { count: user?.answer_count ?? 0 }) }}
          </v-btn>
        </v-btn-group>
      </div>

      <!-- 底部操作：左边关注，右边更多 -->
      <div class="d-flex align-center">
        <FollowButton
          v-if="!isSelf"
          type="user"
          :target-id="user.user_id"
          :following="isFollowing"
          @toggle-follow="emit('toggle-follow', $event)"
        />
        <v-spacer />
        <MoreActions
          type="user"
          :item="user"
          :items="moreItems"
          @select="(item) => emit('more', item)"
        />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import FollowButton from './FollowButton.vue'
import MoreActions from './MoreActions.vue'

const props = defineProps({
  person: { type: Object, required: true },
  following: { type: Boolean, default: false },
})

const emit = defineEmits(['click', 'toggle-follow', 'more'])

const authStore = useAuthStore()
const { t } = useI18n()

const initial = computed(() => props.person?.username?.slice(0, 1).toUpperCase() || '?')

// 是否当前登录用户本人（不允许关注自己）
const isSelf = computed(
  () => Boolean(authStore.user) && Number(authStore.user.user_id) === Number(props.person?.user_id),
)

// 自定义更多项（内置的复制链接/分享/举报由 MoreActions 按权限自动生成）
const moreItems = [{ title: t('userCard.message'), icon: 'mdi-message-outline' }]
</script>

<template>
  <v-card
    class="person-card d-flex flex-column"

    elevation="1"
    @click="emit('click', person)"
  >
    <!-- 上方居中头像 -->
    <div class="person-avatar d-flex align-center justify-center">
      <v-avatar size="72" color="primary" >
        <v-img v-if="person.avatar" :src="person.avatar" cover />
        <span v-else class="text-h4 font-weight-bold text-primary">{{ initial }}</span>
      </v-avatar>
    </div>

    <!-- 用户名 + 个性签名 -->
    <div class="text-center px-3 pb-2">
      <div class="text-subtitle-1 font-weight-bold text-truncate">{{ person.username }}</div>
      <div class="text-body-2 text-medium-emphasis text-truncate">
        {{ person.bio || $t('personCard.noBio') }}
      </div>
    </div>

    <v-spacer />

    <!-- 底部：关注圆钮（本人不显示）+ 更多选项 -->
    <v-card-actions class="justify-space-between px-2 pb-2">
      <FollowButton
        v-if="!isSelf"
        type="user"
        :target-id="person.user_id"
        :following="following"
        @toggle-follow="emit('toggle-follow', person, $event)"
      />

      <v-spacer />

      <MoreActions
        type="user"
        :item="person"
        :items="moreItems"
        @select="(item) => emit('more', person, item.title)"
      />
    </v-card-actions>
  </v-card>
</template>

<style scoped>
.person-card {
  min-height: 240px;
  cursor: pointer;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.person-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12) !important;
  transform: translateY(-2px);
}

.person-avatar {
  flex: 1 1 auto;
  padding: 20px;
}
</style>

<script setup>
import { useRouter } from 'vue-router'
import { useUsersDialogStore } from '../stores/usersDialog'

const props = defineProps({
  topic: { type: Object, required: true },
  following: { type: Boolean, default: false },
})

import FollowButton from './FollowButton.vue'

const usersDialog = useUsersDialogStore()

const emit = defineEmits(['toggle-follow'])
const router = useRouter()

const gradientPalette = [
  'linear-gradient(135deg, #424242 0%, #212121 100%)',
  'linear-gradient(135deg, #616161 0%, #37474F 100%)',
  'linear-gradient(135deg, #757575 0%, #424242 100%)',
  'linear-gradient(135deg, #546E7A 0%, #263238 100%)',
  'linear-gradient(135deg, #9E9E9E 0%, #616161 100%)',
  'linear-gradient(135deg, #455A64 0%, #37474F 100%)',
]

function gradientFor() {
  return gradientPalette[props.topic.topic_id % gradientPalette.length]
}

function openDetail() {
  router.push(`/topics/${props.topic.topic_id}`)
}
</script>

<template>
  <v-card class="topic-card d-flex flex-column"  elevation="1" @click="openDetail">
    <!-- 上方话题图片 -->
    <div class="topic-cover">
      <v-img v-if="topic.cover" :src="topic.cover" cover height="140" />
      <div v-else class="d-flex align-center justify-center fill-height" :style="{ background: gradientFor() }">
        <v-icon icon="mdi-tag" size="44" color="white" />
      </div>
    </div>

    <!-- 左下角话题标题 -->
    <div class="pa-3">
      <div class="text-subtitle-1 font-weight-bold text-truncate">{{ topic.name }}</div>
    </div>

    <!-- 底部操作栏：左关注圆钮，右关注人数 -->
    <v-card-actions class="justify-space-between px-2 pb-2" @click.stop>
      <FollowButton
        type="topic"
        :target-id="topic.topic_id"
        :following="following"
        @toggle-follow="emit('toggle-follow', topic, $event)"
      />
    <v-spacer />
      <v-btn rounded @click="usersDialog.open({ modes: 'followers', type: 'topic', id: topic.topic_id })">
        {{ $t('content.followers', { count: topic.follower_count ?? 0 }) }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<style scoped>
.topic-card {
  /* 占满 slide 宽度，避免内部内容影响卡片宽度 */
  width: 100%;
  min-width: 0;
  overflow: hidden;
  min-height: 140px;
  cursor: pointer;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.topic-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12) !important;
  transform: translateY(-2px);
}

.topic-cover {
  height: 140px;
  /* border-radius: 12px 12px 0 0; */
  overflow: hidden;
}
</style>

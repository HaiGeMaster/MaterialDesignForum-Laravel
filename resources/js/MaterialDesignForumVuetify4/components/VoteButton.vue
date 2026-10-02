<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useAuthStore } from '../stores/auth'
import { useFeedback } from '../composables/useFeedback'

const props = defineProps({
  // 投票目标类型：question / answer / article / comment / reply
  type: { type: String, required: true },
  // 投票目标 ID
  itemId: { type: [Number, String], required: true },
  // 初始赞 / 踩计数（来自内容表 vote_up_count / vote_down_count）
  upCount: { type: Number, default: 0 },
  downCount: { type: Number, default: 0 },
  // 当前用户是否已投票：'up' / 'down' / null
  myVote: { type: String, default: null },
  size: { type: String, default: 'small' },
})

const emit = defineEmits(['vote'])

const authStore = useAuthStore()
const router = useRouter()
const { show } = useFeedback()
const { t } = useI18n()

// 本地状态，与外部 props 保持同步
const upCount = ref(props.upCount)
const downCount = ref(props.downCount)
const upValue = ref(props.myVote === 'up')
const downValue = ref(props.myVote === 'down')
const loading = ref(false)

watch(
  () => [props.upCount, props.downCount, props.myVote],
  () => {
    upCount.value = props.upCount
    downCount.value = props.downCount
    upValue.value = props.myVote === 'up'
    downValue.value = props.myVote === 'down'
  },
)

// 用户组是否允许投票
const canVote = () => Boolean(authStore.user?.user_group?.ability_vote)

async function toggleVote(type) {
  if (loading.value) return

  // 未登录时引导到登录页（个人资料页）
  if (!authStore.isLoggedIn) {
    show(t('vote.loginFirst'), 'info')
    router.push('/login')
    return
  }

  // 用户组无投票权限
  if (!canVote()) {
    show(t('vote.noPermission'), 'warning')
    return
  }

  loading.value = true
  try {
    const { data } = await http.post('/votes/toggle', {
      votable_type: props.type,
      votable_id: props.itemId,
      type,
    })
    upCount.value = data.up.count
    downCount.value = data.down.count
    upValue.value = data.up.value
    downValue.value = data.down.value
    emit('vote', data)
  } catch (error) {
    show(error.message, 'error')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="d-inline-flex align-center">
    <v-tooltip :text="$t('vote.up')" >
      <template v-slot:activator="{ props: tipProps }">
        <v-btn
          v-bind="tipProps"
          icon
          :size="size"
          variant="plain"
          :loading="loading"
          class="mr-1"
          @click.stop.prevent="toggleVote('up')"
        >
          <v-badge
            bordered
            offset-x="-8"
            offset-y="-8"
            color="primary"
            :content="upCount"
            :model-value="upCount > 0"
          >
            <v-icon size="small" :color="upValue ? 'primary' : ''">
              {{ upValue ? 'mdi-thumb-up' : 'mdi-thumb-up-outline' }}
            </v-icon>
          </v-badge>
        </v-btn>
      </template>
    </v-tooltip>

    <v-tooltip :text="$t('vote.down')" >
      <template v-slot:activator="{ props: tipProps }">
        <v-btn
          v-bind="tipProps"
          icon
          :size="size"
          variant="plain"
          :loading="loading"
          @click.stop.prevent="toggleVote('down')"
        >
          <v-badge
            bordered
            offset-x="-8"
            offset-y="-8"
            color="primary"
            :content="downCount"
            :model-value="downCount > 0"
          >
            <v-icon size="small" :color="downValue ? 'primary' : ''">
              {{ downValue ? 'mdi-thumb-down' : 'mdi-thumb-down-outline' }}
            </v-icon>
          </v-badge>
        </v-btn>
      </template>
    </v-tooltip>
  </div>
</template>

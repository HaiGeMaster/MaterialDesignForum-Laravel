<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  /** 是否已没有更多数据（停止观察并显示到底文案） */
  finished: { type: Boolean, default: false },
  /** 是否正在加载中（避免重复触发） */
  loading: { type: Boolean, default: false },
  /** 加载是否出错（显示重试按钮） */
  error: { type: Boolean, default: false },
  /** 进入视口提前量（像素），滚到接近底部时即触发 */
  rootMargin: { type: String, default: '0px 0px 300px 0px' },
})

const emit = defineEmits(['load', 'retry'])

const root = ref(null)
let observer = null
/** 本观察周期内是否已触发过 load（避免连续触发） */
let triggered = false

function startObserve() {
  if (!observer || !root.value) return
  triggered = false
  observer.observe(root.value)
}

function stopObserve() {
  observer?.unobserve(root.value)
}

function canTrigger() {
  return !triggered && !props.loading && !props.finished && !props.error
}

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting && canTrigger()) {
        triggered = true
        stopObserve()
        emit('load')
      }
    },
    { rootMargin: props.rootMargin },
  )
  startObserve()
})

// 状态变化后重新评估：加载中/到底/出错时暂停，否则恢复观察
watch(
  () => [props.loading, props.finished, props.error],
  () => {
    if (props.loading || props.finished || props.error) {
      stopObserve()
    } else {
      startObserve()
    }
  },
)

onBeforeUnmount(() => observer?.disconnect())
</script>

<template>
  <div ref="root" class="infinite-scroll">
    <div v-if="loading" class="infinite-scroll__status">
      <v-progress-circular indeterminate size="20" />
      <span>{{ $t('infiniteScroll.loading') }}</span>
    </div>

    <div v-else-if="error" class="infinite-scroll__status">
      <v-btn variant="text" color="error" prepend-icon="mdi-refresh" @click="emit('retry')">
        {{ $t('infiniteScroll.retry') }}
      </v-btn>
    </div>

    <div v-else-if="finished" class="infinite-scroll__status infinite-scroll__status--end">
      <v-icon icon="mdi-chevron-double-down" size="small" class="mr-1" />
      <span>{{ $t('infiniteScroll.noMore') }}</span>
    </div>
  </div>
</template>

<style scoped>
.infinite-scroll {
  min-height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.infinite-scroll__status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: rgba(0, 0, 0, 0.6);
}

.infinite-scroll__status--end {
  font-size: 12px;
}

.v-theme--dark .infinite-scroll__status {
  color: rgba(255, 255, 255, 0.6);
}
</style>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import http from '../api'
import TopicCard from './TopicCard.vue'
import { useDisplay } from 'vuetify'

const topics = ref([])
const loading = ref(false)
const scroller = ref(null)
const { xs } = useDisplay()

// 滚动位置状态：到达最左/最右时隐藏对应的方向按钮
const canPrev = ref(false)
const canNext = ref(false)

function updateNavState() {
  const el = scroller.value
  if (!el) return
  // 容差 1px 避免浮点/亚像素误差导致按钮在尽头仍闪现
  canPrev.value = el.scrollLeft > 1
  canNext.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 1
}

// 内容变化（数据到达）后重新计算，此时卡片宽度才确定
watch(topics, async () => {
  await nextTick()
  updateNavState()
})

async function fetchTopics() {
  loading.value = true
  try {
    const { data } = await http.get('/topics')
    topics.value = data
  } catch (error) {
    // 首页加载失败不打断页面
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchTopics()
  const el = scroller.value
  if (el) {
    el.addEventListener('scroll', updateNavState, { passive: true })
    window.addEventListener('resize', onWindowResize)
    requestAnimationFrame(updateNavState)
  }
})

onBeforeUnmount(() => {
  scroller.value?.removeEventListener('scroll', updateNavState)
  window.removeEventListener('resize', onWindowResize)
})

// 窗口尺寸变化会改变一屏卡片数（--per-view 响应式），需要重算是否可滚动
function onWindowResize() {
  updateNavState()
}

// 关注状态由服务器随列表数据返回（topic.is_following），切换后回写最新状态与关注数
function toggleFollow(topic, { following, followerCount }) {
  topic.is_following = following
  if (followerCount !== undefined) {
    topic.follower_count = followerCount
  }
}

function scrollByCard(dir) {
  const el = scroller.value
  if (!el) return
  const slide = el.querySelector('.topic-slide')
  const step = slide ? slide.offsetWidth : el.clientWidth / 4
  el.scrollBy({ left: dir * step, behavior: 'smooth' })
}
</script>

<template>
  <div class="topic-carousel " :class="
  xs ? 'pl-3' : 'px-3'
  ">
    <!-- 上一页按钮（仅在鼠标设备显示，滚到最左时隐藏） -->
    <v-btn
      v-show="canPrev"
      class="carousel-nav carousel-prev"
      icon
      variant="flat"
      :aria-label="$t('carousel.prev')"
      @click="scrollByCard(-1)"
    >
      <v-icon icon="mdi-chevron-left" />
    </v-btn>

    <!-- 横向滚动轨道：触摸屏上可直接手指滑动 -->
    <div ref="scroller" class="carousel-track">
      <div v-for="t in topics" :key="t.topic_id" class="topic-slide">
        <TopicCard :topic="t" :following="t.is_following" @toggle-follow="toggleFollow" />
      </div>
    </div>

    <!-- 下一页按钮（仅在鼠标设备显示，滚到最右时隐藏） -->
    <v-btn
      v-show="canNext"
      class="carousel-nav carousel-next"
      icon
      variant="flat"
      :aria-label="$t('carousel.next')"
      @click="scrollByCard(1)"
    >
      <v-icon icon="mdi-chevron-right" />
    </v-btn>

    <v-progress-circular
      v-if="loading && !topics.length"
      indeterminate
      class="mx-auto d-block mt-4"
    />
  </div>
</template>

<style scoped>
.topic-carousel {
  position: relative;

}

.carousel-track {
  --per-view: 4;
  display: flex;
  gap: 18px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  padding: 4px 4px 8px;
  /* 隐藏滚动条，保持干净外观 */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE/Edge */
}

.carousel-track::-webkit-scrollbar {
  display: none; /* Chrome/Safari */
}

.topic-slide {
    padding: 3px;
  /* min-width: 0 防止卡片内文字/按钮把固定宽度撑开，保证所有卡片宽度统一 */
  flex: 0 0 calc((100% - (var(--per-view) - 1) * 16px) / var(--per-view));
  min-width: 0;
  scroll-snap-align: start;
}

/* 触摸屏设备：隐藏上一页/下一页按钮，改为手指滑动 */
@media (hover: none) and (pointer: coarse) and (max-width: 600px) {
  .carousel-nav {
    display: none;
  }
}

/* 响应式：一屏显示的卡片数 */
@media (max-width: 1280px) {
  .carousel-track {
    --per-view: 3;
  }
}

@media (max-width: 960px) {
  .carousel-track {
    --per-view: 2;
  }
}

@media (max-width: 600px) {
  .carousel-track {
    --per-view: 1.2; /* 露出下一张的边缘，提示可滑动 */
  }
}

.carousel-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1;
  background-color: rgb(var(--v-theme-surface)) !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
}

.carousel-prev {
  left: -8px;
}

.carousel-next {
  right: -8px;
}
</style>

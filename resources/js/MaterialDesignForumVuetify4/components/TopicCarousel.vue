<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import http from '../api'
import TopicCard from './TopicCard.vue'
import { useDisplay } from 'vuetify'

const topics = ref([])
const loading = ref(false)
const scroller = ref(null)
const { xs } = useDisplay()

const canPrev = ref(false)
const canNext = ref(false)

let rafId = 0
let ro = null

function updateNavState() {
  const el = scroller.value
  if (!el) return
  canPrev.value = el.scrollLeft > 1
  canNext.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 1
}

// scroll 高频触发，用 rAF 合并成每帧一次
function onScroll() {
  if (rafId) return
  rafId = requestAnimationFrame(() => {
    rafId = 0
    updateNavState()
  })
}

watch(topics, async () => {
  await nextTick()
  updateNavState()
})

async function fetchTopics() {
  loading.value = true
  try {
    const { data } = await http.get('/topics')
    topics.value = data
  } catch {
    // 首页加载失败不打断页面
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchTopics()
  const el = scroller.value
  if (!el) return
  el.addEventListener('scroll', onScroll, { passive: true })
  // 比 window.resize 更准：侧栏折叠、父容器宽度变化都能捕获
  ro = new ResizeObserver(updateNavState)
  ro.observe(el)
})

onBeforeUnmount(() => {
  if (rafId) cancelAnimationFrame(rafId)
  scroller.value?.removeEventListener('scroll', onScroll)
  ro?.disconnect()
})

function toggleFollow(topic, { following, followerCount }) {
  topic.is_following = following
  if (followerCount !== undefined) topic.follower_count = followerCount
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
  <div class="topic-carousel" :class="xs ? 'pl-3' : 'px-3'">
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

    <!-- :aria-label="$t('carousel.label')" -->
    <div ref="scroller" class="carousel-track" role="region" >
      <div v-for="t in topics" :key="t.topic_id" class="topic-slide">
        <TopicCard :topic="t" :following="t.is_following" @toggle-follow="toggleFollow" />
      </div>
    </div>

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
  width: 100%;
  /* 关键：作为 flex/grid item 时允许被压缩到小于内容宽度 */
  min-width: 0;
  max-width: 100%;
}

.carousel-track {
  --per-view: 4;
  --gap: 18px;

  display: flex;
  gap: var(--gap);
  width: 100%;
  min-width: 0;
  overflow-x: auto;
  overscroll-behavior-x: contain; /* 滑到尽头不把页面一起带走 */
  scroll-snap-type: x proximity;  /* proximity 比 mandatory 更不容易卡住/漂移 */
  /* 只留纵向 padding：横向 padding 在滚动容器里会被浏览器吞掉，是宽度算错的元凶 */
  padding-block: 4px 8px;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.carousel-track::-webkit-scrollbar {
  display: none;
}

.topic-slide {
  /* 左右各 3px 给卡片阴影留出空间，卡片整体仍在 basis 内（border-box） */
  padding: 3px;
  /* 唯一宽度来源：和 gap 共用 --gap，不会再出现 16/18 不一致 */
  flex: 0 0 calc((100% - (var(--per-view) - 1) * var(--gap)) / var(--per-view));
  min-width: 0; /* 防止卡片内长文本把固定宽度撑开 */
  scroll-snap-align: start;
}

@media (hover: none) and (pointer: coarse) and (max-width: 600px) {
  .carousel-nav {
    display: none;
  }
}

@media (max-width: 1280px) {
  .carousel-track { --per-view: 3; }
}

@media (max-width: 960px) {
  .carousel-track { --per-view: 2; }
}

@media (max-width: 600px) {
  .carousel-track { --per-view: 1.2; } /* 露出下一张边缘，提示可滑动 */
}

.carousel-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1;
  background-color: rgb(var(--v-theme-surface)) !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
}

/* 不再用负值：负值会顶出父容器边界 */
/* .carousel-prev { left: 0; }
.carousel-next { right: 0; } */
.carousel-prev { left: -8px; }
.carousel-next { right: -8px; }
</style>
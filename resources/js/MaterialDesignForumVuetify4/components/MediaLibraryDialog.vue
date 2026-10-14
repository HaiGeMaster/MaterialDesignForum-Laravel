<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useFeedback } from '../composables/useFeedback'
import { useInfiniteList } from '../composables/useInfiniteList'
import { useAuthStore } from '../stores/auth'
import InfiniteScroll from './InfiniteScroll.vue'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

// v-model: 对话框开关
const open = defineModel('open', { type: Boolean, default: false })

const props = defineProps({
  /** tiptap 编辑器实例（确认后插入选中媒体） */
  editor: { type: Object, default: null },
})

const { show } = useFeedback()
const { t } = useI18n()
const auth = useAuthStore()

// 从媒体库插入：弹窗多选当前用户已上传的图片/视频，确认后批量插入
const {
  list: libraryItems,
  loading: libraryLoading,
  error: libraryError,
  finished: libraryFinished,
  load: loadLibrary,
  loadNext: loadLibraryNext,
  retry: retryLibrary,
} = useInfiniteList(async (page) => {
  const { data } = await http.get(`/users/${auth.user?.user_id}/media`, { params: { page } })
  return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
})

const librarySelected = ref(new Set())
const libraryCount = computed(() => librarySelected.value.size)

// 打开对话框时重置选择并加载第一页
watch(open, (val) => {
  if (!val) return
  librarySelected.value = new Set()
  loadLibrary(true)
})

function toggleLibraryItem(id) {
  const next = new Set(librarySelected.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  librarySelected.value = next
}

function insertFromLibrary() {
  if (!libraryCount.value) {
    show(t('editor.libraryNoSelection'), 'error')
    return
  }
  // 将选中的媒体一次性构造成节点数组插入。
  // 不能逐个 setImage/setVideo：TipTap 在空选区插入 block 节点后，
  // 选区会指向刚插入的节点，下一次 setImage 会把它替换掉，导致只插入最后一张。
  const nodes = libraryItems.value
    .filter((m) => librarySelected.value.has(m.media_id))
    .map((item) => {
      if (item.type === 'image') return { type: 'image', attrs: { src: item.url, alt: item.filename || '' } }
      if (item.type === 'video') return { type: 'video', attrs: { src: item.url } }
      return null
    })
    .filter(Boolean)
  if (nodes.length) {
    props.editor?.chain().focus().insertContent(nodes).run()
  }
  open.value = false
}
</script>

<template>
  <v-dialog v-model="open" max-width="760" scrollable :fullscreen="xs">
    <v-card rounded="lg" >
      <v-toolbar :title="$t('editor.libraryDialogTitle')">
        <template #prepend>
          <v-btn icon variant="text" @click="open = false">
            <v-icon>mdi-close</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.close') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-card-text class="pa-2">
        <div v-if="libraryItems.length" class="library-grid">
          <v-hover v-for="item in libraryItems" :key="item.media_id" v-slot="{ isHovering, props }">
            <v-card rounded="lg" v-bind="props" class="library-item cursor-pointer"
              :class="{ 'library-item--selected': librarySelected.has(item.media_id) }"
              :elevation="isHovering ? 4 : 1"
              @click="toggleLibraryItem(item.media_id)">
              <div class="library-item__thumb">
                <v-img v-if="item.type === 'image'" :src="item.url" height="80" cover />
                <div v-else class="library-item__video">
                  <video :src="item.url" preload="metadata" muted playsinline />
                  <v-icon icon="mdi-play-circle" size="24" color="white" />
                </div>
              </div>
              <v-checkbox-btn :model-value="librarySelected.has(item.media_id)" density="compact"
                color="primary" class="library-item__check" @click.stop="toggleLibraryItem(item.media_id)" />
              <div class="pa-1">
                <div class="text-caption text-truncate">{{ item.filename }}</div>
              </div>
            </v-card>
          </v-hover>
        </div>

        <InfiniteScroll
          v-if="libraryItems.length"
          :loading="libraryLoading"
          :error="libraryError"
          :finished="libraryFinished"
          @load="loadLibraryNext"
          @retry="retryLibrary"
        />
        <v-empty-state v-else-if="!libraryLoading" icon="mdi-image-multiple-outline"
          :title="$t('editor.libraryEmptyTitle')" :text="$t('editor.libraryEmptyText')" />
      </v-card-text>

      <v-card-actions class="px-4 pb-3">
        <span class="text-body-2 text-medium-emphasis">
          {{ $t('editor.librarySelected', { count: libraryCount }) }}
        </span>
        <v-spacer />
        <v-btn variant="text" @click="open = false">{{ $t('editor.libraryCancel') }}</v-btn>
        <v-btn color="primary" variant="flat" :disabled="!libraryCount" @click="insertFromLibrary">
          <v-icon icon="mdi-import" size="small" class="mr-1" />
          {{ $t('editor.libraryInsert') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
/* —— 媒体库选择网格 —— */
.library-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 10px;
}

.library-item {
  position: relative;
  overflow: hidden;
}

.library-item--selected {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: -2px;
}

.library-item__thumb {
  position: relative;
  background: rgb(var(--v-theme-surface-variant));
}

.library-item__video {
  position: relative;
  height: 80px;
  background: #000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.library-item__video video {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.library-item__check {
  position: absolute;
  top: 2px;
  right: 2px;
  background: rgba(0, 0, 0, 0.35);
  border-radius: 50%;
}
</style>

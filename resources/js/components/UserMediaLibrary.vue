<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useFeedback } from '../composables/useFeedback'
import { useInfiniteList } from '../composables/useInfiniteList'
import { useDeleteDialogStore } from '../stores/deleteDialog'
import InfiniteScroll from './InfiniteScroll.vue'
import MediaPreviewDialog from './MediaPreviewDialog.vue'

const props = defineProps({
    /** 用户 ID */
    userId: { type: [Number, String], required: true },
})

const { t } = useI18n()
const { show: feedback } = useFeedback()

// 媒体库：无限滚动分页加载
const {
    list: media,
    loading,
    error,
    finished,
    load,
    loadNext,
    retry,
} = useInfiniteList(async (page) => {
    const { data } = await http.get(`/users/${props.userId}/media`, { params: { page } })
    return { items: data.data ?? [], hasMore: data.current_page < data.last_page }
})

// 挂载即加载（父级 v-window-item 惰性渲染，天然实现 tab 懒加载）
onMounted(() => load(true))

// —— 视图切换（网格 / 列表），偏好持久化到 localStorage ——
const viewMode = ref(localStorage.getItem('mediaViewMode') || 'grid')

watch(viewMode, (mode) => {
    localStorage.setItem('mediaViewMode', mode)
})

// —— 多选 / 批量操作 ——
const selectionMode = ref(false)
const selectedIds = ref(new Set())

const selectedCount = computed(() => selectedIds.value.size)
const allSelected = computed(() => media.value.length > 0 && media.value.every((m) => selectedIds.value.has(m.media_id)))

function isSelected(item) {
    return selectedIds.value.has(item.media_id)
}

function toggleSelect(item) {
    const next = new Set(selectedIds.value)
    if (next.has(item.media_id)) {
        next.delete(item.media_id)
    } else {
        next.add(item.media_id)
    }
    selectedIds.value = next
}

function toggleSelectAll() {
    if (allSelected.value) {
        selectedIds.value = new Set()
    } else {
        selectedIds.value = new Set(media.value.map((m) => m.media_id))
    }
}

function exitSelection() {
    selectionMode.value = false
    selectedIds.value = new Set()
}

function handleItemClick(item) {
    if (selectionMode.value) {
        toggleSelect(item)
    } else {
        openPreview(item)
    }
}

// —— 下载（多选时逐个触发浏览器下载） ——
function downloadItem(item) {
    const a = document.createElement('a')
    a.href = item.url
    a.download = item.filename || ''
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
}

function downloadSelected() {
    if (!selectedCount.value) {
        feedback(t('userContent.noSelection'), 'error')
        return
    }
    const items = media.value.filter((m) => selectedIds.value.has(m.media_id))
    items.forEach((item, index) => {
        setTimeout(() => downloadItem(item), index * 300)
    })
    feedback(t('userContent.downloadStarted', { count: items.length }), 'success')
}

// —— 批量删除：复用全局 DeleteDialog（App.vue 已挂载）——
const deleteDialog = useDeleteDialogStore()

function openDeleteDialog() {
    if (!selectedCount.value) {
        feedback(t('userContent.noSelection'), 'error')
        return
    }
    deleteDialog.open({
        title: t('userContent.deleteConfirmTitle'),
        text: t('userContent.deleteConfirmText', { count: selectedCount.value }),
        icon: 'mdi-delete-alert-outline',
        confirmText: t('userContent.confirm'),
        cancelText: t('userContent.cancel'),
        onConfirm: confirmDelete,
    })
}

// 删除成功后对话框由 store 自动关闭；失败时保持打开以便重试（符合全局约定）
async function confirmDelete() {
    try {
        const { data } = await http.delete('/media', {
            data: { media_ids: [...selectedIds.value] },
        })
        const deletedIds = new Set(media.value.filter((m) => selectedIds.value.has(m.media_id)).map((m) => m.media_id))
        media.value = media.value.filter((m) => !deletedIds.has(m.media_id))
        exitSelection()
        feedback(t('userContent.deleted', { count: data.deleted ?? 0 }), 'success')
    } catch (e) {
        feedback(t('userContent.deleteFailed'), 'error')
        throw e
    }
}

// —— 复制链接 ——
async function copyLink(item) {
    try {
        const url = window.location.origin + item.url
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(url)
        } else {
            const ta = document.createElement('textarea')
            ta.value = url
            ta.style.position = 'fixed'
            ta.style.opacity = '0'
            document.body.appendChild(ta)
            ta.select()
            document.execCommand('copy')
            document.body.removeChild(ta)
        }
        feedback(t('userContent.linkCopied'), 'success')
    } catch {
        feedback(t('userContent.linkCopyFailed'), 'error')
    }
}

// —— 预览 ——
const preview = ref(null)
const previewOpen = ref(false)

function openPreview(item) {
    preview.value = item
    previewOpen.value = true
}

function formatSize(bytes) {
    if (!bytes) return ''
    if (bytes < 1024) return `${bytes} B`
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function mediaDate(item) {
    return item.create_time ? String(item.create_time).slice(0, 10) : ''
}
</script>

<template>
    <div>
        <!-- 工具栏：标题 + 批量操作 + 视图切换 -->
        <v-toolbar density="comfortable" color="transparent" flat>
            <v-toolbar-title class="text-subtitle-2 text-medium-emphasis">
                {{ selectionMode ? t('userContent.selectedCount', { count: selectedCount }) : $t('userContent.media') }}
            </v-toolbar-title>
            <v-spacer />

            <template v-if="selectionMode" #append>
                <v-btn variant="text"  :prepend-icon="allSelected ? 'mdi-checkbox-marked' : 'mdi-checkbox-blank-outline'"
                    @click="toggleSelectAll">
                    {{ allSelected ? $t('userContent.deselectAll') : $t('userContent.selectAll') }}
                </v-btn>
                <v-btn icon="mdi-download"  variant="text" color="primary" :title="$t('userContent.download')"
                    :disabled="!selectedCount" @click="downloadSelected" />
                <v-btn icon="mdi-delete-outline"  variant="text" color="error" :title="$t('userContent.delete')"
                    :disabled="!selectedCount" @click="openDeleteDialog" />
                <v-btn icon="mdi-close"  variant="text" :title="$t('userContent.exitSelect')"
                    @click="exitSelection" />
            </template>
            <template v-else #append>
                <v-btn-toggle v-model="viewMode" density="comfortable" color="primary" variant="outlined" divided>
                    <v-btn icon="mdi-view-grid-outline" value="grid"
                        :title="$t('userContent.gridView')" />
                    <v-btn icon="mdi-view-list-outline" value="list"
                        :title="$t('userContent.listView')" />
                </v-btn-toggle>
                <v-btn icon="mdi-checkbox-multiple-outline"  variant="text"
                    :title="$t('userContent.selectMode')" class="ml-1" @click="selectionMode = true" />
            </template>
        </v-toolbar>

        <!-- 网格视图 -->
        <div v-if="media.length && viewMode === 'grid'" class="media-grid pa-4">
            <v-hover v-for="item in media" :key="item.media_id" v-slot="{ isHovering, props }">
                <v-card v-bind="props" class="media-card cursor-pointer" :elevation="isHovering ? 6 : 1"
                    :class="{ 'media-selected': isSelected(item) }" @click="handleItemClick(item)">
                    <div class="media-thumb-wrap">
                        <v-img v-if="item.type === 'image'" :src="item.url" height="110" cover class="media-thumb" />
                        <div v-else class="media-thumb media-thumb-video">
                            <video :src="item.url" preload="metadata" muted playsinline />
                            <div class="media-play">
                                <v-icon icon="mdi-play-circle" size="40" color="white" />
                            </div>
                        </div>

                        <!-- 多选：左上角选择框 -->
                        <v-checkbox-btn v-if="selectionMode" :model-value="isSelected(item)" density="compact"
                            color="primary" class="media-select-check" @click.stop="toggleSelect(item)" />
                        <!-- 普通：hover 显示复制链接 -->
                        <v-btn v-else-if="isHovering" icon="mdi-link-variant" size="x-small" color="surface"
                            variant="flat" class="media-copy-btn" :title="$t('userContent.copyLink')"
                            @click.stop="copyLink(item)" />
                    </div>
                    <v-card-text class="pa-2">
                        <div class="text-body-2 text-truncate" :title="item.filename">{{ item.filename }}</div>
                        <div class="text-caption text-medium-emphasis">
                            {{ item.type === 'image' ? $t('userContent.image') : $t('userContent.video') }} · {{
                            formatSize(item.size) }} · {{ mediaDate(item) }}
                        </div>
                    </v-card-text>
                </v-card>
            </v-hover>
        </div>

        <!-- 列表视图 -->
        <v-list v-else-if="media.length && viewMode === 'list'" class="media-list pa-0">
            <v-list-item v-for="item in media" :key="item.media_id" class="cursor-pointer"
                :class="{ 'media-selected-list': isSelected(item) }" @click="handleItemClick(item)">
                <template #prepend>
                    <v-checkbox-btn v-if="selectionMode" :model-value="isSelected(item)" density="compact"
                        color="primary" class="mr-1" @click.stop="toggleSelect(item)" />
                    <div class="media-list-thumb mr-3">
                        <template v-if="item.type === 'image'">
                            <img :src="item.url" :alt="item.filename" loading="lazy" />
                        </template>
                        <template v-else>
                            <video :src="item.url" preload="metadata" muted playsinline />
                            <div class="media-list-play">
                                <v-icon icon="mdi-play-circle" size="22" color="white" />
                            </div>
                        </template>
                    </div>
                </template>
                <v-list-item-title class="text-truncate">{{ item.filename }}</v-list-item-title>
                <v-list-item-subtitle class="text-caption">
                    {{ item.type === 'image' ? $t('userContent.image') : $t('userContent.video') }} · {{
                    formatSize(item.size) }} · {{ mediaDate(item) }}
                </v-list-item-subtitle>
                <template #append>
                    <v-btn v-if="!selectionMode" icon="mdi-link-variant"  variant="text"
                        :title="$t('userContent.copyLink')" @click.stop="copyLink(item)" />
                </template>
            </v-list-item>
        </v-list>

        <InfiniteScroll
            v-if="media.length"
            :loading="loading"
            :error="error"
            :finished="finished"
            @load="loadNext"
            @retry="retry"
        />
        <v-empty-state v-else-if="!loading" icon="mdi-image-multiple-outline"
            :title="$t('userContent.emptyMediaTitle')" :text="$t('userContent.emptyMediaText')" class="pa-4" />

        <!-- 媒体预览 -->
        <MediaPreviewDialog v-model:open="previewOpen" :item="preview" />
    </div>
</template>

<style scoped>
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}

.media-card {
    width: 100%;
}

.media-card.media-selected {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: -2px;
}

.media-thumb-wrap {
    position: relative;
}

.media-thumb {
    background: rgb(var(--v-theme-surface-variant));
}

.media-thumb-video {
    height: 110px;
    background: #000;
    overflow: hidden;
}

.media-thumb-video video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.media-play {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.25);
}

.media-select-check {
    position: absolute;
    top: 4px;
    left: 4px;
    background: rgba(0, 0, 0, 0.35);
    border-radius: 50%;
}

.media-copy-btn {
    position: absolute;
    top: 6px;
    right: 6px;
}

/* —— 列表视图 —— */
.media-list {
    border-top: 1px solid rgb(var(--v-border-color), var(--v-border-opacity));
}

.media-list-thumb {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    border-radius: 6px;
    overflow: hidden;
    background: rgb(var(--v-theme-surface-variant));
    position: relative;
}

.media-list-thumb img,
.media-list-thumb video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.media-list-play {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.25);
}

.media-selected-list {
    background: rgb(var(--v-theme-primary), 0.08);
}
</style>

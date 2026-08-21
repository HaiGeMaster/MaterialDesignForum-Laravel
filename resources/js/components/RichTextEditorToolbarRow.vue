<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import { useFeedback } from '../composables/useFeedback'
import { useAuthStore } from '../stores/auth'
import MediaLibraryDialog from './MediaLibraryDialog.vue'

const props = defineProps({
  /** 一行工具栏按钮数组 */
  buttons: { type: Array, required: true },
  /** tiptap 编辑器实例（图片/链接操作使用） */
  editor: { type: Object, default: null },
  /** 内容/选区变化计数，用于强制刷新按钮高亮状态 */
  refresh: { type: Number, default: 0 },
})

const { show } = useFeedback()
const { t } = useI18n()

// 当前正在上传的媒体类型（'' 表示空闲），用于按钮 loading 状态
const uploading = ref('')

/** 上传单个媒体到后端，成功后返回可访问 URL；失败返回空串并提示 */
async function uploadMediaFile(file, type) {
  const form = new FormData()
  form.append('type', type)
  form.append('file', file)
  try {
    const { data } = await http.post('/media', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data.url || ''
  } catch (err) {
    show(err.message || t('editorUpload.uploadFailed', { name: file.name }), 'error')
    return ''
  }
}

/**
 * 批量上传并插入：过滤出符合类型的文件，并行上传后逐个插入编辑器
 * @param {FileList|File[]} rawFiles
 * @param {'image'|'video'} type
 */
async function uploadAndInsert(rawFiles, type) {
  const files = Array.from(rawFiles || [])
  if (!files.length) return
  const valid = files.filter((f) => f.type.startsWith(`${type}/`))
  if (valid.length !== files.length) {
    show(t('editorUpload.ignored'), 'warning')
  }
  if (!valid.length) return
  uploading.value = type
  try {
    // 并行上传，全部完成后统一插入，避免光标位置跳动
    const urls = await Promise.all(valid.map((f) => uploadMediaFile(f, type)))
    // 一次性插入所有上传成功的媒体（与从媒体库插入同理，避免逐个 setImage/setVideo 相互替换）
    const nodes = urls
      .filter((u) => u)
      .map((url) =>
        type === 'image'
          ? { type: 'image', attrs: { src: url } }
          : { type: 'video', attrs: { src: url } },
      )
    if (nodes.length) {
      props.editor?.chain().focus().insertContent(nodes).run()
    }
    const failed = urls.filter((u) => !u).length
    if (failed) show(t('editorUpload.failedCount', { count: failed }), 'error')
  } finally {
    uploading.value = ''
  }
}

// —— 图片：先上传，拿 URL 后再插入（支持多选）——
const imageInput = ref(null)

function insertImage() {
  imageInput.value?.click()
}

function onImageChange(e) {
  const files = Array.from(e.target.files || [])
  e.target.value = ''
  uploadAndInsert(files, 'image')
}

// —— 视频：先上传，拿 URL 后再插入（支持多选）——
const videoInput = ref(null)

function insertVideo() {
  videoInput.value?.click()
}

function onVideoChange(e) {
  const files = Array.from(e.target.files || [])
  e.target.value = ''
  uploadAndInsert(files, 'video')
}

// —— 链接（位于“插入”分组菜单的链接面板中编辑）——
const linkUrl = ref('')

function applyLink(id) {
  if (linkUrl.value.trim()) {
    props.editor
      ?.chain()
      .focus()
      .extendMarkRange('link')
      .setLink({ href: linkUrl.value.trim() })
      .run()
  }
  openMenus.value[id] = false
  linkPanelMenu.value = ''
}

function unsetLink(id) {
  props.editor?.chain().focus().extendMarkRange('link').unsetLink().run()
  openMenus.value[id] = false
  linkPanelMenu.value = ''
}

// —— 从媒体库插入：弹窗多选当前用户已上传的图片/视频，确认后批量插入（逻辑见 MediaLibraryDialog.vue）——
const auth = useAuthStore()
const libraryOpen = ref(false)

// —— 分组菜单：把功能相近的按钮合并成一个小菜单，点击后弹出子项让用户选择 ——
const openMenus = ref({})      // 分组菜单 id -> 是否展开
const linkPanelMenu = ref('')  // 正在显示“链接输入面板”的分组菜单 id（'' 表示未显示）

function onMenuClick(id, item) {
  if (item.type === 'link') {
    // 切换到链接输入面板（保持菜单展开，可返回重新选择）
    linkPanelMenu.value = id
    linkUrl.value = props.editor?.getAttributes('link').href || ''
    return
  }
  openMenus.value[id] = false
  if (item.type === 'image') return insertImage()
  if (item.type === 'video') return insertVideo()
  if (item.type === 'library') {
    if (!auth.user?.user_id) {
      show(t('editor.libraryNoSelection'), 'error')
      return
    }
    libraryOpen.value = true
    return
  }
  item.action?.()
}

/** 分组菜单关闭时清理链接面板状态 */
function onMenuVisibility(id, open) {
  if (!open && linkPanelMenu.value === id) linkPanelMenu.value = ''
}
</script>

<template>
  <div class="rich-editor__toolbar-row">
    <template v-for="(btn, i) in buttons" :key="i">
      <v-divider v-if="btn.type === 'divider'" vertical inset class="mx-1" />

      <!-- 文字颜色 -->
      <v-tooltip v-else-if="btn.type === 'color'" :text="btn.title" >
        <template #activator="{ props: tipProps }">
          <v-menu  offset="6">
            <template #activator="{ props: menuProps }">
              <v-btn v-bind="{ ...menuProps, ...tipProps }" size="small" variant="text" icon>
                <v-icon :icon="btn.icon" size="small" />
              </v-btn>
            </template>
            <v-card min-width="180" class="pa-2">
              <div class="d-flex flex-wrap ga-1">
                <v-tooltip v-for="c in btn.colors" :key="c.value" :text="c.label" >
                  <template #activator="{ props: tipProps }">
                    <v-btn
                      v-bind="tipProps"
                      size="x-small"
                      icon
                      variant="flat"
                      :color="c.value"
                      :class="{ 'color-active': btn.current() === c.value }"
                      @click="btn.apply(c.value)"
                    />
                  </template>
                </v-tooltip>
              </div>
              <v-btn
                size="small"
                variant="text"
                prepend-icon="mdi-format-clear"
                class="mt-1"
                block
                @click="btn.clear()"
              >
                {{ $t('editorUpload.clearTitle', { title: btn.title }) }}
              </v-btn>
            </v-card>
          </v-menu>
        </template>
      </v-tooltip>

      <!-- 背景高亮 -->
      <v-tooltip v-else-if="btn.type === 'highlight'" :text="btn.title" >
        <template #activator="{ props: tipProps }">
          <v-menu  offset="6">
            <template #activator="{ props: menuProps }">
              <v-btn v-bind="{ ...menuProps, ...tipProps }" size="small" variant="text" icon>
                <v-icon :icon="btn.icon" size="small" />
              </v-btn>
            </template>
            <v-card min-width="180" class="pa-2">
              <div class="d-flex flex-wrap ga-1">
                <v-tooltip v-for="c in btn.colors" :key="c.value" :text="c.label" >
                  <template #activator="{ props: tipProps }">
                    <v-btn
                      v-bind="tipProps"
                      size="x-small"
                      icon
                      variant="flat"
                      :style="{ backgroundColor: c.value }"
                      :class="{ 'color-active': btn.current() === c.value }"
                      @click="btn.apply(c.value)"
                    />
                  </template>
                </v-tooltip>
              </div>
              <v-btn
                size="small"
                variant="text"
                prepend-icon="mdi-format-clear"
                class="mt-1"
                block
                @click="btn.clear()"
              >
                {{ $t('editorUpload.clearTitle', { title: btn.title }) }}
              </v-btn>
            </v-card>
          </v-menu>
        </template>
      </v-tooltip>

      <!-- 字号 / 行距下拉 -->
      <v-tooltip v-else-if="btn.type === 'select'" :text="btn.title" >
        <template #activator="{ props: tipProps }">
          <v-menu  offset="6">
            <template #activator="{ props: menuProps }">
              <v-btn
                v-bind="{ ...menuProps, ...tipProps }"
                size="small"
                variant="text"
                class="px-1 text-none"
              >
                <v-icon :icon="btn.icon" size="small" class="mr-0.5" />
                <span class="text-caption">{{ btn.label }}</span>
                <v-icon icon="mdi-menu-down" size="x-small" />
              </v-btn>
            </template>
            <v-list density="compact" min-width="120" max-height="280" class="overflow-y-auto">
              <v-list-item
                v-for="opt in btn.options"
                :key="opt.value"
                density="compact"
                :active="btn.current() === opt.value"
                @click="btn.action(opt.value)"
              >
                <v-list-item-title class="text-body-2">{{ opt.label }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </template>
      </v-tooltip>

      <!-- 功能分组菜单：合并同类按钮，点击弹出子项选择 -->
      <v-tooltip v-else-if="btn.type === 'menu'" :text="btn.title" >
        <template #activator="{ props: tipProps }">
          <v-menu
            v-model="openMenus[btn.id]"

            offset="6"
            @update:model-value="onMenuVisibility(btn.id, $event)"
          >
            <template #activator="{ props: menuProps }">
              <v-btn v-bind="{ ...menuProps, ...tipProps }" size="small" variant="text" icon>
                <v-icon :icon="btn.icon" size="small" />
              </v-btn>
            </template>

            <!-- 插入菜单中点“链接”后切换为链接输入面板 -->
            <v-card v-if="linkPanelMenu === btn.id" min-width="260" class="pa-2">
              <div class="d-flex align-center ga-1 mb-1">
                <v-btn size="x-small" variant="text" icon @click="linkPanelMenu = ''">
                  <v-icon icon="mdi-arrow-left" size="small" />
                </v-btn>
                <span class="text-body-2 font-weight-medium">{{ btn.title }}</span>
              </div>
              <v-text-field
                v-model="linkUrl"
                density="compact"
                variant="outlined"
                :label="$t('editorUpload.linkUrl')"
                placeholder="https://…"
                hide-details
                @keydown.enter.prevent="applyLink(btn.id)"
              />
              <div class="d-flex ga-1 mt-2">
                <v-btn size="small" color="primary" variant="tonal" @click="applyLink(btn.id)">
                  {{ $t('editorUpload.apply') }}
                </v-btn>
                <v-btn size="small" variant="tonal" @click="unsetLink(btn.id)">
                  {{ $t('editorUpload.unsetLink') }}
                </v-btn>
              </div>
            </v-card>

            <!-- 子功能列表 -->
            <v-list v-else density="compact" min-width="170">
              <v-list-item
                v-for="item in btn.items"
                :key="item.title"
                density="compact"
                :active="item.active?.()"
                @click="onMenuClick(btn.id, item)"
              >
                <v-list-item-title class="text-body-2">
                  <v-icon :icon="item.icon" size="small" class="mr-1" />
                  {{ item.title }}
                </v-list-item-title>
                <template #append>
                  <v-progress-circular
                    v-if="(item.type === 'image' && uploading === 'image') || (item.type === 'video' && uploading === 'video')"
                    size="16"
                    indeterminate
                  />
                </template>
              </v-list-item>
            </v-list>
          </v-menu>
        </template>
      </v-tooltip>

      <!-- 插入图片 -->
      <v-tooltip
        v-else-if="btn.type === 'image'"
        :text="uploading === 'image' ? $t('editorUpload.uploadingImage') : btn.title"

      >
        <template #activator="{ props: tipProps }">
          <v-btn
            v-bind="tipProps"
            size="small"
            variant="text"
            icon
            :loading="uploading === 'image'"
            :disabled="!!uploading"
            @click="insertImage"
          >
            <v-icon :icon="btn.icon" size="small" />
          </v-btn>
        </template>
      </v-tooltip>

      <!-- 插入视频 -->
      <v-tooltip
        v-else-if="btn.type === 'video'"
        :text="uploading === 'video' ? $t('editorUpload.uploadingVideo') : btn.title"

      >
        <template #activator="{ props: tipProps }">
          <v-btn
            v-bind="tipProps"
            size="small"
            variant="text"
            icon
            :loading="uploading === 'video'"
            :disabled="!!uploading"
            @click="insertVideo"
          >
            <v-icon :icon="btn.icon" size="small" />
          </v-btn>
        </template>
      </v-tooltip>

      <!-- 普通按钮 -->
      <v-tooltip v-else :text="btn.title" >
        <template #activator="{ props: tipProps }">
          <v-btn
            v-bind="tipProps"
            size="small"
            variant="text"
            icon
            :color="btn.active?.() ? 'primary' : undefined"
            @click="btn.action()"
          >
            <v-icon :icon="btn.icon" size="small" />
          </v-btn>
        </template>
      </v-tooltip>
    </template>

    <input ref="imageInput" type="file" accept="image/*" multiple hidden @change="onImageChange" />
    <input ref="videoInput" type="file" accept="video/*" multiple hidden @change="onVideoChange" />
    <!-- 隐藏占位：强制工具栏高亮随选区刷新 -->
    <span class="d-none">{{ refresh }}</span>

    <!-- 从媒体库插入 -->
    <MediaLibraryDialog v-model="libraryOpen" :editor="editor" />
  </div>
</template>

<style scoped>
/* 色块选中态 */
.color-active {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 1px;
}
</style>

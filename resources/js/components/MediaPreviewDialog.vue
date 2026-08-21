<script setup>
// 媒体预览对话框：图片 / 视频大图展示
// 用法：<MediaPreviewDialog v-model:item="preview" v-model:open="previewOpen" />
// 或：<MediaPreviewDialog v-model="previewOpen" :item="preview" />

const open = defineModel('open', { type: Boolean, default: false })

const props = defineProps({
    /** 当前预览的媒体项，type / url / filename */
    item: { type: Object, default: null },
})
</script>

<template>
    <v-dialog v-model="open" max-width="880" scrollable>
        <v-card style="margin-top: 40px;">
            <v-toolbar>
                <template #prepend>
                    <v-btn icon="mdi-image-outline"></v-btn>
                </template>
                <v-toolbar-title>
                    {{ props.item?.filename }}
                </v-toolbar-title>
                <template #append>
                    <v-btn icon="mdi-close" variant="text" @click="open = false" />
                </template>
            </v-toolbar>
            <v-card-text class="pa-0 bg-black">
                <v-img v-if="props.item?.type === 'image'" :src="props.item.url" max-height="70vh" contain />
                <video v-else :src="props.item?.url" controls autoplay class="d-block w-100 mx-auto"
                    style="max-height: 70vh" />
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

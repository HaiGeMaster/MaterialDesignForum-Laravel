<script setup>
import { ref, watch, nextTick } from 'vue'
import http from '../api'

const props = defineProps({
  clients: { type: Array, default: () => [] },
  query: { type: String, default: '' },
  command: { type: Function, required: true },
  virtualProps: { type: Object, default: () => ({}) },
})

const selectedIndex = ref(0)
const filtered = ref([])
let searchTimer = null

watch(
  () => props.query,
  (q) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(async () => {
      try {
        const { data } = await http.get('/users', { params: { q, per_page: 10 } })
        const list = Array.isArray(data) ? data : data?.data || []
        filtered.value = list.map((u) => ({
          id: u.user_id,
          label: u.username,
        }))
      } catch {
        filtered.value = []
      }
      selectedIndex.value = 0
    }, 200)
  },
  { immediate: true },
)

watch(selectedIndex, () => {
  nextTick(() => {
    const el = document.querySelector('.mention-item.active')
    el?.scrollIntoView({ block: 'nearest' })
  })
})

function upHandler() {
  selectedIndex.value = (selectedIndex.value - 1 + filtered.value.length) % filtered.value.length
}

function downHandler() {
  selectedIndex.value = (selectedIndex.value + 1) % filtered.value.length
}

function enterHandler() {
  const item = filtered.value[selectedIndex.value]
  if (item) selectItem(item)
}

function selectItem(item) {
  props.command({ id: item.id, label: item.label })
}

defineExpose({ upHandler, downHandler, enterHandler })
</script>

<template>
  <v-card rounded="lg" min-width="180" max-height="200" class="overflow-y-auto">
    <div v-if="filtered.length" class="py-1">
      <div
        v-for="(item, i) in filtered"
        :key="item.id"
        class="mention-item px-3 py-1.5 d-flex align-center"
        :class="{ active: i === selectedIndex }"
        style="cursor: pointer"
        @click="selectItem(item)"
        @mouseenter="selectedIndex = i"
      >
        <v-icon icon="mdi-account" size="small" class="mr-2" />
        <span class="text-body-2">{{ item.label }}</span>
      </div>
    </div>
    <div v-else class="pa-3 text-body-2 text-center" style="color: rgba(var(--v-theme-on-surface), 0.5)">
      {{ $t('editor.mention.noResult') }}
    </div>
  </v-card>
</template>

<style scoped>
.mention-item:hover,
.mention-item.active {
  background: rgb(var(--v-theme-primary), 0.08);
}
</style>

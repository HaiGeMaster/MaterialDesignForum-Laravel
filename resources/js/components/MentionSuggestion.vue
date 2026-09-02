<script setup>
import { ref, watch, nextTick } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  query: { type: String, default: '' },
  command: { type: Function, required: true },
})

const selectedIndex = ref(0)
const filtered = ref(props.items)

watch(
  () => props.items,
  (items) => {
    filtered.value = items
    selectedIndex.value = 0
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
  if (!filtered.value.length) return
  selectedIndex.value = (selectedIndex.value - 1 + filtered.value.length) % filtered.value.length
}

function downHandler() {
  if (!filtered.value.length) return
  selectedIndex.value = (selectedIndex.value + 1) % filtered.value.length
}

function enterHandler() {
  const item = filtered.value[selectedIndex.value]
  if (item) selectItem(item)
}

function selectItem(item) {
  props.command({ id: item.id, label: item.label })
}

function onKeyDown({ event }) {
  if (event.key === 'ArrowUp') {
    upHandler()
    return true
  }

  if (event.key === 'ArrowDown') {
    downHandler()
    return true
  }

  if (event.key === 'Enter') {
    enterHandler()
    return true
  }

  return false
}

defineExpose({ onKeyDown, upHandler, downHandler, enterHandler })
</script>

<template>
  <v-card rounded="lg" min-width="180" max-height="200" class="mention-card overflow-y-auto">
    <v-list v-if="filtered.length" rounded="lg" density="compact" class="pa-0">
      <v-list-item
        v-for="(item, i) in filtered"
        :key="item.id"
        class="mention-item"
        :class="{ active: i === selectedIndex }"
        @click="selectItem(item)"
        @mouseenter="selectedIndex = i"
      >
        <!-- :title="item.label" -->
        <template #prepend>
          <v-avatar size="32">
            <v-img v-if="item.avatar" :src="item.avatar" :alt="item.label" cover />
            <span v-else>{{ item.label?.slice(0, 1).toUpperCase() || '?' }}</span>
          </v-avatar>
        </template>
        <v-list-item-title class="text-body-2 text-truncate">
          {{ item.label }}
        </v-list-item-title>
      </v-list-item>
    </v-list>
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

.mention-card {
  z-index: 1201;
}
</style>

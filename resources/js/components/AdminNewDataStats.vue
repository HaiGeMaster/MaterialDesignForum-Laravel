<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart } from 'echarts/charts'
import { GridComponent, TooltipComponent } from 'echarts/components'

use([CanvasRenderer, LineChart, GridComponent, TooltipComponent])

const props = defineProps({
  item: { type: Object, required: true },
  data: { type: Array, default: () => [] },
  range: { type: String, default: 'last_7_days' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:range', 'open'])
const { t, locale } = useI18n()

const rangeItems = computed(() => [
  { title: t('admin.lastSevenDays'), value: 'last_7_days' },
  { title: t('admin.thisMonth'), value: 'this_month' },
  { title: t('admin.lastMonth'), value: 'last_month' },
  { title: t('admin.lastThirtyDays'), value: 'last_30_days' },
  { title: t('admin.thisYear'), value: 'this_year' },
  { title: t('admin.lastYear'), value: 'last_year' },
  { title: t('admin.lastOneYear'), value: 'last_1_year' },
  ...Array.from({ length: 5 }, (_, index) => {
    const year = new Date().getFullYear() - index
    return { title: String(year), value: String(year) }
  }),
])

const chartOption = computed(() => {
  const dates = props.data.map((point) => point.date)
  const values = props.data.map((point) => Number(point.count || 0))
  const labelDates = dates.map((date) => new Intl.DateTimeFormat(locale.value, { month: 'numeric', day: 'numeric' }).format(new Date(`${date}T00:00:00`)))

  return {
    animationDuration: 450,
    grid: { left: 12, right: 12, top: 18, bottom: 12, containLabel: true },
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'line' },
      backgroundColor: 'rgba(30, 30, 30, 0.9)',
      borderWidth: 0,
      textStyle: { color: '#fff' },
      formatter: (params) => {
        const point = params[0]
        return `<strong>${point.axisValue}</strong><br/>${t(`admin.${props.item.label}`)}: <strong>${point.value}</strong>`
      },
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: labelDates,
      axisLine: { lineStyle: { color: 'rgba(128,128,128,0.35)' } },
      axisLabel: { color: 'rgba(128,128,128,0.9)', hideOverlap: true },
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      axisLine: { show: false },
      axisTick: { show: false },
      axisLabel: { color: 'rgba(128,128,128,0.9)' },
      splitLine: { lineStyle: { color: 'rgba(128,128,128,0.14)' } },
    },
    series: [{
      type: 'line',
      data: values,
      smooth: true,
      symbol: 'circle',
      symbolSize: 6,
      lineStyle: { color: props.item.chartColor, width: 2.5 },
      itemStyle: { color: props.item.chartColor },
      areaStyle: { color: props.item.areaColor },
    }],
  }
})

function changeRange(value) {
  emit('update:range', value)
}
</script>

<template>
  <v-card rounded="lg" class="stats-card h-100" @click="emit('open', item)">
    <v-toolbar :title="$t(`admin.${item.label}`)">
      <template #prepend>
        <v-avatar :color="item.chartColor" size="32" class="mx-2">
          <v-icon :icon="item.icon" size="18" />
        </v-avatar>
         </template>
      <template #append>

        <v-select :model-value="range" :items="rangeItems" item-title="title" item-value="value" density="compact"
          variant="underlined" hide-details prepend-inner-icon="mdi-calendar-month-outline" class="range-select"
          @click.stop @update:model-value="changeRange" />
      </template>
    </v-toolbar>
    <v-card-text>
      <div class="chart-wrap">
        <v-progress-linear v-if="loading" indeterminate color="primary" class="chart-loading" />
        <div v-if="!loading && !data.length" class="chart-empty">{{ $t('admin.noData') }}</div>
        <VChart v-if="data.length" :option="chartOption" autoresize class="chart" />
      </div>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.stats-card {
  /* border-radius: 12px; */
  cursor: pointer;
}

.range-select {
  flex: 0 1 150px;
  min-width: 112px;
}

.chart-wrap {
  position: relative;
  height: 270px;
}

.chart {
  height: 270px;
  width: 100%;
}

.chart-loading {
  position: absolute;
  top: 0;
  left: 0;
}

.chart-empty {
  display: grid;
  height: 270px;
  place-items: center;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

@media (max-width: 600px) {
  .range-select {
    flex-basis: 118px;
  }
}
</style>

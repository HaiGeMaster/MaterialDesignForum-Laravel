<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import http from '../api'
import { useFeedback } from '../composables/useFeedback'

const { t, locale } = useI18n()
const router = useRouter()
const { xs } = useDisplay()
const { show } = useFeedback()

const loading = ref(false)
const status = ref({
  signed_today: false,
  total_sign_days: 0,
  continuous_sign_days: 0,
  last_sign_date: null,
  last_reward_points: 0,
  total_reward_points: 0,
  month_dates: [],
})

function formatDateKey(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const calendarValue = ref(new Date())
const monthLabel = computed(() => calendarValue.value.toLocaleDateString(locale.value === 'en' ? 'en-US' : 'zh-CN', { year: 'numeric', month: 'long' }))
const calendarEvents = computed(() =>
  status.value.month_dates.map((date) => ({
    title: t('signIn.checkedBadge'),
    start: `${date}T00:00:00`,
    end: `${date}T23:59:59`,
    color: 'primary',
    timed: false,
  })),
)

function isSignedDay(date) {
  if (!date) return false
  return status.value.month_dates.includes(formatDateKey(date))
}

function isToday(date) {
  if (!date) return false
  const today = new Date()
  return date.getFullYear() === today.getFullYear() && date.getMonth() === today.getMonth() && date.getDate() === today.getDate()
}

function getBadge(day) {
  if (!day) return ''
  return status.value.month_dates.includes(formatDateKey(day)) ? t('signIn.checkedBadge') : ''
}

async function fetchStatus() {
  loading.value = true
  try {
    const { data } = await http.get('/sign-in/status')
    status.value = data
  } catch (error) {
    show(error.message || t('signIn.fetchFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function handleCheckIn() {
  if (status.value.signed_today) {
    show(t('signIn.already'), 'info')
    return
  }

  loading.value = true
  try {
    const { data } = await http.post('/sign-in/check-in')
    status.value.signed_today = true
    status.value.total_sign_days = data.total_sign_days
    status.value.continuous_sign_days = data.continuous_sign_days
    status.value.last_reward_points = data.reward_points
    status.value.total_reward_points = data.total_reward_points
    const todayKey = formatDateKey(new Date())
    if (!status.value.month_dates.includes(todayKey)) {
      status.value.month_dates.push(todayKey)
    }
    show(t('signIn.success', { points: data.reward_points }), 'success')
  } catch (error) {
    show(error.message || t('signIn.failed'), 'error')
  } finally {
    loading.value = false
  }
}

onMounted(fetchStatus)
</script>

<template>
  <v-container :class="xs ? 'pa-0' : 'px-4'" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-card rounded="lg" class="mx-auto" max-width="900">
      <v-toolbar :title="t('route.signIn')">
        <template #prepend>
          <v-btn icon variant="text" @click="router.back()">
            <v-icon>mdi-arrow-left</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ t('common.back') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <v-btn
            color="primary"
            variant="flat"
            :loading="loading"
            :disabled="status.signed_today"
            prepend-icon="mdi-calendar-check"
            @click="handleCheckIn"
          >
            {{ status.signed_today ? t('signIn.checkedIn') : t('signIn.checkIn') }}
          </v-btn>
        </template>
      </v-toolbar>

      <v-divider />

      <v-card-text class="pa-4 pa-md-6">
        <div class="d-flex flex-column flex-md-row align-md-center justify-space-between mb-5 ga-3">
          <div>
            <div class="text-overline text-primary mb-1">{{ t('route.signIn') }}</div>
            <h1 class="text-h4 font-weight-bold mb-0">{{ t('signIn.title') }}</h1>
          </div>
        </div>

        <v-row class="mb-4">
          <v-col cols="12" sm="6" md="3">
            <v-sheet color="primary" rounded="lg" class="pa-4 text-white" elevation="0">
              <div class="text-caption opacity-80">{{ t('signIn.total') }}</div>
              <div class="text-h4 font-weight-bold mt-2">{{ status.total_sign_days }}</div>
              <div class="text-caption opacity-80">{{ t('signIn.days') }}</div>
            </v-sheet>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-sheet color="amber-lighten-2" rounded="lg" class="pa-4" elevation="0">
              <div class="text-caption">{{ t('signIn.continuous') }}</div>
              <div class="text-h4 font-weight-bold mt-2">{{ status.continuous_sign_days }}</div>
              <div class="text-caption">{{ t('signIn.days') }}</div>
            </v-sheet>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-sheet color="green-lighten-2" rounded="lg" class="pa-4" elevation="0">
              <div class="text-caption">{{ t('signIn.totalReward') }}</div>
              <div class="text-h4 font-weight-bold mt-2">{{ status.total_reward_points || 0 }}</div>
              <div class="text-caption">{{ t('signIn.points') }}</div>
            </v-sheet>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-sheet color="teal-lighten-2" rounded="lg" class="pa-4" elevation="0">
              <div class="text-caption">{{ t('signIn.reward') }}</div>
              <div class="text-h4 font-weight-bold mt-2">{{ status.last_reward_points || 10 }}</div>
              <div class="text-caption">{{ t('signIn.points') }}</div>
            </v-sheet>
          </v-col>
        </v-row>

        <v-card variant="outlined" class="pa-4 rounded-xl mb-4" color="surface">
            <div class="d-flex align-center justify-space-between mb-3">
              <div class="text-subtitle-1 font-weight-bold">{{ monthLabel }}</div>
              <div class="text-body-2 text-medium-emphasis">
                {{ status.signed_today ? t('signIn.checkedInToday') : t('signIn.notCheckedInToday') }}
              </div>
            </div>

            <v-calendar
              v-model="calendarValue"
              type="month"
              :events="calendarEvents"
              color="primary"
              event-color="primary"
              :show-current="true"
              class="sign-calendar"
            />
          </v-card>
        <v-card variant="text" class="pa-0" color="transparent">
          <div class="text-subtitle-1 font-weight-bold mb-2">{{ t('signIn.rulesTitle') }}</div>
          <v-list density="compact" class="pa-0" nav>
            <v-list-item prepend-icon="mdi-star-circle" :title="t('signIn.ruleBase')" />
            <v-list-item prepend-icon="mdi-trophy" :title="t('signIn.ruleChain')" />
            <v-list-item prepend-icon="mdi-shield-check" :title="t('signIn.ruleLimit')" />
          </v-list>
        </v-card>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<style scoped>
.sign-calendar {
  --v-calendar-line-height: 1.4;
}

.sign-calendar :deep(.v-calendar-weekly__day) {
  min-height: 86px;
}

.sign-calendar :deep(.v-calendar-month__day) {
  min-height: 84px;
}
</style>

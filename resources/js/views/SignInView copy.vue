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
  month_dates: [],
})

function formatDateKey(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const monthCursor = ref(new Date())
const monthLabel = computed(() => monthCursor.value.toLocaleDateString(locale.value === 'en' ? 'en-US' : 'zh-CN', { year: 'numeric', month: 'long' }))
const monthDates = computed(() => {
  const year = monthCursor.value.getFullYear()
  const month = monthCursor.value.getMonth()
  const start = new Date(year, month, 1)
  const end = new Date(year, month + 1, 0)
  const grid = []
  const firstWeekday = (start.getDay() + 6) % 7

  for (let i = 0; i < firstWeekday; i++) {
    grid.push(null)
  }

  for (let day = 1; day <= end.getDate(); day++) {
    grid.push(new Date(year, month, day))
  }

  while (grid.length < 42) {
    grid.push(null)
  }

  return grid
})

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
          <v-col cols="12" md="4">
            <v-sheet color="primary" rounded="lg" class="pa-4 text-white" elevation="0">
              <div class="text-caption opacity-80">{{ t('signIn.total') }}</div>
              <div class="text-h4 font-weight-bold mt-2">{{ status.total_sign_days }}</div>
              <div class="text-caption opacity-80">{{ t('signIn.days') }}</div>
            </v-sheet>
          </v-col>
          <v-col cols="12" md="4">
            <v-sheet color="amber-lighten-2" rounded="lg" class="pa-4" elevation="0">
              <div class="text-caption">{{ t('signIn.continuous') }}</div>
              <div class="text-h4 font-weight-bold mt-2">{{ status.continuous_sign_days }}</div>
              <div class="text-caption">{{ t('signIn.days') }}</div>
            </v-sheet>
          </v-col>
          <v-col cols="12" md="4">
            <v-sheet color="green-lighten-2" rounded="lg" class="pa-4" elevation="0">
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

          <v-row class="text-center text-caption mb-2" no-gutters>
            <v-col v-for="label in ['一', '二', '三', '四', '五', '六', '日']" :key="label" cols="1" class="py-1 text-medium-emphasis">
              {{ label }}
            </v-col>
          </v-row>

          <v-row no-gutters>
            <v-col v-for="(day, index) in monthDates" :key="day ? `${day.getFullYear()}-${day.getMonth()}-${day.getDate()}` : `empty-${index}`" cols="1" class="calendar-cell">
              <div
                v-if="day"
                :class="[
                  'calendar-day',
                  { 'signed': isSignedDay(day), 'today': isToday(day), 'muted': day.getMonth() !== monthCursor.getMonth() }
                ]"
              >
                <span>{{ day.getDate() }}</span>
                <small v-if="getBadge(day)">{{ getBadge(day) }}</small>
              </div>
              <div v-else class="calendar-day empty" />
            </v-col>
          </v-row>
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
.calendar-cell {
  padding: 4px;
}

.calendar-day {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  aspect-ratio: 1;
  border-radius: 12px;
  background: rgba(99, 102, 241, 0.06);
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
  transition: 0.2s ease;
}

.calendar-day.small {
  font-size: 0.8rem;
}

.calendar-day.signed {
  background: linear-gradient(135deg, #ffd54f, #ffb300);
  color: #fff;
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.7);
}

.calendar-day.today {
  outline: 2px solid rgb(var(--v-theme-primary));
}

.calendar-day.empty {
  background: transparent;
  box-shadow: none;
}

.calendar-day small {
  margin-top: 2px;
  font-size: 9px;
  font-weight: 700;
}

.calendar-day.muted {
  opacity: 0.45;
}
</style>

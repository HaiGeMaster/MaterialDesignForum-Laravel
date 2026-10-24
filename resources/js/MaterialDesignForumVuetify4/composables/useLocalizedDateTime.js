import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const clockSubscribers = new Set()
let clockInterval = null

export function useLocalizedDateTime() {
  const { locale, t } = useI18n()
  const now = ref(Date.now())

  const dateLocale = computed(() => {
    const vuetifyLangcode = t('baseLanguagePackInfo.vuetifyLangcode')
    const packageLocale = t('baseLanguagePackInfo.bcp47')
    const localeByVuetifyCode = {
      zhHans: 'zh-CN',
      zhHant: 'zh-TW',
    }

    if (packageLocale && packageLocale !== 'baseLanguagePackInfo.bcp47') {
      return packageLocale
    }
    return localeByVuetifyCode[vuetifyLangcode] || locale.value
  })

  onMounted(() => {
    clockSubscribers.add(now)
    if (!clockInterval) {
      clockInterval = setInterval(() => {
        const currentTime = Date.now()
        for (const subscriber of clockSubscribers) {
          subscriber.value = currentTime
        }
      }, 30_000)
    }
  })

  onBeforeUnmount(() => {
    clockSubscribers.delete(now)
    if (!clockSubscribers.size && clockInterval) {
      clearInterval(clockInterval)
      clockInterval = null
    }
  })

  function formatDateTime(value, { relative = true, includeTime = true } = {}) {
    if (value === null || value === undefined || value === '') return ''

    const date = value instanceof Date
      ? value
      : typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)
        ? new Date(`${value}T00:00:00`)
        : new Date(value)
    if (Number.isNaN(date.getTime())) return String(value)

    const elapsed = now.value - date.getTime()
    if (relative && elapsed >= 0 && elapsed < 60_000) {
      return t('dateTime.justNow')
    }
    if (relative && elapsed >= 60_000 && elapsed < 60 * 60_000) {
      const minutes = Math.floor(elapsed / 60_000)
      return minutes === 1
        ? t('dateTime.minuteAgo')
        : t('dateTime.minutesAgo', { count: new Intl.NumberFormat(dateLocale.value).format(minutes) })
    }

    const currentYear = new Date(now.value).getFullYear()
    const options = {
      month: 'numeric',
      day: 'numeric',
    }
    if (date.getFullYear() !== currentYear) options.year = 'numeric'
    if (includeTime) {
      options.hour = '2-digit'
      options.minute = '2-digit'
    }

    return new Intl.DateTimeFormat(dateLocale.value, options).format(date)
  }

  return formatDateTime
}

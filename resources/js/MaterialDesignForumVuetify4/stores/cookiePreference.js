import { defineStore } from 'pinia'
import { useFeedback } from '../composables/useFeedback'
import { i18n } from '../i18n'
import { initClarity, stopClarity } from '../clarity'

// 政策版本：Cookie 清单或服务方发生变化时递增，触发重新征求同意
const CONSENT_VERSION = '1.0'
// 拒绝后多少天内不再重复弹出
const REASK_COOLDOWN_DAYS = 180
// 默认选择：非必要类别全部关闭（无预勾选）
const DEFAULT_PREFS = Object.freeze({
  necessary: true,//必要类别默认勾选
  functional: false,//功能偏好类别默认不勾选
  analytics: false,//分析统计类别默认不勾选
  marketing: false,//广告营销类别默认不勾选
})

function defaultPrefs() {
  return { necessary: true, functional: false, analytics: false, marketing: false }
}

/**
 * 独立的同意检查（无需 Pinia 上下文，供外部脚本门控使用）
 * 读取 localStorage 中的同意记录，校验政策版本后返回指定类别是否已同意
 */
export function checkConsent(category) {
  try {
    const saved = localStorage.getItem('cookie-consent')
    if (!saved) return false
    const parsed = JSON.parse(saved)
    if (parsed.version !== CONSENT_VERSION) return false
    return !!parsed.choice?.[category]
  } catch {
    return false
  }
}

// 与站点实际扫描结果保持一致；新增第三方服务后必须同步更新
// 文本字段存储 i18n key，由组件用 t() 解析；thirdParty 标记第三方 Cookie（不可由本站清除）
const CATEGORIES = [
  {
    key: 'necessary',
    required: true,
    nameKey: 'cookiePref.cat.necessary.name',
    descKey: 'cookiePref.cat.necessary.desc',
    introKey: 'cookiePref.cat.necessary.intro',
    legalBasisKey: 'cookiePref.cat.necessary.legalBasis',
    noteKey: 'cookiePref.cat.necessary.note',
    cookies: [
      {
        name: 'XSRF-TOKEN',
        id: 'XSRF_TOKEN',
        thirdParty: false,
        providerKey: 'cookiePref.cookie.XSRF_TOKEN.provider',
        purposeKey: 'cookiePref.cookie.XSRF_TOKEN.purpose',
        typeKey: 'cookiePref.cookie.type.firstPartySession',
        durationKey: 'cookiePref.cookie.XSRF_TOKEN.duration',
      },
      {
        name: 'xbedrock-session / xxx-session',
        id: 'xbedrock_session',
        thirdParty: false,
        providerKey: 'cookiePref.cookie.xbedrock_session.provider',
        purposeKey: 'cookiePref.cookie.xbedrock_session.purpose',
        typeKey: 'cookiePref.cookie.type.firstPartySession',
        durationKey: 'cookiePref.cookie.xbedrock_session.duration',
      },
      {
        name: 'cf_clearance',
        id: 'cf_clearance',
        thirdParty: true,
        providerKey: 'cookiePref.cookie.cf_clearance.provider',
        purposeKey: 'cookiePref.cookie.cf_clearance.purpose',
        typeKey: 'cookiePref.cookie.type.thirdPartyPersistent',
        durationKey: 'cookiePref.cookie.cf_clearance.duration',
      },
    ],
  },
  {
    key: 'functional',
    required: false,
    nameKey: 'cookiePref.cat.functional.name',
    descKey: 'cookiePref.cat.functional.desc',
    introKey: 'cookiePref.cat.functional.intro',
    legalBasisKey: 'cookiePref.cat.functional.legalBasis',
    noteKey: '',
    cookies: [
      {
        name: 'ui-locale',
        id: 'ui_locale',
        thirdParty: false,
        providerKey: 'cookiePref.cookie.ui_locale.provider',
        purposeKey: 'cookiePref.cookie.ui_locale.purpose',
        typeKey: 'cookiePref.cookie.type.firstPartyPersistent',
        durationKey: 'cookiePref.cookie.ui_locale.duration',
      },
      {
        name: 'ui-theme',
        id: 'ui_theme',
        thirdParty: false,
        providerKey: 'cookiePref.cookie.ui_theme.provider',
        purposeKey: 'cookiePref.cookie.ui_theme.purpose',
        typeKey: 'cookiePref.cookie.type.firstPartyPersistent',
        durationKey: 'cookiePref.cookie.ui_theme.duration',
      },
    ],
  },
  {
    key: 'analytics',
    required: false,
    nameKey: 'cookiePref.cat.analytics.name',
    descKey: 'cookiePref.cat.analytics.desc',
    introKey: 'cookiePref.cat.analytics.intro',
    legalBasisKey: 'cookiePref.cat.analytics.legalBasis',
    noteKey: 'cookiePref.cat.analytics.note',
    cookies: [
      {
        name: '_clck',
        id: '_clck',
        thirdParty: false,
        providerKey: 'cookiePref.cookie._clck.provider',
        purposeKey: 'cookiePref.cookie._clck.purpose',
        typeKey: 'cookiePref.cookie.type.firstPartyPersistent',
        durationKey: 'cookiePref.cookie._clck.duration',
      },
      {
        name: '_clsk',
        id: '_clsk',
        thirdParty: false,
        providerKey: 'cookiePref.cookie._clsk.provider',
        purposeKey: 'cookiePref.cookie._clsk.purpose',
        typeKey: 'cookiePref.cookie.type.firstPartyPersistent',
        durationKey: 'cookiePref.cookie._clsk.duration',
        },
      {
        name: 'MUID',
        id: 'MUID',
        thirdParty: true,
        providerKey: 'cookiePref.cookie.MUID.provider',
        purposeKey: 'cookiePref.cookie.MUID.purpose',
        typeKey: 'cookiePref.cookie.type.thirdPartyPersistent',
        durationKey: 'cookiePref.cookie.MUID.duration',
      },
    ],
  },
  {
    key: 'marketing',
    required: false,
    nameKey: 'cookiePref.cat.marketing.name',
    descKey: 'cookiePref.cat.marketing.desc',
    introKey: 'cookiePref.cat.marketing.intro',
    legalBasisKey: 'cookiePref.cat.marketing.legalBasis',
    noteKey: 'cookiePref.cat.marketing.note',
    cookies: [
    //   {
    //     name: 'MUID',
    //     id: 'MUID',
    //     thirdParty: true,
    //     providerKey: 'cookiePref.cookie.MUID.provider',
    //     purposeKey: 'cookiePref.cookie.MUID.purpose',
    //     typeKey: 'cookiePref.cookie.type.thirdPartyPersistent',
    //     durationKey: 'cookiePref.cookie.MUID.duration',
    //   },
    ],
  },
]

/**
 * Cookie 同意 + 偏好中心 store
 *
 * 全局单例，管理 Cookie 同意横幅、偏好中心对话框与同意记录。
 * 供 DrawerCopyright / 任意入口通过 openPreferenceCenter() 打开偏好中心。
 *
 * 用法：
 *   const cookiePref = useCookiePreferenceStore()
 *   cookiePref.openPreferenceCenter()
 */
export const useCookiePreferenceStore = defineStore('cookiePreference', {
  state: () => ({
    /** 底部同意横幅是否显示 */
    bannerSheet: false,
    /** 偏好中心对话框是否显示 */
    prefSheet: false,
    /** 是否已做出过同意决策（决定对话框是否可点击遮罩关闭） */
    decided: false,
    /** 上次同意更新的本地时间字符串 */
    lastUpdated: '',
    /** 展开的面板数字索引 */
    openedPanels: [0],
    /** 当前各类别选择（未保存的草稿） */
    prefs: defaultPrefs(),
    /** 已保存的基线快照，用于比对是否有未保存更改 */
    savedSnapshot: null,
    /** 同意记录（时间 + 类别 + 政策版本） */
    consentRecord: null,
    /** Cookie 类别与明细清单（文本字段为 i18n key，由组件解析） */
    categories: CATEGORIES,
    /** 是否已初始化（避免重复读取 localStorage） */
    initialized: false,
  }),

  getters: {
    /** 当前已启用的类别（必要类 + 用户开启的类别） */
    enabledCategories: (state) =>
      state.categories.filter((c) => c.required || state.prefs[c.key]),
    /** 比对基线：首次决策前为默认值 */
    baseline: (state) => state.savedSnapshot || DEFAULT_PREFS,
    /** 是否存在未保存的更改 */
    dirty: (state) =>
      JSON.stringify(state.prefs) !==
      JSON.stringify(state.savedSnapshot || DEFAULT_PREFS),
    /** 同意记录的可读文本 */
    consentRecordText: (state) =>
      state.consentRecord ? JSON.stringify(state.consentRecord, null, 2) : '（暂无）',
  },

  actions: {
    /** 操作反馈：复用全局 snackbar，文本通过 i18n key 解析 */
    toast(key) {
      useFeedback().show(i18n.global.t(key), 'info')
    },

    /** 打开偏好中心（同时关闭横幅） */
    openPreferenceCenter() {
      this.bannerSheet = false
      this.prefSheet = true
    },

    /** 打开同意横幅；force=true 时视为重新征求（decided 重置） */
    openBanner(force = false) {
      this.prefSheet = false
      this.bannerSheet = true
      if (force) this.decided = false
    },

    closePreferenceCenter() {
      this.prefSheet = false
    },

    closeBanner() {
      this.bannerSheet = false
    },

    /** 切换某类别开关（必要类不可关闭） */
    toggleCategory(cat, value) {
      if (cat.required) return
      this.prefs[cat.key] = !!value
    },

    // 撤回同意时按类别删除已落地的 Cookie（第三方域 Cookie 需由提供方侧清除）
    deleteCookiesByKey(key) {
      const cat = this.categories.find((c) => c.key === key)
      if (!cat) return
      const host = location.hostname
      const parts = host.split('.')
      const baseDomain = parts.length > 2 ? parts.slice(-2).join('.') : host
      const domains = [host, '.' + host, '.' + baseDomain]
      cat.cookies.forEach((ck) => {
        if (ck.thirdParty) return
        domains.forEach((d) => {
          document.cookie =
            ck.name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + d
        })
        document.cookie =
          ck.name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/'
      })
    },

    // 脚本门控：撤回同意时按类别删除已落地的 Cookie（第三方域 Cookie 需由提供方侧清除）
    syncScripts(before, after) {
      ;['functional', 'analytics', 'marketing'].forEach((key) => {
        if (before[key] && !after[key]) {
          this.deleteCookiesByKey(key)
        }
      })
    },

    /** 应用一次同意决策：同步脚本、写入同意记录、更新基线 */
    applyConsent(choice) {
      const before = Object.assign({}, this.savedSnapshot || defaultPrefs())
      this.syncScripts(before, choice)

      const record = {
        choice: Object.assign({}, choice),
        timestamp: new Date().toISOString(),
        version: CONSENT_VERSION,
        nextAskAt: new Date(
          Date.now() + REASK_COOLDOWN_DAYS * 86400000,
        ).toISOString(),
      }
      // 同意证据：时间 + 类别 + 政策版本，用于自证合规
      localStorage.setItem('cookie-consent', JSON.stringify(record))
      this.consentRecord = record
      this.savedSnapshot = Object.assign({}, choice)
      this.decided = true
      this.lastUpdated = new Date().toLocaleString()
      this.bannerSheet = false
      this.prefSheet = false

      // Clarity 门控：需要同时同意 analytics + marketing 才初始化/恢复
      // localStorage 已写入，initClarity 内部的 checkConsent 可读到最新同意状态
      const clarityBefore = !!(before.analytics && before.marketing)
      const clarityAfter = !!(choice.analytics && choice.marketing)
      if (!clarityBefore && clarityAfter) {
        initClarity()
      } else if (clarityBefore && !clarityAfter) {
        stopClarity()
      }
    },

    acceptAll() {
      this.prefs = { necessary: true, functional: true, analytics: true, marketing: true }
      this.applyConsent(this.prefs)
      this.toast('cookiePref.toast.acceptAll')
    },

    rejectAll() {
      this.prefs = defaultPrefs()
      this.applyConsent(this.prefs)
      this.toast('cookiePref.toast.rejectAll')
    },

    savePrefs() {
      this.applyConsent(this.prefs)
      this.toast('cookiePref.toast.saved')
    },

    /** 放弃未保存的更改，回退到基线 */
    revertChanges() {
      this.prefs = Object.assign({}, this.savedSnapshot || DEFAULT_PREFS)
      this.toast('cookiePref.toast.reverted')
    },

    /** 清除本地同意记录，刷新后将重新征求 */
    resetAll() {
      localStorage.removeItem('cookie-consent')
      this.consentRecord = null
      this.savedSnapshot = null
      this.decided = false
      this.lastUpdated = ''
      this.prefs = defaultPrefs()
      this.toast('cookiePref.toast.reset')
    },

    /**
     * 初始化：从 localStorage 读取同意记录
     * 政策版本变更 → 旧记录失效，重新弹出横幅；首次访问自动弹出横幅
     */
    init() {
      if (this.initialized) return
      this.initialized = true
      const saved = localStorage.getItem('cookie-consent')
      if (saved) {
        const parsed = JSON.parse(saved)
        if (parsed.version !== CONSENT_VERSION) {
          this.bannerSheet = true
          return
        }
        this.prefs = Object.assign(defaultPrefs(), parsed.choice)
        this.savedSnapshot = Object.assign({}, this.prefs)
        this.consentRecord = parsed
        this.decided = true
        this.lastUpdated = new Date(parsed.timestamp).toLocaleString()
        this.syncScripts(parsed.choice, parsed.choice)
      } else {
        // 首次访问自动弹出横幅
        this.bannerSheet = true
      }
    },
  },
})

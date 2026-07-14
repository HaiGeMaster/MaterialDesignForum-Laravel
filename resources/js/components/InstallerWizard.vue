<template>
  <v-app>
    <!-- 工具栏 color="primary"-->
    <v-app-bar >
      <v-app-bar-title>
        <v-icon icon="mdi-package-variant-closed" class="mr-2" />
        {{ $t('Message.NewInstall.title') }}
      <span class="text-caption mr-4">v{{ version }}</span>
      </v-app-bar-title>

      <v-spacer />

      <!-- 语言切换 -->
      <v-menu offset-y>
        <template v-slot:activator="{ props: menuProps }">
          <v-btn
            variant="text"
            icon="mdi-translate"
            v-bind="menuProps"
          />
        </template>
        <v-list density="compact" min-width="180">
          <v-list-item
            v-for="lang in availableLocales"
            :key="lang.value"
            :title="lang.label"
            :append-icon="$i18n.locale === lang.value ? 'mdi-check' : ''"
            @click="setLocale(lang.value)"
          />
        </v-list>
      </v-menu>

      <!-- 主题切换 -->
      <v-btn
        variant="text"
        :icon="themeDark ? 'mdi-weather-night' : 'mdi-weather-sunny'"
        @click="toggleTheme"
      />

    </v-app-bar>

    <v-main>
      <v-container class="fill-height" style="max-width: 720px">
        <v-row justify="center" align="center">
          <v-col cols="12">
            <v-stepper-vertical
              v-model="currentStep"
              :items="steps"
              color="primary"
            >
              <!-- Step 1: 环境检测 -->
              <template v-slot:[`item.1`]>
                <v-card rounded="lg" variant="flat">
                  <v-card-title class="text-h6">
                    <v-icon icon="mdi-check-circle-outline" color="primary" class="mr-2" />
                    {{ $t('Message.NewInstall.env.title') }}
                  </v-card-title>
                  <v-card-text>
                    <v-list density="compact" bg-color="transparent">
                      <v-list-item
                        v-for="item in envItems"
                        :key="item.label"
                        :prepend-icon="item.ok ? 'mdi-check-circle' : 'mdi-alert-circle'"
                        :class="item.ok ? 'text-success' : 'text-error'"
                      >
                        <v-list-item-title>{{ item.label }}</v-list-item-title>
                        <v-list-item-subtitle>{{ item.value }}</v-list-item-subtitle>
                      </v-list-item>
                    </v-list>

                    <v-expansion-panels v-if="!envAllPassed" class="mt-4">
                      <v-expansion-panel>
                        <v-expansion-panel-title>
                          <v-icon icon="mdi-alert" color="warning" class="mr-2" />
                          {{ $t('Message.NewInstall.env.notPassed') }}
                        </v-expansion-panel-title>
                        <v-expansion-panel-text>
                          <v-list density="compact" bg-color="transparent">
                            <v-list-item
                              v-for="item in failedEnvItems"
                              :key="item.label"
                            >
                              <v-list-item-title class="text-error">{{ item.label }}</v-list-item-title>
                              <v-list-item-subtitle>{{ item.help }}</v-list-item-subtitle>
                            </v-list-item>
                          </v-list>
                        </v-expansion-panel-text>
                      </v-expansion-panel>
                    </v-expansion-panels>
                  </v-card-text>
                </v-card>
              </template>

              <!-- Step 2: 数据库配置 -->
              <template v-slot:[`item.2`]>
                <v-card rounded="lg" variant="flat">
                  <v-card-title class="text-h6">
                    <v-icon icon="mdi-database-cog" color="primary" class="mr-2" />
                    {{ $t('Message.NewInstall.db.title') }}
                  </v-card-title>
                  <v-card-text>
                    <v-row>
                      <v-col cols="8">
                        <v-text-field
                          v-model="db.host"
                          :label="$t('Message.NewInstall.db.host')"
                          variant="outlined"
                          density="comfortable"
                          prepend-inner-icon="mdi-server"
                          hide-details
                        />
                      </v-col>
                      <v-col cols="4">
                        <v-text-field
                          v-model="db.port"
                          :label="$t('Message.NewInstall.db.port')"
                          variant="outlined"
                          density="comfortable"
                          hide-details
                        />
                      </v-col>
                    </v-row>
                    <v-row class="mt-2">
                      <v-col cols="12">
                        <v-text-field
                          v-model="db.database"
                          :label="$t('Message.NewInstall.db.database')"
                          variant="outlined"
                          density="comfortable"
                          prepend-inner-icon="mdi-database"
                          hide-details
                        />
                      </v-col>
                    </v-row>
                    <v-row class="mt-2">
                      <v-col cols="6">
                        <v-text-field
                          v-model="db.username"
                          :label="$t('Message.NewInstall.db.username')"
                          variant="outlined"
                          density="comfortable"
                          prepend-inner-icon="mdi-account"
                          hide-details
                        />
                      </v-col>
                      <v-col cols="6">
                        <v-text-field
                          v-model="db.password"
                          :label="$t('Message.NewInstall.db.password')"
                          variant="outlined"
                          density="comfortable"
                          type="password"
                          prepend-inner-icon="mdi-lock"
                          hide-details
                        />
                      </v-col>
                    </v-row>
                    <div class="mt-4">
                      <v-btn
                        variant="tonal"
                        color="primary"
                        prepend-icon="mdi-connection"
                        :loading="dbTesting"
                        @click="testDbConnection"
                      >
                        {{ $t('Message.NewInstall.db.testConnection') }}
                      </v-btn>
                      <v-chip
                        v-if="dbTestResult !== null"
                        :color="dbTestResult ? 'success' : 'error'"
                        class="ml-3"
                        size="small"
                      >
                        {{ dbTestResult ? $t('Message.NewInstall.db.success') : $t('Message.NewInstall.db.fail') }}
                      </v-chip>
                    </div>
                  </v-card-text>
                </v-card>
              </template>

              <!-- Step 3: 站点设置 -->
              <template v-slot:[`item.3`]>
                <v-card rounded="lg" variant="flat">
                  <v-card-title class="text-h6">
                    <v-icon icon="mdi-cog" color="primary" class="mr-2" />
                    {{ $t('Message.NewInstall.site.title') }}
                  </v-card-title>
                  <v-card-text>
                    <v-row>
                      <v-col cols="12">
                        <v-text-field
                          v-model="site.name"
                          :label="$t('Message.NewInstall.site.name')"
                          variant="outlined"
                          density="comfortable"
                          prepend-inner-icon="mdi-web"
                          hide-details
                        />
                      </v-col>
                    </v-row>
                    <v-row class="mt-2">
                      <v-col cols="12">
                        <v-text-field
                          v-model="site.url"
                          :label="$t('Message.NewInstall.site.url')"
                          variant="outlined"
                          density="comfortable"
                          prepend-inner-icon="mdi-link"
                          hide-details
                        />
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </template>

              <!-- Step 4: 管理员账号 -->
              <template v-slot:[`item.4`]>
                <v-card rounded="lg" variant="flat">
                  <v-card-title class="text-h6">
                    <v-icon icon="mdi-account-star" color="primary" class="mr-2" />
                    {{ $t('Message.NewInstall.site.adminSection') }}
                  </v-card-title>
                  <v-card-text>
                    <v-row>
                      <v-col cols="6">
                        <v-text-field
                          v-model="admin.username"
                          :label="$t('Message.NewInstall.site.adminUsername')"
                          variant="outlined"
                          density="comfortable"
                          prepend-inner-icon="mdi-account-star"
                          hide-details
                        />
                      </v-col>
                      <v-col cols="6">
                        <v-text-field
                          v-model="admin.email"
                          :label="$t('Message.NewInstall.site.email')"
                          variant="outlined"
                          density="comfortable"
                          prepend-inner-icon="mdi-email"
                          hide-details
                        />
                      </v-col>
                    </v-row>
                    <v-row class="mt-2">
                      <v-col cols="6">
                        <v-text-field
                          v-model="admin.password"
                          :label="$t('Message.NewInstall.site.password')"
                          variant="outlined"
                          density="comfortable"
                          type="password"
                          prepend-inner-icon="mdi-lock"
                          :hint="$t('Message.NewInstall.site.passwordHint')"
                          persistent-hint
                          hide-details
                        />
                      </v-col>
                      <v-col cols="6">
                        <v-text-field
                          v-model="admin.password_confirmation"
                          :label="$t('Message.NewInstall.site.confirmPassword')"
                          variant="outlined"
                          density="comfortable"
                          type="password"
                          prepend-inner-icon="mdi-lock-check"
                          :error="passwordMismatch"
                          :error-messages="passwordMismatch ? $t('Message.NewInstall.site.passwordMismatch') : ''"
                          hide-details
                        />
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </template>

              <!-- Step 5: 安装中 -->
              <template v-slot:[`item.5`]>
                <v-card rounded="lg" variant="flat">
                  <v-card-title class="text-h6">
                    <v-icon icon="mdi-progress-download" color="primary" class="mr-2" />
                    {{ $t('Message.NewInstall.install.title') }}
                  </v-card-title>
                  <v-card-text>
                    <v-progress-linear
                      :model-value="installProgress"
                      :indeterminate="installProgress === 0 && installing"
                      color="primary"
                      height="6"
                      class="mb-4"
                    />

                    <v-list v-if="installLogs.length > 0" density="compact" bg-color="transparent" lines="two">
                      <v-list-item
                        v-for="log in installLogs"
                        :key="log.id"
                        :prepend-icon="log.status === 'done' ? 'mdi-check-circle' : log.status === 'error' ? 'mdi-close-circle' : 'mdi-loading mdi-spin'"
                        :class="log.status === 'done' ? 'text-success' : log.status === 'error' ? 'text-error' : 'text-primary'"
                      >
                        <v-list-item-title>{{ log.message }}</v-list-item-title>
                        <v-list-item-subtitle v-if="log.error" class="text-error">{{ log.error }}</v-list-item-subtitle>
                      </v-list-item>
                    </v-list>

                    <p v-if="installLogs.length === 0" class="text-medium-emphasis">{{ $t('Message.NewInstall.install.hint') }}</p>

                    <v-alert
                      v-if="installFailed"
                      type="error"
                      variant="tonal"
                      class="mt-4"
                    >
                      {{ $t('Message.NewInstall.install.failed') }}
                    </v-alert>

                    <v-alert
                      v-if="installComplete"
                      type="success"
                      variant="tonal"
                      class="mt-4"
                    >
                      {{ $t('Message.NewInstall.install.completed') }}
                    </v-alert>

                    <div v-if="installComplete" class="mt-4 text-center">
                      <v-btn color="primary" size="large" @click="goToHome">
                        <v-icon icon="mdi-home" class="mr-2" />
                        {{ $t('Message.NewInstall.install.goHome') }}
                      </v-btn>
                    </div>
                  </v-card-text>
                </v-card>
              </template>

              <template v-slot:actions="{ step, prev, next }">
                <div class="d-flex justify-space-between mt-4">
                  <v-btn
                    variant="outlined" rounded="xl"
                    :disabled="step === 1 || installing"
                    @click="prev"
                  >
                    {{ $t('Message.NewInstall.actions.prev') }}
                  </v-btn>
                  <v-btn
                    color="primary"
                    variant="flat" rounded="xl"
                    :disabled="installing"
                    @click="handleStepAction(step, next)"
                  >
                    {{ step === 5 ? $t('Message.NewInstall.actions.startInstall') : $t('Message.NewInstall.actions.next') }}
                  </v-btn>
                </div>
              </template>
            </v-stepper-vertical>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- 全局提示 -->
    <v-snackbar
      v-model="snackbar.visible"
      :color="snackbar.color"
      location="bottom"
      timeout="3000"
    >
      {{ snackbar.message }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.visible = false">{{ $t('Message.NewInstall.actions.close') }}</v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<script>
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'

export default {
  name: 'InstallerWizard',

  props: {
    version: { type: String, default: '1.0.0' },
    locale: { type: String, default: 'zh_CN' },
    phpVersion: { type: String, default: '' },
    envCheckData: { type: Object, default: () => ({}) },
  },

  setup() {
    const { t } = useI18n()
    const theme = useTheme()

    // 跟随系统主题
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    theme.global.name.value = mediaQuery.matches ? 'dark' : 'light'

    const onSystemThemeChange = (e) => {
      theme.global.name.value = e.matches ? 'dark' : 'light'
    }

    mediaQuery.addEventListener('change', onSystemThemeChange)

    return { t, theme, _mediaQuery: mediaQuery, _onSystemThemeChange: onSystemThemeChange }
  },

  beforeUnmount() {
    this._mediaQuery.removeEventListener('change', this._onSystemThemeChange)
  },

  data() {
    return {
      currentStep: 1,

      // Step 2
      db: { host: '127.0.0.1', port: '3306', database: '', username: 'root', password: '' },
      dbTesting: false,
      dbTestResult: null,

      // Step 3
      site: { name: 'MaterialDesignForum', url: window.location.origin },
      admin: { username: 'admin', email: '', password: '', password_confirmation: '' },

      // Snackbar
      snackbar: { visible: false, message: '', color: 'warning' },

      // 语言列表
      availableLocales: [
        { label: 'Deutsch', value: 'de_DE' },
        { label: 'English (UK)', value: 'en_GB' },
        { label: 'English (US)', value: 'en_US' },
        { label: 'Français', value: 'fr_FR' },
        { label: '日本語', value: 'ja_JP' },
        { label: '한국어', value: 'ko_KR' },
        { label: 'Русский', value: 'ru_RU' },
        { label: '简体中文', value: 'zh_CN' },
        { label: '繁體中文', value: 'zh_TW' },
      ],

      // Step 4
      installing: false,
      installProgress: 0,
      installLogs: [],
      installFailed: false,
      installComplete: false,
    }
  },

  computed: {
    steps() {
      return [
        { title: this.$t('Message.NewInstall.steps.envCheck'), value: 1, completeIcon: 'mdi-check-circle' },
        { title: this.$t('Message.NewInstall.steps.dbConfig'), value: 2, completeIcon: 'mdi-check-circle' },
        { title: this.$t('Message.NewInstall.steps.siteSettings'), value: 3, completeIcon: 'mdi-check-circle' },
        { title: this.$t('Message.NewInstall.steps.adminAccount'), value: 4, completeIcon: 'mdi-check-circle' },
        { title: this.$t('Message.NewInstall.steps.startInstall'), value: 5, completeIcon: 'mdi-check-circle' },
      ]
    },

    envItems() {
      const t = this.$t
      const items = []

      const phpOk = this.envCheckData.php_ok ?? true
      items.push({
        label: t('Message.NewInstall.env.phpVersion'),
        value: `${this.envCheckData.php_version || this.phpVersion} (≥ 8.1)`,
        ok: phpOk,
        help: t('Message.NewInstall.env.phpVersionHelp'),
      })

      const extKeys = ['pdo', 'mbstring', 'openssl', 'json', 'fileinfo', 'tokenizer', 'ctype', 'xml', 'gd']
      extKeys.forEach(key => {
        items.push({
          label: t('Message.NewInstall.env.extPrefix', { name: t(`Message.NewInstall.env.extLabel.${key}`) }),
          value: this.envCheckData[key] ? t('Message.NewInstall.env.enabled') : t('Message.NewInstall.env.disabled'),
          ok: this.envCheckData[key],
          help: t(`Message.NewInstall.env.extHelp.${key}`),
        })
      })

      const dirKeys = ['storage_writable', 'bootstrap_writable', 'env_writable']
      dirKeys.forEach(key => {
        items.push({
          label: t('Message.NewInstall.env.dirPrefix', { name: t(`Message.NewInstall.env.dirLabel.${key}`) }),
          value: this.envCheckData[key] ? t('Message.NewInstall.env.writable') : t('Message.NewInstall.env.unwritable'),
          ok: this.envCheckData[key],
          help: t(`Message.NewInstall.env.dirHelp.${key}`),
        })
      })

      return items
    },

    envAllPassed() {
      return this.envItems.every(i => i.ok)
    },

    failedEnvItems() {
      return this.envItems.filter(i => !i.ok)
    },

    passwordMismatch() {
      return this.admin.password_confirmation && this.admin.password !== this.admin.password_confirmation
    },

    themeDark() {
      return this.theme.global.name.value === 'dark'
    },
  },

  methods: {
    handleStepAction(step, next) {
      if (this.installing) return

      if (step === 1 && !this.envAllPassed) {
        this.showSnackbar(this.$t('Message.NewInstall.snackbar.fixEnvFirst'))
        return
      }

      if (step === 2 && !this.dbTestResult) {
        this.showSnackbar(this.$t('Message.NewInstall.snackbar.testDbFirst'))
        return
      }

      if (step === 4 && this.passwordMismatch) {
        this.showSnackbar(this.$t('Message.NewInstall.snackbar.passwordMismatch'))
        return
      }

      if (step === 5) {
        this.runInstall()
        return
      }

      next()
    },

    async testDbConnection() {
      this.dbTesting = true
      this.dbTestResult = null
      try {
        const { data } = await axios.post('/install/test-db', this.db)
        this.dbTestResult = data.ok
      } catch {
        this.dbTestResult = false
      } finally {
        this.dbTesting = false
      }
    },

    addLog(message, status = 'pending', error = '') {
      this.installLogs.push({ id: this.installLogs.length + 1, message, status, error })
    },

    updateLog(id, status, error = '') {
      const log = this.installLogs.find(l => l.id === id)
      if (log) { log.status = status; log.error = error }
    },

    async runInstall() {
      if (this.installing) return

      if (!this.dbTestResult) {
        this.showSnackbar(this.$t('Message.NewInstall.snackbar.backToDbConfig'))
        this.currentStep = 2
        return
      }

      this.installing = true
      this.installFailed = false
      this.installComplete = false
      this.installProgress = 0
      this.installLogs = []

      const payload = { db: this.db, site: this.site, admin: this.admin }

      try {
        let logId

        logId = this.installLogs.length + 1
        this.addLog(this.$t('Message.NewInstall.install.logs.savingDb'), 'pending')
        this.installProgress = 10
        await this.postStep('save-db', payload)
        this.updateLog(logId, 'done')
        this.installProgress = 25

        logId = this.installLogs.length + 1
        this.addLog(this.$t('Message.NewInstall.install.logs.migrating'), 'pending')
        this.installProgress = 30
        await this.postStep('migrate', payload)
        this.updateLog(logId, 'done')
        this.installProgress = 55

        logId = this.installLogs.length + 1
        this.addLog(this.$t('Message.NewInstall.install.logs.creatingAdmin'), 'pending')
        this.installProgress = 60
        await this.postStep('create-admin', payload)
        this.updateLog(logId, 'done')
        this.installProgress = 75

        logId = this.installLogs.length + 1
        this.addLog(this.$t('Message.NewInstall.install.logs.savingSite'), 'pending')
        this.installProgress = 80
        await this.postStep('save-site', payload)
        this.updateLog(logId, 'done')
        this.installProgress = 95

        this.addLog(this.$t('Message.NewInstall.install.logs.completed'), 'done')
        this.installProgress = 100
        this.installComplete = true
      } catch (e) {
        this.installFailed = true
        const errMsg = e.response?.data?.message || e.message || this.$t('Message.NewInstall.install.logs.unknownError')
        const lastLog = this.installLogs[this.installLogs.length - 1]
        if (lastLog && lastLog.status === 'pending') {
          this.updateLog(lastLog.id, 'error', errMsg)
        } else {
          this.addLog(this.$t('Message.NewInstall.install.logs.failed'), 'error', errMsg)
        }
      } finally {
        this.installing = false
      }
    },

    async postStep(step, payload) {
      const { data } = await axios.post(`/install/${step}`, payload)
      if (!data.ok) throw new Error(data.message || this.$t('Message.NewInstall.install.logs.executeFailed'))
      return data
    },

    showSnackbar(message, color = 'warning') {
      this.snackbar.message = message
      this.snackbar.color = color
      this.snackbar.visible = true
    },

    setLocale(locale) {
      this.$i18n.locale = locale
      localStorage.setItem('installer_locale', locale)
    },

    toggleTheme() {
      this.theme.global.name.value = this.theme.global.name.value === 'dark' ? 'light' : 'dark'
    },

    goToHome() {
      window.location.href = '/'
    },
  },
}
</script>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useFeedback } from '../composables/useFeedback'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import http from '../api'

const router = useRouter()
const { show: feedback } = useFeedback()
const { t } = useI18n()

// 安装步骤：1=环境检查 / 2=数据库 / 3=应用配置 / 4=管理员账号 / 5=完成
const step = ref(1)
const loading = ref(false)
const running = ref(false)
const installResult = ref(null)

// 环境检查结果
const requirements = ref({ all_pass: false, checks: {} })

// 各步表单
const dbForm = reactive({
  host: '127.0.0.1',
  port: 3306,
  database: '',
  username: 'root',
  password: '',
})

const appForm = reactive({
  app_name: 'MDForum',
  // 本站域名地址：默认读取当前访问地址（含端口），如 http://localhost:85
  app_url: window.location.origin,
  timezone: 'Asia/Shanghai',
  locale: 'zh_CN',
  // 邮件配置（可选，留空则用 log mailer）
  mail_mailer: 'log',
  mail_host: '',
  mail_port: 587,
  mail_username: '',
  mail_password: '',
  mail_from_address: '',
  mail_from_name: '',
  mail_scheme: 'smtps',
})

const adminForm = reactive({
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
})

// 常用时区列表（精简版）
const timezoneOptions = [
  'Asia/Shanghai',
  'Asia/Tokyo',
  'Asia/Singapore',
  'Asia/Hong_Kong',
  'Asia/Taipei',
  'Europe/London',
  'Europe/Berlin',
  'America/New_York',
  'America/Los_Angeles',
  'UTC',
]

// 邮件驱动选项（i18n 标题）
const mailMailerOptions = computed(() => [
  { value: 'log', title: t('install.app.mailers.log') },
  { value: 'smtp', title: t('install.app.mailers.smtp') },
  { value: 'sendmail', title: t('install.app.mailers.sendmail') },
])

// 加密方案选项
const mailSchemeOptions = computed(() => [
  { value: '', title: t('install.app.encryption.none') },
  { value: 'smtps', title: t('install.app.encryption.ssl') },
  { value: 'smtp', title: t('install.app.encryption.tls') },
])

// 步骤进度数据
const steps = computed(() => [
  { n: 1, label: t('install.steps.requirements') },
  { n: 2, label: t('install.steps.database') },
  { n: 3, label: t('install.steps.app') },
  { n: 4, label: t('install.steps.admin') },
  { n: 5, label: t('install.steps.done') },
])

onMounted(async () => {
  await loadRequirements()
})

async function loadRequirements() {
  loading.value = true
  try {
    const { data } = await http.get('/install/requirements')
    requirements.value = data
  } catch (error) {
    // 403 = 系统已安装，重定向到首页（避免已部署后还能看到空白安装页）
    if (error?.response?.status === 403) {
      router.replace('/')
      return
    }
    feedback(error.message || t('install.requirements.loadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function saveDatabase() {
  loading.value = true
  try {
    const { data } = await http.post('/install/database', dbForm)
    feedback(data.message || t('install.database.saved'), 'success')
    step.value = 3
  } catch (error) {
    feedback(error.message || t('install.database.saveFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function saveApp() {
  loading.value = true
  try {
    const { data } = await http.post('/install/app', appForm)
    feedback(data.message || t('install.app.saved'), 'success')
    step.value = 4
  } catch (error) {
    feedback(error.message || t('install.app.saveFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function runInstall() {
  if (adminForm.password !== adminForm.password_confirmation) {
    feedback(t('install.admin.passwordMismatch'), 'error')
    return
  }
  running.value = true
  try {
    const { data } = await http.post('/install/run', {
      username: adminForm.username,
      email: adminForm.email,
      password: adminForm.password,
    })
    installResult.value = data
    step.value = 5
    feedback(data.message || t('install.errors.installSuccess'), 'success')
  } catch (error) {
    feedback(error.message || t('install.errors.installFailed'), 'error')
  } finally {
    running.value = false
  }
}

function goLogin() {
  router.replace('/login')
}

// 是否允许进入下一步：环境检查必须全部通过
const canProceedFromRequirements = computed(() => requirements.value.all_pass)
</script>

<template>
  <v-container class="d-flex align-center justify-center" style="min-height: 80vh">
    <v-card rounded="lg" width="720" max-width="100%" elevation="8">

      <v-toolbar>
        <template v-slot:prepend>
          <v-btn icon="mdi-tune-vertical">
          </v-btn>

        </template>
        <v-toolbar-title>
          {{ t('install.title') }}
        </v-toolbar-title>
        <template v-slot:append>

          <LanguageSwitcher />
        </template>
      </v-toolbar>

      <!-- <v-card-title class="text-h5 font-weight-bold pa-6">
        <v-icon class="mr-2">mdi-tune-vertical</v-icon>
        {{ t('install.title') }}

      </v-card-title> -->

      <v-divider />

      <v-card-text class="pa-6">
        <!-- 步骤进度条 -->
        <div class="d-flex align-center justify-space-between mb-6 px-2">
          <div v-for="(s, i) in steps" :key="s.n" class="d-flex flex-column align-center"
            style="flex: 1; position: relative;">
            <v-avatar :color="step > s.n ? 'success' : step === s.n ? 'primary' : 'grey-lighten-2'" size="32">
              <v-icon v-if="step > s.n" size="20">mdi-check</v-icon>
              <span v-else>{{ s.n }}</span>
            </v-avatar>
            <div class="text-caption mt-2 text-center"
              :class="step === s.n ? 'text-primary font-weight-bold' : 'text-medium-emphasis'">
              {{ s.label }}
            </div>
            <v-divider v-if="i < steps.length - 1" class="position-absolute"
              style="top: 16px; left: 60%; right: -40%;" />
          </div>
        </div>

        <!-- Step 1: 环境检查 -->
        <div v-if="step === 1">
          <v-alert v-if="!requirements.all_pass" type="warning" variant="tonal" class="mb-4">
            {{ t('install.requirements.warning') }}
          </v-alert>
          <v-alert v-else type="success" variant="tonal" class="mb-4">
            {{ t('install.requirements.success') }}
          </v-alert>

          <v-list density="compact">
            <v-list-item v-for="(check, key) in requirements.checks" :key="key">
              <template #prepend>
                <v-icon :color="check.pass ? 'success' : 'error'">
                  {{ check.pass ? 'mdi-check-circle' : 'mdi-alert-circle' }}
                </v-icon>
              </template>
              <v-list-item-title>{{ t(`install.requirements.labels.${key}`) }}</v-list-item-title>
              <v-list-item-subtitle>
                <!-- detail 有值时优先显示具体值（如 PHP 8.3.0），否则显示标准化状态文案 -->
                {{ check.detail || t(`install.requirements.status.${check.status}`) }}
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>

          <div v-if="loading" class="text-center py-4">
            <v-progress-circular indeterminate color="primary" />
          </div>
        </div>

        <!-- Step 2: 数据库 -->
        <div v-else-if="step === 2">
          <p class="text-body-2 mb-4 text-medium-emphasis">
            {{ t('install.database.hint') }}
          </p>
          <v-form @submit.prevent="saveDatabase">
            <v-row>
              <v-col cols="12" sm="8">
                <v-text-field v-model="dbForm.host" :label="t('install.database.host')"
                  prepend-inner-icon="mdi-server" />
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field v-model.number="dbForm.port" type="number" :label="t('install.database.port')"
                  prepend-inner-icon="mdi-door" />
              </v-col>
            </v-row>
            <v-text-field v-model="dbForm.database" :label="t('install.database.name')"
              prepend-inner-icon="mdi-database" class="mb-3" />
            <v-text-field v-model="dbForm.username" :label="t('install.database.username')"
              prepend-inner-icon="mdi-account" class="mb-3" />
            <v-text-field v-model="dbForm.password" type="password" :label="t('install.database.password')"
              prepend-inner-icon="mdi-lock" />
          </v-form>
        </div>

        <!-- Step 3: 应用配置 -->
        <div v-else-if="step === 3">
          <p class="text-body-2 mb-4 text-medium-emphasis">
            {{ t('install.app.hint') }}
          </p>

          <v-text-field v-model="appForm.app_name" :label="t('install.app.appName')" prepend-inner-icon="mdi-tag"
            class="mb-3" />

          <v-text-field v-model="appForm.app_url" :label="t('install.app.appUrl')" prepend-inner-icon="mdi-web"
            :hint="t('install.app.appUrlHint')" persistent-hint class="mb-3" />

          <v-select v-model="appForm.timezone" :items="timezoneOptions" :label="t('install.app.timezone')"
            prepend-inner-icon="mdi-clock-outline" class="mb-3" />

          <v-divider class="my-4">
            <template #default><span class="text-caption">{{ t('install.app.mailDivider') }}</span></template>
          </v-divider>

          <v-select v-model="appForm.mail_mailer" :items="mailMailerOptions" item-value="value" item-title="title"
            :label="t('install.app.mailMailer')" prepend-inner-icon="mdi-email" class="mb-3" />

          <template v-if="appForm.mail_mailer === 'smtp'">
            <v-row>
              <v-col cols="12" sm="8">
                <v-text-field v-model="appForm.mail_host" :label="t('install.app.mailHost')" />
              </v-col>
              <v-col cols="12" sm="4">
                <v-text-field v-model.number="appForm.mail_port" type="number" :label="t('install.app.mailPort')" />
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field v-model="appForm.mail_username" :label="t('install.app.mailUsername')" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="appForm.mail_password" type="password" :label="t('install.app.mailPassword')" />
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field v-model="appForm.mail_from_address" :label="t('install.app.mailFromAddress')" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="appForm.mail_from_name" :label="t('install.app.mailFromName')" />
              </v-col>
            </v-row>
            <v-select v-model="appForm.mail_scheme" :items="mailSchemeOptions" item-value="value" item-title="title"
              :label="t('install.app.mailEncryption')" />
          </template>
        </div>

        <!-- Step 4: 管理员账号 -->
        <div v-else-if="step === 4">
          <v-alert type="info" variant="tonal" class="mb-4">
            {{ t('install.admin.hint') }}
          </v-alert>

          <v-text-field v-model="adminForm.username" :label="t('install.admin.username')"
            prepend-inner-icon="mdi-account-circle" :hint="t('install.admin.usernameHint')" persistent-hint
            class="mb-3" />

          <v-text-field v-model="adminForm.email" :label="t('install.admin.email')" prepend-inner-icon="mdi-email"
            class="mb-3" />

          <v-text-field v-model="adminForm.password" type="password" :label="t('install.admin.password')"
            prepend-inner-icon="mdi-lock" :hint="t('install.admin.passwordHint')" persistent-hint class="mb-3" />

          <v-text-field v-model="adminForm.password_confirmation" type="password"
            :label="t('install.admin.passwordConfirm')" prepend-inner-icon="mdi-lock-check" />
        </div>

        <!-- Step 5: 完成 -->
        <div v-else-if="step === 5" class="text-center py-6">
          <v-icon color="success" size="64" class="mb-4">mdi-check-circle</v-icon>
          <div class="text-h6 mb-2">{{ t('install.done.success') }}</div>
          <p class="text-body-2 text-medium-emphasis mb-4">
            {{ t('install.done.adminCreated') }}
          </p>
          <v-card rounded="lg" variant="outlined" max-width="320" class="mx-auto mb-6">
            <v-list density="compact">
              <v-list-item>
                <template #prepend><v-icon>mdi-account</v-icon></template>
                <v-list-item-title>{{ installResult?.admin?.username || adminForm.username }}</v-list-item-title>
                <v-list-item-subtitle>{{ t('install.done.username') }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template #prepend><v-icon>mdi-email</v-icon></template>
                <v-list-item-title>{{ installResult?.admin?.email || adminForm.email }}</v-list-item-title>
                <v-list-item-subtitle>{{ t('install.done.email') }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card>
          <v-btn color="primary" size="large" @click="goLogin">
            <v-icon class="mr-2">mdi-login</v-icon>
            {{ t('install.done.goLogin') }}
          </v-btn>
        </div>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn v-if="step > 1 && step < 5" variant="text" @click="step--">
          <v-icon class="mr-1">mdi-arrow-left</v-icon>
          {{ t('install.actions.prev') }}
        </v-btn>
        <v-spacer />
        <v-btn v-if="step === 1" color="primary" :loading="loading" :disabled="!canProceedFromRequirements"
          @click="step = 2">
          {{ t('install.actions.next') }}
          <v-icon class="ml-1">mdi-arrow-right</v-icon>
        </v-btn>
        <v-btn v-else-if="step === 2" color="primary" :loading="loading" @click="saveDatabase">
          {{ t('install.database.testAndSave') }}
        </v-btn>
        <v-btn v-else-if="step === 3" color="primary" :loading="loading" @click="saveApp">
          {{ t('install.app.save') }}
        </v-btn>
        <v-btn v-else-if="step === 4" color="success" size="large" :loading="running" @click="runInstall">
          <v-icon class="mr-1">mdi-rocket-launch</v-icon>
          {{ t('install.admin.runInstall') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

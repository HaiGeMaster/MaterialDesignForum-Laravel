<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { siteIcpBeian, siteGonganBeian } from '../themeConfig'

const props = defineProps({
  modelValue: { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])

const authStore = useAuthStore()
const { t } = useI18n()

const profilePath = computed(() =>
  authStore.isLoggedIn ? `/users/${authStore.user?.user_id}` : '/login',
)

// 底部版权信息（参考旧项目 navigation-drawer）
const copyrightInfo = {
  startYear: 2026,
  currentYear: new Date().getFullYear(),
  poweredBy: [
    { name: 'Vuetify', url: 'https://vuetifyjs.com/' },
    { name: 'Material Design Forum', url: 'https://www.xbedrock.com/info' },
  ],
}

// 构建时间：由 vite.config.js 的 define 注入；格式化为本地时间字符串
const buildTime = (() => {
  try {
    const d = new Date(__BUILD_TIME__)
    if (Number.isNaN(d.getTime())) return ''
    return d.toLocaleString('zh-CN', { hour12: false })
  } catch {
    return ''
  }
})()

// 从公安备案号中提取数字，用于公安备案查询链接
const gonganBeianUrl = computed(() => {
  const recordCode = siteGonganBeian.value.match(/\d+/)?.[0] || ''
  return `http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=${recordCode}`
})

// 备案图标（public 静态资源，绑定形式避免被 Vite 当作模块导入）
const icpBeianImg = '/assets/fixed_content/icpbeian.png'
const gonganBeianImg = '/assets/fixed_content/gwbeian.png'

// 内容导航：始终显示
const contentNavItems = computed(() => [
  { title: t('nav.home'), icon: 'mdi-home', to: '/' },
  { title: t('nav.topics'), icon: 'mdi-book-variant', to: '/topics' },
  { title: t('nav.questions'), icon: 'mdi-forum', to: '/questions' },
  { title: t('nav.articles'), icon: 'mdi-file-document', to: '/articles' },
])

// 账号导航：人脉始终显示，个人资料、通知仅在登录后显示
const accountNavItems = computed(() => {
  const items = []
  if (authStore.isLoggedIn) {
    items.push({ title: t('nav.profile'), icon: 'mdi-account-circle', to: profilePath.value })
  }
  items.push({ title: t('nav.people'), icon: 'mdi-account-multiple', to: '/users' })
  if (authStore.isLoggedIn) {
    items.push({ title: t('nav.notifications'), icon: 'mdi-bell', to: '/notifications' })
  }
  return items
})

</script>

<template>
  <v-navigation-drawer
    :model-value="modelValue"
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <v-list rounded="lg" nav  color="primary">
      <v-list-item
        v-for="item in contentNavItems"
        :key="item.to"
        :to="item.to"
        :prepend-icon="item.icon"
        :title="item.title"
        exact
      />

      <v-divider class="my-2" />

      <v-list-item
        v-for="item in accountNavItems"
        :key="item.to"
        :to="item.to"
        :prepend-icon="item.icon"
        :title="item.title"
        exact
      />

    </v-list>

    <template v-slot:append>
      <div class="copyright">

        <!-- ICP 备案 -->
        <p v-if="siteIcpBeian">
          <a href="http://beian.miit.gov.cn" target="_blank">
            <img :src="icpBeianImg" alt="ICP备案">
            {{ siteIcpBeian }}
          </a>
        </p>

        <!-- 公安备案 -->
        <p v-if="siteGonganBeian">
          <a :href="gonganBeianUrl" target="_blank">
            <img :src="gonganBeianImg" alt="公安备案">
            {{ siteGonganBeian }}
          </a>
        </p>


        <!-- 版权信息 -->
        <p>
          <a href="https://github.com/HaiGeMaster" target="_blank">
            Copyright © {{ copyrightInfo.startYear }} - {{ copyrightInfo.currentYear }}
          </a>
        </p>

        <!-- 技术支持 -->
        <p>
          Powered by
          <template v-for="(tech, index) in copyrightInfo.poweredBy" :key="tech.name">
            <a :href="tech.url" target="_blank">
              {{ tech.name }}{{ index < copyrightInfo.poweredBy.length - 1 ? ' & ' : '' }}
            </a>
            <br>
          </template>
        </p>

        <!-- 构建时间 -->
        <p v-if="buildTime">BUILD TIME: {{ buildTime }}</p>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<style scoped>
/* 底部版权信息样式（参考旧项目 navigation-drawer） */
.copyright {
  box-sizing: border-box;
  width: 100%;
  padding: 20px 16px;
}

.copyright p {
  margin: 0;
  overflow: hidden;
  font-size: 13px;
  line-height: 20px;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.copyright a {
  padding: 2px;
  font-size: 13px;
  color: rgb(var(--v-theme-surface-variant));
  text-decoration: none;
}

.copyright a:hover {
  text-decoration: underline;
}

.copyright a img {
  width: 16px;
  height: 16px;
  margin-right: 4px;
  float: left;
}
</style>

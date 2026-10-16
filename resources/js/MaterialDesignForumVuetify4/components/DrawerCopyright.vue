<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { siteIcpBeian, siteGonganBeian } from '../themeConfig'
import { useCookiePreferenceStore } from '../stores/cookiePreference'

const { t } = useI18n()
const cookiePrefStore = useCookiePreferenceStore()

const props = defineProps({
  // 版权起始年份（前台/后台可能不同）
  startYear: { type: Number, default: 2023 },
})

const copyrightInfo = computed(() => ({
  startYear: props.startYear,
  currentYear: new Date().getFullYear(),
  poweredBy: [
    { name: 'Vuetify', url: 'https://vuetifyjs.com/' },
    { name: 'Material Design Forum', url: 'https://www.xbedrock.com/info' },
  ],
}))

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
</script>

<template>
  <div class="copyright">

    <!-- Cookie 偏好设置：通过 Pinia store 打开偏好中心 -->
    <p>
      <a href="#" @click.prevent="cookiePrefStore.openPreferenceCenter()">
        {{ t('cookiePref.drawerLink') }}
      </a>
    </p>

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
    <!-- <p v-if="buildTime">BUILD TIME: {{ buildTime }}</p> -->
  </div>
</template>

<style scoped>
/* 底部版权信息样式 */
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

  
  opacity: 0.6;
}

.copyright a {
  padding: 2px;
  font-size: 13px;

  /* color: rgb(var(--v-theme-surface-variant)); */
  
  opacity: 0.6;
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

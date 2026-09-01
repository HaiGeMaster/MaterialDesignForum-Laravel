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

const adminNavGroups = computed(() => [
  [
    { id: 'admin-dashboard', title: t('admin.dashboard'), icon: 'mdi-view-dashboard', to: '/' },
  ],
  [
    { id: 'admin-topics', title: t('admin.manageTopics'), icon: 'mdi-book-variant', to: '/topics', permission: 'ability_admin_manage_topic' },
    { id: 'admin-questions', title: t('admin.manageQuestions'), icon: 'mdi-forum', to: '/questions', permission: 'ability_admin_manage_question' },
    { id: 'admin-answers', title: t('admin.manageAnswers'), icon: 'mdi-message-reply', to: '/answers', permission: 'ability_admin_manage_answer' },
    { id: 'admin-articles', title: t('admin.manageArticles'), icon: 'mdi-file-document', to: '/articles', permission: 'ability_admin_manage_article' },
    { id: 'admin-comments', title: t('admin.manageComments'), icon: 'mdi-message-reply-text', to: '/comments', permission: 'ability_admin_manage_comment' },
    { id: 'admin-replies', title: t('admin.manageReplies'), icon: 'mdi-reply-all', to: '/replies', permission: 'ability_admin_manage_reply' },
  ],
  [
    { id: 'admin-user-groups', title: t('admin.manageUserGroups'), icon: 'mdi-account-group', to: '/user-groups', permission: 'ability_admin_manage_user_group' },
    { id: 'admin-users', title: t('admin.manageUsers'), icon: 'mdi-account-multiple', to: '/users', permission: 'ability_admin_manage_user' },
    { id: 'admin-reports', title: t('admin.manageReports'), icon: 'mdi-flag', to: '/reports', permission: 'ability_admin_manage_report' },
  ],
  [
    { id: 'admin-options', title: t('admin.manageSettings'), icon: 'mdi-cog', to: '/options', permission: 'ability_admin_manage_option' },
  ],
])

const visibleAdminNavGroups = computed(() => adminNavGroups.value
  .map((group) => group.filter((item) => (
    !item.permission
    || Boolean(authStore.user?.user_group?.[item.permission])
    || authStore.user?.user_group?.is_admin
  )))
  .filter((group) => group.length))

// 底部版权信息（与前台抽屉一致）
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
</script>

<template>
  <v-navigation-drawer
    :model-value="props.modelValue"
    width="260"
    @update:model-value="(value) => emit('update:modelValue', value)"
  >
    <v-list nav density="comfortable" color="primary">
      <template v-for="(group, groupIndex) in visibleAdminNavGroups" :key="group[0].id">
        <v-divider v-if="groupIndex" class="my-2" />
        <v-list-item
          v-for="item in group"
          :key="item.id"
          :prepend-icon="item.icon"
          :title="item.title"
          :to="item.to"

          exact
        />
      </template>
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

        <!-- 构建时间 -->
        <p v-if="buildTime">{{ buildTime }}</p>

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
      </div>
    </template>
  </v-navigation-drawer>
</template>

<style scoped>
/* 底部版权信息样式（与前台抽屉一致） */
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

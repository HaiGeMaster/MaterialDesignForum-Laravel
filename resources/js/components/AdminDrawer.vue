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
      <div v-if="siteIcpBeian || siteGonganBeian" class="pa-3 text-caption text-medium-emphasis">
        <div v-if="siteIcpBeian" class="mb-1">
          {{ $t('footer.icpBeian') }}: {{ siteIcpBeian }}
        </div>
        <div v-if="siteGonganBeian">
          {{ $t('footer.gonganBeian') }}: {{ siteGonganBeian }}
        </div>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '../api'
import ColorTextField from './ColorTextField.vue'
import { useFeedback } from '../composables/useFeedback'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()


const props = defineProps({
  /** 对话框显隐 */
  modelValue: { type: Boolean, default: false },
  /** 编辑模式传入用户组对象；新建模式传 null */
  userGroup: { type: Object, default: null },
  /** API 基础路径，默认 '/admin/modules/user-groups' */
  apiBase: { type: String, default: '/admin/modules/user-groups' },
})

const emit = defineEmits(['update:modelValue', 'submit'])

const { show } = useFeedback()
const { t } = useI18n()

const formRef = ref(null)
const submitting = ref(false)

const isEdit = computed(() => Boolean(props.userGroup))
const dialogOpen = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// ================== 表单默认值 ==================
const DEFAULTS = {
  user_group_name: '',
  user_group_description: '',
  user_group_icon: 'mdi-account',
  user_group_color: '',
  user_group_icon_show: false,
  is_admin: false,
  ability_normal_login: true,
  ability_admin_login: false,
  ability_admin_manage_user_group: false,
  ability_admin_manage_user: false,
  ability_admin_manage_topic: false,
  ability_admin_manage_question: false,
  ability_admin_manage_article: false,
  ability_admin_manage_comment: false,
  ability_admin_manage_answer: false,
  ability_admin_manage_reply: false,
  ability_admin_manage_report: false,
  ability_admin_manage_option: false,
  ability_create_article: true,
  ability_create_question: true,
  ability_create_answer: true,
  ability_create_comment: true,
  ability_create_reply: true,
  ability_create_topic: false,
  ability_edit_own_article: true,
  ability_edit_own_question: true,
  ability_edit_own_answer: true,
  ability_edit_own_comment: true,
  ability_edit_own_reply: true,
  ability_edit_own_topic: true,
  ability_delete_own_article: true,
  ability_delete_own_question: true,
  ability_delete_own_answer: true,
  ability_delete_own_comment: true,
  ability_delete_own_reply: true,
  ability_delete_own_topic: true,
  time_before_edit_article: 5,
  time_before_edit_question: 5,
  time_before_edit_answer: 5,
  time_before_edit_comment: 5,
  time_before_edit_reply: 5,
  time_before_edit_topic: 5,
  time_before_delete_article: 5,
  time_before_delete_question: 5,
  time_before_delete_answer: 5,
  time_before_delete_comment: 5,
  time_before_delete_reply: 5,
  time_before_delete_topic: 5,
  ability_edit_article_only_no_comment: true,
  ability_edit_question_only_no_answer: true,
  ability_edit_answer_only_no_comment: true,
  ability_edit_question_only_no_comment: true,
  ability_edit_comment_only_no_reply: true,
  ability_edit_reply_only_no_reply: true,
  ability_edit_topic_only_no_article_or_question: true,
  ability_delete_article_only_no_comment: true,
  ability_delete_question_only_no_answer: true,
  ability_delete_answer_only_no_comment: true,
  ability_delete_question_only_no_comment: true,
  ability_delete_comment_only_no_reply: true,
  ability_delete_reply_only_no_reply: true,
  ability_delete_topic_only_no_article_or_question: true,
  ability_edit_own_info: true,
  ability_vote: true,
}

// ================== 权限分组配置 ==================
const PERMISSION_GROUPS = [
  {
    titleKey: 'userGroupDialog.groups.loginAdmin',
    icon: 'mdi-shield-account',
    fields: [
      { key: 'ability_normal_login', labelKey: 'userGroupDialog.fields.normalLogin' },
      { key: 'ability_admin_login', labelKey: 'userGroupDialog.fields.adminLogin' },
      { key: 'ability_admin_manage_user_group', labelKey: 'userGroupDialog.fields.manageUserGroup' },
      { key: 'ability_admin_manage_user', labelKey: 'userGroupDialog.fields.manageUser' },
      { key: 'ability_admin_manage_topic', labelKey: 'userGroupDialog.fields.manageTopic' },
      { key: 'ability_admin_manage_question', labelKey: 'userGroupDialog.fields.manageQuestion' },
      { key: 'ability_admin_manage_article', labelKey: 'userGroupDialog.fields.manageArticle' },
      { key: 'ability_admin_manage_comment', labelKey: 'userGroupDialog.fields.manageComment' },
      { key: 'ability_admin_manage_answer', labelKey: 'userGroupDialog.fields.manageAnswer' },
      { key: 'ability_admin_manage_reply', labelKey: 'userGroupDialog.fields.manageReply' },
      { key: 'ability_admin_manage_report', labelKey: 'userGroupDialog.fields.manageReport' },
      { key: 'ability_admin_manage_option', labelKey: 'userGroupDialog.fields.manageOption' },
    ],
  },
  {
    titleKey: 'userGroupDialog.groups.contentCreate',
    icon: 'mdi-pen-plus',
    fields: [
      { key: 'ability_create_article', labelKey: 'userGroupDialog.fields.createArticle' },
      { key: 'ability_create_question', labelKey: 'userGroupDialog.fields.createQuestion' },
      { key: 'ability_create_answer', labelKey: 'userGroupDialog.fields.createAnswer' },
      { key: 'ability_create_comment', labelKey: 'userGroupDialog.fields.createComment' },
      { key: 'ability_create_reply', labelKey: 'userGroupDialog.fields.createReply' },
      { key: 'ability_create_topic', labelKey: 'userGroupDialog.fields.createTopic' },
    ],
  },
  {
    titleKey: 'userGroupDialog.groups.editOwn',
    icon: 'mdi-pencil',
    fields: [
      { key: 'ability_edit_own_article', labelKey: 'userGroupDialog.fields.editOwnArticle' },
      { key: 'ability_edit_own_question', labelKey: 'userGroupDialog.fields.editOwnQuestion' },
      { key: 'ability_edit_own_answer', labelKey: 'userGroupDialog.fields.editOwnAnswer' },
      { key: 'ability_edit_own_comment', labelKey: 'userGroupDialog.fields.editOwnComment' },
      { key: 'ability_edit_own_reply', labelKey: 'userGroupDialog.fields.editOwnReply' },
      { key: 'ability_edit_own_topic', labelKey: 'userGroupDialog.fields.editOwnTopic' },
    ],
  },
  {
    titleKey: 'userGroupDialog.groups.deleteOwn',
    icon: 'mdi-delete',
    fields: [
      { key: 'ability_delete_own_article', labelKey: 'userGroupDialog.fields.deleteOwnArticle' },
      { key: 'ability_delete_own_question', labelKey: 'userGroupDialog.fields.deleteOwnQuestion' },
      { key: 'ability_delete_own_answer', labelKey: 'userGroupDialog.fields.deleteOwnAnswer' },
      { key: 'ability_delete_own_comment', labelKey: 'userGroupDialog.fields.deleteOwnComment' },
      { key: 'ability_delete_own_reply', labelKey: 'userGroupDialog.fields.deleteOwnReply' },
      { key: 'ability_delete_own_topic', labelKey: 'userGroupDialog.fields.deleteOwnTopic' },
    ],
  },
  {
    titleKey: 'userGroupDialog.groups.editTime',
    icon: 'mdi-timer-edit',
    fields: [
      { key: 'time_before_edit_article', labelKey: 'userGroupDialog.fields.editArticle', type: 'number' },
      { key: 'time_before_edit_question', labelKey: 'userGroupDialog.fields.editQuestion', type: 'number' },
      { key: 'time_before_edit_answer', labelKey: 'userGroupDialog.fields.editAnswer', type: 'number' },
      { key: 'time_before_edit_comment', labelKey: 'userGroupDialog.fields.editComment', type: 'number' },
      { key: 'time_before_edit_reply', labelKey: 'userGroupDialog.fields.editReply', type: 'number' },
      { key: 'time_before_edit_topic', labelKey: 'userGroupDialog.fields.editTopic', type: 'number' },
    ],
  },
  {
    titleKey: 'userGroupDialog.groups.deleteTime',
    icon: 'mdi-timer-remove',
    fields: [
      { key: 'time_before_delete_article', labelKey: 'userGroupDialog.fields.deleteArticle', type: 'number' },
      { key: 'time_before_delete_question', labelKey: 'userGroupDialog.fields.deleteQuestion', type: 'number' },
      { key: 'time_before_delete_answer', labelKey: 'userGroupDialog.fields.deleteAnswer', type: 'number' },
      { key: 'time_before_delete_comment', labelKey: 'userGroupDialog.fields.deleteComment', type: 'number' },
      { key: 'time_before_delete_reply', labelKey: 'userGroupDialog.fields.deleteReply', type: 'number' },
      { key: 'time_before_delete_topic', labelKey: 'userGroupDialog.fields.deleteTopic', type: 'number' },
    ],
  },
  {
    titleKey: 'userGroupDialog.groups.editCondition',
    icon: 'mdi-lock-check',
    fields: [
      { key: 'ability_edit_article_only_no_comment', labelKey: 'userGroupDialog.fields.editArticleNoComment' },
      { key: 'ability_edit_question_only_no_answer', labelKey: 'userGroupDialog.fields.editQuestionNoAnswer' },
      { key: 'ability_edit_answer_only_no_comment', labelKey: 'userGroupDialog.fields.editAnswerNoComment' },
      { key: 'ability_edit_question_only_no_comment', labelKey: 'userGroupDialog.fields.editQuestionNoComment' },
      { key: 'ability_edit_comment_only_no_reply', labelKey: 'userGroupDialog.fields.editCommentNoReply' },
      { key: 'ability_edit_reply_only_no_reply', labelKey: 'userGroupDialog.fields.editReplyNoReply' },
      { key: 'ability_edit_topic_only_no_article_or_question', labelKey: 'userGroupDialog.fields.editTopicNoContent' },
    ],
  },
  {
    titleKey: 'userGroupDialog.groups.deleteCondition',
    icon: 'mdi-lock-remove',
    fields: [
      { key: 'ability_delete_article_only_no_comment', labelKey: 'userGroupDialog.fields.deleteArticleNoComment' },
      { key: 'ability_delete_question_only_no_answer', labelKey: 'userGroupDialog.fields.deleteQuestionNoAnswer' },
      { key: 'ability_delete_answer_only_no_comment', labelKey: 'userGroupDialog.fields.deleteAnswerNoComment' },
      { key: 'ability_delete_question_only_no_comment', labelKey: 'userGroupDialog.fields.deleteQuestionNoComment' },
      { key: 'ability_delete_comment_only_no_reply', labelKey: 'userGroupDialog.fields.deleteCommentNoReply' },
      { key: 'ability_delete_reply_only_no_reply', labelKey: 'userGroupDialog.fields.deleteReplyNoReply' },
      { key: 'ability_delete_topic_only_no_article_or_question', labelKey: 'userGroupDialog.fields.deleteTopicNoContent' },
    ],
  },
  {
    titleKey: 'userGroupDialog.groups.other',
    icon: 'mdi-account-cog',
    fields: [
      { key: 'ability_edit_own_info', labelKey: 'userGroupDialog.fields.editOwnInfo' },
      { key: 'ability_vote', labelKey: 'userGroupDialog.fields.vote' },
    ],
  },
]

// ================== 响应式表单 ==================
const form = ref({ ...DEFAULTS })

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    if (props.userGroup) {
      // 编辑模式：从传入数据初始化，缺失字段用默认值兜底
      const merged = { ...DEFAULTS }
      for (const key of Object.keys(DEFAULTS)) {
        const val = props.userGroup[key]
        if (val !== undefined && val !== null) {
          merged[key] = val
        }
      }
      form.value = merged
    } else {
      // 新建模式：用默认值
      form.value = { ...DEFAULTS }
    }
    formRef.value?.resetValidation()
  },
)

const nameRules = [
  (v) => (v?.trim() ? true : t('userGroupDialog.nameRequired')),
  (v) => (v?.trim().length <= 30 ? true : t('userGroupDialog.nameMax')),
]

const descriptionRules = [
  (v) => (!v || v.trim().length <= 500 ? true : t('userGroupDialog.descriptionMax')),
]

const iconRules = [
  (v) => (!v || /^mdi-[a-z0-9-]+$/i.test(v.trim()) ? true : t('userGroupDialog.iconFormat')),
  (v) => (!v || v.trim().length <= 50 ? true : t('userGroupDialog.iconMax')),
]

const colorRules = [
  (v) => (!v || /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v.trim()) ? true : t('userGroupDialog.colorFormat')),
]

async function submit() {
  const { valid } = await formRef.value?.validate()
  if (!valid) return

  submitting.value = true
  try {
    // 提取全部表单字段作为 payload
    const payload = { ...form.value }
    // 清理 name / description 的空白
    payload.user_group_name = payload.user_group_name.trim()
    payload.user_group_description = payload.user_group_description.trim()

    let data
    if (isEdit.value) {
      const res = await http.put(`${props.apiBase}/${props.userGroup.user_group_id}`, payload)
      data = res.data
      show(t('userGroupDialog.updateSuccess'), 'success')
    } else {
      const res = await http.post(props.apiBase, payload)
      data = res.data
      show(t('userGroupDialog.createSuccess'), 'success')
    }

    emit('submit', data)
    dialogOpen.value = false
  } catch (error) {
    show(error.message || t('userGroupDialog.submitError'), 'error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <v-dialog v-model="dialogOpen" max-width="800"
    :fullscreen="xs">
    <v-card rounded="lg" >
      <v-toolbar :title="isEdit ? $t('userGroupDialog.titleEdit') : $t('userGroupDialog.titleNew')">
        <template #prepend>
          <v-btn icon variant="text" @click="dialogOpen = false">
            <v-icon>mdi-close</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.cancel') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <v-btn icon variant="text" :loading="submitting" @click="submit">
            <v-icon>mdi-check</v-icon>
            <v-tooltip activator="parent" location="left">
              {{ isEdit ? $t('common.save') : $t('common.submit') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-card-text style="max-height: 65vh; overflow-y: auto;">
        <v-form ref="formRef" @submit.prevent="submit">
          <!-- 基础信息 -->
          <div class="text-subtitle-1 font-weight-bold mb-3">{{ $t('userGroupDialog.groups.basic') }}</div>
          <v-text-field color="primary"
            v-model="form.user_group_name"
            :label="$t('userGroupDialog.name')"
            :rules="nameRules"
            counter="30"
            variant="outlined"
            density="compact"
            hide-details="auto"
            class="mb-3"
          />
          <v-textarea color="primary"
            v-model="form.user_group_description"
            :label="$t('userGroupDialog.description')"
            :rules="descriptionRules"
            counter="500"
            variant="outlined"
            density="compact"
            rows="2"
            auto-grow
            hide-details="auto"
            class="mb-3"
          />
          <v-row class="mb-1">
            <v-col cols="6">
              <v-text-field color="primary"
                v-model="form.user_group_icon"
                :label="$t('userGroupDialog.icon')"
                :rules="iconRules"
                counter="50"
                variant="outlined"
                density="compact"
                hide-details="auto"
                :hint="$t('userGroupDialog.iconHint')"
                persistent-hint
              >
                <template #prepend-inner>
                  <v-icon :icon="form.user_group_icon || 'mdi-account'" />
                </template>
              </v-text-field>
            </v-col>
            <v-col cols="6">
              <ColorTextField color="primary"
                v-model="form.user_group_color"
                :label="$t('userGroupDialog.color')"
                :rules="colorRules"
                counter="20"
                variant="outlined"
                density="compact"
                hide-details="auto"
                :hint="$t('userGroupDialog.colorHint')"
                persistent-hint
              />
            </v-col>
          </v-row>
          <v-row class="mb-1">
            <v-col cols="6">
              <v-checkbox
                v-model="form.is_admin"
                :label="$t('userGroupDialog.fields.isAdmin')"
                density="compact"
                hide-details
                color="primary"
              />
            </v-col>
            <v-col cols="6">
              <v-checkbox
                v-model="form.user_group_icon_show"
                :label="$t('userGroupDialog.fields.iconShow')"
                density="compact"
                hide-details
                color="primary"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- 权限分组折叠面板 -->
          <v-expansion-panels multiple>
            <v-expansion-panel v-for="group in PERMISSION_GROUPS" :key="group.titleKey">
              <v-expansion-panel-title>
                <div class="d-flex align-center" style="gap: 8px;">
                  <v-icon :icon="group.icon" size="small" />
                  <span>{{ $t(group.titleKey) }}</span>
                </div>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <v-row dense>
                  <v-col
                    v-for="field in group.fields"
                    :key="field.key"
                    cols="12"
                    sm="6"
                  >
                    <!-- 时间限制：数字输入 -->
                    <v-text-field color="primary"
                      v-if="field.type === 'number'"
                      v-model.number="form[field.key]"
                      :label="`${$t(field.labelKey)} (${$t('userGroupDialog.fields.minutes')})`"
                      type="number"
                      min="0"
                      variant="outlined"
                      density="compact"
                      hide-details="auto"
                      :hint="$t('userGroupDialog.fields.timeHint')"
                      persistent-hint
                    />
                    <!-- 权限：复选框 -->
                    <v-checkbox
                      v-else
                      v-model="form[field.key]"
                      :label="$t(field.labelKey)"
                      density="compact"
                      hide-details
                      color="primary"
                    />
                  </v-col>
                </v-row>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-form>
      </v-card-text>

    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useUserStore } from '../stores/user'
import { useProfileEditStore } from '../stores/profileEditDialog'
import { useFeedback } from '../composables/useFeedback'
import { useDisplay } from 'vuetify'
import http from '../api'
const { xs } = useDisplay()

const props = defineProps({
  /** API 基础路径：管理员后台传 '/admin/modules/users'，默认空走用户端 '/user/profile' */
  apiBase: { type: String, default: '' },
})

const emit = defineEmits(['saved'])

const profileEdit = useProfileEditStore()

const authStore = useAuthStore()
const userStore = useUserStore()
const { show: feedback } = useFeedback()
const { t } = useI18n()

// 管理员模式：store.apiBase 优先于 prop
const effectiveApiBase = computed(() => profileEdit.apiBase || props.apiBase)

// 管理员模式：额外可编辑用户组
const isAdmin = computed(() => Boolean(effectiveApiBase.value))

const form = reactive({
  username: '',
  headline: '',
  bio: '',
  blog: '',
  company: '',
  location: '',
  user_group_id: null,
})

const formRef = ref(null)
const saving = ref(false)

// 管理员模式下可选的用户组列表（公开接口 GET /user-groups）
const userGroups = ref([])

async function loadUserGroups() {
  try {
    const { data } = await http.get('/user-groups')
    userGroups.value = Array.isArray(data) ? data : []
  } catch {
    userGroups.value = []
  }
}

// 打开对话框时用 store.user 填充表单
watch(
  () => profileEdit.model,
  (visible) => {
    if (visible && profileEdit.user) {
      const user = profileEdit.user
      form.username = user.username ?? ''
      form.headline = user.headline ?? ''
      form.bio = user.bio ?? ''
      form.blog = user.blog ?? ''
      form.company = user.company ?? ''
      form.location = user.location ?? ''
      form.user_group_id = user.user_group_id ?? null
    }
    if (visible && isAdmin.value) {
      loadUserGroups()
    }
  },
)

const rules = {
  username: [
    (v) => Boolean(v) || t('profileEdit.usernameRequired'),
    (v) => !v || v.length <= 30 || t('profileEdit.usernameTooLong'),
  ],
  userGroup: [
    (v) => v != null || t('profileEdit.userGroupRequired'),
  ],
}

function close() {
  if (saving.value) return
  profileEdit.close()
}

async function save() {
  const { valid } = await formRef.value?.validate()
  if (!valid) return

  saving.value = true
  try {
    const payload = {
      username: form.username,
      headline: form.headline,
      bio: form.bio,
      blog: form.blog,
      company: form.company,
      location: form.location,
    }
    if (isAdmin.value) {
      payload.user_group_id = form.user_group_id
    }

    if (effectiveApiBase.value) {
      // 管理员模式：PUT /admin/modules/users/{id}
      const userId = profileEdit.user?.user_id
      const { data } = await http.put(`${effectiveApiBase.value}/${userId}`, payload)
      feedback(t('admin.editSaved'), 'success')
      profileEdit.emitSaved(data)
      emit('saved', data)
    } else {
      // 普通模式：更新自己的资料
      const result = await userStore.updateProfile(payload)
      authStore.setUser(result.user)
      feedback(result.message || t('profileEdit.saved'), 'success')
      profileEdit.emitSaved(result.user)
      emit('saved', result.user)
    }
    profileEdit.close()
  } catch (error) {
    feedback(error.message, 'error')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <v-dialog :model-value="profileEdit.model" max-width="560"
    @update:model-value="(v) => { if (!v) profileEdit.close() }" :fullscreen="xs">
    <v-card rounded="lg" >
      <v-toolbar :title="$t('profileEdit.title')">
        <template v-slot:prepend>
          <v-btn icon variant="text" @click="close" >
            <v-icon>
              mdi-close
            </v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.cancel') }}
            </v-tooltip>
          </v-btn>
        </template>
        <template #append>
          <v-btn icon variant="text" @click="save":loading="saving" >
            <v-icon>
              mdi-check
            </v-icon>
            <v-tooltip activator="parent" location="left">
              {{ $t('common.save') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-card-text>
        <v-form ref="formRef">
          <v-row>
            <v-col cols="12">
              <v-text-field color="primary"
                v-model="form.username"
                :label="$t('profileEdit.username')"
                :rules="rules.username"
                variant="outlined"
                density="comfortable"
              />
            </v-col>

            <!-- 管理员模式：变更用户所属用户组 -->
            <v-col v-if="isAdmin" cols="12">
              <v-select color="primary" 
                v-model="form.user_group_id"
                :label="$t('profileEdit.userGroup')"
                :items="userGroups"
                item-title="user_group_name"
                item-value="user_group_id"
                :rules="rules.userGroup"
                variant="outlined"
                density="comfortable"
                :loading="userGroups.length === 0"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field color="primary"
                v-model="form.headline"
                :label="$t('profileEdit.headline')"
                :hint="$t('profileEdit.headlineHint')"
                counter="40"
                maxlength="40"
                variant="outlined"
                density="comfortable"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea color="primary"
                v-model="form.bio"
                :label="$t('profileEdit.bio')"
                :hint="$t('profileEdit.bioHint')"
                counter="160"
                maxlength="160"
                variant="outlined"
                density="comfortable"
                auto-grow
                rows="3"
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field color="primary"
                v-model="form.location"
                :label="$t('profileEdit.location')"
                variant="outlined"
                density="comfortable"
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field color="primary"
                v-model="form.company"
                :label="$t('profileEdit.company')"
                variant="outlined"
                density="comfortable"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field color="primary"
                v-model="form.blog"
                :label="$t('profileEdit.blog')"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <!-- <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="close">{{ $t('common.cancel') }}</v-btn>
        <v-btn color="primary" :loading="saving" @click="save">{{ $t('common.save') }}</v-btn>
      </v-card-actions> -->
    </v-card>
  </v-dialog>
</template>

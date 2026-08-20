<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useUserStore } from '../stores/user'
import { useProfileEditStore } from '../stores/profileEditDialog'
import { useFeedback } from '../composables/useFeedback'
import { useDisplay } from 'vuetify'
import http from '../api'
const { mobile } = useDisplay()

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

const form = reactive({
  username: '',
  headline: '',
  bio: '',
  blog: '',
  company: '',
  location: '',
})

const formRef = ref(null)
const saving = ref(false)

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
    }
  },
)

const rules = {
  username: [
    (v) => Boolean(v) || t('profileEdit.usernameRequired'),
    (v) => !v || v.length <= 30 || t('profileEdit.usernameTooLong'),
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
    @update:model-value="(v) => { if (!v) profileEdit.close() }" :fullscreen="mobile">
    <v-card >
      <v-toolbar  :title="$t('profileEdit.title')">
        <template #append>
          <v-btn icon="mdi-close" variant="text" @click="close" />
        </template>
      </v-toolbar>

      <v-card-text>
        <v-form ref="formRef">
          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="form.username"
                :label="$t('profileEdit.username')"
                :rules="rules.username"
                variant="outlined"
                density="comfortable"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field
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
              <v-textarea
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
              <v-text-field
                v-model="form.location"
                :label="$t('profileEdit.location')"
                variant="outlined"
                density="comfortable"
              />
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.company"
                :label="$t('profileEdit.company')"
                variant="outlined"
                density="comfortable"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model="form.blog"
                :label="$t('profileEdit.blog')"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="close">{{ $t('common.cancel') }}</v-btn>
        <v-btn color="primary" :loading="saving" @click="save">{{ $t('common.save') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

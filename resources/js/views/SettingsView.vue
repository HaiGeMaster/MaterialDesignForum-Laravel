<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useUserStore } from '../stores/user'
import { useFeedback } from '../composables/useFeedback'
import ImageUploadField from '../components/ImageUploadField.vue'

import { useDisplay } from 'vuetify'
const { xs } = useDisplay()

const authStore = useAuthStore()
const userStore = useUserStore()
const router = useRouter()
const { show: feedback } = useFeedback()
const { t } = useI18n()

const isLoggedIn = computed(() => authStore.isLoggedIn)

// 当前展示的区块：list=选项列表 / profile=编辑个人资料 / password=修改密码
const section = ref('list')

// 未登录直接回登录页
watch(
  isLoggedIn,
  (val) => {
    if (!val) router.replace('/users/me')
  },
  { immediate: true },
)

// ---------- 编辑个人资料 ----------
const profileFormRef = ref()
const profileForm = reactive({
  username: '',
  email: '',
  avatar: '',
  cover: '',
  headline: '',
  bio: '',
  blog: '',
  company: '',
  location: '',
  language: 'zh_CN',
})

const languageOptions = [
  { value: 'zh_CN', label: '简体中文' },
  { value: 'zh_TW', label: '繁體中文' },
  { value: 'en_US', label: 'English' },
  { value: 'ja_JP', label: '日本語' },
]

const rules = {
  required: (v) => !!v || t('settings.rules.required'),
  email: (v) => !v || /.+@.+\..+/.test(v) || t('settings.rules.email'),
  username: (v) => !v || v.length >= 2 || t('settings.rules.username'),
}

function syncProfileForm() {
  const u = authStore.user
  if (!u) return
  profileForm.username = u.username || ''
  profileForm.email = u.email || ''
  profileForm.avatar = u.avatar || ''
  profileForm.cover = u.cover || ''
  profileForm.headline = u.headline || ''
  profileForm.bio = u.bio || ''
  profileForm.blog = u.blog || ''
  profileForm.company = u.company || ''
  profileForm.location = u.location || ''
  profileForm.language = u.language || 'zh_CN'
}

async function handleUpdateProfile() {
  const { valid } = await profileFormRef.value.validate()
  if (!valid) return
  try {
    const result = await userStore.updateProfile({
      username: profileForm.username || undefined,
      email: profileForm.email || undefined,
      avatar: profileForm.avatar || null,
      cover: profileForm.cover || null,
      headline: profileForm.headline || null,
      bio: profileForm.bio || null,
      blog: profileForm.blog || null,
      company: profileForm.company || null,
      location: profileForm.location || null,
      language: profileForm.language || 'zh_CN',
    })
    authStore.setUser(result.user)
    feedback(result.message || t('settings.profileUpdated'), 'success')
  } catch (error) {
    feedback(error.message, 'error')
  }
}

// ---------- 修改密码 ----------
const passwordFormRef = ref()
const passwordForm = reactive({
  old_password: '',
  password: '',
  password_confirmation: '',
})

const passwordRules = {
  old_password: (v) => !!v || t('settings.rules.oldPassword'),
  password: (v) => (v && v.length >= 6) || t('settings.rules.password'),
  confirm: (v) => v === passwordForm.password || t('settings.rules.confirm'),
}

async function handleUpdatePassword() {
  const { valid } = await passwordFormRef.value.validate()
  if (!valid) return
  try {
    const result = await userStore.updatePassword({
      old_password: passwordForm.old_password,
      password: passwordForm.password,
      password_confirmation: passwordForm.password_confirmation,
    })
    feedback(result.message || t('settings.passwordUpdated'), 'success')
    passwordForm.old_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (error) {
    feedback(error.message, 'error')
  }
}

watch(
  () => authStore.user,
  (u) => {
    if (u) syncProfileForm()
  },
)

onMounted(() => {
  if (authStore.user) syncProfileForm()
})
</script>

<template>
  <v-container  :class="xs ? 'px-0' : 'px-4'" :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}">
    <v-row justify="center">
      <v-col cols="12" md="10" lg="8">
        <!-- 设置选项列表 -->
        <template v-if="section === 'list'">
          <v-card >
            <v-card-title >
              <v-icon icon="mdi-cog-outline" />
              {{ $t('settings.title') }}
            </v-card-title>
            <v-divider />
            <v-list>
              <v-list-item
                prepend-icon="mdi-account-edit"
                :title="$t('settings.editProfile')"
                :subtitle="$t('settings.editProfileSub')"
                append-icon="mdi-chevron-right"
                class="pa-4"
                @click="section = 'profile'"
              />
              <v-divider />
              <v-list-item
                prepend-icon="mdi-shield-lock"
                :title="$t('settings.changePassword')"
                :subtitle="$t('settings.changePasswordSub')"
                append-icon="mdi-chevron-right"
                class="pa-4"
                @click="section = 'password'"
              />
            </v-list>
          </v-card>
        </template>

        <!-- 编辑个人资料 -->
        <template v-else-if="section === 'profile'">
          <v-card >
            <v-card-title>
              <v-btn
                icon="mdi-arrow-left"
                size="small"
                variant="text"
                class="mr-1"
                @click="section = 'list'"
              />
              <v-icon icon="mdi-account-edit" class="mr-2" />
              {{ $t('settings.editProfile') }}
            </v-card-title>
            <v-divider />
            <v-card-text>
              <v-form ref="profileFormRef" @submit.prevent="handleUpdateProfile">
                <v-row>
                  <v-col cols="12" sm="6">
                    <v-text-field v-model="profileForm.username" :label="$t('settings.username')" :rules="[rules.username]" />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field v-model="profileForm.email" :label="$t('settings.email')" type="email" :rules="[rules.required, rules.email]" />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <div class="text-subtitle-2 mb-1">{{ $t('settings.avatar') }}</div>
                    <ImageUploadField v-model="profileForm.avatar" type="avatar" :label="$t('settings.avatar')" preview-ratio="1" />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <div class="text-subtitle-2 mb-1">{{ $t('settings.cover') }}</div>
                    <ImageUploadField v-model="profileForm.cover" type="cover" :label="$t('settings.cover')" preview-ratio="16 / 6" />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="profileForm.headline" :label="$t('settings.headline')" counter="40" maxlength="40" />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea v-model="profileForm.bio" :label="$t('settings.bio')" counter="160" maxlength="160" rows="3" />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field v-model="profileForm.blog" :label="$t('settings.blog')" prepend-inner-icon="mdi-link" />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field v-model="profileForm.company" :label="$t('settings.company')" />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field v-model="profileForm.location" :label="$t('settings.location')" />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-select
                      v-model="profileForm.language"
                      :items="languageOptions"
                      item-title="label"
                      item-value="value"
                      :label="$t('settings.language')"
                    />
                  </v-col>
                </v-row>
                <v-btn type="submit" color="primary" prepend-icon="mdi-content-save" :loading="userStore.loading">
                  {{ $t('settings.saveProfile') }}
                </v-btn>
              </v-form>
            </v-card-text>
          </v-card>
        </template>

        <!-- 修改密码 -->
        <template v-else>
          <v-card >
            <v-card-title>
              <v-btn
                icon="mdi-arrow-left"
                size="small"
                variant="text"
                class="mr-1"
                @click="section = 'list'"
              />
              <v-icon icon="mdi-shield-lock" class="mr-2" />
              {{ $t('settings.changePassword') }}
            </v-card-title>
            <v-divider />
            <v-card-text>
              <v-form ref="passwordFormRef" @submit.prevent="handleUpdatePassword">
                <v-text-field
                  v-model="passwordForm.old_password"
                  :label="$t('settings.oldPassword')"
                  type="password"
                  prepend-inner-icon="mdi-lock"
                  :rules="[passwordRules.old_password]"
                  autocomplete="current-password"
                />
                <v-text-field
                  v-model="passwordForm.password"
                  :label="$t('settings.newPassword')"
                  type="password"
                  prepend-inner-icon="mdi-lock"
                  :rules="[passwordRules.password]"
                  autocomplete="new-password"
                />
                <v-text-field
                  v-model="passwordForm.password_confirmation"
                  :label="$t('settings.confirmNewPassword')"
                  type="password"
                  prepend-inner-icon="mdi-lock"
                  :rules="[passwordRules.confirm]"
                  autocomplete="new-password"
                />
                <v-btn type="submit" color="primary" prepend-icon="mdi-shield-key" :loading="userStore.loading">
                  {{ $t('settings.changePasswordBtn') }}
                </v-btn>
              </v-form>
            </v-card-text>
          </v-card>
        </template>
      </v-col>
    </v-row>
  </v-container>
</template>

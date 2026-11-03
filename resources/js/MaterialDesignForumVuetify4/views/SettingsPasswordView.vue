<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { storeToRefs } from 'pinia'
import { useSettingsPasswordViewStore } from '../stores/views/settingsPassword'
import { useFeedback } from '../composables/useFeedback'
import { useDisplay } from 'vuetify'

const router = useRouter()
const passwordStore = useSettingsPasswordViewStore()
const { show: feedback } = useFeedback()
const { t } = useI18n()
const { xs } = useDisplay()

const { form: passwordForm, loading } = storeToRefs(passwordStore)

const passwordFormRef = ref()

const passwordRules = {
  old_password: (value) => !!value || t('settings.rules.oldPassword'),
  password: (value) => (value && value.length >= 6) || t('settings.rules.password'),
  confirm: (value) => value === passwordForm.value.password || t('settings.rules.confirm'),
}

async function handleUpdatePassword() {
  const { valid } = await passwordFormRef.value.validate()
  if (!valid) return

  try {
    const result = await passwordStore.updatePassword()
    feedback(result.message || t('settings.passwordUpdated'), 'success')
  } catch (error) {
    feedback(error.message, 'error')
  }
}
</script>

<template>
  <v-container
    :class="xs ? 'px-0' : 'px-4'"
    :style="$route.meta.maxWidth ? { maxWidth: `${$route.meta.maxWidth}px`, margin: '0 auto' } : {}"
  >
    <v-card rounded="lg">
      <v-toolbar :title="$t('settings.changePassword')">
        <template #prepend>
          <v-btn icon variant="text" @click="router.push({ name: 'settings' })">
            <v-icon>mdi-arrow-left</v-icon>
            <v-tooltip activator="parent" location="right">
              {{ $t('common.back') }}
            </v-tooltip>
          </v-btn>
        </template>
      </v-toolbar>

      <v-card-text>
        <v-form ref="passwordFormRef" @submit.prevent="handleUpdatePassword">
          <v-text-field
            v-model="passwordForm.old_password"
            color="primary"
            :label="$t('settings.oldPassword')"
            type="password"
            prepend-inner-icon="mdi-lock"
            :rules="[passwordRules.old_password]"
            autocomplete="current-password"
          />
          <v-text-field
            v-model="passwordForm.password"
            color="primary"
            :label="$t('settings.newPassword')"
            type="password"
            prepend-inner-icon="mdi-lock"
            :rules="[passwordRules.password]"
            autocomplete="new-password"
          />
          <v-text-field
            v-model="passwordForm.password_confirmation"
            color="primary"
            :label="$t('settings.confirmNewPassword')"
            type="password"
            prepend-inner-icon="mdi-lock"
            :rules="[passwordRules.confirm]"
            autocomplete="new-password"
          />
          <v-btn
            type="submit"
            color="primary"
            prepend-icon="mdi-shield-key"
            :loading="loading"
          >
            {{ $t('settings.changePasswordBtn') }}
          </v-btn>
        </v-form>
      </v-card-text>
    </v-card>
  </v-container>
</template>

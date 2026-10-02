

<script setup>
import { useDisplay } from 'vuetify'
import { useRoute, useRouter } from 'vue-router'
const router = useRouter()
const { smAndDown } = useDisplay()

</script>

<template>
  <!-- 桌面端：直接显示两个按钮 -->
  <template v-if="!smAndDown">
    <v-btn  variant="flat" color="primary" class="login-btn" prepend-icon="mdi-login"
      style="-webkit-app-region: no-drag;margin: 0 4px;" @click="router.push('/login')">
      <!-- @click="dialogStore.setLoginDialog(true)"> -->
      {{ $t('auth.login') }}
    </v-btn>

    <v-btn  variant="tonal" color="primary" class="register-btn" prepend-icon="mdi-account-plus"
      style="-webkit-app-region: no-drag;margin: 0 4px;" @click="router.push('/login#register')">
      {{ $t('auth.register') }}
    </v-btn>
  </template>

  <!-- 移动端：折叠到菜单中 rounded="lg"-->
  <v-menu v-else>
    <template v-slot:activator="{ props }">
      <v-btn v-bind="props" style="-webkit-app-region: no-drag;" icon="mdi-dots-vertical">
      </v-btn>
    </template>
    <v-list >
      <v-list-item prepend-icon="mdi-login"  @click="router.push('/login')">
        <v-list-item-title>
      {{ $t('auth.login') }}
    </v-list-item-title>
      </v-list-item>
      <v-list-item prepend-icon=" mdi-account-plus" @click="router.push('/login#register')">
        <v-list-item-title>
          {{ $t('auth.register') }}
        </v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>


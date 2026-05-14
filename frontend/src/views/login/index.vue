<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

import { login, type LoginParams } from '@/api/auth'
import AppBrand from '@/components/AppBrand.vue'
import AuthPageLayout from '@/components/AuthPageLayout.vue'

import LoginForm from './components/LoginForm.vue'

const route = useRoute()
const loading = ref(false)
const error = ref('')

const success = computed(() => {
  const value = route.query.success
  return value === 'registered' ? '注册成功，请登录' : ''
})

const next = computed(() => {
  const value = route.query.next
  return typeof value === 'string' ? value : ''
})

const routeError = computed(() => {
  const value = route.query.error
  return typeof value === 'string' ? value : ''
})

const handleSubmit = async (payload: LoginParams) => {
  loading.value = true
  error.value = ''

  try {
    const { ok, result } = await login(payload)

    if (!ok || result.code !== 0) {
      error.value = result.msg || '登录失败，请稍后重试'
      return
    }

    window.location.href = result.data?.redirect || '/dashboard'
  } catch {
    error.value = '网络异常，请稍后重试'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthPageLayout
    footer-text="还没有账户?"
    footer-link-label="注册"
    :footer-link-href="`/register${next ? `?next=${encodeURIComponent(next)}` : ''}`"
  >
    <template #brand>
      <AppBrand />
    </template>

    <LoginForm
      :loading="loading"
      :error="error || routeError"
      :success="success"
      :next="next"
      @submit="handleSubmit"
    />
  </AuthPageLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

import AppBrand from '@/components/AppBrand.vue'
import AuthPageLayout from '@/components/AuthPageLayout.vue'

import LoginForm from './components/LoginForm.vue'

type LoginPayload = {
  email: string
  password: string
  next: string
}

type LoginResponse = {
  code: number
  msg: string
  data?: {
    redirect?: string
  }
}

const route = useRoute()
const loading = ref(false)
const error = ref('')

const next = computed(() => {
  const value = route.query.next
  return typeof value === 'string' ? value : ''
})

const handleSubmit = async (payload: LoginPayload) => {
  loading.value = true
  error.value = ''

  try {
    const body = new URLSearchParams()
    body.set('email', payload.email)
    body.set('password', payload.password)
    body.set('next', payload.next)

    const response = await fetch('/api/v1/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        Accept: 'application/json',
      },
      credentials: 'include',
      body: body.toString(),
    })

    const result = (await response.json()) as LoginResponse

    if (!response.ok || result.code !== 0) {
      error.value = result.msg || '登录失败，请稍后重试'
      return
    }

    window.location.href = result.data?.redirect || '/home'
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

    <LoginForm :loading="loading" :error="error" :next="next" @submit="handleSubmit" />
  </AuthPageLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import GithubLoginButton from './components/GithubLoginButton.vue'
import LoginBrand from './components/LoginBrand.vue'
import LoginDivider from './components/LoginDivider.vue'
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
const router = useRouter()

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

const handleGithubLogin = () => {
  error.value = 'GitHub 登录暂未开放'
}
</script>

<template>
  <main class="login-page">
    <div class="login-page__grid" aria-hidden="true" />
    <section class="login-page__content">
      <LoginBrand />

      <div class="login-page__card">
        <LoginForm :loading="loading" :error="error" :next="next" @submit="handleSubmit" />
        <LoginDivider />
        <GithubLoginButton :disabled="loading" @click="handleGithubLogin" />
      </div>

      <p class="login-page__signup">
        还没有账户?
        <a :href="`/register${next ? `?next=${encodeURIComponent(next)}` : ''}`">注册</a>
      </p>

      <p class="login-page__copyright">© 2026 FlyMux. All rights reserved.</p>
    </section>
  </main>
</template>

<style scoped>
:global(body) {
  margin: 0;
  font-family:
    Inter,
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    sans-serif;
  background: #f6f8fc;
}

:global(*) {
  box-sizing: border-box;
}

.login-page {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
  background:
    radial-gradient(circle at top, rgba(255, 255, 255, 0.88), rgba(246, 248, 252, 0.92)),
    #f6f8fc;
}

.login-page__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(222, 229, 240, 0.7) 1px, transparent 1px),
    linear-gradient(90deg, rgba(222, 229, 240, 0.7) 1px, transparent 1px);
  background-size: 62px 62px;
  mask-image: radial-gradient(circle at center, black 45%, transparent 100%);
}

.login-page__content {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 28px;
  padding: 32px 16px;
}

.login-page__card {
  width: min(100%, 450px);
  padding: 36px 34px 32px;
  border: 1px solid rgba(227, 232, 241, 0.95);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 24px 60px rgba(43, 57, 96, 0.12);
  display: grid;
  gap: 24px;
}

.login-page__signup,
.login-page__copyright {
  margin: 0;
  text-align: center;
}

.login-page__signup {
  color: #98a3b7;
  font-size: 14px;
}

.login-page__signup a {
  margin-left: 8px;
  color: #556684;
  font-weight: 700;
  text-decoration: none;
}

.login-page__copyright {
  color: #b1b9c8;
  font-size: 13px;
}

@media (max-width: 640px) {
  .login-page__content {
    gap: 24px;
  }

  .login-page__card {
    padding: 28px 20px 24px;
    border-radius: 20px;
  }
}
</style>

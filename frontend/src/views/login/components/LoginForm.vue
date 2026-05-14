<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import BaseButton from '@/components/BaseButton.vue'
import BaseInput from '@/components/BaseInput.vue'

const props = defineProps<{
  loading?: boolean
  error?: string
  next?: string
}>()

const emit = defineEmits<{
  submit: [{ email: string; password: string; next: string }]
}>()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const emailError = ref('')
const passwordError = ref('')

watch(
  () => [email.value, password.value],
  () => {
    if (emailError.value) {
      emailError.value = ''
    }
    if (passwordError.value) {
      passwordError.value = ''
    }
  },
)

const passwordType = computed(() => (showPassword.value ? 'text' : 'password'))

const validate = () => {
  emailError.value = ''
  passwordError.value = ''

  if (!email.value.trim()) {
    emailError.value = '请输入邮箱'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
    emailError.value = '请输入正确的邮箱'
  }

  if (!password.value) {
    passwordError.value = '请输入密码'
  }

  return !emailError.value && !passwordError.value
}

const submit = () => {
  if (!validate()) {
    return
  }

  emit('submit', {
    email: email.value.trim(),
    password: password.value,
    next: props.next ?? '',
  })
}
</script>

<template>
  <form class="login-form" @submit.prevent="submit">
    <div class="login-form__heading">
      <h1>欢迎回来</h1>
      <p>登录您的账户以继续</p>
    </div>

    <p v-if="error" class="login-form__alert">{{ error }}</p>

    <BaseInput
      v-model="email"
      label="邮箱"
      type="email"
      autocomplete="email"
      placeholder="请输入邮箱"
      :error="emailError"
    >
      <template #prefix>
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path
            d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-9Z"
            fill="none"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.7"
          />
          <path
            d="m5 8 6.06 4.55a1.6 1.6 0 0 0 1.88 0L19 8"
            fill="none"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.7"
          />
        </svg>
      </template>
    </BaseInput>

    <div class="login-form__password-block">
      <BaseInput
        v-model="password"
        label="密码"
        :type="passwordType"
        autocomplete="current-password"
        placeholder="请输入密码"
        :error="passwordError"
      >
        <template #prefix>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M8 10V7.75A4 4 0 0 1 12 4a4 4 0 0 1 4 3.75V10"
              fill="none"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.7"
            />
            <rect
              x="5"
              y="10"
              width="14"
              height="10"
              rx="2.5"
              fill="none"
              stroke="currentColor"
              stroke-width="1.7"
            />
          </svg>
        </template>
        <template #suffix>
          <button
            class="login-form__toggle"
            type="button"
            :aria-label="showPassword ? '隐藏密码' : '显示密码'"
            @click="showPassword = !showPassword"
          >
            <svg v-if="!showPassword" viewBox="0 0 24 24" aria-hidden="true">
              <path
                d="M2.8 12s3.2-5.5 9.2-5.5S21.2 12 21.2 12 18 17.5 12 17.5 2.8 12 2.8 12Z"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.7"
              />
              <circle cx="12" cy="12" r="2.8" fill="none" stroke="currentColor" stroke-width="1.7" />
            </svg>
            <svg v-else viewBox="0 0 24 24" aria-hidden="true">
              <path
                d="M3 4.5 21 19.5"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-width="1.7"
              />
              <path
                d="M10.6 6.7A9.66 9.66 0 0 1 12 6.5c6 0 9.2 5.5 9.2 5.5a17.54 17.54 0 0 1-3 3.76"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.7"
              />
              <path
                d="M6.57 8.14A16.4 16.4 0 0 0 2.8 12S6 17.5 12 17.5a9.7 9.7 0 0 0 3.08-.48"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.7"
              />
            </svg>
          </button>
        </template>
      </BaseInput>

      <div class="login-form__meta">
        <a href="/forgot-password" @click.prevent>忘记密码?</a>
      </div>
    </div>

    <BaseButton type="submit" block :loading="loading">
      <svg class="login-form__submit-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path
          d="M13 6h-3a3 3 0 0 0-3 3v6a3 3 0 0 0 3 3h3"
          fill="none"
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.7"
        />
        <path
          d="m12 12 8-5v10l-8-5Z"
          fill="none"
          stroke="currentColor"
          stroke-linejoin="round"
          stroke-width="1.7"
        />
      </svg>
      <span>{{ loading ? '登录中...' : '登录' }}</span>
    </BaseButton>
  </form>
</template>

<style scoped>
.login-form {
  display: grid;
  gap: 22px;
}

.login-form__heading {
  text-align: center;
}

.login-form__heading h1 {
  margin: 0;
  color: #1e2a3f;
  font-size: 24px;
  font-weight: 800;
}

.login-form__heading p {
  margin: 12px 0 0;
  color: #8f9aae;
  font-size: 14px;
}

.login-form__alert {
  margin: 0;
  padding: 12px 14px;
  border-radius: 12px;
  color: #bf4545;
  background: rgba(227, 107, 107, 0.12);
  font-size: 13px;
}

.login-form__password-block {
  display: grid;
  gap: 8px;
}

.login-form__meta {
  display: flex;
  justify-content: flex-end;
}

.login-form__meta a {
  color: #697793;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}

.login-form__meta a:hover {
  color: #50607f;
}

.login-form__toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  padding: 0;
  color: #98a3b7;
  background: transparent;
  border: 0;
  cursor: pointer;
}

.login-form__toggle svg,
.login-form :deep(.base-input__prefix svg) {
  width: 20px;
  height: 20px;
}

.login-form__submit-icon {
  width: 18px;
  height: 18px;
}
</style>

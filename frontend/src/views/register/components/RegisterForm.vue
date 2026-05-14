<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import { register } from '@/api/auth'
import BaseButton from '@/components/BaseButton.vue'
import BaseInput from '@/components/BaseInput.vue'

const props = defineProps<{
  next?: string
}>()

const email = ref('')
const displayName = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const submitting = ref(false)
const submitError = ref('')
const emailError = ref('')
const passwordError = ref('')
const passwordConfirmationError = ref('')

watch(
  () => [email.value, password.value, passwordConfirmation.value, displayName.value],
  () => {
    emailError.value = ''
    passwordError.value = ''
    passwordConfirmationError.value = ''
    submitError.value = ''
  },
)

const passwordType = computed(() => (showPassword.value ? 'text' : 'password'))
const passwordConfirmationType = computed(() =>
  showPasswordConfirmation.value ? 'text' : 'password',
)

const validate = () => {
  emailError.value = ''
  passwordError.value = ''
  passwordConfirmationError.value = ''

  if (!email.value.trim()) {
    emailError.value = '请输入邮箱'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
    emailError.value = '请输入正确的邮箱'
  }

  if (!password.value) {
    passwordError.value = '请输入密码'
  } else if (password.value.length < 8) {
    passwordError.value = '密码至少 8 位'
  }

  if (!passwordConfirmation.value) {
    passwordConfirmationError.value = '请再次输入密码'
  } else if (passwordConfirmation.value !== password.value) {
    passwordConfirmationError.value = '两次输入的密码不一致'
  }

  return !emailError.value && !passwordError.value && !passwordConfirmationError.value
}

const submit = async () => {
  if (!validate()) {
    return
  }

  submitting.value = true

  try {
    const { ok, result } = await register({
      email: email.value.trim(),
      password: password.value,
      passwordConfirmation: passwordConfirmation.value,
      displayName: displayName.value.trim(),
      next: props.next ?? '',
    })

    if (!ok || result.code !== 0) {
      submitError.value = result.msg || '注册失败，请稍后重试'
      return
    }

    const redirect = result.data?.redirect || '/login?toast=register-success'
    window.location.href = redirect
  } catch {
    submitError.value = '网络异常，请稍后重试'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form class="register-form" @submit.prevent="submit">

    <div class="register-form__heading">
      <h1>创建账户</h1>
      <p>注册后默认使用免费会员配额与类型限制</p>
    </div>

    <p v-if="submitError" class="register-form__alert">{{ submitError }}</p>

    <BaseInput
      v-model="email"
      label="邮箱"
      name="email"
      type="email"
      autocomplete="email"
      placeholder="请输入邮箱"
      required
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

    <BaseInput
      v-model="displayName"
      label="昵称"
      name="display_name"
      type="text"
      autocomplete="nickname"
      placeholder="请输入昵称（可选）"
      maxlength="64"
    >
      <template #prefix>
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path
            d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"
            fill="none"
            stroke="currentColor"
            stroke-width="1.7"
          />
          <path
            d="M4.5 19.5a7.5 7.5 0 0 1 15 0"
            fill="none"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="1.7"
          />
        </svg>
      </template>
    </BaseInput>

    <BaseInput
      v-model="password"
      label="密码"
      name="password"
      :type="passwordType"
      autocomplete="new-password"
      placeholder="请输入至少 8 位密码"
      required
      minlength="8"
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
          class="register-form__toggle"
          type="button"
          :aria-label="showPassword ? '隐藏密码' : '显示密码'"
          @click="showPassword = !showPassword"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true">
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
        </button>
      </template>
    </BaseInput>

    <BaseInput
      v-model="passwordConfirmation"
      label="确认密码"
      name="password_confirmation"
      :type="passwordConfirmationType"
      autocomplete="new-password"
      placeholder="请再次输入密码"
      required
      minlength="8"
      :error="passwordConfirmationError"
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
          class="register-form__toggle"
          type="button"
          :aria-label="showPasswordConfirmation ? '隐藏密码' : '显示密码'"
          @click="showPasswordConfirmation = !showPasswordConfirmation"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true">
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
        </button>
      </template>
    </BaseInput>

    <BaseButton type="submit" block :loading="submitting">
      <span>{{ submitting ? '注册中...' : '注册' }}</span>
    </BaseButton>
  </form>
</template>

<style scoped>
.register-form {
  display: grid;
  gap: 22px;
}

.register-form__heading {
  text-align: center;
}

.register-form__heading h1 {
  margin: 0;
  color: #1e2a3f;
  font-size: 24px;
  font-weight: 800;
}

.register-form__heading p {
  margin: 12px 0 0;
  color: #8f9aae;
  font-size: 14px;
}

.register-form__alert {
  margin: 0;
  padding: 12px 14px;
  border-radius: 12px;
  color: #bf4545;
  background: rgba(227, 107, 107, 0.12);
  font-size: 13px;
}

.register-form__toggle {
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

.register-form__toggle svg,
.register-form :deep(.base-input__prefix svg) {
  width: 20px;
  height: 20px;
}
</style>

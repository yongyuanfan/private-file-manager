<script setup lang="ts">
defineOptions({
  name: 'RegisterView',
})

import { computed } from 'vue'
import { useRoute } from 'vue-router'

import AppBrand from '@/components/AppBrand.vue'
import AuthPageLayout from '@/components/AuthPageLayout.vue'

import RegisterForm from './components/RegisterForm.vue'

const route = useRoute()

const next = computed(() => {
  const value = route.query.next
  return typeof value === 'string' ? value : ''
})
</script>

<template>
  <AuthPageLayout
    footer-text="已有账户?"
    footer-link-label="登录"
    :footer-link-href="`/login${next ? `?next=${encodeURIComponent(next)}` : ''}`"
  >
    <template #brand>
      <AppBrand />
    </template>

    <RegisterForm :next="next" />
  </AuthPageLayout>
</template>

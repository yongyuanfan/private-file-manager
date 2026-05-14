<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import BaseButton from '@/components/BaseButton.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const loggingOut = ref(false)

const handleLogout = async () => {
  if (loggingOut.value) {
    return
  }

  loggingOut.value = true

  try {
    await authStore.logout()
    await router.push('/login?toast=logout-success')
  } finally {
    loggingOut.value = false
  }
}
</script>

<template>
  <main class="dashboard-page">
    <section class="dashboard-page__hero">
      <div class="dashboard-page__header">
        <div>
          <h1>仪表盘</h1>
          <p>这里是登录后可访问的页面，后续可以在这里继续接入应用核心功能。</p>
        </div>

        <BaseButton variant="secondary" :disabled="loggingOut" @click="handleLogout">
          <span>{{ loggingOut ? '退出中...' : '退出登录' }}</span>
        </BaseButton>
      </div>
    </section>
  </main>
</template>

<style scoped>
.dashboard-page {
  min-height: 100vh;
  padding: 32px;
  background: #f6f8fc;
}

.dashboard-page__hero {
  max-width: 960px;
  margin: 0 auto;
  padding: 32px;
  border: 1px solid #e3e8f1;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(43, 57, 96, 0.08);
}

.dashboard-page__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
}

.dashboard-page__hero h1 {
  margin: 0;
  color: #1e2a3f;
  font-size: 28px;
  font-weight: 800;
}

.dashboard-page__hero p {
  margin: 12px 0 0;
  color: #72809b;
  font-size: 15px;
  line-height: 1.7;
}

@media (max-width: 640px) {
  .dashboard-page {
    padding: 16px;
  }

  .dashboard-page__hero {
    padding: 24px;
  }

  .dashboard-page__header {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>

<script setup lang="ts">
import { storeToRefs } from 'pinia'

import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()
const { items } = storeToRefs(toastStore)
</script>

<template>
  <Teleport to="body">
    <div v-if="items.length" class="toast-container" aria-live="polite" aria-atomic="true">
      <div v-for="item in items" :key="item.id" class="toast" :class="`toast--${item.type}`">
        <span>{{ item.message }}</span>
        <button class="toast__close" type="button" aria-label="关闭提示" @click="toastStore.remove(item.id)">
          ×
        </button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 2000;
  display: grid;
  gap: 12px;
  width: min(360px, calc(100vw - 32px));
}

.toast {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border: 1px solid #d9e5da;
  border-radius: 16px;
  color: #215c37;
  background: rgba(243, 252, 246, 0.98);
  box-shadow: 0 18px 44px rgba(28, 45, 33, 0.14);
  backdrop-filter: blur(8px);
}

.toast__close {
  flex: none;
  width: 28px;
  height: 28px;
  border: 0;
  border-radius: 999px;
  color: inherit;
  background: transparent;
  font-size: 20px;
  line-height: 1;
  cursor: pointer;
}

.toast__close:hover {
  background: rgba(33, 92, 55, 0.08);
}

@media (max-width: 640px) {
  .toast-container {
    top: 12px;
    right: 12px;
    left: 12px;
    width: auto;
  }
}
</style>

<script setup lang="ts">
import { storeToRefs } from 'pinia'

import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
const { appName } = storeToRefs(appStore)
</script>

<template>
  <div class="app-brand">
    <div class="app-brand__mark" aria-hidden="true">
      <svg viewBox="0 0 48 48" role="img">
        <rect x="0" y="0" width="48" height="48" rx="16" fill="url(#brand-gradient)" />
        <circle cx="17" cy="19" r="5" fill="#ffffff" />
        <circle cx="31" cy="19" r="5" fill="#ffffff" />
        <circle cx="17" cy="19" r="2.2" fill="#ff8b16" />
        <circle cx="31" cy="19" r="2.2" fill="#ff8b16" />
        <path d="M14 31V24H18V31L21 28L24 31L27 28L30 31V24H34V31" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
        <defs>
          <linearGradient id="brand-gradient" x1="8" y1="6" x2="38" y2="42" gradientUnits="userSpaceOnUse">
            <stop stop-color="#ffb01f" />
            <stop offset="1" stop-color="#ff6d0c" />
          </linearGradient>
        </defs>
      </svg>
    </div>
    <span class="app-brand__name">{{ appName }}</span>
  </div>
</template>

<style scoped>
.app-brand {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.app-brand__mark {
  width: 54px;
  height: 54px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 16px 34px rgba(33, 46, 79, 0.15);
}

.app-brand__mark svg {
  width: 46px;
  height: 46px;
}

.app-brand__name {
  color: #60708c;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.03em;
}

@media (max-width: 640px) {
  .app-brand__name {
    font-size: 24px;
  }
}
</style>

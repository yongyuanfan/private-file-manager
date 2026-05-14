<script setup lang="ts">
defineProps<{
  type?: 'button' | 'submit' | 'reset'
  variant?: 'primary' | 'secondary'
  loading?: boolean
  disabled?: boolean
  block?: boolean
}>()
</script>

<template>
  <button
    class="base-button"
    :class="[
      `base-button--${variant ?? 'primary'}`,
      { 'base-button--block': block, 'is-loading': loading },
    ]"
    :type="type ?? 'button'"
    :disabled="disabled || loading"
  >
    <slot />
  </button>
</template>

<style scoped>
.base-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 48px;
  padding: 0 20px;
  border: 1px solid transparent;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 600;
  line-height: 1;
  cursor: pointer;
  transition:
    transform 0.16s ease,
    box-shadow 0.16s ease,
    background-color 0.16s ease,
    border-color 0.16s ease;
}

.base-button:disabled {
  cursor: not-allowed;
  opacity: 0.72;
}

.base-button:not(:disabled):hover {
  transform: translateY(-1px);
}

.base-button--block {
  width: 100%;
}

.base-button--primary {
  color: #ffffff;
  background: #222d4d;
  box-shadow: 0 10px 24px rgba(34, 45, 77, 0.22);
}

.base-button--primary:not(:disabled):hover {
  background: #1c2745;
}

.base-button--secondary {
  color: #39465f;
  background: #ffffff;
  border-color: #d8deea;
  box-shadow: 0 8px 20px rgba(39, 52, 89, 0.08);
}

.base-button--secondary:not(:disabled):hover {
  border-color: #c8d1e4;
}

.is-loading {
  pointer-events: none;
}
</style>

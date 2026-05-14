<script setup lang="ts">
defineProps<{
  modelValue: string
  label: string
  name?: string
  type?: string
  placeholder?: string
  autocomplete?: string
  maxlength?: number | string
  minlength?: number | string
  required?: boolean
  autofocus?: boolean
  error?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <label class="base-input">
    <span class="base-input__label">{{ label }}</span>
    <span class="base-input__control" :class="{ 'has-error': error }">
      <span class="base-input__prefix">
        <slot name="prefix" />
      </span>
      <input
        class="base-input__field"
        :name="name"
        :type="type ?? 'text'"
        :value="modelValue"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :maxlength="maxlength"
        :minlength="minlength"
        :required="required"
        :autofocus="autofocus"
        @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
      <span v-if="$slots.suffix" class="base-input__suffix">
        <slot name="suffix" />
      </span>
    </span>
    <span v-if="error" class="base-input__error">{{ error }}</span>
  </label>
</template>

<style scoped>
.base-input {
  display: grid;
  gap: 10px;
}

.base-input__label {
  color: #2f3a4d;
  font-size: 14px;
  font-weight: 600;
}

.base-input__control {
  display: flex;
  align-items: center;
  min-height: 48px;
  padding: 0 14px;
  border: 1px solid #d8deea;
  border-radius: 14px;
  background: #ffffff;
  transition:
    border-color 0.16s ease,
    box-shadow 0.16s ease;
}

.base-input__control:focus-within {
  border-color: #8c9aca;
  box-shadow: 0 0 0 4px rgba(140, 154, 202, 0.14);
}

.base-input__control.has-error {
  border-color: #e36b6b;
  box-shadow: 0 0 0 4px rgba(227, 107, 107, 0.1);
}

.base-input__prefix,
.base-input__suffix {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #98a3b7;
}

.base-input__prefix {
  margin-right: 10px;
}

.base-input__suffix {
  margin-left: 10px;
}

.base-input__field {
  width: 100%;
  padding: 0;
  border: 0;
  outline: 0;
  color: #27334a;
  font-size: 15px;
  background: transparent;
}

.base-input__field::placeholder {
  color: #a7afbf;
}

.base-input__error {
  color: #d24f4f;
  font-size: 12px;
}
</style>

import { ref } from 'vue'
import { defineStore } from 'pinia'

type ToastType = 'success'

type ToastItem = {
  id: number
  message: string
  type: ToastType
}

const TOAST_DURATION = 3000

export const useToastStore = defineStore('toast', () => {
  const items = ref<ToastItem[]>([])
  let nextId = 1

  const remove = (id: number) => {
    items.value = items.value.filter((item) => item.id !== id)
  }

  const show = (message: string, type: ToastType = 'success') => {
    const id = nextId++
    items.value.push({ id, message, type })

    window.setTimeout(() => {
      remove(id)
    }, TOAST_DURATION)
  }

  return {
    items,
    show,
    remove,
  }
})

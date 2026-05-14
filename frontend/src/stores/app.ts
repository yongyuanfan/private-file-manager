import { ref } from 'vue'
import { defineStore } from 'pinia'

export const useAppStore = defineStore('app', () => {
  const appName = ref('私有文件管理')

  const buildPageTitle = (pageTitle?: string) => {
    const title = pageTitle?.trim()
    return title ? `${title} - ${appName.value}` : appName.value
  }

  return {
    appName,
    buildPageTitle,
  }
})

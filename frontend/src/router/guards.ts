import type { Router } from 'vue-router'

import { useAppStore } from '@/stores/app'

export const setupRouterGuards = (router: Router) => {
  router.afterEach((to) => {
    const appStore = useAppStore()
    const pageTitle = typeof to.meta.title === 'string' ? to.meta.title : ''

    document.title = appStore.buildPageTitle(pageTitle)
  })
}

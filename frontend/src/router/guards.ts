import type { Router } from 'vue-router'

import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'

export const setupRouterGuards = (router: Router) => {
  router.beforeEach(async (to) => {
    const authStore = useAuthStore()
    const requiresAuth = to.meta.requiresAuth === true
    const guestOnly = to.meta.guestOnly === true
    const user = await authStore.ensureAuth()

    if (requiresAuth && !user) {
      return {
        path: '/login',
        query: {
          next: to.fullPath,
        },
      }
    }

    if (guestOnly && user) {
      return {
        path: '/dashboard',
      }
    }

    return true
  })

  router.afterEach((to) => {
    const appStore = useAppStore()
    const pageTitle = typeof to.meta.title === 'string' ? to.meta.title : ''

    document.title = appStore.buildPageTitle(pageTitle)
  })
}

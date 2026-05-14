import type { Router } from 'vue-router'

import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const toastMessages: Record<string, string> = {
  'login-success': '登录成功',
  'logout-success': '退出成功',
  'register-success': '注册成功，请登录',
}

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
    const toastStore = useToastStore()
    const pageTitle = typeof to.meta.title === 'string' ? to.meta.title : ''
    const toast = typeof to.query.toast === 'string' ? to.query.toast : ''

    document.title = appStore.buildPageTitle(pageTitle)

    if (!toast || !toastMessages[toast]) {
      return
    }

    toastStore.show(toastMessages[toast])

    const nextQuery = { ...to.query }
    delete nextQuery.toast

    void router.replace({
      path: to.path,
      query: nextQuery,
      hash: to.hash,
    })
  })
}

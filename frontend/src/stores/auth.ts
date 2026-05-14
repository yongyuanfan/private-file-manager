import { ref } from 'vue'
import { defineStore } from 'pinia'

import { getCurrentUser, logout as logoutRequest, type AuthUser } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const checked = ref(false)
  const checking = ref(false)

  const setUser = (nextUser: AuthUser | null) => {
    user.value = nextUser
    checked.value = true
  }

  const clearAuth = () => {
    user.value = null
    checked.value = true
  }

  const ensureAuth = async () => {
    if (checked.value) {
      return user.value
    }

    if (checking.value) {
      return user.value
    }

    checking.value = true

    try {
      const { ok, result } = await getCurrentUser()

      if (!ok) {
        clearAuth()
        return null
      }

      if (result.code !== 0 || !result.data?.user) {
        clearAuth()
        return null
      }

      setUser(result.data.user)
      return user.value
    } catch {
      clearAuth()
      return null
    } finally {
      checking.value = false
    }
  }

  const logout = async () => {
    try {
      const { ok, result } = await logoutRequest()

      if (ok) {
        if (result.code === 0) {
          clearAuth()
          return true
        }
      }
    } catch {
    }

    clearAuth()
    return false
  }

  return {
    user,
    checked,
    checking,
    setUser,
    clearAuth,
    ensureAuth,
    logout,
  }
})

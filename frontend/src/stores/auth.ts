import { ref } from 'vue'
import { defineStore } from 'pinia'

type AuthUser = {
  id: number
  email: string
  display_name: string
}

type MeResponse = {
  code: number
  msg: string
  data?: {
    user?: AuthUser
  }
}

type BasicResponse = {
  code: number
  msg: string
}

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
      const response = await fetch('/api/v1/auth/me', {
        method: 'GET',
        headers: {
          Accept: 'application/json',
        },
        credentials: 'include',
      })

      if (!response.ok) {
        clearAuth()
        return null
      }

      const result = (await response.json()) as MeResponse
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
      const response = await fetch('/api/v1/auth/logout', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
        },
        credentials: 'include',
      })

      if (response.ok) {
        const result = (await response.json()) as BasicResponse
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

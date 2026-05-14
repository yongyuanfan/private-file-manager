import { request, requestForm } from '@/api/http'

export type AuthUser = {
  id: number
  email: string
  display_name: string
}

type AuthPayload = {
  redirect?: string
  user?: AuthUser
}

type MePayload = {
  user?: AuthUser
}

export type LoginParams = {
  email: string
  password: string
  next: string
}

export type RegisterParams = {
  email: string
  password: string
  passwordConfirmation: string
  displayName: string
  next: string
}

const buildLoginBody = (params: LoginParams) => {
  const body = new URLSearchParams()
  body.set('email', params.email)
  body.set('password', params.password)
  body.set('next', params.next)
  return body
}

const buildRegisterBody = (params: RegisterParams) => {
  const body = new URLSearchParams()
  body.set('email', params.email)
  body.set('password', params.password)
  body.set('password_confirmation', params.passwordConfirmation)
  body.set('display_name', params.displayName)
  body.set('next', params.next)
  return body
}

export const login = (params: LoginParams) => {
  return requestForm<AuthPayload>('/api/v1/auth/login', buildLoginBody(params))
}

export const getCurrentUser = () => {
  return request<MePayload>('/api/v1/auth/me')
}

export const logout = () => {
  return request('/api/v1/auth/logout', {
    method: 'POST',
  })
}

export const register = (params: RegisterParams) => {
  return requestForm<AuthPayload>('/api/v1/auth/register', buildRegisterBody(params))
}

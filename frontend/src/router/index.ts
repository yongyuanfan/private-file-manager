import { createRouter, createWebHistory } from 'vue-router'

import { setupRouterGuards } from './guards'

import LoginView from '@/views/login/index.vue'
import RegisterView from '@/views/register/index.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: (to) => ({ path: '/login', query: to.query }),
    },
    {
      path: '/login',
      name: 'login',
      meta: {
        title: '登录',
      },
      component: LoginView,
    },
    {
      path: '/register',
      name: 'register',
      meta: {
        title: '注册',
      },
      component: RegisterView,
    },
  ],
})

setupRouterGuards(router)

export default router

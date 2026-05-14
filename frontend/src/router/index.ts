import { createRouter, createWebHistory } from 'vue-router'

import { setupRouterGuards } from './guards'

import LoginView from '@/views/login/index.vue'

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
  ],
})

setupRouterGuards(router)

export default router

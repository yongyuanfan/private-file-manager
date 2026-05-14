<script setup lang="ts">
defineProps<{
  footerText: string
  footerLinkLabel: string
  footerLinkHref: string
}>()
</script>

<template>
  <main class="auth-page">
    <div class="auth-page__grid" aria-hidden="true" />
    <section class="auth-page__content">
      <slot name="brand" />

      <div class="auth-page__card">
        <slot />
      </div>

      <p class="auth-page__footer">
        {{ footerText }}
        <a :href="footerLinkHref">{{ footerLinkLabel }}</a>
      </p>

      <p class="auth-page__copyright">© 2026 Private File Manager. All rights reserved.</p>
    </section>
  </main>
</template>

<style scoped>
:global(body) {
  margin: 0;
  font-family:
    Inter,
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    sans-serif;
  background: #f6f8fc;
}

:global(*) {
  box-sizing: border-box;
}

.auth-page {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
  background:
    radial-gradient(circle at top, rgba(255, 255, 255, 0.88), rgba(246, 248, 252, 0.92)),
    #f6f8fc;
}

.auth-page__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(222, 229, 240, 0.7) 1px, transparent 1px),
    linear-gradient(90deg, rgba(222, 229, 240, 0.7) 1px, transparent 1px);
  background-size: 62px 62px;
  mask-image: radial-gradient(circle at center, black 45%, transparent 100%);
}

.auth-page__content {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 28px;
  padding: 32px 16px;
}

.auth-page__card {
  width: min(100%, 450px);
  padding: 36px 34px 32px;
  border: 1px solid rgba(227, 232, 241, 0.95);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 24px 60px rgba(43, 57, 96, 0.12);
}

.auth-page__footer,
.auth-page__copyright {
  margin: 0;
  text-align: center;
}

.auth-page__footer {
  color: #98a3b7;
  font-size: 14px;
}

.auth-page__footer a {
  margin-left: 8px;
  color: #556684;
  font-weight: 700;
  text-decoration: none;
}

.auth-page__copyright {
  color: #b1b9c8;
  font-size: 13px;
}

@media (max-width: 640px) {
  .auth-page__content {
    gap: 24px;
  }

  .auth-page__card {
    padding: 28px 20px 24px;
    border-radius: 20px;
  }
}
</style>

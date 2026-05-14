<?php

namespace app\service;

use app\model\MembershipPlan;
use app\model\User;
use support\Request;
use Throwable;

class AuthRegisterService
{
    public function attempt(Request $request): array
    {
        if ($request->authUser !== null) {
            return [
                'ok' => false,
                'msg' => '当前已登录',
                'redirect' => '/home',
            ];
        }

        if (!(bool) config('app.registration_open', true)) {
            return [
                'ok' => false,
                'msg' => '注册已关闭',
                'redirect' => '/login?error=' . rawurlencode('注册已关闭'),
            ];
        }

        $email = strtolower(trim((string) $request->post('email', '')));
        $password = (string) $request->post('password', '');
        $password2 = (string) $request->post('password_confirmation', '');
        $displayName = trim((string) $request->post('display_name', ''));
        $next = $this->sanitizeNext((string) $request->post('next', ''));

        if ($email === '' || $password === '') {
            return ['ok' => false, 'msg' => '请填写邮箱和密码'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => '邮箱格式不正确'];
        }

        if (strlen($password) < 8) {
            return ['ok' => false, 'msg' => '密码至少 8 位'];
        }

        if ($password !== $password2) {
            return ['ok' => false, 'msg' => '两次输入的密码不一致'];
        }

        if (User::query()->where('email', $email)->exists()) {
            return ['ok' => false, 'msg' => '该邮箱已被注册'];
        }

        $planId = MembershipPlan::query()->where('code', 'free')->where('is_active', true)->value('id');
        if ($planId === null) {
            return ['ok' => false, 'msg' => '系统未配置默认会员等级，请联系管理员'];
        }

        try {
            $user = User::query()->create([
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'display_name' => $displayName !== '' ? substr($displayName, 0, 64) : null,
                'plan_id' => (int) $planId,
                'plan_expires_at' => null,
                'status' => 'active',
            ]);
        } catch (Throwable) {
            return ['ok' => false, 'msg' => '注册失败，请稍后重试'];
        }

        $user->load('membershipPlan');

        return [
            'ok' => true,
            'msg' => '注册成功，请登录',
            'user' => $user,
            'next' => $next,
            'redirect' => $this->buildLoginRedirect($next),
        ];
    }

    private function sanitizeNext(string $next): string
    {
        $next = trim($next);
        if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//')) {
            return '/home';
        }

        return $next;
    }

    private function buildLoginRedirect(string $next): string
    {
        $query = 'success=' . rawurlencode('registered');
        if ($next !== '/home') {
            $query .= '&next=' . rawurlencode($next);
        }

        return '/login?' . $query;
    }
}

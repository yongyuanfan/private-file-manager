<?php

namespace app\service;

use app\model\MembershipPlan;
use app\model\User;
use Carbon\Carbon;
use support\Request;

class AuthLoginService
{
    /**
     * @return array{ok: bool, msg: string, user?: User, next?: string}
     */
    public function attempt(Request $request, string $email, string $password, string $next = ''): array
    {
        $email = strtolower(trim($email));
        $password = (string) $password;
        $next = $this->sanitizeNext($next);

        if ($request->authUser !== null) {
            return [
                'ok' => true,
                'msg' => 'ok',
                'user' => $request->authUser,
                'next' => $next,
            ];
        }

        if ($email === '' || $password === '') {
            return ['ok' => false, 'msg' => '请填写邮箱和密码'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => '邮箱格式不正确'];
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null || !password_verify($password, $user->password_hash)) {
            return ['ok' => false, 'msg' => '邮箱或密码错误'];
        }

        if (!$user->isActive()) {
            return ['ok' => false, 'msg' => '账号已禁用'];
        }

        $this->syncExpiredPlan($user);

        $user->last_login_at = Carbon::now();
        $ip = $request->connection?->getRemoteIp() ?? '';
        $user->last_login_ip = substr($ip, 0, 45);
        $user->save();
        $user->load('membershipPlan');

        return [
            'ok' => true,
            'msg' => 'ok',
            'user' => $user,
            'next' => $next,
        ];
    }

    public function sanitizeNext(string $next): string
    {
        $next = trim($next);
        if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//')) {
            return '/home';
        }

        return $next;
    }

    private function syncExpiredPlan(User $user): void
    {
        if ($user->plan_expires_at === null || $user->plan_expires_at->gte(Carbon::now())) {
            return;
        }

        $freeId = MembershipPlan::query()->where('code', 'free')->where('is_active', true)->value('id');
        if ($freeId === null) {
            return;
        }

        $user->plan_id = (int) $freeId;
        $user->plan_expires_at = null;
        $user->save();
    }
}

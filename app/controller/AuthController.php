<?php

namespace app\controller;

use app\middleware\GuestOnly;
use app\middleware\RequireLogin;
use app\model\MembershipPlan;
use app\model\User;
use app\service\AuthTokenService;
use Carbon\Carbon;
use support\annotation\Middleware as MiddlewareAttr;
use support\annotation\route\Route;
use support\Request;
use support\Response;
use Throwable;
use function json;
use function redirect;
use function view;

class AuthController
{
    #[Route('/login', 'GET')]
    #[MiddlewareAttr(GuestOnly::class)]
    public function showLogin(Request $request): Response
    {
        return view('auth/login', [
            'next' => $this->sanitizeNext((string) $request->get('next', '')),
            'error' => (string) $request->get('error', ''),
            'registration_open' => $this->isRegistrationOpen(),
        ]);
    }

    #[Route('/login', 'POST')]
    public function login(Request $request): Response
    {
        if ($request->authUser !== null) {
            return redirect('/home');
        }

        $email = strtolower(trim((string) $request->post('email', '')));
        $password = (string) $request->post('password', '');
        $next = $this->sanitizeNext((string) $request->post('next', ''));

        if ($email === '' || $password === '') {
            return redirect($this->loginFailUrl('请填写邮箱和密码', $next));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect($this->loginFailUrl('邮箱格式不正确', $next));
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null || !password_verify($password, $user->password_hash)) {
            return redirect($this->loginFailUrl('邮箱或密码错误', $next));
        }

        if (!$user->isActive()) {
            return redirect($this->loginFailUrl('账号已禁用', $next));
        }

        $this->syncExpiredPlan($user);

        $user->last_login_at = Carbon::now();
        $ip = $request->connection?->getRemoteIp() ?? '';
        $user->last_login_ip = substr($ip, 0, 45);
        $user->save();

        [$token] = AuthTokenService::createSession($user, $request);

        return AuthTokenService::attachAuthCookie(redirect($next), $token);
    }

    #[Route('/register', 'GET')]
    #[MiddlewareAttr(GuestOnly::class)]
    public function showRegister(): Response
    {
        if (!$this->isRegistrationOpen()) {
            return redirect($this->registrationClosedLoginUrl());
        }

        return view('auth/register', [
            'error' => '',
        ]);
    }

    #[Route('/register', 'POST')]
    public function register(Request $request): Response
    {
        if ($request->authUser !== null) {
            return redirect('/home');
        }

        if (!$this->isRegistrationOpen()) {
            return redirect($this->registrationClosedLoginUrl());
        }

        $email = strtolower(trim((string) $request->post('email', '')));
        $password = (string) $request->post('password', '');
        $password2 = (string) $request->post('password_confirmation', '');
        $displayName = trim((string) $request->post('display_name', ''));

        if ($email === '' || $password === '') {
            return view('auth/register', ['error' => '请填写邮箱和密码']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return view('auth/register', ['error' => '邮箱格式不正确']);
        }

        if (strlen($password) < 8) {
            return view('auth/register', ['error' => '密码至少 8 位']);
        }

        if ($password !== $password2) {
            return view('auth/register', ['error' => '两次输入的密码不一致']);
        }

        if (User::query()->where('email', $email)->exists()) {
            return view('auth/register', ['error' => '该邮箱已被注册']);
        }

        $planId = MembershipPlan::query()->where('code', 'free')->where('is_active', true)->value('id');
        if ($planId === null) {
            return view('auth/register', ['error' => '系统未配置默认会员等级，请联系管理员']);
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
            return view('auth/register', ['error' => '注册失败，请稍后重试']);
        }

        $user->load('membershipPlan');
        [$token] = AuthTokenService::createSession($user, $request);

        return AuthTokenService::attachAuthCookie(redirect('/home'), $token);
    }

    #[Route('/logout', 'POST')]
    #[MiddlewareAttr(RequireLogin::class)]
    public function logout(Request $request): Response
    {
        AuthTokenService::revokeCurrent($request);

        return AuthTokenService::clearAuthCookie(redirect('/login'));
    }

    private function sanitizeNext(string $next): string
    {
        $next = trim($next);
        if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//')) {
            return '/home';
        }

        return $next;
    }

    private function loginFailUrl(string $message, string $next): string
    {
        $q = 'error=' . rawurlencode($message);
        if ($next !== '/home') {
            $q .= '&next=' . rawurlencode($next);
        }

        return '/login?' . $q;
    }

    private function isRegistrationOpen(): bool
    {
        return (bool) config('app.registration_open', true);
    }

    private function registrationClosedLoginUrl(): string
    {
        return '/login?error=' . rawurlencode('注册已关闭');
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
        $user->load('membershipPlan');
    }
}

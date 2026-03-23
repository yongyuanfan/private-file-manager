<?php

namespace app\service;

use app\model\User;
use app\model\UserSession;
use Carbon\Carbon;
use support\Request;
use Webman\Http\Response;

class AuthTokenService
{
    public const COOKIE_NAME = 'oss_token';

    public static function cookieMaxAge(): int
    {
        return (int) config('session.lifetime', 7 * 24 * 60 * 60);
    }

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public static function resolveUserFromRequest(Request $request): ?User
    {
        $raw = (string) $request->cookie(self::COOKIE_NAME, '');
        if ($raw === '' || strlen($raw) !== 64 || !ctype_xdigit($raw)) {
            return null;
        }

        $hash = self::hashToken($raw);
        $now = Carbon::now();

        $row = UserSession::query()
            ->where('token_hash', $hash)
            ->where('expires_at', '>', $now)
            ->first();

        if ($row === null) {
            return null;
        }

        $user = User::query()->with('membershipPlan')->find($row->user_id);
        if ($user === null || !$user->isActive()) {
            return null;
        }

        $row->last_seen_at = $now;
        $row->save();

        return $user;
    }

    /**
     * @return array{0: string, 1: UserSession} raw token, db row
     */
    public static function createSession(User $user, Request $request): array
    {
        $raw = bin2hex(random_bytes(32));
        $now = Carbon::now();
        $expires = $now->copy()->addSeconds(self::cookieMaxAge());

        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => self::hashToken($raw),
            'user_agent' => self::truncate($request->header('user-agent', '') ?? '', 255),
            'ip' => self::clientIp($request),
            'expires_at' => $expires,
            'created_at' => $now,
            'last_seen_at' => $now,
        ]);

        return [$raw, $session];
    }

    public static function attachAuthCookie(Response $response, string $rawToken): Response
    {
        $cfg = config('session', []);
        $path = $cfg['cookie_path'] ?? '/';
        $domain = $cfg['domain'] ?? '';
        $secure = (bool) ($cfg['secure'] ?? false);
        $httpOnly = (bool) ($cfg['http_only'] ?? true);
        $sameSite = (string) ($cfg['same_site'] ?? 'Lax');
        if ($sameSite === '') {
            $sameSite = 'Lax';
        }

        return $response->cookie(
            self::COOKIE_NAME,
            $rawToken,
            self::cookieMaxAge(),
            $path,
            $domain,
            $secure,
            $httpOnly,
            $sameSite
        );
    }

    public static function clearAuthCookie(Response $response): Response
    {
        $cfg = config('session', []);
        $path = $cfg['cookie_path'] ?? '/';
        $domain = $cfg['domain'] ?? '';
        $secure = (bool) ($cfg['secure'] ?? false);
        $httpOnly = (bool) ($cfg['http_only'] ?? true);
        $sameSite = (string) ($cfg['same_site'] ?? 'Lax');
        if ($sameSite === '') {
            $sameSite = 'Lax';
        }

        return $response->cookie(self::COOKIE_NAME, '', 0, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public static function revokeCurrent(Request $request): void
    {
        $raw = (string) $request->cookie(self::COOKIE_NAME, '');
        if ($raw === '' || strlen($raw) !== 64 || !ctype_xdigit($raw)) {
            return;
        }
        UserSession::query()->where('token_hash', self::hashToken($raw))->delete();
    }

    private static function truncate(string $s, int $max): string
    {
        if (strlen($s) <= $max) {
            return $s;
        }

        return substr($s, 0, $max);
    }

    private static function clientIp(Request $request): string
    {
        $conn = $request->connection;
        if ($conn !== null) {
            $ip = $conn->getRemoteIp();
            if ($ip !== '') {
                return substr($ip, 0, 45);
            }
        }

        return '';
    }
}

<?php

namespace app\service;

use app\model\FileShare;
use app\model\FileShareAccessLog;
use app\model\UserUpload;
use Carbon\Carbon;
use support\Request;

class FileShareService
{
    public const COOKIE_PREFIX = 'xinkin_share_';

    public static function cookieNameForShare(int $shareId): string
    {
        return self::COOKIE_PREFIX . $shareId;
    }

    public static function log(int $fileShareId, string $action, ?string $detail, Request $request): void
    {
        $ip = $request->connection?->getRemoteIp() ?? '';
        $ip = substr((string) $ip, 0, 45);
        $ua = (string) $request->header('user-agent', '');
        $ua = substr($ua, 0, 512);

        try {
            FileShareAccessLog::query()->create([
                'file_share_id' => $fileShareId,
                'action' => $action,
                'detail' => $detail,
                'ip' => $ip !== '' ? $ip : null,
                'user_agent' => $ua !== '' ? $ua : null,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable) {
            // 审计失败不阻断主流程
        }
    }

    /**
     * 校验「已输入访问密码」Cookie；仅适用于带 password_hash 的分享。
     */
    public static function verifyUnlockCookie(Request $request, FileShare $share): bool
    {
        if (!$share->hasPassword()) {
            return true;
        }

        $name = self::cookieNameForShare((int) $share->id);
        $raw = (string) $request->cookie($name, '');
        if ($raw === '') {
            return false;
        }

        $parts = explode('.', $raw, 3);
        if (count($parts) !== 3 || $parts[0] !== 'v1') {
            return false;
        }

        $payloadB64 = $parts[1];
        $sig = $parts[2];
        $payload = self::base64UrlDecode($payloadB64);
        if ($payload === null) {
            return false;
        }

        $expected = self::signPayload($payload, $share);
        if (!hash_equals($expected, $sig)) {
            return false;
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return false;
        }

        $sid = (int) ($data['sid'] ?? 0);
        $exp = (int) ($data['exp'] ?? 0);
        if ($sid !== (int) $share->id || $exp <= Carbon::now()->timestamp) {
            return false;
        }

        if ($share->expires_at !== null && $share->expires_at->timestamp < $exp) {
            return false;
        }

        return true;
    }

    /**
     * @return array{value: string, max_age: int}
     */
    public static function makeUnlockCookieValue(FileShare $share): array
    {
        $now = Carbon::now();
        $cookieExp = $now->copy()->addDay()->timestamp;
        if ($share->expires_at !== null) {
            $cookieExp = min($cookieExp, $share->expires_at->timestamp);
        }

        $payload = json_encode(['sid' => (int) $share->id, 'exp' => $cookieExp], JSON_THROW_ON_ERROR);
        $sig = self::signPayload($payload, $share);
        $value = 'v1.' . self::base64UrlEncode($payload) . '.' . $sig;
        $maxAge = max(60, $cookieExp - $now->timestamp);

        return ['value' => $value, 'max_age' => $maxAge];
    }

    public static function signPayload(string $payload, FileShare $share): string
    {
        $secret = (string) config('app.share_link_secret', 'change-me-share-link-secret');
        $salt = $share->password_hash ?? '';

        return hash_hmac('sha256', $payload, $secret . "\0" . $salt);
    }

    public static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $b64): ?string
    {
        $b64 = strtr($b64, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        $out = base64_decode($b64, true);

        return $out === false ? null : $out;
    }

    /**
     * 原子占用一次浏览次数；成功返回 true。
     */
    public static function tryConsumeView(FileShare $share): bool
    {
        $now = Carbon::now();

        $q = FileShare::query()
            ->where('id', $share->id)
            ->whereNull('revoked_at')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function ($q) {
                $q->whereNull('max_views')->orWhereRaw('view_count < max_views');
            });

        return (int) $q->increment('view_count') > 0;
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function createRetention(UserUpload $upload, ?Carbon $expiresAt, ?Carbon $now = null): FileShare
    {
        $now = $now ?? Carbon::now();

        return FileShare::query()->create([
            'user_id' => $upload->user_id,
            'user_upload_id' => $upload->id,
            'purpose' => 'retention',
            'token' => self::generateToken(),
            'password_hash' => null,
            'max_views' => null,
            'view_count' => 0,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
            'created_at' => $now,
        ]);
    }

    public static function retentionForUpload(int $uploadId): ?FileShare
    {
        return FileShare::query()
            ->where('user_upload_id', $uploadId)
            ->where('purpose', 'retention')
            ->orderByDesc('id')
            ->first();
    }

    public static function retentionExpiredForUpload(int $uploadId, ?Carbon $now = null): bool
    {
        $retention = self::retentionForUpload($uploadId);
        if ($retention === null) {
            return false;
        }

        return $retention->isExpired($now ?? Carbon::now());
    }
}

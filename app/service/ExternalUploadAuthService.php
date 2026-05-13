<?php

namespace app\service;

use app\model\ExternalUploadAuthAccessLog;
use app\model\User;
use app\model\UserExternalUploadAuth;
use app\model\UserUpload;
use Carbon\Carbon;
use support\Request;

class ExternalUploadAuthService
{
    public function validateName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('请填写授权名称');
        }
        if (mb_strlen($name) > 100) {
            throw new \InvalidArgumentException('授权名称不能超过 100 个字符');
        }

        return $name;
    }

    public function validateRetentionTtlDays(string $ttlRaw): ?int
    {
        $ttlRaw = trim($ttlRaw);
        if ($ttlRaw === '') {
            return null;
        }

        $ttlDays = (int) $ttlRaw;
        if ($ttlDays < 1 || $ttlDays > 3650) {
            throw new \InvalidArgumentException('有效期天数须在 1～3650 之间，或留空表示永久');
        }

        return $ttlDays;
    }

    public function sanitizeDefaultSubdir(string $defaultSubdir): ?string
    {
        $policy = new UploadPolicyService();
        $subdir = $policy->sanitizeRelativeSubdir($defaultSubdir);
        if ($subdir === null) {
            throw new \InvalidArgumentException('默认子目录不合法');
        }

        return $subdir !== '' ? $subdir : null;
    }

    public function generatePlainToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function resolveFromRequest(Request $request): ?UserExternalUploadAuth
    {
        $header = trim((string) $request->header('authorization', ''));
        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        $rawToken = trim((string) ($matches[1] ?? ''));
        if ($rawToken === '') {
            return null;
        }

        return UserExternalUploadAuth::query()
            ->where('token_hash', $this->hashToken($rawToken))
            ->with('user.membershipPlan')
            ->first();
    }

    public function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public function createForUser(User $user, string $name, string $defaultSubdir, ?int $retentionTtlDays): array
    {
        $name = $this->validateName($name);
        $subdir = $this->sanitizeDefaultSubdir($defaultSubdir);

        $plainToken = $this->generatePlainToken();
        $auth = UserExternalUploadAuth::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => $this->hashToken($plainToken),
            'status' => 'active',
            'default_subdir' => $subdir,
            'retention_ttl_days' => $retentionTtlDays,
            'created_at' => Carbon::now(),
            'last_used_at' => null,
            'revoked_at' => null,
        ]);

        return ['auth' => $auth, 'plain_token' => $plainToken];
    }

    public function updateAuthorization(UserExternalUploadAuth $auth, string $name, string $defaultSubdir, ?int $retentionTtlDays): void
    {
        $auth->name = $this->validateName($name);
        $auth->default_subdir = $this->sanitizeDefaultSubdir($defaultSubdir);
        $auth->retention_ttl_days = $retentionTtlDays;
        $auth->save();
    }

    public function rotateToken(UserExternalUploadAuth $auth): string
    {
        $plainToken = $this->generatePlainToken();
        $auth->token_hash = $this->hashToken($plainToken);
        $auth->save();

        return $plainToken;
    }

    public function validateAuthorization(?UserExternalUploadAuth $auth): ?string
    {
        if ($auth === null) {
            return 'invalid';
        }
        if (!$auth->isActive()) {
            return 'disabled';
        }
        if ($auth->user === null || !$auth->user->isActive()) {
            return 'disabled';
        }

        return null;
    }

    public function effectiveSubdir(UserExternalUploadAuth $auth, string $requestSubdir): string
    {
        $policy = new UploadPolicyService();
        $candidate = trim($requestSubdir) !== '' ? $requestSubdir : (string) ($auth->default_subdir ?? '');
        $sanitized = $policy->sanitizeRelativeSubdir($candidate);

        return $sanitized ?? '';
    }

    public function retentionExpiresAt(UserExternalUploadAuth $auth, ?Carbon $now = null): ?Carbon
    {
        $now = $now ?? Carbon::now();

        return $auth->retentionExpiresAt($now);
    }

    public function touchLastUsed(UserExternalUploadAuth $auth, ?Carbon $now = null): void
    {
        $auth->last_used_at = $now ?? Carbon::now();
        $auth->save();
    }

    public function user(UserExternalUploadAuth $auth): User
    {
        return $auth->user;
    }

    public function log(?UserExternalUploadAuth $auth, string $action, ?string $detail, Request $request, ?UserUpload $upload = null): void
    {
        if ($auth === null) {
            return;
        }

        $ip = $request->connection?->getRemoteIp() ?? '';
        $ip = substr((string) $ip, 0, 45);
        $ua = (string) $request->header('user-agent', '');
        $ua = substr($ua, 0, 512);

        try {
            ExternalUploadAuthAccessLog::query()->create([
                'external_upload_auth_id' => (int) $auth->id,
                'user_upload_id' => $upload?->id,
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
}

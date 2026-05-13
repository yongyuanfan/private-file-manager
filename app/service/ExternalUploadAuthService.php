<?php

namespace app\service;

use app\model\User;
use app\model\UserExternalUploadAuth;
use Carbon\Carbon;
use support\Request;

class ExternalUploadAuthService
{
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
}

<?php

namespace app\service;

use app\model\MembershipPlan;
use app\model\MembershipPlanExtension;
use app\model\User;
use app\model\UserUpload;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use function str_starts_with;

class UploadPolicyService
{
    /**
     * 会员到期后按免费档规则限制（不修改当前请求外的持久数据，由登录流程同步 plan_id）。
     */
    public function effectivePlan(User $user): MembershipPlan
    {
        $now = Carbon::now();
        if ($user->plan_expires_at !== null && $user->plan_expires_at->lt($now)) {
            $free = MembershipPlan::query()->where('code', 'free')->where('is_active', 1)->first();
            if ($free !== null) {
                return $free;
            }
        }

        $plan = $user->membershipPlan;
        if ($plan === null || !$plan->is_active) {
            return MembershipPlan::query()->where('code', 'free')->where('is_active', 1)->firstOrFail();
        }

        return $plan;
    }

    public function countUploadsInQuota(User $user, MembershipPlan $plan): int
    {
        $q = UserUpload::query()->where('user_id', $user->id);
        $period = $plan->quota_period;

        if ($period === 'month') {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->addMonth()->startOfMonth();
            $q->where('created_at', '>=', $start)->where('created_at', '<', $end);
        } elseif ($period === 'day') {
            $start = Carbon::now()->startOfDay();
            $end = Carbon::now()->addDay()->startOfDay();
            $q->where('created_at', '>=', $start)->where('created_at', '<', $end);
        }

        return (int) $q->count();
    }

    /**
     * 有配置扩展名则白名单校验；无任何配置则视为不限制类型。
     */
    public function isExtensionAllowed(MembershipPlan $plan, string $extensionWithoutDot): bool
    {
        $ext = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extensionWithoutDot) ?? '');
        $count = MembershipPlanExtension::query()->where('plan_id', $plan->id)->count();
        if ($count === 0) {
            return true;
        }
        if ($ext === '') {
            return false;
        }

        return MembershipPlanExtension::query()
            ->where('plan_id', $plan->id)
            ->where('extension', $ext)
            ->exists();
    }

    public function assertCanUpload(User $user, int $fileSizeBytes, string $extensionWithoutDot): ?string
    {
        $plan = $this->effectivePlan($user);

        if ($plan->max_file_size !== null && $fileSizeBytes > $plan->max_file_size) {
            return '文件超过当前会员等级允许的单文件大小上限';
        }

        if (!$this->isExtensionAllowed($plan, $extensionWithoutDot)) {
            return '当前会员等级不允许上传此文件类型';
        }

        if ($plan->max_uploads !== null) {
            $used = $this->countUploadsInQuota($user, $plan);
            if ($used >= $plan->max_uploads) {
                return '已达到当前会员等级在本周期内的上传数量上限';
            }
        }

        return null;
    }

    /**
     * 供首页展示与前端校验提示。
     *
     * @return array{
     *   plan_code: string,
     *   plan_name: string,
     *   quota_period: string,
     *   max_uploads: int|null,
     *   used_uploads: int,
     *   max_file_size: int|null,
     *   allowed_extensions: string[]|null
     * }
     */
    public function limitsPayload(User $user): array
    {
        $plan = $this->effectivePlan($user);
        $used = $this->countUploadsInQuota($user, $plan);

        $extRows = MembershipPlanExtension::query()->where('plan_id', $plan->id)->pluck('extension');
        /** @var Collection<int, string> $extRows */
        $allowed = $extRows->isEmpty() ? null : $extRows->map(fn ($e) => (string) $e)->values()->all();

        return [
            'plan_code' => (string) $plan->code,
            'plan_name' => (string) $plan->name,
            'quota_period' => (string) $plan->quota_period,
            'max_uploads' => $plan->max_uploads,
            'used_uploads' => $used,
            'max_file_size' => $plan->max_file_size,
            'allowed_extensions' => $allowed,
        ];
    }

    public function userOwnsStoragePath(User $user, string $relativePath): bool
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');

        return UserUpload::query()
            ->where('user_id', $user->id)
            ->where('storage_path', $relativePath)
            ->exists();
    }

    /**
     * storage 根下以邮箱派生的一级目录名（字符集与 IndexController::normalizeStoragePathForKey 分段规则一致）。
     */
    public function userStorageDirSegment(User $user): string
    {
        $email = strtolower(trim((string) $user->email));
        $seg = str_replace('@', '_at_', $email);
        $seg = (string) preg_replace('/[^a-z0-9._-]+/i', '_', $seg);
        $seg = trim($seg, '._-');
        if ($seg === '' || strlen($seg) > 200) {
            return 'user_' . $user->id;
        }

        return $seg;
    }

    /**
     * 供 /file、/image 查询参数使用：去掉当前用户账号目录前缀，避免邮箱相关信息出现在 URL 中。
     */
    public function pathParamForFileUrl(User $user, string $storagePath): string
    {
        $storagePath = trim(str_replace('\\', '/', $storagePath), '/');
        $seg = $this->userStorageDirSegment($user);
        $prefix = $seg . '/';
        if ($storagePath !== '' && str_starts_with($storagePath, $prefix)) {
            return substr($storagePath, strlen($prefix));
        }

        return $storagePath;
    }

    /**
     * 是否与 {@see \app\controller\IndexController::serveStorageImage()} 支持的常见光栅图一致（按扩展名推断，用于生成打开链接）。
     */
    public function isRasterImageExtension(string $extensionWithoutDot): bool
    {
        $ext = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extensionWithoutDot) ?? '');

        return in_array($ext, ['jpg', 'jpeg', 'jpe', 'jfif', 'png', 'gif', 'webp', 'bmp'], true);
    }

    /**
     * 浏览器打开已上传文件：图片走 /image，其余走 /file；path 查询参数规则同 {@see pathParamForFileUrl()}。
     */
    public function fileViewUrl(User $user, string $storagePath, string $extensionWithoutDot): string
    {
        $pathParam = $this->pathParamForFileUrl($user, $storagePath);
        $q = http_build_query(['path' => $pathParam]);
        $base = $this->isRasterImageExtension($extensionWithoutDot) ? '/image' : '/file';

        return $base . '?' . $q;
    }
}

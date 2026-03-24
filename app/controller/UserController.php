<?php

namespace app\controller;

use app\middleware\RequireLogin;
use app\model\UserUpload;
use app\service\UploadPolicyService;
use app\support\HumanBytes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use support\annotation\Middleware;
use support\annotation\route\Route;
use support\Request;
use support\Response;
use function view;

#[Middleware(RequireLogin::class)]
class UserController
{
    #[Route('/user', 'GET')]
    public function center(Request $request): Response
    {
        $user = $request->authUser;
        $user->load('membershipPlan');

        $uid = $user->id;
        $q = static fn (): Builder => UserUpload::query()->where('user_id', $uid);

        $totalCount = (int) $q()->count();
        $totalBytes = (int) $q()->sum('file_size');

        $monthStart = Carbon::now()->startOfMonth();
        $monthCount = (int) $q()->where('created_at', '>=', $monthStart)->count();
        $monthBytes = (int) $q()->where('created_at', '>=', $monthStart)->sum('file_size');

        $todayStart = Carbon::now()->startOfDay();
        $todayCount = (int) $q()->where('created_at', '>=', $todayStart)->count();

        $extensionRows = $q()
            ->selectRaw('extension, COUNT(*) as cnt, COALESCE(SUM(file_size), 0) as bytes')
            ->groupBy('extension')
            ->orderByDesc('cnt')
            ->limit(12)
            ->get();

        $byExtension = [];
        foreach ($extensionRows as $row) {
            $ext = (string) $row->extension;
            $label = $ext !== '' ? '.' . $ext : '无扩展名';
            $byExtension[] = [
                'label' => $label,
                'count' => (int) $row->cnt,
                'bytes' => (int) $row->bytes,
                'bytes_label' => HumanBytes::format((int) $row->bytes),
            ];
        }

        $recentModels = $q()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $policy = new UploadPolicyService();
        $recent = [];
        foreach ($recentModels as $row) {
            $path = (string) $row->storage_path;
            $recent[] = [
                'name' => $row->original_name !== null && $row->original_name !== ''
                    ? (string) $row->original_name
                    : basename($path),
                /** 列表展示用：相对账号目录，不含邮箱派生的一级目录名 */
                'path_display' => $policy->pathParamForFileUrl($user, $path),
                'extension' => (string) $row->extension,
                'size_label' => HumanBytes::format((int) ($row->file_size ?? 0)),
                'created_label' => $row->created_at !== null
                    ? $row->created_at->format('Y-m-d H:i')
                    : '—',
                'file_url' => $policy->fileViewUrl($user, $path, (string) $row->extension),
            ];
        }

        $display = ($user->display_name !== null && $user->display_name !== '')
            ? $user->display_name
            : $user->email;
        $planName = $user->membershipPlan !== null
            ? (string) $user->membershipPlan->name
            : '—';

        return view('user/center', [
            'userDisplay' => $display,
            'userEmail' => (string) $user->email,
            'planName' => $planName,
            'stats' => [
                'total_count' => $totalCount,
                'total_bytes' => $totalBytes,
                'total_bytes_label' => HumanBytes::format($totalBytes),
                'month_count' => $monthCount,
                'month_bytes' => $monthBytes,
                'month_bytes_label' => HumanBytes::format($monthBytes),
                'today_count' => $todayCount,
            ],
            'byExtension' => $byExtension,
            'recent' => $recent,
        ]);
    }
}

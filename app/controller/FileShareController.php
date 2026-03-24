<?php

namespace app\controller;

use app\middleware\RequireLogin;
use app\model\FileShare;
use app\model\FileShareAccessLog;
use app\model\UserUpload;
use app\service\FileShareService;
use app\service\StorageFileServeService;
use Carbon\Carbon;
use support\annotation\Middleware;
use support\annotation\route\Route;
use support\Request;
use support\Response;
use Throwable;
use function json;
use function redirect;
use function view;

class FileShareController
{
    private function validTokenFormat(string $token): bool
    {
        return strlen($token) === 64 && ctype_xdigit($token);
    }

    #[Route('/share/{token}/file', 'GET')]
    public function shareFile(Request $request, string $token): Response
    {
        if (!$this->validTokenFormat($token)) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '分享不存在或已失效');
        }

        $share = FileShare::query()->where('token', $token)->with('userUpload')->first();
        if ($share === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '分享不存在或已失效');
        }

        $now = Carbon::now();

        if ($share->isRevoked()) {
            FileShareService::log((int) $share->id, 'file_denied', 'revoked', $request);

            return new Response(410, ['Content-Type' => 'text/plain; charset=utf-8'], '分享已撤销');
        }
        if ($share->isExpired($now)) {
            FileShareService::log((int) $share->id, 'file_denied', 'expired', $request);

            return new Response(410, ['Content-Type' => 'text/plain; charset=utf-8'], '分享已过期');
        }
        if ($share->viewsExhausted()) {
            FileShareService::log((int) $share->id, 'file_denied', 'exhausted', $request);

            return new Response(410, ['Content-Type' => 'text/plain; charset=utf-8'], '查看次数已用完');
        }

        if ($share->hasPassword() && !FileShareService::verifyUnlockCookie($request, $share)) {
            FileShareService::log((int) $share->id, 'file_denied', 'password_required', $request);

            return redirect('/share/' . $token);
        }

        $upload = $share->userUpload;
        if ($upload === null) {
            FileShareService::log((int) $share->id, 'file_denied', 'upload_missing', $request);

            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件记录不存在');
        }

        $key = (string) $upload->storage_path;
        $absolute = StorageFileServeService::resolveAbsolutePath($key);
        if ($absolute === null) {
            FileShareService::log((int) $share->id, 'file_denied', 'file_missing', $request);

            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或已删除');
        }

        if (!FileShareService::tryConsumeView($share)) {
            FileShareService::log((int) $share->id, 'file_denied', 'exhausted', $request);

            return new Response(410, ['Content-Type' => 'text/plain; charset=utf-8'], '查看次数已用完');
        }

        FileShareService::log((int) $share->id, 'file_served', null, $request);

        $response = StorageFileServeService::buildFileResponse($absolute, true);
        if ($response === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或已删除');
        }

        return $response;
    }

    #[Route('/share/{token}/unlock', 'POST')]
    public function shareUnlock(Request $request, string $token): Response
    {
        if (!$this->validTokenFormat($token)) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '分享不存在或已失效');
        }

        $share = FileShare::query()->where('token', $token)->first();
        if ($share === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '分享不存在或已失效');
        }

        if (!$share->hasPassword()) {
            return redirect('/share/' . $token);
        }

        $now = Carbon::now();
        if ($share->isRevoked() || $share->isExpired($now)) {
            FileShareService::log((int) $share->id, 'unlock_fail', 'inactive', $request);

            return redirect('/share/' . $token);
        }

        $password = (string) $request->post('password', '');
        if ($password === '' || !password_verify($password, (string) $share->password_hash)) {
            FileShareService::log((int) $share->id, 'unlock_fail', 'invalid_password', $request);

            return redirect('/share/' . $token . '?err=1');
        }

        FileShareService::log((int) $share->id, 'unlock_success', null, $request);

        $cookie = FileShareService::makeUnlockCookieValue($share);
        $response = redirect('/share/' . $token);

        return $response->cookie(
            FileShareService::cookieNameForShare((int) $share->id),
            $cookie['value'],
            $cookie['max_age'],
            '/',
            '',
            false,
            true,
            'Lax'
        );
    }

    #[Route('/share/{token}', 'GET')]
    public function shareLanding(Request $request, string $token): Response
    {
        if (!$this->validTokenFormat($token)) {
            return view('share/gone', ['message' => '分享不存在或已失效']);
        }

        $share = FileShare::query()->where('token', $token)->with('userUpload')->first();
        if ($share === null) {
            return view('share/gone', ['message' => '分享不存在或已失效']);
        }

        FileShareService::log((int) $share->id, 'landing_view', null, $request);

        $upload = $share->userUpload;
        $fileName = '文件';
        if ($upload !== null) {
            $on = (string) ($upload->original_name ?? '');
            $fileName = $on !== '' ? $on : basename((string) $upload->storage_path);
        }

        $now = Carbon::now();
        $passwordErr = (string) $request->get('err', '') === '1';

        return view('share/landing', [
            'token' => $token,
            'fileName' => $fileName,
            'revoked' => $share->isRevoked(),
            'expired' => $share->isExpired($now),
            'exhausted' => $share->viewsExhausted(),
            'needsPassword' => $share->hasPassword() && !FileShareService::verifyUnlockCookie($request, $share),
            'passwordError' => $passwordErr,
            'expiresLabel' => $share->expires_at !== null ? $share->expires_at->format('Y-m-d H:i') : '不限',
            'viewsLabel' => $share->max_views !== null
                ? ((int) $share->view_count . ' / ' . (int) $share->max_views)
                : ((int) $share->view_count . ' / 不限'),
            'fileUrl' => '/share/' . $token . '/file',
        ]);
    }

    #[Route('/user/shares', 'GET')]
    #[Middleware(RequireLogin::class)]
    public function listMine(Request $request): Response
    {
        $user = $request->authUser;
        $rows = FileShare::query()
            ->where('user_id', $user->id)
            ->with('userUpload')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $upload = $row->userUpload;
            $label = '—';
            if ($upload !== null) {
                $on = (string) ($upload->original_name ?? '');
                $label = $on !== '' ? $on : basename((string) $upload->storage_path);
            }
            $items[] = [
                'id' => (int) $row->id,
                'file_label' => $label,
                'token' => (string) $row->token,
                'landing_url' => '/share/' . $row->token,
                'has_password' => $row->hasPassword(),
                'expires_label' => $row->expires_at !== null ? $row->expires_at->format('Y-m-d H:i') : '不限',
                'views' => (int) $row->view_count . ($row->max_views !== null ? ' / ' . (int) $row->max_views : ' / 不限'),
                'revoked' => $row->isRevoked(),
                'created_label' => $row->created_at !== null ? $row->created_at->format('Y-m-d H:i') : '—',
            ];
        }

        $display = ($user->display_name !== null && $user->display_name !== '')
            ? $user->display_name
            : $user->email;
        $user->load('membershipPlan');
        $planName = $user->membershipPlan !== null ? (string) $user->membershipPlan->name : '—';

        return view('user/shares', [
            'userDisplay' => $display,
            'headerUserMeta' => $planName,
            'items' => $items,
            'flashCreated' => (string) $request->get('created', '') === '1',
            'flashRevoked' => (string) $request->get('revoked', '') === '1',
        ]);
    }

    #[Route('/user/shares', 'POST')]
    #[Middleware(RequireLogin::class)]
    public function create(Request $request): Response
    {
        $user = $request->authUser;
        $uploadId = (int) $request->post('user_upload_id', 0);
        $maxViewsRaw = trim((string) $request->post('max_views', ''));
        $expiresRaw = trim((string) $request->post('expires_at', ''));
        $password = (string) $request->post('password', '');

        $upload = UserUpload::query()->where('id', $uploadId)->where('user_id', $user->id)->first();
        if ($upload === null) {
            return $this->createErrorResponse($request, '文件不存在或无权操作');
        }

        $maxViews = null;
        if ($maxViewsRaw !== '') {
            $maxViews = (int) $maxViewsRaw;
            if ($maxViews < 1 || $maxViews > 999_999) {
                return $this->createErrorResponse($request, '次数须在 1～999999 之间，或留空表示不限');
            }
        }

        $expiresAt = null;
        if ($expiresRaw !== '') {
            try {
                $expiresAt = Carbon::parse($expiresRaw);
            } catch (Throwable) {
                return $this->createErrorResponse($request, '过期时间格式不正确');
            }
            if ($expiresAt->lte(Carbon::now())) {
                return $this->createErrorResponse($request, '过期时间须晚于当前时间');
            }
            if ($expiresAt->gt(Carbon::now()->addYear())) {
                return $this->createErrorResponse($request, '过期时间最长不超过一年');
            }
        }

        $passwordHash = null;
        if ($password !== '') {
            if (strlen($password) < 4) {
                return $this->createErrorResponse($request, '访问密码至少 4 位，或留空表示不设密码');
            }
            if (strlen($password) > 128) {
                return $this->createErrorResponse($request, '访问密码过长');
            }
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        }

        $token = FileShareService::generateToken();
        $share = null;
        for ($i = 0; $i < 5; $i++) {
            try {
                $share = FileShare::query()->create([
                    'user_id' => $user->id,
                    'user_upload_id' => $upload->id,
                    'token' => $token,
                    'password_hash' => $passwordHash,
                    'max_views' => $maxViews,
                    'view_count' => 0,
                    'expires_at' => $expiresAt,
                    'revoked_at' => null,
                    'created_at' => Carbon::now(),
                ]);
                FileShareService::log((int) $share->id, 'share_created', null, $request);
                break;
            } catch (Throwable) {
                $token = FileShareService::generateToken();
            }
        }

        if ($share === null) {
            return $this->createErrorResponse($request, '创建失败，请稍后重试');
        }

        $landing = '/share/' . $share->token;

        if ($this->wantsJson($request)) {
            return json([
                'code' => 0,
                'msg' => 'ok',
                'data' => [
                    'id' => (int) $share->id,
                    'token' => (string) $share->token,
                    'landing_url' => $landing,
                    'file_url' => $landing . '/file',
                ],
            ]);
        }

        return redirect('/user/shares?created=1');
    }

    #[Route('/user/shares/{id}/revoke', 'POST')]
    #[Middleware(RequireLogin::class)]
    public function revoke(Request $request, string $id): Response
    {
        $user = $request->authUser;
        $sid = (int) $id;
        $share = FileShare::query()->where('id', $sid)->where('user_id', $user->id)->first();
        if ($share === null) {
            return redirect('/user/shares?err=notfound');
        }

        if (!$share->isRevoked()) {
            $share->revoked_at = Carbon::now();
            $share->save();
            FileShareService::log($sid, 'share_revoked', null, $request);
        }

        return redirect('/user/shares?revoked=1');
    }

    #[Route('/user/shares/{id}/audit', 'GET')]
    #[Middleware(RequireLogin::class)]
    public function audit(Request $request, string $id): Response
    {
        $user = $request->authUser;
        $sid = (int) $id;
        $share = FileShare::query()->where('id', $sid)->where('user_id', $user->id)->with('userUpload')->first();
        if ($share === null) {
            return redirect('/user/shares?err=notfound');
        }

        $logs = FileShareAccessLog::query()
            ->where('file_share_id', $sid)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                'action' => (string) $log->action,
                'detail' => $log->detail !== null ? (string) $log->detail : '—',
                'ip' => $log->ip !== null ? (string) $log->ip : '—',
                'ua' => $log->user_agent !== null ? (string) $log->user_agent : '—',
                'time' => $log->created_at !== null ? $log->created_at->format('Y-m-d H:i:s') : '—',
            ];
        }

        $upload = $share->userUpload;
        $fileLabel = '—';
        if ($upload !== null) {
            $on = (string) ($upload->original_name ?? '');
            $fileLabel = $on !== '' ? $on : basename((string) $upload->storage_path);
        }

        $display = ($user->display_name !== null && $user->display_name !== '')
            ? $user->display_name
            : $user->email;
        $user->load('membershipPlan');
        $planName = $user->membershipPlan !== null ? (string) $user->membershipPlan->name : '—';

        return view('user/share_audit', [
            'userDisplay' => $display,
            'headerUserMeta' => $planName,
            'shareId' => $sid,
            'fileLabel' => $fileLabel,
            'landingUrl' => '/share/' . $share->token,
            'logs' => $rows,
        ]);
    }

    private function wantsJson(Request $request): bool
    {
        $accept = (string) $request->header('accept', '');

        return str_contains($accept, 'application/json');
    }

    private function createErrorResponse(Request $request, string $message): Response
    {
        if ($this->wantsJson($request)) {
            return json(['code' => 1, 'msg' => $message])->withStatus(400);
        }

        return new Response(400, ['Content-Type' => 'text/plain; charset=utf-8'], $message);
    }
}

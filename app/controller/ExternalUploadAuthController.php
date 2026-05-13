<?php

namespace app\controller;

use app\middleware\RequireLogin;
use app\model\UserExternalUploadAuth;
use app\service\ExternalUploadAuthService;
use support\annotation\Middleware;
use support\annotation\route\Route;
use support\Request;
use support\Response;
use Throwable;
use function redirect;
use function view;

#[Middleware(RequireLogin::class)]
class ExternalUploadAuthController
{
    #[Route('/user/external-auths', 'GET')]
    public function index(Request $request): Response
    {
        $user = $request->authUser;
        $rows = UserExternalUploadAuth::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'status' => (string) $row->status,
                'default_subdir' => $row->default_subdir !== null && $row->default_subdir !== '' ? (string) $row->default_subdir : '自动按日期目录',
                'retention_label' => $row->retention_ttl_days === null ? '永久' : ((int) $row->retention_ttl_days . ' 天'),
                'last_used_label' => $row->last_used_at !== null ? $row->last_used_at->format('Y-m-d H:i') : '—',
                'created_label' => $row->created_at !== null ? $row->created_at->format('Y-m-d H:i') : '—',
                'disabled' => !$row->isActive(),
            ];
        }

        $display = ($user->display_name !== null && $user->display_name !== '') ? $user->display_name : $user->email;
        $user->load('membershipPlan');
        $planName = $user->membershipPlan !== null ? (string) $user->membershipPlan->name : '—';

        return view('user/external_auths', [
            'userDisplay' => $display,
            'headerUserMeta' => $planName,
            'items' => $items,
            'flashCreated' => (string) $request->get('created', '') === '1',
            'flashDisabled' => (string) $request->get('disabled', '') === '1',
            'flashEnabled' => (string) $request->get('enabled', '') === '1',
            'flashDeleted' => (string) $request->get('deleted', '') === '1',
            'createdToken' => trim((string) $request->get('token', '')),
            'errorMessage' => (string) $request->get('err', ''),
        ]);
    }

    #[Route('/user/external-auths', 'POST')]
    public function create(Request $request): Response
    {
        $user = $request->authUser;
        $name = trim((string) $request->post('name', ''));
        $defaultSubdir = trim((string) $request->post('default_subdir', ''));
        $ttlRaw = trim((string) $request->post('retention_ttl_days', ''));

        if ($name === '') {
            return redirect('/user/external-auths?err=' . rawurlencode('请填写授权名称'));
        }
        if (mb_strlen($name) > 100) {
            return redirect('/user/external-auths?err=' . rawurlencode('授权名称不能超过 100 个字符'));
        }

        $ttlDays = null;
        if ($ttlRaw !== '') {
            $ttlDays = (int) $ttlRaw;
            if ($ttlDays < 1 || $ttlDays > 3650) {
                return redirect('/user/external-auths?err=' . rawurlencode('有效期天数须在 1～3650 之间，或留空表示永久'));
            }
        }

        $service = new ExternalUploadAuthService();

        try {
            $created = $service->createForUser($user, $name, $defaultSubdir, $ttlDays);
        } catch (\InvalidArgumentException $e) {
            return redirect('/user/external-auths?err=' . rawurlencode($e->getMessage()));
        } catch (Throwable) {
            return redirect('/user/external-auths?err=' . rawurlencode('创建授权失败，请稍后重试'));
        }

        return redirect('/user/external-auths?created=1&token=' . rawurlencode((string) $created['plain_token']));
    }

    #[Route('/user/external-auths/{id}/disable', 'POST')]
    public function disable(Request $request, string $id): Response
    {
        $user = $request->authUser;
        $auth = UserExternalUploadAuth::query()
            ->where('id', (int) $id)
            ->where('user_id', $user->id)
            ->first();

        if ($auth === null) {
            return redirect('/user/external-auths?err=' . rawurlencode('授权不存在或无权操作'));
        }

        if ($auth->status !== 'disabled') {
            $auth->status = 'disabled';
            $auth->save();
        }

        return redirect('/user/external-auths?disabled=1');
    }

    #[Route('/user/external-auths/{id}/enable', 'POST')]
    public function enable(Request $request, string $id): Response
    {
        $user = $request->authUser;
        $auth = UserExternalUploadAuth::query()
            ->where('id', (int) $id)
            ->where('user_id', $user->id)
            ->first();

        if ($auth === null) {
            return redirect('/user/external-auths?err=' . rawurlencode('授权不存在或无权操作'));
        }

        if ($auth->status !== 'active') {
            $auth->status = 'active';
            $auth->revoked_at = null;
            $auth->save();
        }

        return redirect('/user/external-auths?enabled=1');
    }

    #[Route('/user/external-auths/{id}', 'POST')]
    public function delete(Request $request, string $id): Response
    {
        $user = $request->authUser;
        $method = strtoupper(trim((string) $request->post('_method', '')));
        if ($method !== 'DELETE') {
            return redirect('/user/external-auths?err=' . rawurlencode('不支持的请求方式'));
        }

        $auth = UserExternalUploadAuth::query()
            ->where('id', (int) $id)
            ->where('user_id', $user->id)
            ->first();

        if ($auth === null) {
            return redirect('/user/external-auths?err=' . rawurlencode('授权不存在或无权操作'));
        }

        try {
            $auth->delete();
        } catch (Throwable) {
            return redirect('/user/external-auths?err=' . rawurlencode('删除授权失败，请稍后重试'));
        }

        return redirect('/user/external-auths?deleted=1');
    }
}

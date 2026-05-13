<?php

namespace app\controller;

use app\model\UserUpload;
use app\service\ExternalUploadAuthService;
use app\service\FileShareService;
use app\service\UploadPolicyService;
use app\service\UserUploadService;
use Carbon\Carbon;
use support\annotation\route\Route;
use support\Request;
use support\Response;
use Throwable;
use function json;

class ExternalUploadController
{
    #[Route('/api/external/upload', 'POST')]
    public function upload(Request $request): Response
    {
        $authService = new ExternalUploadAuthService();
        $auth = $authService->resolveFromRequest($request);
        $authError = $authService->validateAuthorization($auth);
        if ($authError === 'invalid') {
            return json(['code' => 401, 'msg' => '授权无效'])->withStatus(401);
        }
        if ($authError !== null) {
            $authService->log($auth, 'upload_denied', 'authorization_disabled', $request);

            return json(['code' => 403, 'msg' => '授权已禁用或撤销'])->withStatus(403);
        }

        $file = $request->file('file');
        if ($file === null) {
            $authService->log($auth, 'upload_denied', 'file_missing', $request);

            return json(['code' => 1, 'msg' => '未收到文件'])->withStatus(400);
        }

        $subdir = $authService->effectiveSubdir($auth, (string) $request->post('subdir', ''));
        $uploadService = new UserUploadService();
        $user = $authService->user($auth);
        $stored = $uploadService->store($user, $file, $subdir);
        if (!$stored['ok']) {
            $status = in_array($stored['code'], [1, 2, 3, 4, 5], true) ? 400 : ($stored['code'] === 6 ? 422 : 500);
            $authService->log($auth, 'upload_denied', (string) $stored['msg'], $request);

            return json(['code' => $stored['code'], 'msg' => $stored['msg']])->withStatus($status);
        }

        /** @var UserUpload $upload */
        $upload = $stored['upload'];
        $absolutePath = (string) $stored['absolute_path'];
        $now = Carbon::now();

        try {
            $upload->getConnection()->transaction(function () use ($auth, $authService, $upload, $now) {
                if (FileShareService::retentionForUpload((int) $upload->id) !== null) {
                    throw new \RuntimeException('retention_exists');
                }

                FileShareService::createRetention($upload, $authService->retentionExpiresAt($auth, $now), $now);
                $authService->touchLastUsed($auth, $now);
            });
        } catch (Throwable) {
            $upload->delete();
            @unlink($absolutePath);
            $authService->log($auth, 'upload_failed', 'retention_create_failed', $request);

            return json(['code' => 500, 'msg' => '创建文件有效期记录失败'])->withStatus(500);
        }

        $policy = new UploadPolicyService();
        $authService->log($auth, 'upload_success', (string) $stored['relative_path'], $request, $upload);

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'upload_id' => (int) $upload->id,
                'saved_as' => (string) $stored['saved_as'],
                'relative_path' => (string) $stored['relative_path'],
                'view_url' => $policy->absoluteFileViewUrl($user, (string) $stored['relative_path'], (string) $stored['extension']),
                'expires_at' => $authService->retentionExpiresAt($auth, $now)?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}

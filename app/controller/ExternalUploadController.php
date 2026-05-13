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
use Webman\Http\UploadFile;
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

        $files = $this->normalizeFiles([
            $request->file('file'),
            $request->file('files'),
            $request->file('files[]'),
        ]);
        if ($files === []) {
            $authService->log($auth, 'upload_denied', 'file_missing', $request);

            return json(['code' => 1, 'msg' => '未收到文件'])->withStatus(400);
        }
        if (count($files) > 4) {
            $authService->log($auth, 'upload_denied', 'too_many_files', $request);

            return json(['code' => 8, 'msg' => '一次最多上传 4 个文件'])->withStatus(400);
        }

        $subdir = $authService->effectiveSubdir($auth, (string) $request->post('subdir', ''));
        $uploadService = new UserUploadService();
        $user = $authService->user($auth);
        $now = Carbon::now();

        $policy = new UploadPolicyService();
        $items = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($files as $file) {
            $item = [
                'name' => (string) $file->getUploadName(),
                'ok' => false,
                'code' => 0,
                'msg' => 'ok',
            ];

            $stored = $uploadService->store($user, $file, $subdir);
            if (!$stored['ok']) {
                $item['code'] = (int) $stored['code'];
                $item['msg'] = (string) $stored['msg'];
                $items[] = $item;
                $failedCount++;
                $authService->log($auth, 'upload_denied', (string) $stored['msg'], $request);
                continue;
            }

            /** @var UserUpload $upload */
            $upload = $stored['upload'];
            $absolutePath = (string) $stored['absolute_path'];

            try {
                $upload->getConnection()->transaction(function () use ($auth, $authService, $upload, $now) {
                    if (FileShareService::retentionForUpload((int) $upload->id) !== null) {
                        throw new \RuntimeException('retention_exists');
                    }

                    FileShareService::createRetention($upload, $authService->retentionExpiresAt($auth, $now), $now);
                });
            } catch (Throwable) {
                $upload->delete();
                @unlink($absolutePath);
                $item['code'] = 500;
                $item['msg'] = '创建文件有效期记录失败';
                $items[] = $item;
                $failedCount++;
                $authService->log($auth, 'upload_failed', 'retention_create_failed', $request);
                continue;
            }

            $item['ok'] = true;
            $item['upload_id'] = (int) $upload->id;
            $item['saved_as'] = (string) $stored['saved_as'];
            $item['relative_path'] = (string) $stored['relative_path'];
            $item['view_url'] = $policy->absoluteFileViewUrl($user, (string) $stored['relative_path'], (string) $stored['extension']);
            $item['expires_at'] = $authService->retentionExpiresAt($auth, $now)?->format('Y-m-d H:i:s');
            $items[] = $item;
            $successCount++;
            $authService->log($auth, 'upload_success', (string) $stored['relative_path'], $request, $upload);
        }

        if ($successCount > 0) {
            $authService->touchLastUsed($auth, $now);
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'items' => $items,
                'summary' => [
                    'total' => count($files),
                    'success' => $successCount,
                    'failed' => $failedCount,
                ],
            ],
        ]);
    }

    /**
     * @param array<int, mixed> $candidates
     * @return array<int, UploadFile>
     */
    private function normalizeFiles(array $candidates): array
    {
        $files = [];

        foreach ($candidates as $candidate) {
            if ($candidate instanceof UploadFile) {
                $files[] = $candidate;
                continue;
            }
            if (!is_array($candidate)) {
                continue;
            }

            foreach ($this->normalizeFiles($candidate) as $file) {
                $files[] = $file;
            }
        }

        return $files;
    }
}

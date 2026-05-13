<?php

namespace app\service;

use app\model\User;
use app\model\UserUpload;
use Carbon\Carbon;
use Symfony\Component\Mime\MimeTypes;
use Webman\Http\UploadFile;
use function base_path;

class UserUploadService
{
    /**
     * @return array{ok: bool, code: int, msg: string, upload: UserUpload|null, saved_as?: string, relative_path?: string, absolute_path?: string, extension?: string}
     */
    public function store(User $user, UploadFile $file, string $subdirRaw): array
    {
        if (!$file->isValid()) {
            return ['ok' => false, 'code' => 2, 'msg' => '文件无效或未完整上传', 'upload' => null];
        }

        $policy = new UploadPolicyService();
        $subdir = $policy->sanitizeRelativeSubdir($subdirRaw);
        if ($subdir === null) {
            return ['ok' => false, 'code' => 5, 'msg' => '子目录不合法：每一级须以字母或数字开头、结尾，中间可为字母、数字、下划线、连字符，多级用 / 分隔', 'upload' => null];
        }
        if ($subdir === '') {
            $subdir = Carbon::now()->format('Ymd');
        }

        $extRaw = pathinfo((string) $file->getUploadName(), PATHINFO_EXTENSION);
        $extNoDot = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $extRaw));
        $ext = $extNoDot !== '' ? '.' . $extNoDot : '';
        $deny = $policy->assertCanUpload($user, (int) $file->getSize(), $extNoDot);
        if ($deny !== null) {
            return ['ok' => false, 'code' => 6, 'msg' => $deny, 'upload' => null];
        }

        $userSeg = $policy->userStorageDirSegment($user);
        $relativeDir = $userSeg . '/' . $subdir;

        $root = base_path('storage');
        $dir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'code' => 3, 'msg' => '无法创建存储目录', 'upload' => null];
        }

        $destName = $this->newUuidV4() . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $destName;

        try {
            $file->move($dest);
        } catch (\Throwable) {
            return ['ok' => false, 'code' => 4, 'msg' => '保存文件失败', 'upload' => null];
        }

        $relative = $relativeDir . '/' . $destName;
        $mimeTypes = new MimeTypes();
        $mime = $mimeTypes->guessMimeType($dest) ?? null;

        try {
            $upload = UserUpload::query()->create([
                'user_id' => $user->id,
                'storage_path' => $relative,
                'original_name' => $file->getUploadName(),
                'extension' => $extNoDot,
                'file_size' => (int) (@filesize($dest) ?: 0),
                'mime_type' => $mime,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable) {
            @unlink($dest);

            return ['ok' => false, 'code' => 7, 'msg' => '保存上传记录失败', 'upload' => null];
        }

        return [
            'ok' => true,
            'code' => 0,
            'msg' => 'ok',
            'upload' => $upload,
            'saved_as' => $destName,
            'relative_path' => $relative,
            'absolute_path' => $dest,
            'extension' => $extNoDot,
        ];
    }

    private function newUuidV4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
        $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
        $h = bin2hex($b);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($h, 0, 8),
            substr($h, 8, 4),
            substr($h, 12, 4),
            substr($h, 16, 4),
            substr($h, 20, 12)
        );
    }
}

<?php

namespace app\controller;

use app\middleware\RequireLogin;
use app\model\UserUpload;
use app\service\FileShareService;
use app\service\StorageFileServeService;
use app\service\UploadPolicyService;
use app\service\UserUploadService;
use Carbon\Carbon;
use support\annotation\Middleware;
use support\annotation\route\Route;
use support\Request;
use support\Response;
use Throwable;
use function json;
use function view;
use function redirect;

/**
 * 默认控制器
 */
#[Middleware(RequireLogin::class)]
class IndexController
{
    /**
     * 首页重定向到上传页面
     */
    #[Route('/', 'GET')]
    public function index() : Response {
        return redirect('/home');
    }

    /**
     * 上传页面
     * @param Request $request
     * @return Response
     */
    #[Route('/home', 'GET')]
    public function home(Request $request): Response
    {
        $policy = new UploadPolicyService();
        $limits = $policy->limitsPayload($request->authUser);
        $u = $request->authUser;
        $display = ($u->display_name !== null && $u->display_name !== '') ? $u->display_name : $u->email;

        return view('index/home', [
            'uploadUrl' => '/upload',
            'limits' => $limits,
            'userDisplay' => $display,
        ]);
    }

    /**
     * 上传文件：写入 storage/{用户邮箱目录}/{子目录}/，子目录未填时默认为当日 Ymd（如 20260324）；文件名为 UUID（保留扩展名）。
     *
     * @param Request $request multipart：file；可选 subdir（相对用户目录，规则同原 sanitize）
     * @return Response JSON：data.saved_as、data.relative_path（相对 storage 根）
     */
    #[Route('/upload', 'POST')]
    public function upload(Request $request): Response
    {
        $file = $request->file('file');
        if ($file === null) {
            return json(['code' => 1, 'msg' => '未收到文件']);
        }
        if (!$file->isValid()) {
            return json(['code' => 2, 'msg' => '文件无效或未完整上传']);
        }

        $uploadService = new UserUploadService();
        $stored = $uploadService->store($request->authUser, $file, (string) $request->post('subdir', ''));
        if (!$stored['ok']) {
            return json(['code' => $stored['code'], 'msg' => $stored['msg']]);
        }

        $policy = new UploadPolicyService();

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'saved_as' => $stored['saved_as'],
                'relative_path' => $stored['relative_path'],
                'view_url' => $policy->fileViewUrl($request->authUser, (string) $stored['relative_path'], (string) $stored['extension']),
            ],
        ]);
    }

    /**
     * 访问 storage 内文件：GET path 须为相对当前用户账号目录的路径（不得包含该目录名，避免泄露）；兼容旧数据中的完整相对路径（无账号目录前缀时）。
     */
    #[Route('/file', 'GET')]
    public function serveStorageFile(Request $request): Response
    {
        $relative = (string) $request->get('path', '');
        $resolved = $this->resolveAuthorizedStorageKeyForRead($request, $relative);
        if ($resolved['error'] === 'empty' || $resolved['error'] === 'invalid') {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或路径不合法');
        }
        if ($resolved['error'] === 'path_forbidden') {
            return new Response(403, ['Content-Type' => 'text/plain; charset=utf-8'], '链接路径中不得包含账号目录名；请使用相对该目录的路径（用户中心「打开」链接已按此规则生成）。');
        }
        if ($resolved['error'] === 'forbidden') {
            return new Response(403, ['Content-Type' => 'text/plain; charset=utf-8'], '无权访问该文件');
        }

        $absolute = StorageFileServeService::resolveAbsolutePath($resolved['key']);
        if ($absolute === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或路径不合法');
        }
        if ($resolved['upload'] !== null && FileShareService::retentionExpiredForUpload((int) $resolved['upload']->id)) {
            return new Response(410, ['Content-Type' => 'text/plain; charset=utf-8'], '文件已过期');
        }

        $response = StorageFileServeService::buildFileResponse($absolute, false);
        if ($response === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或路径不合法');
        }

        return $response;
    }

    /**
     * 访问 storage 内图片并可选缩放：path 规则同 {@see serveStorageFile()}；w、h 为最大宽/高（像素），可只传其一，等比缩小不放大。
     * 不传 w、h 时返回原图。仅支持常见光栅格式（JPEG/PNG/GIF/WebP/BMP），不含 SVG。
     */
    #[Route('/image', 'GET')]
    public function serveStorageImage(Request $request): Response
    {
        if (!extension_loaded('gd')) {
            return new Response(501, ['Content-Type' => 'text/plain; charset=utf-8'], '服务器未启用 GD 扩展，无法处理图片');
        }

        $relative = (string) $request->get('path', '');
        $resolved = $this->resolveAuthorizedStorageKeyForRead($request, $relative);
        if ($resolved['error'] === 'empty' || $resolved['error'] === 'invalid') {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或路径不合法');
        }
        if ($resolved['error'] === 'path_forbidden') {
            return new Response(403, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或路径不合法');
        }
        if ($resolved['error'] === 'forbidden') {
            return new Response(403, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或路径不合法');
        }

        $absolute = StorageFileServeService::resolveAbsolutePath($resolved['key']);
        if ($absolute === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或路径不合法');
        }
        if ($resolved['upload'] !== null && FileShareService::retentionExpiredForUpload((int) $resolved['upload']->id)) {
            return new Response(410, ['Content-Type' => 'text/plain; charset=utf-8'], '文件已过期');
        }

        $info = @getimagesize($absolute);
        if ($info === false) {
            return new Response(415, ['Content-Type' => 'text/plain; charset=utf-8'], '无法识别为图片');
        }

        $mime = strtolower($info['mime']);
        if (!$this->isAllowedRasterImageMime($mime)) {
            return new Response(415, ['Content-Type' => 'text/plain; charset=utf-8'], '不支持的图片类型（请使用 JPEG/PNG/GIF/WebP/BMP）');
        }

        $reqW = $this->parseImageDimensionParam($request->get('w'));
        $reqH = $this->parseImageDimensionParam($request->get('h'));

        $srcW = $info[0];
        $srcH = $info[1];
        [$dstW, $dstH, $needResize] = $this->computeImageTargetSize($srcW, $srcH, $reqW, $reqH);

        $basename = basename($absolute);
        $basename = preg_replace('/["\\\\\x00-\x1F\x7F]/', '', $basename) ?: 'image';

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $basename . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ];

        if (!$needResize) {
            $response = new Response();
            foreach ($headers as $k => $v) {
                $response->header($k, $v);
            }

            return $response->withFile($absolute);
        }

        $body = $this->renderResizedRaster($absolute, $mime, $dstW, $dstH);
        if ($body === null) {
            return new Response(500, ['Content-Type' => 'text/plain; charset=utf-8'], '图片缩放失败');
        }

        return new Response(200, $headers, $body);
    }

    private const IMAGE_MAX_EDGE = 4096;

    /**
     * @param mixed $value
     */
    private function parseImageDimensionParam(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $n = (int) $value;

        return $n <= 0 ? 0 : min(self::IMAGE_MAX_EDGE, $n);
    }

    /**
     * @return array{0: int, 1: int, 2: bool} 目标宽高、是否需要重新编码输出
     */
    private function computeImageTargetSize(int $srcW, int $srcH, int $reqW, int $reqH): array
    {
        $maxW = $reqW > 0 ? $reqW : PHP_INT_MAX;
        $maxH = $reqH > 0 ? $reqH : PHP_INT_MAX;
        if ($maxW === PHP_INT_MAX && $maxH === PHP_INT_MAX) {
            return [$srcW, $srcH, false];
        }

        $ratio = min($maxW / $srcW, $maxH / $srcH, 1.0);
        $dstW = max(1, (int) floor($srcW * $ratio));
        $dstH = max(1, (int) floor($srcH * $ratio));
        $needResize = $dstW !== $srcW || $dstH !== $srcH;

        return [$dstW, $dstH, $needResize];
    }

    private function isAllowedRasterImageMime(string $mime): bool
    {
        return in_array($mime, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/x-ms-bmp',
        ], true);
    }

    private function renderResizedRaster(string $path, string $mime, int $dstW, int $dstH): ?string
    {
        $bin = @file_get_contents($path);
        if ($bin === false) {
            return null;
        }

        $src = @imagecreatefromstring($bin);
        if ($src === false) {
            return null;
        }

        $dst = imagecreatetruecolor($dstW, $dstH);
        if ($dst === false) {
            imagedestroy($src);

            return null;
        }

        try {
            $srcW = imagesx($src);
            $srcH = imagesy($src);
            if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
                imagealphablending($dst, true);
            } else {
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

            ob_start();
            $ok = match ($mime) {
                'image/jpeg' => imagejpeg($dst, null, 86),
                'image/png' => imagepng($dst, null, 6),
                'image/gif' => imagegif($dst),
                'image/webp' => function_exists('imagewebp') && imagewebp($dst, null, 86),
                'image/bmp', 'image/x-ms-bmp' => function_exists('imagebmp') && imagebmp($dst, null, true),
                default => false,
            };
            $out = ob_get_clean();
            if ($ok === false || $out === false || $out === '') {
                return null;
            }

            return $out;
        } catch (Throwable) {
            return null;
        } finally {
            imagedestroy($src);
            imagedestroy($dst);
        }
    }

    /**
     * 解析 GET path：禁止 URL 中出现当前用户账号目录名（首段）；在库中先按 path 原样匹配（旧数据），再按「账号目录/path」匹配。
     *
     * @return array{key: string|null, upload: UserUpload|null, error: 'empty'|'invalid'|'path_forbidden'|'forbidden'|null}
     */
    private function resolveAuthorizedStorageKeyForRead(Request $request, string $relativeRaw): array
    {
        $relativeRaw = trim($relativeRaw);
        if ($relativeRaw === '') {
            return ['key' => null, 'upload' => null, 'error' => 'empty'];
        }

        $policy = new UploadPolicyService();
        $userSeg = $policy->userStorageDirSegment($request->authUser);

        $k1 = StorageFileServeService::normalizeStoragePathForKey($relativeRaw);
        if ($k1 === null) {
            return ['key' => null, 'upload' => null, 'error' => 'invalid'];
        }

        $parts = explode('/', $k1);
        if ($parts[0] === $userSeg) {
            return ['key' => null, 'upload' => null, 'error' => 'path_forbidden'];
        }

        $upload = UserUpload::query()->where('user_id', $request->authUser->id)->where('storage_path', $k1)->first();
        if ($upload !== null) {
            return ['key' => $k1, 'upload' => $upload, 'error' => null];
        }

        $k2 = StorageFileServeService::normalizeStoragePathForKey($userSeg . '/' . ltrim($relativeRaw, '/'));
        if ($k2 !== null) {
            $upload = UserUpload::query()->where('user_id', $request->authUser->id)->where('storage_path', $k2)->first();
            if ($upload !== null) {
                return ['key' => $k2, 'upload' => $upload, 'error' => null];
            }
        }

        return ['key' => null, 'upload' => null, 'error' => 'forbidden'];
    }
}

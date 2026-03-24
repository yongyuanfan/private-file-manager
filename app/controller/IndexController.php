<?php

namespace app\controller;

use app\middleware\RequireLogin;
use app\model\UserUpload;
use app\service\UploadPolicyService;
use Carbon\Carbon;
use support\annotation\Middleware;
use support\annotation\route\Route;
use support\Request;
use support\Response;
use Symfony\Component\Mime\MimeTypes;
use Throwable;
use function base_path;
use function json;
use function str_starts_with;
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

        $subdir = $this->sanitizeStorageSubdir((string) $request->post('subdir', ''));
        if ($subdir === null) {
            return json(['code' => 5, 'msg' => '子目录不合法：每一级须以字母或数字开头、结尾，中间可为字母、数字、下划线、连字符，多级用 / 分隔']);
        }
        if ($subdir === '') {
            $subdir = Carbon::now()->format('Ymd');
        }

        $extRaw = pathinfo((string) $file->getUploadName(), PATHINFO_EXTENSION);
        $extNoDot = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $extRaw));
        $ext = $extNoDot !== '' ? '.' . $extNoDot : '';

        $policy = new UploadPolicyService();
        $deny = $policy->assertCanUpload($request->authUser, (int) $file->getSize(), $extNoDot);
        if ($deny !== null) {
            return json(['code' => 6, 'msg' => $deny]);
        }

        $userSeg = $policy->userStorageDirSegment($request->authUser);
        $relativeDir = $userSeg . '/' . $subdir;

        $root = base_path('storage');
        $dir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return json(['code' => 3, 'msg' => '无法创建存储目录']);
        }

        $destName = $this->newUuidV4() . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $destName;

        try {
            $file->move($dest);
        } catch (\Throwable) {
            return json(['code' => 4, 'msg' => '保存文件失败']);
        }

        $relative = $relativeDir . '/' . $destName;

        $mimeTypes = new MimeTypes();
        $mime = $mimeTypes->guessMimeType($dest) ?? null;

        try {
            UserUpload::query()->create([
                'user_id' => $request->authUser->id,
                'storage_path' => $relative,
                'original_name' => $file->getUploadName(),
                'extension' => $extNoDot,
                'file_size' => (int) (@filesize($dest) ?: 0),
                'mime_type' => $mime,
                'created_at' => Carbon::now(),
            ]);
        } catch (Throwable) {
            @unlink($dest);

            return json(['code' => 7, 'msg' => '保存上传记录失败']);
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'saved_as' => $destName,
                'relative_path' => $relative,
                'view_url' => $policy->fileViewUrl($request->authUser, $relative, $extNoDot),
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

        $absolute = $this->resolveStorageFilePath($resolved['key']);
        if ($absolute === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或路径不合法');
        }

        $mimeTypes = new MimeTypes();
        $mime = $mimeTypes->guessMimeType($absolute) ?? 'application/octet-stream';
        $basename = basename($absolute);
        $basename = preg_replace('/["\\\\\x00-\x1F\x7F]/', '', $basename);
        if ($basename === '') {
            $basename = 'file';
        }

        $inline = $this->isBrowserInlineMime($mime);
        $disposition = $inline
            ? 'inline; filename="' . $basename . '"'
            : 'attachment; filename="' . $basename . '"';

        $response = new Response();
        $response->header('Content-Type', $mime);
        $response->header('Content-Disposition', $disposition);
        $response->header('X-Content-Type-Options', 'nosniff');

        return $response->withFile($absolute);
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

        $absolute = $this->resolveStorageFilePath($resolved['key']);
        if ($absolute === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], '文件不存在或路径不合法');
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
     * 生成 RFC 4122 版本 4 的 UUID 字符串。
     * @return string
     */
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

    /**
     * 解析 GET path：禁止 URL 中出现当前用户账号目录名（首段）；在库中先按 path 原样匹配（旧数据），再按「账号目录/path」匹配。
     *
     * @return array{key: string|null, error: 'empty'|'invalid'|'path_forbidden'|'forbidden'|null}
     */
    private function resolveAuthorizedStorageKeyForRead(Request $request, string $relativeRaw): array
    {
        $relativeRaw = trim($relativeRaw);
        if ($relativeRaw === '') {
            return ['key' => null, 'error' => 'empty'];
        }

        $policy = new UploadPolicyService();
        $userSeg = $policy->userStorageDirSegment($request->authUser);

        $k1 = $this->normalizeStoragePathForKey($relativeRaw);
        if ($k1 === null) {
            return ['key' => null, 'error' => 'invalid'];
        }

        $parts = explode('/', $k1);
        if ($parts[0] === $userSeg) {
            return ['key' => null, 'error' => 'path_forbidden'];
        }

        if ($policy->userOwnsStoragePath($request->authUser, $k1)) {
            return ['key' => $k1, 'error' => null];
        }

        $k2 = $this->normalizeStoragePathForKey($userSeg . '/' . ltrim($relativeRaw, '/'));
        if ($k2 !== null && $policy->userOwnsStoragePath($request->authUser, $k2)) {
            return ['key' => $k2, 'error' => null];
        }

        return ['key' => null, 'error' => 'forbidden'];
    }

    /**
     * 校验并规范化 storage 下的相对子目录，禁止路径穿越。
     * 每段须以字母或数字开头、结尾，中间可为 [a-zA-Z0-9_-]；规范化后的相对路径（使用 /），空字符串表示未填写（上传时会替换为当日 Ymd）；非法时返回 null。
     * @return string|null
     */
    private function sanitizeStorageSubdir(string $raw): ?string
    {
        $raw = trim(str_replace('\\', '/', $raw), '/');
        if ($raw === '') {
            return '';
        }
        $parts = array_values(array_filter(explode('/', $raw), static fn ($p) => $p !== '' && $p !== '.' && $p !== '..'));
        if ($parts === [] || count($parts) > 8) {
            return null;
        }
        foreach ($parts as $p) {
            if (strlen($p) > 64 || !preg_match('/^[a-zA-Z0-9](?:[a-zA-Z0-9_-]*[a-zA-Z0-9])?$/', $p)) {
                return null;
            }
        }

        return implode('/', $parts);
    }

    /**
     * 规范化 storage 相对路径（与库中 user_uploads.storage_path 一致）；非法返回 null。
     */
    private function normalizeStoragePathForKey(string $raw): ?string
    {
        $raw = trim(str_replace('\\', '/', $raw), '/');
        if ($raw === '') {
            return null;
        }
        $parts = array_values(array_filter(explode('/', $raw), static fn ($p) => $p !== '' && $p !== '.' && $p !== '..'));
        if ($parts === [] || count($parts) > 32) {
            return null;
        }
        foreach ($parts as $p) {
            if (strlen($p) > 255 || !preg_match('/^[a-zA-Z0-9._-]+$/', $p)) {
                return null;
            }
        }

        return implode('/', $parts);
    }

    /**
     * 将相对路径解析为 storage 下的绝对文件路径；非法或不存在则返回 null。
     */
    private function resolveStorageFilePath(string $raw): ?string
    {
        $partsStr = $this->normalizeStoragePathForKey($raw);
        if ($partsStr === null) {
            return null;
        }
        $parts = explode('/', $partsStr);

        $root = realpath(base_path('storage'));
        if ($root === false || !is_dir($root)) {
            return null;
        }

        $full = $root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
        if (!is_file($full)) {
            return null;
        }

        $resolved = realpath($full);
        if ($resolved === false || !str_starts_with($resolved, $root)) {
            return null;
        }

        return $resolved;
    }

    /**
     * 是否适合在浏览器中直接打开（非下载）。排除可在页面中执行脚本的类型以降低同源风险。
     */
    private function isBrowserInlineMime(string $mime): bool
    {
        $mime = strtolower($mime);

        if (str_starts_with($mime, 'image/')) {
            return $mime !== 'image/svg+xml';
        }
        if (str_starts_with($mime, 'video/') || str_starts_with($mime, 'audio/')) {
            return true;
        }

        return in_array($mime, [
            'application/pdf',
            'text/plain',
            'text/csv',
            'text/markdown',
        ], true);
    }
}

<?php

namespace app\controller;

use support\annotation\route\Route;
use support\Request;
use support\Response;
use Symfony\Component\Mime\MimeTypes;
use function base_path;
use function json;
use function str_starts_with;
use function view;

/**
 * 默认控制器
 */
class IndexController
{
    /**
     * 上传页面
     * @param Request $request
     * @return Response
     */
    #[Route('/home', 'GET')]
    public function home(Request $request): Response
    {
        return view('index/home', ['uploadUrl' => '/upload']);
    }

    /**
     * 上传文件：写入项目根目录下 storage，POST 可选 subdir 为相对子目录，保存文件名为 UUID（保留扩展名）。
     *
     * @param Request $request multipart：file；可选 subdir
     * @return Response JSON：data.saved_as、data.relative_path（相对 storage）
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
            return json(['code' => 5, 'msg' => '子目录不合法，仅允许字母、数字、下划线、连字符，多级用 / 分隔']);
        }

        $root = base_path('storage');
        $dir = $subdir === '' ? $root : $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subdir);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return json(['code' => 3, 'msg' => '无法创建存储目录']);
        }

        $ext = pathinfo($file->getUploadName(), PATHINFO_EXTENSION);
        $ext = $ext !== '' ? '.' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext)) : '';
        $destName = $this->newUuidV4() . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $destName;

        try {
            $file->move($dest);
        } catch (\Throwable) {
            return json(['code' => 4, 'msg' => '保存文件失败']);
        }

        $relative = $subdir === '' ? $destName : str_replace(DIRECTORY_SEPARATOR, '/', $subdir) . '/' . $destName;

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'saved_as' => $destName,
                'relative_path' => $relative,
            ],
        ]);
    }

    /**
     * 访问 storage 内文件：GET 参数 path 为相对 storage 的路径。
     * 浏览器可直接展示的类型（如常见图片、PDF、音视频、纯文本）使用 inline，其余为 attachment 下载。
     */
    #[Route('/file', 'GET')]
    public function serveStorageFile(Request $request): Response
    {
        $relative = (string) $request->get('path', '');
        $absolute = $this->resolveStorageFilePath($relative);
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
     * 校验并规范化 storage 下的相对子目录，禁止路径穿越。
     * 规范化后的相对路径（使用 /），空字符串表示 storage 根目录；非法时返回 null。
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
            if (strlen($p) > 64 || !preg_match('/^[a-zA-Z0-9_-]+$/', $p)) {
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

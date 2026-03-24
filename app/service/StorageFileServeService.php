<?php

namespace app\service;

use support\Response;
use Symfony\Component\Mime\MimeTypes;
use function base_path;
use function str_starts_with;

/**
 * 从 storage 相对路径解析磁盘文件并生成下载/内联响应（与 {@see IndexController::serveStorageFile()} 行为一致）。
 */
class StorageFileServeService
{
    public static function normalizeStoragePathForKey(string $raw): ?string
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

    public static function resolveAbsolutePath(string $raw): ?string
    {
        $partsStr = self::normalizeStoragePathForKey($raw);
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

    public static function isBrowserInlineMime(string $mime): bool
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

    /**
     * @return Response|null 文件不存在时返回 null
     */
    public static function buildFileResponse(string $absolute, bool $noCache = false): ?Response
    {
        if (!is_file($absolute)) {
            return null;
        }

        $mimeTypes = new MimeTypes();
        $mime = $mimeTypes->guessMimeType($absolute) ?? 'application/octet-stream';
        $basename = basename($absolute);
        $basename = preg_replace('/["\\\\\x00-\x1F\x7F]/', '', $basename);
        if ($basename === '') {
            $basename = 'file';
        }

        $inline = self::isBrowserInlineMime($mime);
        $disposition = $inline
            ? 'inline; filename="' . $basename . '"'
            : 'attachment; filename="' . $basename . '"';

        $response = new Response();
        $response->header('Content-Type', $mime);
        $response->header('Content-Disposition', $disposition);
        $response->header('X-Content-Type-Options', 'nosniff');
        if ($noCache) {
            $response->header('Cache-Control', 'private, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
        }

        return $response->withFile($absolute);
    }
}

<?php

namespace app\controller;

use app\middleware\RequireLogin;
use app\model\UserUpload;
use app\service\UploadPolicyService;
use app\support\HumanBytes;
use support\annotation\Middleware;
use support\annotation\route\Route;
use support\Request;
use support\Response;
use function redirect;
use function view;

#[Middleware(RequireLogin::class)]
class FileManageController
{
    #[Route('/user/files', 'GET')]
    public function index(Request $request): Response
    {
        $user = $request->authUser;
        $user->load('membershipPlan');

        $policy = new UploadPolicyService();
        $relDir = $policy->sanitizeRelativeSubdir((string) $request->get('path', ''));
        if ($relDir === null) {
            return redirect('/user/files');
        }

        $userSeg = $policy->userStorageDirSegment($user);
        $storagePrefix = $userSeg . '/' . ($relDir === '' ? '' : $relDir . '/');
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $storagePrefix) . '%';

        $rows = UserUpload::query()
            ->where('user_id', $user->id)
            ->where('storage_path', 'like', $like)
            ->orderBy('storage_path')
            ->get();

        $subdirNames = [];
        $files = [];

        foreach ($rows as $row) {
            $storagePath = (string) $row->storage_path;
            $relPath = $policy->pathParamForFileUrl($user, $storagePath);

            if ($relDir !== '') {
                if (!str_starts_with($relPath, $relDir . '/')) {
                    continue;
                }
                $rest = substr($relPath, strlen($relDir) + 1);
            } else {
                $rest = $relPath;
            }

            if ($rest === '') {
                continue;
            }

            $slashPos = strpos($rest, '/');
            if ($slashPos !== false) {
                $subdirNames[substr($rest, 0, $slashPos)] = true;
            } else {
                $orig = (string) ($row->original_name ?? '');
                $name = $orig !== '' ? $orig : $rest;
                $files[] = [
                    'name' => $name,
                    'view_url' => $policy->fileViewUrl($user, $storagePath, (string) ($row->extension ?? '')),
                    'size_label' => HumanBytes::format((int) ($row->file_size ?? 0)),
                ];
            }
        }

        $dirs = [];
        foreach (array_keys($subdirNames) as $name) {
            $childPath = $relDir === '' ? $name : $relDir . '/' . $name;
            $dirs[] = [
                'name' => $name,
                'path' => $childPath,
                'url' => '/user/files?' . http_build_query(['path' => $childPath]),
            ];
        }

        usort($dirs, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
        usort($files, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        $breadcrumbs = [];
        if ($relDir !== '') {
            $parts = explode('/', $relDir);
            $acc = [];
            foreach ($parts as $p) {
                $acc[] = $p;
                $pathSoFar = implode('/', $acc);
                $breadcrumbs[] = [
                    'name' => $p,
                    'path' => $pathSoFar,
                    'url' => '/user/files?' . http_build_query(['path' => $pathSoFar]),
                ];
            }
        }

        $parentPath = $this->parentBrowsePath($relDir);
        $parentUrl = $parentPath === null ? null : ($parentPath === '' ? '/user/files' : '/user/files?' . http_build_query(['path' => $parentPath]));

        $display = ($user->display_name !== null && $user->display_name !== '')
            ? $user->display_name
            : $user->email;
        $planName = $user->membershipPlan !== null
            ? (string) $user->membershipPlan->name
            : '—';

        return view('user/files', [
            'userDisplay' => $display,
            'headerUserMeta' => $planName,
            'relDir' => $relDir,
            'parentUrl' => $parentUrl,
            'breadcrumbs' => $breadcrumbs,
            'dirs' => $dirs,
            'files' => $files,
            'isEmpty' => $dirs === [] && $files === [],
        ]);
    }

    private function parentBrowsePath(string $relDir): ?string
    {
        if ($relDir === '') {
            return null;
        }
        $parts = explode('/', $relDir);
        array_pop($parts);

        return implode('/', $parts);
    }
}

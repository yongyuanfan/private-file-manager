<?php

namespace app\controller;

use support\annotation\route\Route;
use support\Request;
use support\Response;
use function json;
use function runtime_path;
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
     * 上传文件
     * @param Request $request
     * @return Response
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
        $dir = runtime_path('uploads');
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return json(['code' => 3, 'msg' => '无法创建上传目录']);
        }
        $base = pathinfo($file->getUploadName(), PATHINFO_FILENAME);
        $base = $base === '' ? 'file' : $base;
        $base = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $base);
        $ext = pathinfo($file->getUploadName(), PATHINFO_EXTENSION);
        $ext = $ext !== '' ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext) : '';
        $destName = $base . '_' . bin2hex(random_bytes(4)) . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $destName;
        try {
            $file->move($dest);
        } catch (\Throwable) {
            return json(['code' => 4, 'msg' => '保存文件失败']);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['saved_as' => $destName]]);
    }
}

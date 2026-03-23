<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use function redirect;

/**
 * 已登录用户访问登录/注册页时跳到上传页。
 */
class GuestOnly implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        if ($request instanceof \support\Request && $request->authUser !== null) {
            return redirect('/home');
        }

        return $handler($request);
    }
}

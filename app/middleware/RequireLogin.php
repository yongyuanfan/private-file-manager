<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use function json;
use function redirect;

class RequireLogin implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        if (!($request instanceof \support\Request) || $request->authUser === null) {
            return $this->unauthorized($request);
        }

        return $handler($request);
    }

    private function unauthorized(Request $request): Response
    {
        $path = $request->path();
        $xhr = strtolower((string) $request->header('x-requested-with', '')) === 'xmlhttprequest';
        $accept = (string) $request->header('accept', '');
        $wantsJson = $xhr
            || str_contains($accept, 'application/json')
            || $path === '/upload';

        if ($wantsJson) {
            return json(['code' => 401, 'msg' => '请先登录'])->withStatus(401);
        }

        $next = $path !== '' && $path !== '/' ? '?next=' . rawurlencode($path) : '';

        return redirect('/login' . $next);
    }
}

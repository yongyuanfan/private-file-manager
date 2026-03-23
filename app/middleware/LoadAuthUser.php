<?php

namespace app\middleware;

use app\service\AuthTokenService;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class LoadAuthUser implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        if ($request instanceof \support\Request) {
            $request->authUser = AuthTokenService::resolveUserFromRequest($request);
        }

        return $handler($request);
    }
}

<?php

namespace app\api\v1;

use app\service\AuthLoginService;
use app\service\AuthTokenService;
use support\annotation\route\Route;
use support\Request;
use support\Response;
use function json;

class AuthApi
{
    #[Route('/api/v1/auth/login', 'POST')]
    public function login(Request $request): Response
    {
        $service = new AuthLoginService();
        $result = $service->attempt(
            $request,
            (string) $request->post('email', ''),
            (string) $request->post('password', ''),
            (string) $request->post('next', '')
        );

        if (!$result['ok']) {
            return json([
                'code' => 1,
                'msg' => $result['msg'],
            ])->withStatus(422);
        }

        [$token] = AuthTokenService::createSession($result['user'], $request);

        return AuthTokenService::attachAuthCookie(json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'redirect' => $result['next'],
                'user' => [
                    'id' => (int) $result['user']->id,
                    'email' => (string) $result['user']->email,
                    'display_name' => (string) ($result['user']->display_name ?? ''),
                ],
            ],
        ]), $token);
    }

    #[Route('/api/v1/auth/me', 'GET')]
    public function me(Request $request): Response
    {
        if ($request->authUser === null) {
            return json([
                'code' => 401,
                'msg' => '请先登录',
            ])->withStatus(401);
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'user' => [
                    'id' => (int) $request->authUser->id,
                    'email' => (string) $request->authUser->email,
                    'display_name' => (string) ($request->authUser->display_name ?? ''),
                ],
            ],
        ]);
    }
}

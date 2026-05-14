<?php

namespace app\api\v1;

use app\service\AuthLoginService;
use app\service\AuthTokenService;
use support\Request;
use support\Response;
use function json;

class AuthApi
{
    #[Route('/api/v1/login', 'POST')]
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
}

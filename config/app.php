<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Request;

return [
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
    'error_reporting' => E_ALL,
    'default_timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Shanghai',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    /**
     * 前台页面顶部展示的网站名称
     */
    'site_name' => getenv('APP_SITE_NAME') ?: '私有文件管理',
    /**
     * 是否开放用户自助注册；为 false 时访问 /register 会跳转登录页并提示「注册已关闭」
     */
    'registration_open' => filter_var(getenv('APP_REGISTRATION_OPEN') ?: false, FILTER_VALIDATE_BOOL),
    /**
     * 外链访问密码 Cookie 签名密钥。
     */
    'share_link_secret' => getenv('APP_SHARE_LINK_SECRET') ?: 'change-me-share-link-secret',
    /**
     * 对外访问本站的完整基础地址，用于生成上传后返回的完整文件 URL。
     */
    'server_url' => rtrim((string) (getenv('APP_SERVER_URL') ?: ''), '/'),
];

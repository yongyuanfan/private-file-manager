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
    /**
     * 前台页面顶部展示的网站名称
     */
    'site_name' => '私有文件管理',
    /**
     * 是否开放用户自助注册；为 false 时访问 /register 会跳转登录页并提示「注册已关闭」
     */
    'registration_open' => true,
    /**
     * 外链访问密码 Cookie 签名密钥。
     */
    'share_link_secret' => 'change-me-share-link-secret',
    'debug' => true,
    'error_reporting' => E_ALL,
    'default_timezone' => 'Asia/Shanghai',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
];

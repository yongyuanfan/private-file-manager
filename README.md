# 私有文件管理

基于 [webman](https://www.workerman.net/webman)（Workerman）的轻量对象存储与用户文件管理 Web 应用：支持登录注册、文件上传与配额、外链分享（可选访问密码）及分享管理。

## 功能概览

- **用户与认证**：登录、登出；注册（可在 `config/app.php` 中通过 `registration_open` 关闭）
- **文件**：上传至 `storage/{用户目录}/`，可按子目录组织；单文件与总用量受会员方案限制
- **访问**：已登录用户可浏览 `/user/files`，通过受控接口读取文件或图片
- **分享**：创建带 token 的外链、`/share/{token}` 落地页、可选密码解锁；分享列表、撤销与访问审计

## 界面预览

以下截图来自仓库 `snapshots/` 目录，按**典型使用流程**排列：先完成账号与登录，再管理文件，最后创建与查看分享；末尾为深色模式与移动端效果。

### 登录与注册

![登录页](snapshots/用户登陆.png)

![注册页](snapshots/用户注册.png)

### 用户中心

![用户中心](snapshots/用户中心.png)

### 文件：上传、管理与访问

![文件上传](snapshots/文件上传.png)

![文件管理](snapshots/文件管理.png)

![文件访问](snapshots/文件访问.png)

### 分享

![创建分享](snapshots/创建分享.png)

![文件分享](snapshots/文件分享.png)

### 主题与移动端

![深色模式](snapshots/深色模式.png)

![手机访问](snapshots/手机访问.jpg)

## 默认账号

- **用户名**： demo@qq.com
- **密码**：12345678

## 技术栈

- PHP ≥ 8.1
- [webman-framework](https://github.com/walkor/webman) 2.x
- MySQL（[webman/database](https://www.workerman.net/doc/webman/db.html)）
- Redis（[webman/redis](https://www.workerman.net/doc/webman/redis.html)）
- Blade 模板（[webman/blade](https://www.workerman.net/doc/webman/view.html)）
- 路由：控制器上的 `#[Route]` 注解

## 环境要求

- PHP 8.1+，建议开启常用扩展（`pdo_mysql`、`json`、`mbstring` 等）
- MySQL 5.7+ / 8.x
- Redis（若项目或会话依赖 Redis，请按 `config/redis.php` 配置）
- [Composer](https://getcomposer.org/)

## 安装与配置

1. **安装依赖**

   ```bash
   composer install
   ```

2. **数据库**  
   在 MySQL 中创建库并导入表结构（请根据团队提供的 SQL 或迁移脚本执行；仓库若未附带迁移文件，需与维护者确认建表方式）。

3. **修改配置**

   - `config/database.php`：填写数据库连接信息。  
   - `config/redis.php`：按环境修改 Redis 地址与密码。  
   - `config/app.php`：  
     - `site_name`：站点名称  
     - `registration_open`：是否开放自助注册  
     - `share_link_secret`：**生产环境务必改为随机长字符串**，用于分享外链 Cookie 签名  
     - `debug`：生产环境建议设为 `false`

4. **可选：`.env`**  
   若使用环境变量覆盖配置，可在项目根目录放置 `.env`（webman 会在启动时加载）。

5. **升级第三方上传能力（已有库）**

   若你的数据库已按旧版本初始化，请执行根目录下的增量脚本：

   ```bash
   mysql -u root -p private-file-manager < database-upgrade-external-upload.sql
   ```

## 启动与访问

- **Linux / macOS**

  ```bash
  php start.php start
  ```

- **Windows**  
  使用项目根目录下的 `windows.php` 启动（参见 [webman Windows 文档](https://www.workerman.net/doc/webman/windows.html)）。

默认 HTTP 监听地址见 `config/process.php` 中 `webman.listen`（常见为 `http://0.0.0.0:8787`）。启动后在浏览器中访问该地址即可。

常用进程命令：`start` | `stop` | `restart` | `reload` | `status`。

## 目录说明（简要）

| 路径 | 说明 |
|------|------|
| `app/controller/` | 控制器与路由注解 |
| `app/model/` | 数据模型 |
| `app/service/` | 业务服务（上传策略、存储、分享等） |
| `app/middleware/` | 中间件（登录校验、访客限制等） |
| `app/view/` | Blade 视图 |
| `public/` | 静态资源（CSS/JS 等） |
| `storage/` | 用户上传文件存储根目录 |
| `config/` | 应用与框架配置 |

## 第三方上传接口

### 设计说明

- 第三方上传必须绑定到系统中的一个用户授权
- 上传文件仍写入该用户目录，并继续受该用户会员上传策略限制
- 文件有效期由 `file_shares` 中 `purpose=retention` 的记录表达
- 未配置有效期时永久有效；配置 `retention_ttl_days` 时按授权自动计算到期时间

### 1. 创建授权

你可以直接在登录后访问 `/user/external-auths` 创建、查看和禁用授权。

如果需要脚本化初始化，也可以手动写库：

1. 生成明文 token
2. 对 token 计算 SHA256
3. 把哈希写入 `user_external_upload_auths.token_hash`

示例：

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
php -r 'echo hash("sha256", "替换为上一步生成的明文token"), PHP_EOL;'
```

示例插入：

```sql
INSERT INTO user_external_upload_auths
  (user_id, name, token_hash, status, default_subdir, retention_ttl_days, created_at)
VALUES
  (1, '第三方系统A', '替换为sha256结果', 'active', 'external', 30, NOW(3));
```

字段说明：

- `user_id`：代表哪个系统用户上传
- `default_subdir`：默认子目录，可为空
- `retention_ttl_days`：文件有效期天数；`NULL` 表示永久

### 2. 上传接口

- 路径：`POST /api/external/upload`
- 鉴权：`Authorization: Bearer <明文token>`
- Content-Type：`multipart/form-data`

请求参数：

- `file`：必填，上传文件
- `subdir`：可选，上传子目录；未传时优先用授权默认目录，否则自动落到当天日期目录

`curl` 示例：

```bash
curl -X POST "http://127.0.0.1:8787/api/external/upload" \
  -H "Authorization: Bearer <明文token>" \
  -F "file=@/path/to/demo.pdf" \
  -F "subdir=contracts/2026"
```

成功响应示例：

```json
{
  "code": 0,
  "msg": "ok",
  "data": {
    "upload_id": 123,
    "saved_as": "1a2b3c4d-xxxx.pdf",
    "relative_path": "demo_at_qq.com/contracts/2026/1a2b3c4d-xxxx.pdf",
    "view_url": "/file?path=contracts%2F2026%2F1a2b3c4d-xxxx.pdf",
    "expires_at": "2026-06-12 09:30:00"
  }
}
```

错误语义：

- `401`：授权无效或未提供 Bearer Token
- `403`：授权已禁用或撤销
- `400`：文件缺失、目录非法、上传不完整
- `422`：超过用户会员上传限制或类型不允许
- `500`：创建 retention 失败；此时上传记录与文件会被回滚/删除

### 3. 文件有效期行为

- 第三方上传成功后，系统会自动创建一条 `file_shares.purpose=retention` 记录
- 到期后，以下入口都会拒绝访问：
  - `/file`
  - `/image`
  - `/share/{token}/file`
- 历史普通上传文件没有 `retention` 记录时，仍按旧逻辑允许访问

## 相关文档

- [webman 官方文档](https://webman.workerman.net)
- [webman 安装说明](https://www.workerman.net/doc/webman/install.html)

## 许可证

本项目依赖的 webman 及相关组件遵循各自开源协议；仓库根目录 `LICENSE` 为 webman 原始 MIT 许可证文本。若你对本仓库另有版权声明，请在 `LICENSE` 或文档中补充说明。

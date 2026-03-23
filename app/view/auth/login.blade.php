<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登录</title>
    <link rel="stylesheet" href="/css/pages/home-upload.css">
    <style>
        .auth-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; font-family: var(--font); background: var(--bg-deep); color: var(--text); }
        .auth-card { width: 100%; max-width: 400px; background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px 24px; box-shadow: var(--card-shadow); }
        .auth-card h1 { font-size: 1.35rem; margin: 0 0 8px; }
        .auth-card .sub { color: var(--muted); font-size: 0.9rem; margin-bottom: 20px; }
        .auth-field { margin-bottom: 16px; }
        .auth-field label { display: block; font-size: 0.85rem; margin-bottom: 6px; color: var(--muted); }
        .auth-field input { width: 100%; box-sizing: border-box; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text); font-size: 1rem; }
        .auth-error { background: var(--toast-error-border); color: var(--danger); padding: 10px 12px; border-radius: 10px; font-size: 0.9rem; margin-bottom: 16px; }
        .auth-actions { display: flex; gap: 12px; align-items: center; margin-top: 20px; flex-wrap: wrap; }
        .auth-actions .btn-primary { flex: 1; min-width: 120px; }
        .auth-actions a { color: var(--accent); text-decoration: none; font-size: 0.9rem; }
        .auth-actions a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <h1>登录</h1>
        <p class="sub">登录后可按会员等级上传文件。</p>
        @if(($error ?? '') !== '')
            <div class="auth-error">{{ $error }}</div>
        @endif
        <form method="post" action="/login">
            <input type="hidden" name="next" value="{{ $next }}">
            <div class="auth-field">
                <label for="email">邮箱</label>
                <input type="email" id="email" name="email" required autocomplete="email" autofocus>
            </div>
            <div class="auth-field">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">登录</button>
                <a href="/register">没有账号？注册</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>

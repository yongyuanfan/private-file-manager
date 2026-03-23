<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注册</title>
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
        <h1>注册</h1>
        <p class="sub">注册后默认使用免费会员配额与类型限制。</p>
        @if(($error ?? '') !== '')
            <div class="auth-error">{{ $error }}</div>
        @endif
        <form method="post" action="/register">
            <div class="auth-field">
                <label for="email">邮箱</label>
                <input type="email" id="email" name="email" required autocomplete="email" autofocus>
            </div>
            <div class="auth-field">
                <label for="display_name">昵称（可选）</label>
                <input type="text" id="display_name" name="display_name" maxlength="64" autocomplete="nickname">
            </div>
            <div class="auth-field">
                <label for="password">密码（至少 8 位）</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
            </div>
            <div class="auth-field">
                <label for="password_confirmation">确认密码</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>
            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">注册</button>
                <a href="/login">已有账号？登录</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>

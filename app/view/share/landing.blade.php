<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>文件分享</title>
    <link rel="stylesheet" href="/css/pages/home-upload.css">
    <style>
        .share-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; font-family: var(--font); background: var(--bg-deep); color: var(--text); }
        .share-card { width: 100%; max-width: 440px; background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px 24px; box-shadow: var(--card-shadow); }
        .share-card h1 { font-size: 1.25rem; margin: 0 0 8px; word-break: break-all; }
        .share-meta { color: var(--muted); font-size: 0.88rem; margin: 0 0 20px; line-height: 1.5; }
        .share-meta span { display: block; }
        .share-alert { background: var(--toast-error-border); color: var(--danger); padding: 10px 12px; border-radius: 10px; font-size: 0.9rem; margin-bottom: 16px; }
        .share-field { margin-bottom: 14px; }
        .share-field label { display: block; font-size: 0.85rem; margin-bottom: 6px; color: var(--muted); }
        .share-field input { width: 100%; box-sizing: border-box; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text); font-size: 1rem; }
        .share-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-top: 20px; }
        .share-actions .btn-primary { flex: 1; min-width: 140px; }
        .share-actions a.btn { text-decoration: none; text-align: center; }
    </style>
</head>
<body>
<div class="share-page">
    <div class="share-card">
        <h1>{{ $fileName }}</h1>
        <p class="share-meta">
            <span>过期时间：{{ $expiresLabel }}</span>
            <span>已用 / 限额次数：{{ $viewsLabel }}</span>
        </p>

        @if($revoked)
            <div class="share-alert">该分享已被发布者撤销。</div>
        @elseif($expired)
            <div class="share-alert">分享已过期。</div>
        @elseif($exhausted)
            <div class="share-alert">查看次数已用完。</div>
        @else
            @if($needsPassword)
                @if($passwordError)
                    <div class="share-alert">密码错误，请重试。</div>
                @endif
                <form method="post" action="/share/{{ $token }}/unlock">
                    <div class="share-field">
                        <label for="password">访问密码</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password" autofocus>
                    </div>
                    <div class="share-actions">
                        <button type="submit" class="btn btn-primary">验证并继续</button>
                    </div>
                </form>
            @else
                <p class="share-meta" style="margin-bottom:16px">点击下方打开文件；每次成功打开将计为一次查看（若设置了次数上限）。</p>
                <div class="share-actions">
                    <a class="btn btn-primary" href="{{ $fileUrl }}">打开文件</a>
                </div>
            @endif
        @endif
    </div>
</div>
</body>
</html>

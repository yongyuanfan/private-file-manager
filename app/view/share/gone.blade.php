<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>分享不可用</title>
    <link rel="stylesheet" href="/css/pages/home-upload.css">
    <style>
        .share-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; font-family: var(--font); background: var(--bg-deep); color: var(--text); }
        .share-card { width: 100%; max-width: 420px; background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px 24px; box-shadow: var(--card-shadow); text-align: center; }
        .share-card h1 { font-size: 1.2rem; margin: 0 0 12px; }
        .share-card p { margin: 0; color: var(--muted); font-size: 0.95rem; line-height: 1.5; }
    </style>
</head>
<body>
<div class="share-page">
    <div class="share-card">
        <h1>无法访问</h1>
        <p>{{ $message ?? '分享不存在或已失效' }}</p>
    </div>
</div>
</body>
</html>

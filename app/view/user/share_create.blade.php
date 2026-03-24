<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>创建外链分享</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('home-upload-theme');
                if (t !== 'light' && t !== 'dark' && t !== 'system') t = 'light';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link rel="stylesheet" href="/css/pages/home-upload.css">
    <link rel="stylesheet" href="/css/pages/user-center.css">
    <link rel="stylesheet" href="/css/pages/user-files.css">
</head>
<body>
@include('partials.site-header', [
    'userDisplay' => $userDisplay,
    'headerUserMeta' => $headerUserMeta,
    'headerNav' => $headerNav,
])
<div class="page page--user-center page--user-files page--share-create">
    <header class="page-header">
        <div class="page-header__main">
            <p class="uc-breadcrumb">
                <a href="/home" class="uc-breadcrumb__link">文件上传</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <a href="/user" class="uc-breadcrumb__link">用户中心</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <a href="/user/files" class="uc-breadcrumb__link">文件管理</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <span class="uc-breadcrumb__here">创建分享</span>
            </p>
            <h1>创建外链分享</h1>
            <p class="lead uc-lead">设置查看次数上限、过期时间与可选密码后，将跳转到<a href="/user/shares" class="uc-breadcrumb__link">分享管理</a>查看链接。</p>
        </div>
    </header>

    @if(!empty($errorMessage))
        <p class="uc-flash uc-flash--err" role="alert">{{ $errorMessage }}</p>
    @endif

    <div class="uc-layout">
        <section class="card uc-card fm-card-shell fm-share-create-card" aria-labelledby="fm-share-create-title">
            <h2 id="fm-share-create-title" class="uc-section-title fm-sr-only">分享选项</h2>
            <p class="fm-share-create__file">文件：<strong>{{ $fileLabel }}</strong></p>
            <form method="post" action="/user/shares" class="fm-share-create__form">
                <input type="hidden" name="user_upload_id" value="{{ $uploadId }}">
                <input type="hidden" name="from" value="{{ $fromRel }}">
                <div class="fm-share-create__field">
                    <label for="fm-share-max-views">最大查看次数（留空不限）</label>
                    <input type="number" name="max_views" id="fm-share-max-views" min="1" max="999999" placeholder="例如 10" value="{{ $fieldValues['max_views'] }}">
                </div>
                <div class="fm-share-create__field">
                    <label for="fm-share-expires">过期时间（留空不过期）</label>
                    <input type="datetime-local" name="expires_at" id="fm-share-expires" value="{{ $fieldValues['expires_at'] }}">
                </div>
                <div class="fm-share-create__field">
                    <label for="fm-share-password">访问密码（留空则公开，至少 4 位）</label>
                    <input type="password" name="password" id="fm-share-password" autocomplete="new-password" maxlength="128" placeholder="可选">
                </div>
                <div class="fm-share-create__actions">
                    <a href="{{ $backUrl }}" class="btn btn-ghost" style="text-align: center;">返回</a>
                    <button type="submit" class="btn btn-primary">生成链接</button>
                </div>
            </form>
        </section>
    </div>
</div>

<script src="/js/pages/home-theme.js"></script>
</body>
</html>

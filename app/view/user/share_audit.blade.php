<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>分享审计</title>
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
    'headerNav' => 'user_shares',
])
<div class="page page--user-center page--user-files">
    <header class="page-header">
        <div class="page-header__main">
            <p class="uc-breadcrumb">
                <a href="/user/shares" class="uc-breadcrumb__link">分享管理</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <span class="uc-breadcrumb__here">审计记录</span>
            </p>
            <h1>分享审计</h1>
            <p class="lead uc-lead">文件：<strong>{{ $fileLabel }}</strong> · 落地页：<a href="{{ $landingUrl }}" class="uc-breadcrumb__link" target="_blank" rel="noopener noreferrer">{{ $landingUrl }}</a></p>
        </div>
    </header>

    <div class="uc-layout">
        <section class="card uc-card fm-card-shell">
            @if(count($logs) === 0)
                <p class="uc-empty">暂无审计记录。</p>
            @else
                <div class="uc-table-scroll fm-table-scroll">
                    <table class="uc-table fm-table">
                        <thead>
                        <tr>
                            <th scope="col">时间</th>
                            <th scope="col">动作</th>
                            <th scope="col">详情</th>
                            <th scope="col">IP</th>
                            <th scope="col">User-Agent</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($logs as $row)
                            <tr>
                                <td class="uc-table__nowrap">{{ $row['time'] }}</td>
                                <td><code class="fm-code">{{ $row['action'] }}</code></td>
                                <td class="uc-table__ellipsis" title="{{ $row['detail'] }}">{{ $row['detail'] }}</td>
                                <td class="uc-table__nowrap">{{ $row['ip'] }}</td>
                                <td class="uc-table__ellipsis" title="{{ $row['ua'] }}">{{ $row['ua'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>

<script src="/js/pages/home-theme.js"></script>
</body>
</html>

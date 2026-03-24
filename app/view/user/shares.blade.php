<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>分享管理</title>
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
                <a href="/home" class="uc-breadcrumb__link">文件上传</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <a href="/user" class="uc-breadcrumb__link">用户中心</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <span class="uc-breadcrumb__here">分享管理</span>
            </p>
            <h1>分享管理</h1>
            <p class="lead uc-lead">查看已创建的外链、复制落地页地址、撤销分享或查看访问审计。在<a href="/user/files" class="uc-breadcrumb__link">文件管理</a>中可为单个文件新建分享。</p>
        </div>
    </header>

    @if(!empty($flashCreated))
        <p class="uc-flash uc-flash--ok" role="status">分享已创建。</p>
    @endif
    @if(!empty($flashRevoked))
        <p class="uc-flash uc-flash--ok" role="status">已撤销分享。</p>
    @endif

    <div class="uc-layout">
        <section class="card uc-card fm-card-shell">
            @if(count($items) === 0)
                <p class="uc-empty">暂无分享。请在文件管理中对文件使用「创建分享」。</p>
            @else
                <div class="uc-table-scroll fm-table-scroll">
                    <table class="uc-table fm-table">
                        <thead>
                        <tr>
                            <th scope="col">文件</th>
                            <th scope="col" class="uc-table__narrow">密码</th>
                            <th scope="col">次数</th>
                            <th scope="col">过期</th>
                            <th scope="col">状态</th>
                            <th scope="col" class="uc-table__narrow">操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($items as $it)
                            <tr>
                                <td class="uc-table__ellipsis">
                                    <span class="fm-table__name" title="{{ $it['file_label'] }}">{{ $it['file_label'] }}</span>
                                    <div class="fm-share-url"><code>{{ $it['landing_url'] }}</code></div>
                                </td>
                                <td class="uc-table__narrow">{{ $it['has_password'] ? '是' : '否' }}</td>
                                <td>{{ $it['views'] }}</td>
                                <td>{{ $it['expires_label'] }}</td>
                                <td>
                                    @if($it['revoked'])
                                        <span class="fm-badge fm-badge--muted">已撤销</span>
                                    @else
                                        <span class="fm-badge">有效</span>
                                    @endif
                                </td>
                                <td class="uc-table__narrow fm-share-actions">
                                    <a href="{{ $it['landing_url'] }}" class="uc-link" target="_blank" rel="noopener noreferrer">打开</a>
                                    <a href="/user/shares/{{ $it['id'] }}/audit" class="uc-link">审计</a>
                                    @if(!$it['revoked'])
                                        <form method="post" action="/user/shares/{{ $it['id'] }}/revoke" class="fm-inline-form" onsubmit="return confirm('确定撤销该分享？外链将立即失效。');">
                                            <button type="submit" class="uc-link fm-link-btn">撤销</button>
                                        </form>
                                    @endif
                                </td>
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

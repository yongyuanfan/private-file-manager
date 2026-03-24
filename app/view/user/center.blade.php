<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>用户中心</title>
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
</head>
<body>
@include('partials.site-header', [
    'userDisplay' => $userDisplay,
    'headerUserMeta' => $planName,
    'headerNav' => 'user_center',
])
<div class="page page--user-center">
    <header class="page-header">
        <div class="page-header__main">
            <p class="uc-breadcrumb"><a href="/home" class="uc-breadcrumb__link">文件上传</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span><span class="uc-breadcrumb__here">用户中心</span></p>
            <h1>用户中心</h1>
            <p class="lead uc-lead">查看账号信息与上传统计；最近上传可在此打开，也可前往<a href="/user/files" class="uc-breadcrumb__link">文件管理</a>按目录浏览全部资源，或管理<a href="/user/shares" class="uc-breadcrumb__link">外链分享</a>。</p>
        </div>
    </header>

    <div class="uc-layout">
        <section class="card uc-card" aria-labelledby="uc-profile-heading">
            <h2 id="uc-profile-heading" class="uc-section-title">账号概览</h2>
            <dl class="uc-dl">
                <div class="uc-dl__row">
                    <dt>邮箱</dt>
                    <dd>{{ $userEmail }}</dd>
                </div>
                <div class="uc-dl__row">
                    <dt>显示名</dt>
                    <dd>{{ $userDisplay }}</dd>
                </div>
                <div class="uc-dl__row">
                    <dt>当前会员</dt>
                    <dd>{{ $planName }}</dd>
                </div>
            </dl>
        </section>

        <section class="card uc-card" aria-labelledby="uc-stats-heading">
            <h2 id="uc-stats-heading" class="uc-section-title">上传统计</h2>
            <div class="uc-stats-grid" role="list">
                <article class="uc-stat" role="listitem">
                    <p class="uc-stat__value">{{ $stats['total_count'] }}</p>
                    <p class="uc-stat__label">累计文件数</p>
                </article>
                <article class="uc-stat" role="listitem">
                    <p class="uc-stat__value">{{ $stats['total_bytes_label'] }}</p>
                    <p class="uc-stat__label">累计占用空间</p>
                </article>
                <article class="uc-stat" role="listitem">
                    <p class="uc-stat__value">{{ $stats['month_count'] }}</p>
                    <p class="uc-stat__label">本月上传数</p>
                    <p class="uc-stat__sub">共{{ $stats['month_bytes_label'] }}</p>
                </article>
                <article class="uc-stat" role="listitem">
                    <p class="uc-stat__value">{{ $stats['today_count'] }}</p>
                    <p class="uc-stat__label">今日上传数</p>
                </article>
            </div>
        </section>

        <section class="card uc-card" aria-labelledby="uc-ext-heading">
            <h2 id="uc-ext-heading" class="uc-section-title">按扩展名分布（Top 12）</h2>
            @if(count($byExtension) === 0)
                <p class="uc-empty">暂无上传记录。</p>
            @else
                <div class="uc-table-scroll">
                    <table class="uc-table">
                        <thead>
                        <tr>
                            <th scope="col">类型</th>
                            <th scope="col" class="uc-table__num">文件数</th>
                            <th scope="col" class="uc-table__num">合计大小</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($byExtension as $row)
                            <tr>
                                <td><code class="uc-code">{{ $row['label'] }}</code></td>
                                <td class="uc-table__num">{{ $row['count'] }}</td>
                                <td class="uc-table__num">{{ $row['bytes_label'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card uc-card" aria-labelledby="uc-recent-heading">
            <h2 id="uc-recent-heading" class="uc-section-title">最近上传</h2>
            @if(count($recent) === 0)
                <p class="uc-empty">暂无上传记录，前往<a href="/home">文件上传</a>开始上传。</p>
            @else
                <div class="uc-table-scroll">
                    <table class="uc-table uc-table--recent">
                        <thead>
                        <tr>
                            <th scope="col">文件名</th>
                            <th scope="col">存储路径</th>
                            <th scope="col" class="uc-table__num">大小</th>
                            <th scope="col" class="uc-table__time">上传时间</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($recent as $item)
                            <tr>
                                <td class="uc-table__ellipsis" title="{{ $item['name'] }}"><a href="{{ $item['file_url'] }}" class="uc-link" target="_blank" rel="noopener noreferrer">{{ $item['name'] }}</a></td>
                                <td class="uc-table__mono uc-table__ellipsis" title="{{ $item['path_display'] }}">{{ $item['path_display'] }}</td>
                                <td class="uc-table__num">{{ $item['size_label'] }}</td>
                                <td class="uc-table__time">{{ $item['created_label'] }}</td>
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

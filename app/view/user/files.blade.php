<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>文件管理</title>
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
    'headerNav' => 'user_files',
])
<div class="page page--user-center page--user-files">
    <header class="page-header">
        <div class="page-header__main">
            <p class="uc-breadcrumb">
                <a href="/home" class="uc-breadcrumb__link">文件上传</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <a href="/user" class="uc-breadcrumb__link">用户中心</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <span class="uc-breadcrumb__here">文件管理</span>
            </p>
            <h1>文件管理</h1>
            <p class="lead uc-lead">按目录浏览已上传文件；点击文件夹进入下一级，文件名可打开预览或下载。可为文件创建限次、限时、可选密码的外链（<a href="/user/shares" class="uc-breadcrumb__link">分享管理</a>）。</p>
        </div>
    </header>

    <div class="uc-layout">
        <section class="card uc-card fm-card-shell" aria-labelledby="fm-heading">
            <h2 id="fm-heading" class="uc-section-title fm-sr-only">资源列表</h2>

            <div class="fm-toolbar">
                <div class="fm-toolbar__actions">
                    @if($parentUrl !== null)
                        <a href="{{ $parentUrl }}" class="btn btn-ghost btn-sm fm-toolbar__back">← 上级目录</a>
                    @else
                        <span class="btn btn-ghost btn-sm fm-toolbar__back fm-btn--muted" aria-disabled="true">← 上级目录</span>
                    @endif

                    <div class="fm-view-toggle" role="group" aria-label="展示方式">
                        <button type="button" class="fm-view-toggle__btn" data-fm-view="grid" aria-pressed="true">宫格</button>
                        <button type="button" class="fm-view-toggle__btn" data-fm-view="list" aria-pressed="false">列表</button>
                    </div>
                </div>

                <nav class="fm-path" aria-label="当前路径">
                    <a href="/user/files" class="fm-path__root {{ $relDir === '' ? 'is-here' : '' }}">根目录</a>
                    @foreach($breadcrumbs as $i => $crumb)
                        <span class="fm-path__sep" aria-hidden="true">/</span>
                        @if($i === count($breadcrumbs) - 1)
                            <span class="fm-path__here">{{ $crumb['name'] }}</span>
                        @else
                            <a href="{{ $crumb['url'] }}" class="fm-path__seg">{{ $crumb['name'] }}</a>
                        @endif
                    @endforeach
                </nav>
            </div>

            @if($isEmpty)
                <p class="uc-empty fm-empty">当前目录下暂无子目录或文件。</p>
            @endif

            <div id="fm-browser" class="fm-browser is-grid">
                <ul class="fm-grid" role="list" @if($isEmpty) hidden @endif>
                    @foreach($dirs as $d)
                        <li class="fm-grid__cell">
                            <a href="{{ $d['url'] }}" class="fm-tile fm-tile--dir">
                                <span class="fm-tile__icon fm-tile__icon--dir" aria-hidden="true"></span>
                                <span class="fm-tile__name">{{ $d['name'] }}</span>
                                <span class="fm-tile__meta">文件夹</span>
                            </a>
                        </li>
                    @endforeach
                    @foreach($files as $f)
                        <li class="fm-grid__cell">
                            <div class="fm-grid__cell-inner">
                                <a href="{{ $f['view_url'] }}" class="fm-tile fm-tile--file" target="_blank" rel="noopener noreferrer">
                                    <span class="fm-tile__icon fm-tile__icon--file" aria-hidden="true"></span>
                                    <span class="fm-tile__name" title="{{ $f['name'] }}">{{ $f['name'] }}</span>
                                    <span class="fm-tile__meta">{{ $f['size_label'] }}</span>
                                </a>
                                <div class="fm-tile__share">
                                    <button type="button" data-fm-share data-upload-id="{{ $f['upload_id'] }}" data-file-name="{{ $f['name'] }}">创建分享</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="uc-table-scroll fm-table-scroll" @if($isEmpty) hidden @endif>
                    <table class="uc-table fm-table">
                        <thead>
                        <tr>
                            <th scope="col">名称</th>
                            <th scope="col" class="uc-table__narrow">类型</th>
                            <th scope="col" class="uc-table__num">大小</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($dirs as $d)
                            <tr>
                                <td class="uc-table__ellipsis">
                                    <a href="{{ $d['url'] }}" class="uc-link fm-table__name fm-table__name--dir">{{ $d['name'] }}</a>
                                </td>
                                <td class="uc-table__narrow"><span class="fm-badge">文件夹</span></td>
                                <td class="uc-table__num">—</td>
                            </tr>
                        @endforeach
                        @foreach($files as $f)
                            <tr>
                                <td class="uc-table__ellipsis">
                                    <a href="{{ $f['view_url'] }}" class="uc-link fm-table__name" target="_blank" rel="noopener noreferrer" title="{{ $f['name'] }}">{{ $f['name'] }}</a>
                                    <div class="fm-table__share">
                                        <button type="button" data-fm-share data-upload-id="{{ $f['upload_id'] }}" data-file-name="{{ $f['name'] }}">创建分享</button>
                                    </div>
                                </td>
                                <td class="uc-table__narrow"><span class="fm-badge fm-badge--muted">文件</span></td>
                                <td class="uc-table__num">{{ $f['size_label'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<div id="fm-share-modal" class="fm-modal" hidden>
    <div class="fm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="fm-share-title">
        <h2 id="fm-share-title" class="fm-modal__title">创建外链分享</h2>
        <p id="fm-share-hint" class="fm-modal__hint"></p>
        <form id="fm-share-form">
            <input type="hidden" name="user_upload_id" id="fm-share-upload-id" value="">
            <div class="fm-modal__field">
                <label for="fm-share-max-views">最大查看次数（留空不限）</label>
                <input type="number" name="max_views" id="fm-share-max-views" min="1" max="999999" placeholder="例如 10">
            </div>
            <div class="fm-modal__field">
                <label for="fm-share-expires">过期时间（留空不过期）</label>
                <input type="datetime-local" name="expires_at" id="fm-share-expires">
            </div>
            <div class="fm-modal__field">
                <label for="fm-share-password">访问密码（留空则公开，至少 4 位）</label>
                <input type="password" name="password" id="fm-share-password" autocomplete="new-password" maxlength="128" placeholder="可选">
            </div>
            <p id="fm-share-result" class="fm-modal__hint" hidden></p>
            <div class="fm-modal__actions">
                <button type="button" class="btn btn-ghost" id="fm-share-cancel">取消</button>
                <button type="submit" class="btn btn-primary" id="fm-share-submit">生成链接</button>
            </div>
        </form>
    </div>
</div>

<script src="/js/pages/home-theme.js"></script>
<script src="/js/pages/user-files.js"></script>
<script src="/js/pages/file-share-modal.js"></script>
</body>
</html>

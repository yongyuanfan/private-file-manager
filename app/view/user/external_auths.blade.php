<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>第三方授权</title>
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
    'headerNav' => 'user_external_auths',
])
<div class="page page--user-center page--user-files page--shares">
    <header class="page-header">
        <div class="page-header__main">
            <p class="uc-breadcrumb">
                <a href="/home" class="uc-breadcrumb__link">文件上传</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <a href="/user" class="uc-breadcrumb__link">用户中心</a><span class="uc-breadcrumb__sep" aria-hidden="true">/</span>
                <span class="uc-breadcrumb__here">第三方授权</span>
            </p>
            <h1>第三方授权</h1>
            <p class="lead uc-lead">为外部系统创建上传授权。第三方上传的文件默认永久有效，也可在授权上配置有效期天数；创建后页面会展示一次 Token 和可直接调用的上传示例。</p>
        </div>
    </header>

    @if(!empty($flashCreated))
        <p class="uc-flash uc-flash--ok" role="status">授权已创建。请立即保存下面的明文 Token，该值只展示这一次。</p>
    @endif
    @if(!empty($flashDisabled))
        <p class="uc-flash uc-flash--ok" role="status">授权已禁用。</p>
    @endif
    @if(!empty($flashEnabled))
        <p class="uc-flash uc-flash--ok" role="status">授权已重新启用。</p>
    @endif
    @if(!empty($flashDeleted))
        <p class="uc-flash uc-flash--ok" role="status">授权已删除。</p>
    @endif
    @if(!empty($errorMessage))
        <p class="uc-flash uc-flash--err" role="alert">{{ $errorMessage }}</p>
    @endif

    @if(!empty($createdToken))
        <section class="card uc-card" aria-labelledby="ext-token-heading" style="margin-bottom: 16px;">
            <h2 id="ext-token-heading" class="uc-section-title">新建 Token</h2>
            <p class="uc-empty" style="text-align:left; margin-bottom: 10px;">请复制保存：<code class="uc-code">{{ $createdToken }}</code></p>
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom: 12px;">
                <button type="button" class="btn btn-primary btn-sm" data-copy-text="{{ $createdToken }}" data-copy-label="Token">复制 Token</button>
                <span class="fm-shares-table__val">该值只展示一次，请立即保存到第三方系统配置中。</span>
            </div>
            <div style="border:1px solid rgba(127,127,127,.22); border-radius:12px; padding:12px; background:rgba(127,127,127,.06);">
                <p class="fm-shares-table__val" style="margin:0 0 8px 0; text-align:left;">调用示例</p>
                @php
                    $curlExample = 'curl -X POST "' . request()->host(true) . '/api/external/upload" \\' . "\n"
                        . '  -H "Authorization: Bearer ' . $createdToken . '" \\' . "\n"
                        . '  -F "file=@/path/to/demo.pdf" \\' . "\n"
                        . '  -F "subdir=external/demo"';
                @endphp
                <pre style="margin:0 0 10px 0; white-space:pre-wrap; word-break:break-all;"><code>{{ $curlExample }}</code></pre>
                <button type="button" class="btn btn-ghost btn-sm" data-copy-text="{{ $curlExample }}" data-copy-label="curl 示例">复制 curl 示例</button>
            </div>
        </section>
    @endif

    <div class="uc-layout">
        <section class="card uc-card" aria-labelledby="ext-create-heading">
            <h2 id="ext-create-heading" class="uc-section-title">创建授权</h2>
            <form method="post" action="/user/external-auths" class="fm-share-create">
                <div class="fm-share-create__grid">
                    <div class="fm-share-create__field">
                        <label for="ext-name">授权名称</label>
                        <input type="text" id="ext-name" name="name" maxlength="100" required placeholder="例如：合同系统 / OA / 小程序">
                    </div>
                    <div class="fm-share-create__field">
                        <label for="ext-subdir">默认子目录</label>
                        <input type="text" id="ext-subdir" name="default_subdir" placeholder="留空则按日期目录，例如 external/contracts">
                    </div>
                    <div class="fm-share-create__field">
                        <label for="ext-ttl">有效期天数</label>
                        <input type="number" id="ext-ttl" name="retention_ttl_days" min="1" max="3650" placeholder="留空表示永久">
                    </div>
                </div>
                <div class="fm-share-create__actions">
                    <button type="submit" class="btn btn-primary">创建授权</button>
                </div>
            </form>
        </section>

        <section class="card uc-card fm-card-shell fm-shares-shell">
            <h2 class="uc-section-title">现有授权</h2>
            @if(count($items) === 0)
                <p class="uc-empty">暂无第三方授权。</p>
            @else
                <div class="uc-table-scroll fm-table-scroll fm-shares-scroll">
                    <table class="uc-table fm-table fm-shares-table">
                        <thead>
                        <tr>
                            <th scope="col">名称</th>
                            <th scope="col">默认目录</th>
                            <th scope="col">有效期</th>
                            <th scope="col">最近使用</th>
                            <th scope="col">状态</th>
                            <th scope="col" class="uc-table__narrow">操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($items as $it)
                            <tr>
                                <td data-label="名称"><span class="fm-table__name">{{ $it['name'] }}</span><div class="fm-table__share"><span class="fm-shares-table__val">创建于 {{ $it['created_label'] }}</span></div></td>
                                <td data-label="默认目录"><span class="fm-shares-table__val">{{ $it['default_subdir'] }}</span></td>
                                <td data-label="有效期"><span class="fm-shares-table__val">{{ $it['retention_label'] }}</span></td>
                                <td data-label="最近使用"><span class="fm-shares-table__val">{{ $it['last_used_label'] }}</span></td>
                                <td data-label="状态">
                                    @if($it['disabled'])
                                        <span class="fm-badge fm-badge--muted">已禁用</span>
                                    @else
                                        <span class="fm-badge">启用中</span>
                                    @endif
                                </td>
                                <td class="uc-table__narrow fm-share-actions" data-label="操作">
                                    @if(!$it['disabled'])
                                        <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                                            <form method="post" action="/user/external-auths/{{ $it['id'] }}/disable" onsubmit="return confirm('确定禁用该授权？禁用后第三方将无法继续上传。');">
                                                <button type="submit" class="btn btn-ghost btn-sm">禁用</button>
                                            </form>
                                            <form method="post" action="/user/external-auths/{{ $it['id'] }}" onsubmit="return confirm('确定删除该授权？删除后无法恢复，第三方将立即失效。');">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" class="btn btn-ghost btn-sm">删除</button>
                                            </form>
                                        </div>
                                    @else
                                        <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                                            <form method="post" action="/user/external-auths/{{ $it['id'] }}/enable">
                                                <button type="submit" class="btn btn-ghost btn-sm">启用</button>
                                            </form>
                                            <form method="post" action="/user/external-auths/{{ $it['id'] }}" onsubmit="return confirm('确定删除该授权？删除后无法恢复。');">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" class="btn btn-ghost btn-sm">删除</button>
                                            </form>
                                        </div>
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
<script src="/js/pages/external-auths.js"></script>
</body>
</html>

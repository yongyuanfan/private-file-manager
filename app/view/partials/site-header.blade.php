@php
    $siteName = $siteName ?? (string) config('app.site_name', 'Xinkin OSS');
@endphp
<header class="site-header">
    <div class="site-header__bar">
        <a href="/home" class="site-header__brand">{{ $siteName }}</a>
        <div class="site-header__right">
            <div class="site-header__user" role="group" aria-label="当前用户">
                <div class="site-header__user-text">
                    <span class="site-header__user-name">{{ $userDisplay }}</span>
                    @if(!empty($headerUserMeta))
                        <span class="site-header__user-meta">{{ $headerUserMeta }}</span>
                    @endif
                </div>
                <div class="site-header__user-actions">
                    @if(($headerNav ?? '') === 'upload')
                        <a href="/user" class="btn btn-ghost btn-sm">用户中心</a>
                        <form class="site-header__logout-form" method="post" action="/logout">
                            <button type="submit" class="btn btn-ghost btn-sm">退出</button>
                        </form>
                    @elseif(($headerNav ?? '') === 'user_center')
                        <a href="/user/files" class="btn btn-ghost btn-sm">文件管理</a>
                        <a href="/home" class="btn btn-ghost btn-sm">返回上传</a>
                        <form class="site-header__logout-form" method="post" action="/logout">
                            <button type="submit" class="btn btn-ghost btn-sm">退出</button>
                        </form>
                    @elseif(($headerNav ?? '') === 'user_files')
                        <a href="/user" class="btn btn-ghost btn-sm">用户中心</a>
                        <a href="/home" class="btn btn-ghost btn-sm">返回上传</a>
                        <form class="site-header__logout-form" method="post" action="/logout">
                            <button type="submit" class="btn btn-ghost btn-sm">退出</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="theme-switcher" role="group" aria-label="颜色主题">
                <button type="button" data-theme-value="light" aria-pressed="true">浅色</button>
                <button type="button" data-theme-value="dark" aria-pressed="false">深色</button>
                <button type="button" data-theme-value="system" aria-pressed="false">跟随系统</button>
            </div>
        </div>
    </div>
</header>

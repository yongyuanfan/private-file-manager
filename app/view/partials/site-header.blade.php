@php
    $siteName = $siteName ?? (string) config('app.site_name', 'Xinkin OSS');
@endphp
<header class="site-header">
    <div class="site-header__bar">
        <a href="/home" class="site-header__brand">{{ $siteName }}</a>
        <div class="site-header__right">
            <details class="site-header__user-dropdown">
                <summary class="site-header__user-trigger" aria-label="用户菜单，点击展开">
                    <div class="site-header__user-text">
                        <span class="site-header__user-name">{{ $userDisplay }}</span>
                        @if(!empty($headerUserMeta))
                            <span class="site-header__user-meta">{{ $headerUserMeta }}</span>
                        @endif
                    </div>
                    <span class="site-header__user-chevron" aria-hidden="true"></span>
                </summary>
                <div class="site-header__dropdown-panel">
                    <div class="site-header__dropdown-body">
                        @if(($headerNav ?? '') === 'upload')
                            <a href="/user" class="site-header__dropdown-item">用户中心</a>
                            <a href="/user/files" class="site-header__dropdown-item">文件管理</a>
                        @elseif(($headerNav ?? '') === 'user_center')
                            <a href="/user/files" class="site-header__dropdown-item">文件管理</a>
                            <a href="/home" class="site-header__dropdown-item">上传文件</a>
                        @elseif(($headerNav ?? '') === 'user_files')
                            <a href="/user" class="site-header__dropdown-item">用户中心</a>
                            <a href="/home" class="site-header__dropdown-item">上传文件</a>
                        @endif
                        <div class="site-header__dropdown-sep" aria-hidden="true"></div>
                        <form class="site-header__logout-form" method="post" action="/logout">
                            <button type="submit" class="site-header__dropdown-item site-header__dropdown-item--danger">退出</button>
                        </form>
                    </div>
                </div>
            </details>
        </div>
    </div>
    <div class="theme-switcher" role="group" aria-label="颜色主题">
        <button type="button" data-theme-value="light" aria-pressed="true">浅色</button>
        <button type="button" data-theme-value="dark" aria-pressed="false">深色</button>
        <button type="button" data-theme-value="system" aria-pressed="false">跟随系统</button>
    </div>
</header>

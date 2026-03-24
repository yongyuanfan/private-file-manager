<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>批量上传</title>
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
</head>
<body>
@include('partials.site-header', [
    'userDisplay' => $userDisplay,
    'headerUserMeta' => $limits['plan_name'] . ' · 本周期已上传 ' . $limits['used_uploads'] . ($limits['max_uploads'] !== null ? ' / ' . $limits['max_uploads'] : '（不限）'),
    'headerNav' => 'upload',
])
<div class="page">
    <header class="page-header">
        <div class="page-header__main">
            <h1>文件上传</h1>
            <p class="lead">点击或拖拽文件到下方区域加入列表，确认后点击「开始上传」。支持多选与批量上传，每个文件单独显示进度。</p>
        </div>
    </header>

    <div class="card">
        <div
            id="upload-zone"
            class="upload-zone"
            data-upload-url="{{ $uploadUrl }}"
            data-limits="{{ json_encode($limits, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) }}"
            role="button"
            tabindex="0"
            aria-label="选择或拖入文件"
        >
            <input type="file" id="file-input" name="files" multiple>
            <div class="icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 16V4m0 0L8 8m4-4l4 4"/>
                    <path d="M4 14v4a2 2 0 002 2h12a2 2 0 002-2v-4"/>
                </svg>
            </div>
            <div class="title">将文件拖放到此处</div>
            <div class="hint">或点击此区域选择文件 · 不会自动上传</div>
        </div>

        <div class="upload-options">
            <label class="field-label" for="upload-subdir">存储子目录（可选）</label>
            <input type="text" id="upload-subdir" class="field-input" name="subdir" placeholder="留空则使用当日日期，如：images 或 images/2025" autocomplete="off" spellcheck="false">
            <p class="field-hint">文件保存在 <code>storage</code> 下以您邮箱命名的目录内；此处为相对该目录的子路径，留空时默认使用当日 <code>Ymd</code>（如 <code>20260324</code>）。仅字母、数字、下划线、连字符，多级用 <code>/</code> 分隔；文件名为 UUID（保留扩展名）。</p>
        </div>

        <div class="toolbar">
            <button type="button" class="btn btn-primary" id="btn-upload" disabled>开始上传</button>
            <button type="button" class="btn btn-ghost" id="btn-clear" disabled>清空列表</button>
        </div>

        <div class="file-list-section">
            <h2>待上传列表</h2>
            <div class="file-list-limit-hint" id="file-list-limit-hint" aria-live="polite"></div>
            <p id="file-list-empty" class="empty-hint">暂无文件，请先选择或拖入文件</p>
            <ul id="file-list" class="file-list"></ul>
        </div>
    </div>
</div>

<div id="toast" class="toast" role="status" aria-live="polite"></div>

<script src="/js/vendor/jquery.min.js"></script>
<script src="/js/pages/home-theme.js"></script>
<script src="/js/pages/home-upload.js"></script>
</body>
</html>

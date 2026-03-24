(function ($) {
  'use strict';

  /** 无服务端单文件上限时的客户端软限制（避免一次性加入超大文件） */
  var DEFAULT_MAX_BYTES = 100 * 1024 * 1024 * 1024;

  function parseLimits($zone) {
    var raw = $zone.attr('data-limits');
    if (!raw) return {};
    try {
      return JSON.parse(raw) || {};
    } catch (e) {
      return {};
    }
  }

  /**
   * 每条单独成块渲染，避免一行内用全角「；」连接时被浏览器在分号处优先断行（未到行尾就换行）。
   */
  function buildLimitHintRows(limits) {
    var rows = [];
    var maxSz = limits.max_file_size;
    if (maxSz != null && maxSz > 0) {
      rows.push('单文件不超过 ' + formatSize(maxSz));
    } else {
      rows.push('单文件大小以服务端校验为准（当前会员无明确上限）');
    }
    if (limits.allowed_extensions && limits.allowed_extensions.length) {
      rows.push('允许类型：.' + limits.allowed_extensions.join('、.'));
    } else {
      rows.push('允许类型：不限制（以服务端为准）');
    }
    var used = limits.used_uploads != null ? limits.used_uploads : 0;
    var maxN = limits.max_uploads;
    if (maxN != null) {
      rows.push('本周期还可上传约 ' + Math.max(0, maxN - used) + ' 个（已用 ' + used + '/' + maxN + '）');
    }
    rows.push('超过限制的文件会在列表中标注，上传时将跳过或失败。');
    return rows;
  }

  function renderLimitHint($el, limits) {
    if (!$el.length) return;
    var rows = buildLimitHintRows(limits);
    $el.empty();
    rows.forEach(function (text) {
      $el.append($('<p class="limit-hint__row" />').text(text));
    });
  }

  function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function uniqueKey(file) {
    return file.name + '|' + file.size + '|' + file.lastModified;
  }

  /**
   * 与后端 IndexController::sanitizeStorageSubdir 规则一致；留空为合法。
   * @returns {{ ok: boolean, msg?: string }}
   */
  function validateStorageSubdir(raw) {
    var s = String(raw == null ? '' : raw)
      .trim()
      .replace(/\\/g, '/');
    s = s.replace(/^\/+|\/+$/g, '');
    if (s === '') {
      return { ok: true };
    }
    var parts = s.split('/').filter(function (p) {
      return p !== '' && p !== '.' && p !== '..';
    });
    if (parts.length === 0) {
      return { ok: false, msg: '路径不合法：不能使用空段、`.` 或 `..` 等。' };
    }
    if (parts.length > 8) {
      return { ok: false, msg: '子目录最多 8 级，请缩短路径。' };
    }
    var segRe = /^[a-zA-Z0-9](?:[a-zA-Z0-9_-]*[a-zA-Z0-9])?$/;
    for (var i = 0; i < parts.length; i++) {
      var p = parts[i];
      if (p.length > 64) {
        return { ok: false, msg: '每一级名称不能超过 64 个字符。' };
      }
      if (!segRe.test(p)) {
        return {
          ok: false,
          msg:
            '每一级须以字母或数字开头、以字母或数字结尾；中间可为字母、数字、下划线（_）与连字符（-）。多级请用 / 分隔。',
        };
      }
    }
    return { ok: true };
  }

  function uploadOne(url, file, subdir, onProgress) {
    var formData = new FormData();
    formData.append('file', file);
    if (subdir) {
      formData.append('subdir', subdir);
    }

    return $.ajax({
      url: url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      xhr: function () {
        var xhr = $.ajaxSettings.xhr();
        if (xhr.upload) {
          xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
              onProgress(e.loaded / e.total);
            }
          });
        }
        return xhr;
      },
    });
  }

  $(function () {
    var $zone = $('#upload-zone');
    var $input = $('#file-input');
    var $list = $('#file-list');
    var $empty = $('#file-list-empty');
    var $btnUpload = $('#btn-upload');
    var $btnClear = $('#btn-clear');
    var $subdir = $('#upload-subdir');
    var $subdirError = $('#upload-subdir-error');
    var $toast = $('#toast');
    var $limitHint = $('#file-list-limit-hint');

    var limits = parseLimits($zone);
    var MAX_UPLOAD_BYTES =
      limits.max_file_size != null && limits.max_file_size > 0
        ? limits.max_file_size
        : DEFAULT_MAX_BYTES;
    renderLimitHint($limitHint, limits);

    var allowedExtSet = null;
    if (limits.allowed_extensions && limits.allowed_extensions.length) {
      allowedExtSet = {};
      limits.allowed_extensions.forEach(function (e) {
        allowedExtSet[String(e).toLowerCase()] = true;
      });
    }

    function fileExtNoDot(name) {
      var i = name.lastIndexOf('.');
      if (i < 0 || i === name.length - 1) return '';
      return name
        .slice(i + 1)
        .toLowerCase()
        .replace(/[^a-z0-9]/g, '');
    }

    function isTypeBlocked(name) {
      if (!allowedExtSet) return false;
      var ext = fileExtNoDot(name);
      if (!ext) return true;
      return !allowedExtSet[ext];
    }

    var uploadUrl = $zone.data('upload-url') || '/upload';
    var queue = [];
    var uploading = false;

    function showToast(msg, isError) {
      $toast.text(msg).toggleClass('is-error', !!isError).addClass('is-visible');
      clearTimeout($toast.data('t'));
      $toast.data(
        't',
        setTimeout(function () {
          $toast.removeClass('is-visible');
        }, 3200)
      );
    }

    function subdirInputValid() {
      return validateStorageSubdir($subdir.val()).ok;
    }

    function applySubdirValidationUI() {
      var r = validateStorageSubdir($subdir.val());
      if (r.ok) {
        $subdir.removeClass('is-invalid').attr('aria-invalid', 'false');
        $subdirError.prop('hidden', true).text('');
      } else {
        $subdir.addClass('is-invalid').attr('aria-invalid', 'true');
        $subdirError.prop('hidden', false).text(r.msg || '子目录格式不正确。');
      }
      return r.ok;
    }

    function syncQueue() {
      var subdirBad = !subdirInputValid();
      $btnUpload.prop('disabled', queue.length === 0 || uploading || subdirBad);
      $btnClear.prop('disabled', queue.length === 0 || uploading);
      $subdir.prop('disabled', uploading);
      $empty.toggle(queue.length === 0);
    }

    function renderList() {
      $list.empty();
      queue.forEach(function (item) {
        var $row = $('<li class="file-item" data-id="' + item.id + '"/>');
        if (item.oversize || item.typeBlocked) {
          $row.addClass('file-item--oversize');
        }
        var $meta = $('<div class="meta"/>');
        $meta.append($('<div class="name"/>').text(item.file.name).attr('title', item.file.name));
        $meta.append($('<div class="size"/>').text(formatSize(item.file.size)));
        var $remove = $('<button type="button" class="remove">移除</button>');
        $remove.prop('disabled', uploading);
        $remove.on('click', function () {
          if (uploading) return;
          queue = queue.filter(function (q) {
            return q.id !== item.id;
          });
          renderList();
          syncQueue();
        });
        $row.append($meta, $remove);
        if (item.oversize) {
          $row.append(
            $('<div class="file-item-hint"/>').text('超过当前会员单文件大小上限，点击「开始上传」时将自动跳过。')
          );
        } else if (item.typeBlocked) {
          $row.append(
            $('<div class="file-item-hint"/>').text('该扩展名不在当前会员允许列表内，将自动跳过。')
          );
        }
        $row.append('<div class="progress-wrap"><div class="progress-bar"/></div>');
        var $statusRow = $('<div class="file-item-status-row"/>');
        $statusRow.append(
          $('<div class="status"/>').text(
            item.status ||
              (item.typeBlocked ? '类型不允许，将跳过上传' : item.oversize ? '超过大小上限，将跳过上传' : '等待上传')
          )
        );
        if (item.viewUrl) {
          $statusRow.append(
            $('<a class="file-item-open" target="_blank" rel="noopener noreferrer">打开文件</a>').attr(
              'href',
              item.viewUrl
            )
          );
        }
        $row.append($statusRow);
        if (item.progress != null) {
          $row.find('.progress-bar').css('width', Math.round(item.progress * 100) + '%');
        }
        if (item.done) $row.addClass('is-done');
        if (item.error) $row.addClass('is-error');
        if (item.skipped) $row.addClass('is-skipped');
        $list.append($row);
      });
      syncQueue();
    }

    function addFiles(fileList) {
      var seen = {};
      queue.forEach(function (q) {
        seen[uniqueKey(q.file)] = true;
      });
      var added = 0;
      for (var i = 0; i < fileList.length; i++) {
        var f = fileList[i];
        var key = uniqueKey(f);
        if (seen[key]) continue;
        seen[key] = true;
        var tooBig = f.size > MAX_UPLOAD_BYTES;
        var typeBlocked = isTypeBlocked(f.name);
        queue.push({
          id: 'f-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
          file: f,
          progress: 0,
          oversize: tooBig,
          typeBlocked: typeBlocked,
          skipped: false,
          viewUrl: null,
          status: typeBlocked
            ? '当前会员不允许此扩展名，将跳过上传'
            : tooBig
              ? '超过单文件大小上限，将跳过上传'
              : '等待上传',
        });
        added++;
      }
      if (added) renderList();
      else if (fileList.length) showToast('所选文件已在列表中');
    }

    $input.on('change', function () {
      if (this.files && this.files.length) {
        addFiles(this.files);
      }
      this.value = '';
    });

    $zone.on('dragover dragenter', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $zone.addClass('is-dragover');
    });

    $zone.on('dragleave', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $zone.removeClass('is-dragover');
    });

    $zone.on('drop', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $zone.removeClass('is-dragover');
      var dt = e.originalEvent.dataTransfer;
      if (dt && dt.files && dt.files.length) {
        addFiles(dt.files);
      }
    });

    $subdir.on('input', function () {
      applySubdirValidationUI();
      syncQueue();
    });

    $btnClear.on('click', function () {
      if (uploading) return;
      queue = [];
      renderList();
    });

    $btnUpload.on('click', function () {
      if (!applySubdirValidationUI() || !queue.length || uploading) return;
      uploading = true;
      syncQueue();
      renderList();

      var i = 0;
      function next() {
        if (i >= queue.length) {
          uploading = false;
          var anyErr = queue.some(function (q) {
            return q.error;
          });
          var anyOk = queue.some(function (q) {
            return q.done && !q.error && !q.skipped;
          });
          var anySkip = queue.some(function (q) {
            return q.skipped;
          });
          syncQueue();
          renderList();
          if (anyErr) {
            showToast('部分文件上传失败', true);
          } else if (anyOk && anySkip) {
            showToast('上传完成，部分项因类型或大小已跳过');
          } else if (anyOk) {
            showToast('全部上传完成');
          } else if (anySkip) {
            showToast('没有可上传的文件（均已跳过）');
          }
          return;
        }
        var item = queue[i];
        if (item.done) {
          i++;
          return next();
        }
        if (item.typeBlocked || item.oversize || item.file.size > MAX_UPLOAD_BYTES) {
          item.skipped = true;
          item.done = true;
          item.error = false;
          item.progress = 0;
          item.status = item.typeBlocked ? '已跳过（类型不允许）' : '已跳过（超过大小上限）';
          i++;
          renderList();
          return next();
        }
        item.status = '上传中…';
        renderList();

        var subdirVal = ($subdir.val() || '').trim();

        uploadOne(uploadUrl, item.file, subdirVal, function (ratio) {
          item.progress = ratio;
          var $row = $list.find('.file-item[data-id="' + item.id + '"]');
          $row.find('.progress-bar').css('width', Math.round(ratio * 100) + '%');
        })
          .done(function (res) {
            if (res && res.code === 0) {
              item.progress = 1;
              item.done = true;
              item.error = false;
              item.skipped = false;
              item.status = '上传成功';
              item.viewUrl =
                res.data && typeof res.data.view_url === 'string' && res.data.view_url !== ''
                  ? res.data.view_url
                  : null;
            } else {
              item.error = true;
              item.done = true;
              item.status = (res && res.msg) || '上传失败';
            }
          })
          .fail(function (xhr) {
            item.error = true;
            item.done = true;
            if (xhr.status === 401) {
              item.status = '未登录或会话已过期，请刷新页面重新登录';
            } else {
              item.status =
                xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : '网络或服务器错误';
            }
          })
          .always(function () {
            i++;
            next();
          });
      }
      next();
    });

    applySubdirValidationUI();
    syncQueue();
  });
})(jQuery);

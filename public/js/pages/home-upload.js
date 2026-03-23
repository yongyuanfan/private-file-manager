(function ($) {
  'use strict';

  /** 单文件大小上限（与页面提示一致） */
  var MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

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
    var $toast = $('#toast');

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

    function syncQueue() {
      $btnUpload.prop('disabled', queue.length === 0 || uploading);
      $btnClear.prop('disabled', queue.length === 0 || uploading);
      $subdir.prop('disabled', uploading);
      $empty.toggle(queue.length === 0);
    }

    function renderList() {
      $list.empty();
      queue.forEach(function (item) {
        var $row = $('<li class="file-item" data-id="' + item.id + '"/>');
        if (item.oversize) {
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
            $('<div class="file-item-hint"/>').text('超过 10MB 的文件无法上传，点击「开始上传」时将自动跳过。')
          );
        }
        $row.append('<div class="progress-wrap"><div class="progress-bar"/></div>');
        $row.append($('<div class="status"/>').text(item.status || (item.oversize ? '超过 10MB，将跳过上传' : '等待上传')));
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
        queue.push({
          id: 'f-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
          file: f,
          progress: 0,
          oversize: tooBig,
          skipped: false,
          status: tooBig ? '超过 10MB，将跳过上传' : '等待上传',
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

    $btnClear.on('click', function () {
      if (uploading) return;
      queue = [];
      renderList();
    });

    $btnUpload.on('click', function () {
      if (!queue.length || uploading) return;
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
            showToast('上传完成，已超过 10MB 的项已跳过');
          } else if (anyOk) {
            showToast('全部上传完成');
          } else if (anySkip) {
            showToast('没有可上传的文件（均超过 10MB 已跳过）');
          }
          return;
        }
        var item = queue[i];
        if (item.done) {
          i++;
          return next();
        }
        if (item.oversize || item.file.size > MAX_UPLOAD_BYTES) {
          item.skipped = true;
          item.done = true;
          item.error = false;
          item.progress = 0;
          item.status = '已跳过（超过 10MB）';
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
            } else {
              item.error = true;
              item.done = true;
              item.status = (res && res.msg) || '上传失败';
            }
          })
          .fail(function (xhr) {
            item.error = true;
            item.done = true;
            item.status = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : '网络或服务器错误';
          })
          .always(function () {
            i++;
            next();
          });
      }
      next();
    });

    syncQueue();
  });
})(jQuery);

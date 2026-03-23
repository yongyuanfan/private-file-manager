(function ($) {
  'use strict';

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

  function uploadOne(url, file, onProgress) {
    var formData = new FormData();
    formData.append('file', file);

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
      $empty.toggle(queue.length === 0);
    }

    function renderList() {
      $list.empty();
      queue.forEach(function (item) {
        var $row = $('<li class="file-item" data-id="' + item.id + '"/>');
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
        $row.append('<div class="progress-wrap"><div class="progress-bar"/></div>');
        $row.append('<div class="status">' + (item.status || '等待上传') + '</div>');
        if (item.progress != null) {
          $row.find('.progress-bar').css('width', Math.round(item.progress * 100) + '%');
        }
        if (item.done) $row.addClass('is-done');
        if (item.error) $row.addClass('is-error');
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
        queue.push({
          id: 'f-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
          file: f,
          progress: 0,
          status: '等待上传',
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
          var ok = queue.every(function (q) {
            return q.done;
          });
          syncQueue();
          renderList();
          if (ok) showToast('全部上传完成');
          return;
        }
        var item = queue[i];
        if (item.done) {
          i++;
          return next();
        }
        item.status = '上传中…';
        renderList();

        uploadOne(uploadUrl, item.file, function (ratio) {
          item.progress = ratio;
          var $row = $list.find('.file-item[data-id="' + item.id + '"]');
          $row.find('.progress-bar').css('width', Math.round(ratio * 100) + '%');
        })
          .done(function (res) {
            if (res && res.code === 0) {
              item.progress = 1;
              item.done = true;
              item.error = false;
              item.status = '上传成功';
            } else {
              item.error = true;
              item.status = (res && res.msg) || '上传失败';
            }
          })
          .fail(function (xhr) {
            item.error = true;
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

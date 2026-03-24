(function () {
  var modal = document.getElementById('fm-share-modal');
  var form = document.getElementById('fm-share-form');
  var hint = document.getElementById('fm-share-hint');
  var uploadId = document.getElementById('fm-share-upload-id');
  var resultEl = document.getElementById('fm-share-result');
  var cancelBtn = document.getElementById('fm-share-cancel');
  if (!modal || !form || !hint || !uploadId || !resultEl) return;

  function openModal(id, fileName) {
    uploadId.value = String(id);
    hint.textContent = '文件：' + (fileName || '—');
    resultEl.hidden = true;
    resultEl.textContent = '';
    form.reset();
    uploadId.value = String(id);
    modal.hidden = false;
    document.getElementById('fm-share-max-views').focus();
  }

  function closeModal() {
    modal.hidden = true;
  }

  document.querySelectorAll('[data-fm-share]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      openModal(btn.getAttribute('data-upload-id'), btn.getAttribute('data-file-name') || '');
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', closeModal);
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    resultEl.hidden = true;
    var fd = new FormData(form);
    fetch('/user/shares', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: fd,
    })
      .then(function (r) {
        return r.json().then(function (j) {
          return { ok: r.ok, status: r.status, body: j };
        });
      })
      .then(function (x) {
        if (!x.ok || x.body.code !== 0) {
          resultEl.textContent = (x.body && x.body.msg) ? x.body.msg : '创建失败（' + x.status + '）';
          resultEl.hidden = false;
          return;
        }
        window.location.assign('/user/shares?created=1');
      })
      .catch(function () {
        resultEl.textContent = '网络错误，请稍后重试';
        resultEl.hidden = false;
      });
  });
})();

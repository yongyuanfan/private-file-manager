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

  cancelBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });

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
        var land = (x.body.data && x.body.data.landing_url) ? x.body.data.landing_url : '';
        var origin = window.location.origin || '';
        var full = land.indexOf('http') === 0 ? land : origin + land;
        resultEl.innerHTML =
          '已创建。落地页：<a href="' +
          full +
          '" target="_blank" rel="noopener noreferrer">' +
          full +
          '</a>（可复制给外部人员）';
        resultEl.hidden = false;
      })
      .catch(function () {
        resultEl.textContent = '网络错误，请稍后重试';
        resultEl.hidden = false;
      });
  });
})();

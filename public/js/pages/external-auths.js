(function () {
  function showToast(msg) {
    var el = document.createElement('div');
    el.className = 'toast';
    el.setAttribute('role', 'status');
    el.style.zIndex = '220';
    el.textContent = msg;
    document.body.appendChild(el);
    requestAnimationFrame(function () {
      el.classList.add('is-visible');
    });
    setTimeout(function () {
      el.classList.remove('is-visible');
      setTimeout(function () {
        if (el.parentNode) {
          el.parentNode.removeChild(el);
        }
      }, 320);
    }, 2600);
  }

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      try {
        if (document.execCommand('copy')) {
          document.body.removeChild(ta);
          resolve();
        } else {
          document.body.removeChild(ta);
          reject(new Error('execCommand failed'));
        }
      } catch (err) {
        try {
          document.body.removeChild(ta);
        } catch (_) {}
        reject(err);
      }
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy-text]');
    if (!btn) return;
    e.preventDefault();
    var text = btn.getAttribute('data-copy-text') || '';
    var label = btn.getAttribute('data-copy-label') || '内容';
    copyText(text)
      .then(function () {
        showToast(label + '已复制到剪贴板');
      })
      .catch(function () {
        window.prompt('复制以下内容：', text);
      });
  });
})();

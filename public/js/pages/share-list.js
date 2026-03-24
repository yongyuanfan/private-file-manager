(function () {
  function positionPanel(details) {
    var sum = details.querySelector('.fm-share-menu__summary');
    var panel = details.querySelector('.fm-share-menu__panel');
    if (!sum || !panel) return;
    var r = sum.getBoundingClientRect();
    var pw = panel.offsetWidth || 168;
    var ph = panel.offsetHeight || 120;
    var left = Math.min(window.innerWidth - pw - 8, Math.max(8, r.right - pw));
    var top = r.bottom + 6;
    if (top + ph > window.innerHeight - 8) {
      top = Math.max(8, r.top - ph - 6);
    }
    panel.style.left = left + 'px';
    panel.style.top = top + 'px';
  }

  document.querySelectorAll('details.fm-share-menu').forEach(function (d) {
    d.addEventListener('toggle', function () {
      if (!d.open) return;
      document.querySelectorAll('details.fm-share-menu').forEach(function (o) {
        if (o !== d) o.open = false;
      });
      requestAnimationFrame(function () {
        positionPanel(d);
      });
    });
  });

  window.addEventListener(
    'resize',
    function () {
      document.querySelectorAll('details.fm-share-menu[open]').forEach(positionPanel);
    },
    { passive: true }
  );

  document.addEventListener('click', function (e) {
    if (e.target.closest('details.fm-share-menu')) return;
    document.querySelectorAll('details.fm-share-menu[open]').forEach(function (d) {
      d.open = false;
    });
  });

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
    var btn = e.target.closest('[data-share-copy]');
    if (!btn) return;
    e.preventDefault();
    var path = btn.getAttribute('data-share-copy') || '';
    var origin = window.location.origin || '';
    var url = path.indexOf('http') === 0 ? path : origin + path;
    var label = btn.getAttribute('data-share-copy-label') || '复制链接';
    copyText(url)
      .then(function () {
        btn.textContent = '已复制';
        setTimeout(function () {
          btn.textContent = label;
        }, 2000);
        var dd = btn.closest('details.fm-share-menu');
        if (dd) dd.open = false;
      })
      .catch(function () {
        window.prompt('复制以下链接：', url);
      });
  });
})();

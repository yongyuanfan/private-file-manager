(function () {
  'use strict';

  var STORAGE_KEY = 'fm-view-mode';

  function init() {
    var root = document.getElementById('fm-browser');
    if (!root) return;

    var buttons = document.querySelectorAll('[data-fm-view]');
    if (!buttons.length) return;

    function setMode(mode) {
      if (mode !== 'grid' && mode !== 'list') {
        mode = 'grid';
      }
      root.classList.remove('is-grid', 'is-list');
      root.classList.add(mode === 'list' ? 'is-list' : 'is-grid');
      try {
        localStorage.setItem(STORAGE_KEY, mode);
      } catch (e) {
        /* ignore */
      }
      buttons.forEach(function (btn) {
        var m = btn.getAttribute('data-fm-view');
        btn.setAttribute('aria-pressed', m === mode ? 'true' : 'false');
      });
    }

    var stored = null;
    try {
      stored = localStorage.getItem(STORAGE_KEY);
    } catch (e) {
      /* ignore */
    }
    setMode(stored === 'list' ? 'list' : 'grid');

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        setMode(btn.getAttribute('data-fm-view'));
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

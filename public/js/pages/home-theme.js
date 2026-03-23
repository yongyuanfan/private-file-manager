(function () {
  'use strict';

  var STORAGE_KEY = 'home-upload-theme';

  function getStored() {
    try {
      var t = localStorage.getItem(STORAGE_KEY);
      if (t === 'light' || t === 'dark' || t === 'system') return t;
    } catch (e) {}
    return 'light';
  }

  function apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (e) {}
    syncSwitcher(theme);
  }

  function syncSwitcher(theme) {
    var $btns = document.querySelectorAll('.theme-switcher [data-theme-value]');
    for (var i = 0; i < $btns.length; i++) {
      var b = $btns[i];
      var on = b.getAttribute('data-theme-value') === theme;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var initial = getStored();
    if (!document.documentElement.getAttribute('data-theme')) {
      apply(initial);
    } else {
      syncSwitcher(document.documentElement.getAttribute('data-theme'));
    }

    var btns = document.querySelectorAll('.theme-switcher [data-theme-value]');
    for (var j = 0; j < btns.length; j++) {
      (function (btn) {
        btn.addEventListener('click', function () {
          var v = btn.getAttribute('data-theme-value');
          if (v === 'light' || v === 'dark' || v === 'system') apply(v);
        });
      })(btns[j]);
    }
  });
})();

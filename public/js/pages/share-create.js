(function ($) {
  'use strict';

  function pickerTheme() {
    var t = document.documentElement.getAttribute('data-theme') || 'light';
    if (t === 'dark') {
      return 'dark';
    }
    if (t === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return 'dark';
    }
    return 'default';
  }

  function startOfToday() {
    var n = new Date();
    return new Date(n.getFullYear(), n.getMonth(), n.getDate());
  }

  function maxCalendarDate() {
    var d = new Date();
    d.setFullYear(d.getFullYear() + 1);
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
  }

  $(function () {
    var $input = $('#fm-share-expires');
    if (!$input.length) {
      return;
    }

    $.datetimepicker.setLocale('zh');

    $input.datetimepicker({
      format: 'Y-m-d H:i',
      formatDate: 'Y-m-d',
      formatTime: 'H:i',
      step: 15,
      dayOfWeekStart: 1,
      scrollMonth: false,
      validateOnBlur: false,
      minDate: startOfToday(),
      maxDate: maxCalendarDate(),
      theme: pickerTheme(),
      onShow: function () {
        document.body.classList.add('fm-share-datetimepicker-open');
        var $our = this.data('input');
        var ourEl = $our && $our[0];
        setTimeout(function () {
          var a = document.activeElement;
          if (!a || !ourEl || a === ourEl) {
            return;
          }
          if (a.tagName === 'INPUT' || a.tagName === 'TEXTAREA') {
            a.blur();
          }
        }, 60);
      },
      onClose: function () {
        document.body.classList.remove('fm-share-datetimepicker-open');
      },
    });
  });
})(jQuery);

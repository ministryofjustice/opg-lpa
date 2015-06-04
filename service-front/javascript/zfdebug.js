/*jshint unused: false, newcap: false */
var ZFDebugLoad = window.onload;
window.onload = function () {
  'use strict';
  if (ZFDebugLoad) {
    ZFDebugLoad();
  }
};

function ZFDebugPanel(name) {
  'use strict';
  $('.ZFDebug_panel').each(function (i) {
    if ($(this).css('display') === 'block') {
      $(this).slideUp();
    } else {
      if ($(this).attr('id') === name) {
        $(this).slideDown();
      } else {
        $(this).slideUp();
      }
    }
  });
}

function ZFDebugSlideBar() {
  'use strict';
  if ($('#ZFDebug_debug').position().left > 0) {
    document.cookie = 'ZFDebugCollapsed=1;expires=;path=/';
    ZFDebugPanel();
    $('#ZFDebug_toggler').html('&#187;');
    return $('#ZFDebug_debug').animate({
      left: '-' + parseInt($('#ZFDebug_debug').outerWidth() - $('#ZFDebug_toggler').outerWidth() + 1, 10)
    }, 'normal', 'swing');
  } else {
    document.cookie = 'ZFDebugCollapsed=0;expires=;path=/';
    $('#ZFDebug_toggler').html('&#171;');
    return $('#ZFDebug_debug').animate({left: '1px'}, 'normal', 'swing');
  }
}

function ZFDebugToggleElement(name, whenHidden, whenVisible) {
  'use strict';
  if ($(name).css('display') === 'none') {
    $(whenVisible).show();
    $(whenHidden).hide();
  } else {
    $(whenVisible).hide();
    $(whenHidden).show();
  }
  $(name).slideToggle();
}
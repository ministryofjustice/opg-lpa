(function () {
  'use strict';

  // test for html5 storage
  moj.Helpers.hasHtml5Storage = function () {
    try {
      return 'sessionStorage' in window && window.sessionStorage !== null;
    } catch (e) {
      return false;
    }
  };

  // helper to check if popup is currently open
  moj.Helpers.isPopupOpen = function () {
    if (moj.Modules.Popup !== undefined) {
      return moj.Modules.Popup.isOpen();
    } else {
      return false;
    }
  };

  // check if children are all empty
  moj.Helpers.hasCleanFields = function (wrap) {
    var clean = true;
    $('input:not([type="submit"]), select:not([name*="country"]), textarea', wrap).each(function () {
      if ($(this).val() !== '') {
        clean = false;
      }
    });
    return clean;
  };

  // helper to return the scroll position of an element
  moj.Helpers.scrollTo = function (e) {
    var $target = e.target !== undefined ? $($(e.target).attr('href')) : $(e),
        $scrollEl = moj.Helpers.isPopupOpen() ? $('#mask') : $('html, body'),
        topPos = moj.Helpers.scrollPos($target);

    $scrollEl
      .animate({
        scrollTop: topPos
      },0)
      .promise()
      .done(function () {
        $target.closest('.group').find('input, select, textarea').first().focus();
      });
  };

  // helper to return the scroll position of an element
  moj.Helpers.scrollPos = function (target) {
    /*jshint laxbreak: true */
    return moj.Helpers.isPopupOpen()
              ? target.offset().top - $('#popup').offset().top
              : target.offset().top;
  };

  moj.Helpers.isMobileWidth = function () {
    var w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    if (w > 640) {
      return false;
    } else {
      return true;
    }
  };
})();
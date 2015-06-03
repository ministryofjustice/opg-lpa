// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.StickyNav = {

    init: function () {
      _.bindAll(this, 'stickIt');
      this.stickIt();
    },

    stickIt: function () {
      if ($('nav.progress-indicator').length) {
        var em = 16, //assume 1em is 16px.
          contentWidth = Math.floor($('#content').width() - em),
          sticky = $('nav.progress-indicator'),
          stickyTop = sticky.offset().top;

        sticky.css({
          width: contentWidth
        });

        $(window).resize(function () {
          contentWidth = Math.floor($('#content').width() - em);
          sticky.css({
            width: contentWidth
          });
        });

        $(window).scroll(function () { // scroll event
          var windowTop = $(window).scrollTop();

          if (stickyTop < windowTop) {
            sticky.addClass('sticky').removeClass('static');
          } else {
            sticky.addClass('static').removeClass('sticky');
          }

        });
      }
    }
  };
})();

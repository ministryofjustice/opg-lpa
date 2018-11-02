// Popup module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  // Define the class
  var Popup = function (options) {
    this.settings = $.extend({}, this.defaults, options);
    this._cacheEls();
  };

  Popup.prototype = {
    defaults: {
      source: $('#content'),
      placement: 'body',
      popupId: 'popup',
      ident: null,
      maskTemplate: lpa.templates['popup.mask'](),
      containerTemplate: lpa.templates['popup.container'](),
      contentTemplate: lpa.templates['popup.content'](),
      closeTemplate: lpa.templates['popup.close'](),
      beforeOpen: null,
      onOpen: null,
      onClose: null
    },

    init: function () {},

    _cacheEls: function () {
      this.$win = $(window);
      this.$html = $('html');
      this.$body = $('body');
      this.$mask = $(this.settings.maskTemplate);
      this.$popup = $(this.settings.containerTemplate);
      this.$content = $(this.settings.contentTemplate);
      this.$close = $(this.settings.closeTemplate);
    },

    _bindEvents: function () {
      var self = this;

      $('body').on('keydown.moj.Modules.Popup', function (e) {
        if (e.which === 27) {
          self.close();
        }
      });
      this.$popup.on('click.moj.Modules.Popup', '.js-popup-close, .js-cancel', function (e) {
        e.preventDefault();
        self.close();
      });
    },

    _unbindEvents: function () {
      $('body').off('keydown.moj.Modules.Popup');
      this.$popup.off('click.moj.Modules.Popup');
    },

    open: function (html, opts) {
      var self = this;
      // combine opts with settings for local settings
      opts = $.extend({}, this.settings, opts);

      // disable body scroll
      $('html').addClass('noscroll');
      // hide main contents from print layout
      $('body > *').addClass('print-hidden');

      // Join it all together
      this.$popup.data('settings', opts)
        .removeClass()
        .addClass('popup ' + opts.ident)
        .append(this.$close)
        .append(this.$content.html(html))
        .appendTo(this.$mask);

      // bind event handlers
      this._bindEvents();

      // Place the mask in the DOM
      // If a placement has been provided, the popup is appended to that element,
      // otherwise the popup is appended to the body element.
      $(opts.placement)[opts.placement === 'body' ? 'append' : 'after'](this.$mask);

      // callback func
      if (opts.beforeOpen && typeof(opts.beforeOpen) === 'function') {
        opts.beforeOpen();
      }

      // prevent tab navigation outside the lightbox
      self.loopTabKeys(self.$popup);

      // Fade in the mask
      this.$mask.fadeTo(200, 1);

      // Center and fase in the popup
      this.$popup.delay(100).fadeIn(200, function () {
        self.$popup.find('h2').attr('tabindex', -1);
        self.$popup.find('.close a').focus(); // for accessibility

        // callback func
        if (opts.onOpen && typeof(opts.onOpen) === 'function') {
          opts.onOpen();
        }
      });
    },

    close: function () {
      // make sure there is a popup to close
      if (this.isOpen()) {
        var self = this,
            opts = $('#popup').data('settings'),
            scrollPosition = $(window).scrollTop();

        self.$popup.fadeOut(400, function () {
          self.$mask.fadeOut(200, function () {
            // focus on previous element
            if (typeof opts.source !== 'undefined' && opts.source) {
              opts.source.focus();
            }
            // clear out any hash locations
            window.location.hash = '';
            if (history.pushState) {
              history.pushState('', document.title, window.location.pathname);
            }

            // callback func
            if (opts.onClose && typeof(opts.onClose) === 'function') {
              opts.onClose();
            }

            // Remove the popup from the DOM
            $(this).remove();

            // re-enable body scroll
            $(window).scrollTop(scrollPosition);
            $('html').removeClass('noscroll');
            // unhide main contents from print layout
            $('body > *').removeClass('print-hidden');

            // unbind event handlers
            self._unbindEvents();
          });
        });
      }
    },

    tabFocusesOn: function (e) {
      // on tab set focus
      if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        e.data.element.focus();
      }
    },

    reverseTabFocusesOn: function (e) {
      // on tab with shift held set focus
      if (e.key === 'Tab' && e.shiftKey) {
        e.preventDefault();
        e.data.element.focus();
      }
    },

    loopTabKeys: function (wrap) {
      var tabbable = 'a, area, button, input, object, select, textarea, [tabindex]';
      this.$first = wrap.find(tabbable).filter(':first');
      this.$last = wrap.find(tabbable).filter(':last');

      this.$first.keydown({'element': this.$last}, this.reverseTabFocusesOn);
      this.$last.keydown({'element': this.$first}, this.tabFocusesOn);
    },

    redoLoopedTabKeys: function () {
      if (typeof this.$first !== 'undefined') {
        this.$first.off('keydown', this.reverseTabFocusesOn);
      }

      if (typeof this.$last !== 'undefined') {
        this.$last.off('keydown', this.tabFocusesOn);
      }

      this.loopTabKeys(this.$popup);
    },

    isOpen: function () {
      return $('#' + this.settings.popupId).length > 0;
    }
  };

  // Add module to MOJ namespace
  moj.Modules.Popup = new Popup();
}());
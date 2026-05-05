// Popup module for LPA
// Dependencies: moj, jQuery
(function () {
  'use strict';

  const moj = window.moj;
  const lpa = window.lpa;

  // Define the class
  const Popup = function (options) {
    this.settings = $.extend({}, this.defaults, options);
    this._cacheEls();
  };

  Popup.prototype = {
    defaults: {
      source: $('#content'),
      placement: 'body',
      popupId: 'popup',
      ident: null,
      containerTemplate: lpa.templates['popup.container'](),
      contentTemplate: lpa.templates['popup.content'](),
      closeTemplate: lpa.templates['popup.close'](),
      beforeOpen: null,
      onOpen: null,
      onClose: null,
    },

    init: function () {},

    _cacheEls: function () {
      this.$body = $('body');
      this.$popup = $(this.settings.containerTemplate);
      this.$content = $(this.settings.contentTemplate);
      this.$close = $(this.settings.closeTemplate);
    },

    _bindEvents: function () {
      const self = this;

      this.$popup.on(
        'click.moj.Modules.Popup',
        '.js-popup-close, .js-cancel',
        function (e) {
          e.preventDefault();
          self.close();
        },
      );

      // Native dialog fires 'cancel' on Escape key press
      this.$popup[0].addEventListener('cancel', function (e) {
        e.preventDefault();
        self.close();
      });
    },

    _unbindEvents: function () {
      this.$popup.off('click.moj.Modules.Popup');
    },

    open: function (html, opts) {
      // combine opts with settings for local settings
      opts = $.extend({}, this.settings, opts);

      // Join it all together
      this.$popup
        .data('settings', opts)
        .removeClass()
        .addClass('popup ' + opts.ident)
        .append(this.$close)
        .append(this.$content.html(html));

      // bind event handlers
      this._bindEvents();

      // Place the popup in the DOM.
      // If a placement has been provided, the popup is appended to that element,
      // otherwise the popup is appended to the body element.
      $(opts.placement)[opts.placement === 'body' ? 'append' : 'after'](
        this.$popup,
      );

      // callback func
      if (opts.beforeOpen && typeof opts.beforeOpen === 'function') {
        opts.beforeOpen();
      }

      // If already open, close it before reloading content so showModal()
      // can be called again without throwing a DOMException
      if (this.$popup[0].open) {
        this.$popup[0].close();
      }

      // Open as a modal – browser handles backdrop, focus trap and scroll lock
      this.$popup[0].showModal();

      // Focus the first heading in the dialog for accessibility;
      // fall back to the close button if no heading is present.
      // tabindex="-1" is required for programmatic focus on non-interactive elements.
      const $heading = this.$popup.find('h1, h2').first();
      if ($heading.length) {
        if (!$heading.attr('tabindex')) {
          $heading.attr('tabindex', '-1');
        }
        $heading.trigger('focus');
      } else {
        this.$popup.find('.close button').trigger('focus');
      }

      // callback func
      if (opts.onOpen && typeof opts.onOpen === 'function') {
        opts.onOpen();
      }
    },

    close: function () {
      // make sure there is a popup to close
      if (this.isOpen()) {
        const self = this;
        const opts = $('#' + this.settings.popupId).data('settings');

        // Close the native dialog
        self.$popup[0].close();

        // focus on previous element
        if (typeof opts !== 'undefined' && opts.source) {
          opts.source.trigger('focus');
        }

        // clear out any hash locations
        window.location.hash = '';
        if (history.pushState) {
          history.pushState('', document.title, window.location.pathname);
        }

        // callback func
        if (opts && opts.onClose && typeof opts.onClose === 'function') {
          opts.onClose();
        }

        // Remove the popup from the DOM
        self.$popup.remove();

        // unbind event handlers
        self._unbindEvents();
      }
    },

    isOpen: function () {
      return $('#' + this.settings.popupId).length > 0;
    },
  };

  // Add module to MOJ namespace
  moj.Modules.Popup = new Popup();
})();

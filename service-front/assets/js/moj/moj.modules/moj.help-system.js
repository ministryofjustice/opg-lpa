// Help System module for LPA
// Dependencies: popup, moj, jQuery
(function (global) {
  'use strict';

  const moj = window.moj;
  const lpa = window.lpa;

  // Define the class
  const HelpSystem = function (options) {
    this.settings = $.extend({}, this.defaults, options);
  };

  HelpSystem.prototype = {
    defaults: {
      guidancePath: 'guide',
      selector: 'a.js-guidance',
      overlayIdent: 'help-system',
      overlaySource: '#content',
      loadingTemplate: lpa.templates['shared.loading-popup'](),
      popupOnClose: function () {
        moj.Modules.HelpSystem.topic = undefined;
      },
    },

    init: function () {
      // only load if not on the static page
      if ($('#help-system').length === 0) {
        this._cacheEls();
        this._bindEvents();

        // open popup if hash is present in url
        const hash = window.location.hash;
        let topic;
        if (this._isGuidanceHash(hash)) {
          // on page load parse hash
          topic = hash.substring(hash.lastIndexOf('/') + 1);
          // set topic
          this._selectHelpTopic(topic);
        }
      }
    },

    _cacheEls: function () {
      this.html = undefined;
      this.topic = false;
      this.source = false;
    },

    _bindEvents: function () {
      const self = this;

      // nav click event
      $('body').on('click', this.settings.selector, function () {
        // Be a normal link for mobile and go to the non-js guidance page
        if (moj.Helpers.isMobileWidth()) {
          return true;
        }
        const href = $(this).attr('href');
        const topic = href.substring(href.lastIndexOf('#') + 1);
        // report the click to ga
        if (typeof global.ga === 'function') {
          global.ga('send', 'pageview', href);
        }

        // set the current click as the source
        self.source = $(this);
        // select topic
        self._selectHelpTopic(topic);
        return false;
      });

      // listen to hash changes in url
      $(window).on('hashchange.moj.Modules.HelpSystem', function () {
        const hash = window.location.hash;
        let topic;

        if (self._isGuidanceHash(hash)) {
          // if a change has been made, select the topic
          topic = hash.substring(hash.lastIndexOf('/') + 1);
          self._selectHelpTopic(topic);
        } else if (hash === '') {
          // if the new hash is empty, clear out the popup
          moj.Modules.Popup.close();
        }
      });
    },

    _isGuidanceHash: function (hash) {
      return (
        hash !== '' &&
        hash !== '#/' &&
        hash.indexOf(this.settings.guidancePath) !== -1
      );
    },

    _selectHelpTopic: function (topic) {
      const self = this;

      // make sure no duplicate calls are fired
      if (topic !== this.topic) {
        // if the overlay is present, set topic immediately
        if ($('#popup.help-system').length > 0) {
          self._setTopic(topic);
          // On small screens, jump to topic
          if (moj.Helpers.isMobileWidth()) {
            $('#' + topic)[0].scrollIntoView(true);
          }
        } else {
          // otherwise, load in the overlay first and set in callback
          this._loadOverlay(topic);
        }
      }
    },

    _setTopic: function (slug) {
      // set topic to global obj
      this.topic = slug;

      // make sure we're not resetting the hash and adding to the history if we don't need to
      if (
        '#/' + this.settings.guidancePath + '/' + slug !==
        window.location.hash
      ) {
        window.location.hash = '#/' + this.settings.guidancePath + '/' + slug;
      }

      // Set nav item as active
      $('.help-navigation a[href$="#' + slug + '"]')
        .parent() // use 'ends with' selector so don't have to define url slug
        .addClass('active')
        .siblings('li')
        .removeClass('active');

      // Show associated content
      $('#' + slug)
        .removeClass('hidden')
        .siblings('article')
        .addClass('hidden');

      // Associated back link visibility
      $('.link-back').addClass('hidden');

      $('#' + slug + ' + .link-back').removeClass('hidden');

      // Scroll back to top of help
      $('#mask').scrollTop(0);

      // shift focus to the help content
      $('#help-sections').trigger('focus');
    },

    _getCachedContent: function () {
      let html;

      // try from this class
      if (typeof this.html !== 'undefined') {
        html = this.html;
      }

      return {
        html,
      };
    },

    _loadOverlay: function (topic) {
      const self = this;
      const cached = this._getCachedContent();
      const html = cached.html;

      // if content has been cached on this object, load it straight in
      if (html !== undefined) {
        moj.Modules.Popup.open(html, {
          ident: this.settings.overlayIdent,
          source: this.source,
          beforeOpen: function () {
            // set topic
            self._setTopic(topic);
          },
          onClose: this.settings.popupOnClose,
        });
      } else {
        // otherwise, AJAX it in and then switch the content in the popup

        // load overlay
        moj.Modules.Popup.open(this.settings.loadingTemplate, {
          ident: self.settings.overlayIdent,
          source: this.source,
          beforeOpen: function () {
            const url = window.cacheBusting.url(
              '/' + self.settings.guidancePath,
            );

            $('#popup-content').load(url, function (html) {
              self.html = html;

              // set the topic now that all content has loaded
              self._setTopic(topic);

              moj.Modules.Popup.redoLoopedTabKeys();
            });
          },
          onClose: this.settings.popupOnClose,
        });
      }
    },
  };

  // Add module to MOJ namespace
  moj.Modules.HelpSystem = new HelpSystem();
})(window);

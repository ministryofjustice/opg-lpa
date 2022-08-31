// Popup module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  const lpa = window.lpa

  // Define the class
  const Popup = function (options) {}

  Popup.prototype = {
    settings: {
      source: document.querySelector('#content'),
      ident: null,
      maskTemplate: lpa.templates['popup.mask'](),
      containerTemplate: lpa.templates['popup.container'](),
      contentTemplate: lpa.templates['popup.content'](),
      closeTemplate: lpa.templates['popup.close'](),
      beforeOpen: null,
      onOpen: null,
      onClose: null
    },

    // tabbable elements
    _first: null,
    _last: null,

    // top level event handlers
    _keydownCloseHandler: function (e) {
      if (e.which === 27) {
        this.close()
      }
    },

    _clickCloseHandler: function (e) {
      if (!moj.Helpers.matchesSelector(e.target, '.js-popup-close, .js-cancel')) {
        return true
      }

      e.preventDefault()
      this.close()

      return false
    },

    _shiftTabHandler: function (e) {
      if (e.key === 'Tab' && e.shiftKey && this.last !== null) {
        e.preventDefault()
        this._last.focus()
      }
    },

    _tabHandler: function (e) {
      // on tab set focus
      if (e.key === 'Tab' && !e.shiftKey && this.first !== null) {
        e.preventDefault()
        this._first.focus()
      }
    },

    _cacheEls: function () {
      this.mask = moj.Helpers.strToHtml(this.settings.maskTemplate)
      this.$mask = $(this.mask)

      this.popup = moj.Helpers.strToHtml(this.settings.containerTemplate)
      this.$popup = $(this.popup)

      this.content = moj.Helpers.strToHtml(this.settings.contentTemplate)
      this.closeButton = moj.Helpers.strToHtml(this.settings.closeTemplate)
    },

    _bindEvents: function () {
      document.body.addEventListener('keydown', this._keydownCloseHandler)
      this.popup.addEventListener('click', this._clickCloseHandler)
    },

    _unbindEvents: function () {
      document.body.removeEventListener('keydown', this._keydownCloseHandler)
      this.popup.removeEventListener('click', this._clickCloseHandler)
    },

    // ensure that tab presses are contained within the popup
    _loopTabKeys: function (wrap) {
      const tabbable = 'a, area, button, input, object, select, textarea, [tabindex]'

      const tabbableElts = wrap.querySelectorAll(tabbable)
      if (tabbableElts.length > 0) {
        this._first = tabbableElts[0]
        this._first.addEventListener('keydown', this._shiftTabHandler)

        this._last = tabbableElts[tabbableElts.length - 1]
        this._last.addEventListener('keydown', this._tabHandler)
      }
    },

    // Public API
    init: function () {
      const self = this

      this._clickCloseHandler = this._clickCloseHandler.bind(self)
      this._keydownCloseHandler = this._keydownCloseHandler.bind(self)
      this._shiftTabHandler = this._shiftTabHandler.bind(self)
      this._tabHandler = this._tabHandler.bind(self)

      this._cacheEls()
    },

    open: function (html, opts) {
      const self = this

      // combine opts with settings for local settings
      this.settings = moj.Helpers.extend(this.settings, opts)

      // disable body scroll
      document.querySelector('html').classList.add('noscroll')

      // hide main contents from print layout
      document.body.querySelectorAll('*').forEach(function (elt) {
        elt.classList.add('print-hidden')
      })

      // append DOM elements to mask
      this.content.innerHTML = html

      this.popup.classList.add('popup')
      this.popup.classList.add(this.settings.ident)
      this.popup.appendChild(this.closeButton)
      this.popup.appendChild(this.content)

      this.mask.appendChild(this.popup)

      // bind event handlers
      this._bindEvents()

      // append mask to body
      document.body.appendChild(this.mask)

      // callback func
      if (typeof this.settings.beforeOpen === 'function') {
        this.settings.beforeOpen()
      }

      // prevent tab navigation outside the popup
      this.redoLoopedTabKeys(this.popup)

      // Fade in the mask
      this.$mask.fadeTo(200, 1)

      // Fade in the popup (starts while mask is still fading in)
      this.$popup.delay(100).fadeIn(200, function () {
        const heading = self.popup.querySelector('h2')
        if (heading !== null) {
          heading.setAttribute('tabindex', -1)
        }

        const closeLink = self.closeButton.querySelector('a')
        if (closeLink !== null) {
          closeLink.focus()
        }

        // callback func
        if (typeof self.settings.onOpen === 'function') {
          self.settings.onOpen()
        }
      })
    },

    close: function () {
      // make sure there is a popup to close
      if (document.querySelectorAll('#popup').length > 0) {
        const self = this

        self.$popup.fadeOut(400, function () {
          self.$mask.fadeOut(200, function () {
            // focus on previous element
            if (typeof self.settings.source !== 'undefined' && self.settings.source !== false) {
              self.settings.source.focus()
            }

            // clear out any hash locations
            window.location.hash = ''
            if (history.pushState) {
              history.pushState('', document.title, window.location.pathname)
            }

            // callback func
            if (typeof self.settings.onClose === 'function') {
              self.settings.onClose()
            }

            // Remove the popup from the DOM
            self.mask = self.mask.parentNode.removeChild(self.mask)

            // re-enable body scroll
            document.querySelector('html').classList.remove('noscroll')

            // unhide main contents from print layout
            document.body.querySelectorAll('*').forEach(function (elt) {
              elt.classList.remove('print-hidden')
            })

            // unbind event handlers
            self._unbindEvents()
          })
        })
      }
    },

    redoLoopedTabKeys: function () {
      if (this._first !== null) {
        this._first.removeEventListener('keydown', this._shiftTabHandler)
      }

      if (this._last !== null) {
        this._last.removeEventListener('keydown', this._tabHandler)
      }

      this._loopTabKeys(this.popup)
    }
  }

  // Add module to MOJ namespace
  moj.Modules.Popup = new Popup()
}())

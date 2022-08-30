// Popup module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  const lpa = window.lpa

  // Define the class
  const Popup = function (options) {
    this._cacheEls()
  }

  Popup.prototype = {
    // top level event handlers
    keydownListener: null,
    clickListener: null,

    // tabbable elements
    first: null,
    last: null,

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

    init: function () {},

    _cacheEls: function () {
      this.mask = moj.Helpers.strToHtml(this.settings.maskTemplate)
      this.$mask = $(this.mask)

      this.popup = moj.Helpers.strToHtml(this.settings.containerTemplate)
      this.$popup = $(this.popup)

      this.content = moj.Helpers.strToHtml(this.settings.contentTemplate)
      this.closeButton = moj.Helpers.strToHtml(this.settings.closeTemplate)
    },

    _bindEvents: function () {
      const self = this

      this.keydownListener = document.body.addEventListener('keydown', function (e) {
        if (e.which === 27) {
          self.close()
        }
      })

      this.clickListener = this.popup.addEventListener('click', function (e) {
        if (!moj.Helpers.matchesSelector(e.target, '.js-popup-close, .js-cancel')) {
          return true
        }

        e.preventDefault()
        self.close()

        return false
      })
    },

    _unbindEvents: function () {
      document.body.removeEventListener('keydown', this.keydownListener)
      this.popup.removeEventListener('click', this.clickListener)
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

      // Join it all together
      this.content.innerHTML = html

      this.popup.classList.add('popup')
      this.popup.classList.add(this.settings.ident)
      this.popup.appendChild(this.closeButton)
      this.popup.appendChild(this.content)

      this.mask.appendChild(this.popup)

      // bind event handlers
      this._bindEvents()

      document.body.appendChild(this.mask)

      // callback func
      if (typeof this.settings.beforeOpen === 'function') {
        this.settings.beforeOpen()
      }

      // prevent tab navigation outside the lightbox
      this.loopTabKeys(this.popup)

      // Fade in the mask
      this.$mask.fadeTo(200, 1)

      // Center and phase in the popup
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
      if (this.isOpen()) {
        const self = this
        const scrollPosition = $(window).scrollTop()

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
            self.$mask.remove()

            // re-enable body scroll
            $(window).scrollTop(scrollPosition)
            document.querySelector('html').classList.remove('noscroll')

            // unhide main contents from print layout
            document.body.querySelectorAll('*').forEach(function (elt) {
              elt.classList.remove('print-hidden')
            })

            // unbind event handlers
            self._unbindEvents.bind(self)()
          })
        })
      }
    },

    tabFocusesOn: function (e) {
      // on tab set focus
      if (e.key === 'Tab' && !e.shiftKey && this.first !== null) {
        e.preventDefault()
        this.first.focus()
      }
    },

    reverseTabFocusesOn: function (e) {
      // on tab with shift held set focus
      if (e.key === 'Tab' && e.shiftKey && this.last !== null) {
        e.preventDefault()
        this.last.focus()
      }
    },

    loopTabKeys: function (wrap) {
      const tabbable = 'a, area, button, input, object, select, textarea, [tabindex]'

      const tabbableElts = wrap.querySelectorAll(tabbable)
      if (tabbableElts.length > 0) {
        this.first = tabbableElts[0]
        this.first.addEventListener('keydown', this.reverseTabFocusesOn.bind(this))

        this.last = tabbableElts[tabbableElts.length - 1]
        this.last.addEventListener('keydown', this.tabFocusesOn.bind(this))
      }
    },

    redoLoopedTabKeys: function () {
      if (this.first !== null) {
        this.first.removeEventListener('keydown', this.reverseTabFocusesOn.bind(this))
      }

      if (this.last !== null) {
        this.last.removeEventListener('keydown', this.tabFocusesOn.bind(this))
      }

      this.loopTabKeys(this.popup)
    },

    isOpen: function () {
      return document.querySelectorAll('#popup').length > 0
    }
  }

  // Add module to MOJ namespace
  moj.Modules.Popup = new Popup()
}())

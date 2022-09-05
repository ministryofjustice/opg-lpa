// Popup module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  const lpa = window.lpa

  // Define the class
  const makePopup = function () {
    const that = {}

    let _settings = {
      source: document.querySelector('#content'),
      ident: null,
      maskTemplate: lpa.templates['popup.mask'](),
      containerTemplate: lpa.templates['popup.container'](),
      contentTemplate: lpa.templates['popup.content'](),
      closeTemplate: lpa.templates['popup.close'](),
      beforeOpen: null,
      onOpen: null,
      onClose: null
    }

    let _first = null
    let _last = null

    const _keydownCloseHandler = function (e) {
      if (e.which === 27) {
        e.preventDefault()
        that.close()
      }
    }

    const _clickCloseHandler = function (e) {
      if (moj.Helpers.matchesSelector(e.target, '.js-popup-close, .js-cancel')) {
        e.preventDefault()
        that.close()
      }
    }

    const _shiftTabHandler = function (e) {
      if (e.key === 'Tab' && e.shiftKey && _last !== null) {
        e.preventDefault()
        _last.focus()
      }
    }

    const _tabHandler = function (e) {
      // on tab set focus
      if (e.key === 'Tab' && !e.shiftKey && _first !== null) {
        e.preventDefault()
        _first.focus()
      }
    }

    const _bindEvents = function () {
      document.body.addEventListener('keydown', _keydownCloseHandler)
      that.popup.addEventListener('click', _clickCloseHandler)
    }

    const _unbindEvents = function () {
      document.body.removeEventListener('keydown', _keydownCloseHandler)
      that.popup.removeEventListener('click', _clickCloseHandler)
    }

    // ensure that tab presses are contained within the popup
    const _loopTabKeys = function (wrap) {
      const tabbable = 'a, area, button, input, object, select, textarea, [tabindex]'

      const tabbableElts = wrap.querySelectorAll(tabbable)
      if (tabbableElts.length > 0) {
        _first = tabbableElts[0]
        _first.addEventListener('keydown', _shiftTabHandler)

        _last = tabbableElts[tabbableElts.length - 1]
        _last.addEventListener('keydown', _tabHandler)
      }
    }

    // Public API
    that.init = function () {
      // cache elements
      that.mask = moj.Helpers.strToHtml(_settings.maskTemplate)
      that.popup = moj.Helpers.strToHtml(_settings.containerTemplate)
      that.content = moj.Helpers.strToHtml(_settings.contentTemplate)
      that.closeButton = moj.Helpers.strToHtml(_settings.closeTemplate)
    }

    that.open = function (html, opts) {
      // combine opts with _settings for local _settings
      _settings = moj.Helpers.extend(_settings, opts)

      // disable body scroll
      document.querySelector('html').classList.add('noscroll')

      // hide main contents from print layout
      document.body.querySelectorAll('*').forEach(function (elt) {
        elt.classList.add('print-hidden')
      })

      // append DOM elements to mask
      that.content.innerHTML = html

      that.popup.classList.add('popup')
      that.popup.classList.add(_settings.ident)
      that.popup.appendChild(that.closeButton)
      that.popup.appendChild(that.content)

      that.mask.appendChild(that.popup)

      // bind event handlers
      _bindEvents()

      // append mask to body
      document.body.appendChild(that.mask)

      // callback func
      if (typeof _settings.beforeOpen === 'function') {
        _settings.beforeOpen()
      }

      // prevent tab navigation outside the popup
      that.redoLoopedTabKeys(that.popup)

      // Fade in the mask
      moj.Helpers.fade(that.mask, 1, 200)

      // Fade in the popup (starts while mask is still fading in)
      setTimeout(
        function () {
          moj.Helpers.fade(that.popup, 1, 200, function () {
            const heading = that.popup.querySelector('h2')
            if (heading !== null) {
              heading.setAttribute('tabindex', -1)
            }

            const closeLink = that.closeButton.querySelector('a')
            if (closeLink !== null) {
              closeLink.focus()
            }

            // callback func
            if (typeof _settings.onOpen === 'function') {
              _settings.onOpen()
            }
          })
        },
        100
      )
    }

    that.close = function () {
      // make sure there is a popup to close
      if (document.querySelectorAll('#popup').length > 0) {
        moj.Helpers.fade(that.popup, 0, 150, function () {
          moj.Helpers.fade(that.mask, 0, 150, function () {
            // focus on previous element
            if (typeof _settings.source !== 'undefined' && _settings.source !== false) {
              _settings.source.focus()
            }

            // clear out any hash locations
            window.location.hash = ''
            if (history.pushState) {
              history.pushState('', document.title, window.location.pathname)
            }

            // callback func
            if (typeof _settings.onClose === 'function') {
              _settings.onClose()
            }

            // Remove the popup from the DOM
            that.mask = that.mask.parentNode.removeChild(that.mask)

            // re-enable body scroll
            document.querySelector('html').classList.remove('noscroll')

            // unhide main contents from print layout
            document.body.querySelectorAll('*').forEach(function (elt) {
              elt.classList.remove('print-hidden')
            })

            // unbind event handlers
            _unbindEvents()
          })
        })
      }
    }

    that.redoLoopedTabKeys = function () {
      if (_first !== null) {
        _first.removeEventListener('keydown', _shiftTabHandler)
      }

      if (_last !== null) {
        _last.removeEventListener('keydown', _tabHandler)
      }

      _loopTabKeys(that.popup)
    }

    return that
  }

  // Add module to MOJ namespace
  moj.Modules.Popup = makePopup()
}())

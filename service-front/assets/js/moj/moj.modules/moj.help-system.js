// Help System module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  const lpa = window.lpa

  // Define the class
  const makeHelpSystem = function () {
    const that = {}

    const _settings = {
      guidancePath: 'guide',
      selector: 'a.js-guidance',
      overlayIdent: 'help-system',
      overlaySource: '#content',
      loadingTemplate: lpa.templates['shared.loading-popup'](),

      popupOnClose: function () {
        that.topic = undefined
      }
    }

    // helper to scroll popup into view
    const _scrollIntoView = function () {
      const scrollElt = document.querySelector('#mask')
      const targetElt = document.querySelector('#popup-content')
      const popupElt = document.querySelector('#popup')

      scrollElt.scrollTop = moj.Helpers.getOffset(targetElt).top - moj.Helpers.getOffset(popupElt).top
    }

    const _bindEvents = function () {
      // nav click event
      document.body.addEventListener('click', function (e) {
        // delegated event handler, so check the click target is the
        // Help link and allow event to be handled normally if not
        if (!moj.Helpers.matchesSelector(e.target, _settings.selector)) {
          return true
        }

        // Be a normal link for mobile and go to the non-js guidance page
        if (moj.Helpers.isMobileWidth()) {
          return true
        }

        e.preventDefault()

        const href = e.target.getAttribute('href')
        const topic = href.substring(href.lastIndexOf('#') + 1)

        // report the click to ga
        if (typeof window.ga === 'function') {
          window.ga('send', 'pageview', href)
        }

        // set the current click as the source
        that.source = e.target

        // select topic
        _selectHelpTopic(topic)

        return false
      })

      // listen to hash changes in url
      window.addEventListener('hashchange', function () {
        const hash = window.location.hash

        // if a change has been made, select the topic
        if (_isGuidanceHash(hash)) {
          const topic = hash.substring(hash.lastIndexOf('/') + 1)
          _selectHelpTopic(topic)
        } else if (hash === '') {
          // if the new hash is empty, clear out the popup
          moj.Modules.Popup.close()
        }

        return true
      })
    }

    const _isGuidanceHash = function (hash) {
      return hash !== '' && hash !== '#/' && hash.indexOf(_settings.guidancePath) !== -1
    }

    const _selectHelpTopic = function (topic) {
      // make sure no duplicate calls are fired
      if (topic !== that.topic) {
        // if the overlay is present, set topic immediately
        if (document.querySelectorAll('#popup.help-system').length > 0) {
          _setTopic(topic)

          // On small screens, jump to topic
          if (moj.Helpers.isMobileWidth()) {
            topic = document.querySelector('#' + topic)
            if (topic !== null) {
              topic.scrollIntoView(true)
            }
          }
        } else {
          // otherwise, load in the overlay first and set in callback
          _loadOverlay(topic)
        }
      }
    }

    const _setTopic = function (slug) {
      // set topic to global obj
      that.topic = slug

      // make sure we're not resetting the hash and adding to the history if we don't need to
      if ('#/' + _settings.guidancePath + '/' + slug !== window.location.hash) {
        window.location.hash = '#/' + _settings.guidancePath + '/' + slug
      }

      // Set nav item as active;
      // use 'ends with' selector so don't have to define url slug
      const activeLink = document.querySelector('.help-navigation a[href$="#' + slug + '"]')

      if (activeLink !== null) {
        // get all list elements in the help popup and make them inactive
        document.querySelectorAll('.help-navigation li[data-role=help-system-link]').forEach(function (elt) {
          elt.classList.remove('active')
        })

        activeLink.parentNode.classList.add('active')
      }

      // Show associated content
      const helpTextElt = document.querySelector('#' + slug)
      if (helpTextElt !== null) {
        // hide other help topics
        document.querySelectorAll('#help-sections article').forEach(function (elt) {
          elt.classList.add('hidden')
        })

        helpTextElt.classList.remove('hidden')
      }

      // Associated back link visibility
      const activeLinkBack = document.querySelector('#' + slug + ' + .link-back')
      if (activeLinkBack !== null) {
        document.querySelectorAll('.link-back').forEach(function (elt) {
          elt.classList.add('hidden')
        })

        activeLinkBack.classList.remove('hidden')
      }

      // Scroll back to top of help
      _scrollIntoView()

      // Shift focus to the help content
      const helpContentElt = document.querySelector('#help-sections')
      if (helpContentElt !== null) {
        helpContentElt.focus()
      }
    }

    const _getCachedContent = function () {
      let html

      // try from this class
      if (typeof that.html !== 'undefined') {
        html = that.html
      }

      return {
        html
      }
    }

    const _loadOverlay = function (topic) {
      const cached = _getCachedContent()
      const html = cached.html

      // if content has been cached on this object, load it straight in
      if (html !== undefined) {
        moj.Modules.Popup.open(html, {
          ident: _settings.overlayIdent,
          source: that.source,
          beforeOpen: function () {
            // set topic
            _setTopic(topic)
          },
          onClose: _settings.popupOnClose
        })
      } else {
        // otherwise, AJAX it in and then switch the content in the popup
        moj.Modules.Popup.open(_settings.loadingTemplate, {
          ident: _settings.overlayIdent,
          source: that.source,
          beforeOpen: function () {
            const url = window.cacheBusting.url('/' + _settings.guidancePath)

            moj.Helpers.ajax({
              url,

              success: function (html) {
                const elt = document.querySelector('#popup-content')

                if (elt !== null) {
                  elt.innerHTML = html
                }

                that.html = html

                // set the topic now that all content has loaded
                _setTopic(topic)

                moj.Modules.Popup.redoLoopedTabKeys()
              }
            })
          },
          onClose: _settings.popupOnClose
        })
      }
    }

    that.init = function () {
      // only load if not on the static page
      if (document.querySelectorAll('#help-system').length === 0) {
        // cache elements
        that.html = undefined
        that.topic = false
        that.source = false

        _bindEvents()

        // open popup if hash is present in url
        const hash = window.location.hash

        if (_isGuidanceHash(hash)) {
          // on page load parse hash
          const topic = hash.substring(hash.lastIndexOf('/') + 1)

          // set topic
          _selectHelpTopic(topic)
        }
      }
    }

    return that
  }

  // Add module to MOJ namespace
  moj.Modules.HelpSystem = makeHelpSystem()
}())

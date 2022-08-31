// Disable link after use module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  // Define the class
  const SingleUse = function () {
    this.settings = {
      selector: '.js-single-use'
    }
  }

  SingleUse.prototype = {
    init: function () {
      const useHandler = this.useHandler.bind(this)
      document.body.addEventListener('click', useHandler)
      document.body.addEventListener('submit', useHandler)
    },

    noop: function (e) {
      e.preventDefault()
      return false
    },

    useHandler: function (e) {
      const target = e.target

      if (!moj.Helpers.matchesSelector(target, this.settings.selector)) {
        return false
      }

      // When clicked, we prevent the event firing or percolating from now on
      target.addEventListener('click', this.noop)
      target.addEventListener('submit', this.noop)

      // Disable the link or form submit button
      if (target.tagName === 'A') {
        // Disable link
        target.setAttribute('disabled', 'disabled')
      } else if (target.tagName === 'FORM') {
        // Disable submit buttons
        target.querySelectorAll('input[type=submit]').forEach(function (elt) {
          elt.setAttribute('disabled', 'disabled')
          elt.addEventListener('click', this.noop)
        })
      }

      return true
    }
  }

  // Add module to MOJ namespace
  moj.Modules.SingleUse = new SingleUse()
}())

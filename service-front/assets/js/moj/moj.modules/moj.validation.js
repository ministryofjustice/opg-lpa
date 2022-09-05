// Validation module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  moj.Modules.Validation = {
    selector: '.error-summary[role=alert]',

    init: function () {
      moj.Events.on('Validation.render', this.render.bind(this))
      this.render(null, { wrap: 'body' })
    },

    render: function (e, params) {
      const wrappedElt = document.querySelector(params.wrap)
      if (wrappedElt === null) {
        return
      }

      // Focus on error summary
      const errorElts = wrappedElt.querySelectorAll(this.selector)
      if (errorElts.length > 0) {
        errorElts[0].focus()
      }
    }
  }
})()

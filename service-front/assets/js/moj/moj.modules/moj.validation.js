/* globals _, $ */
// Validation module for LPA
// Dependencies: moj, _, jQuery
;(function () {
  'use strict'

  const moj = window.moj

  moj.Modules.Validation = {
    selector: '.error-summary[role=alert]',

    init: function () {
      _.bindAll(this, 'render')
      this.bindEvents()
      this.render(null, { wrap: 'body' })
    },

    bindEvents: function () {
      moj.Events.on('Validation.render', this.render)
    },

    render: function (e, params) {
      const $el = $(this.selector, $(params.wrap))

      // Focus on error summary
      if ($el.length > 0) {
        $el.trigger('focus')
      }
    }
  }
})()

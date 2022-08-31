// Print link module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  moj.Modules.PrintLink = {
    init: function () {
      document.querySelectorAll('.js-print').forEach(function (elt) {
        elt.addEventListener('click', function (e) {
          e.preventDefault()
          document.body.classList.add('summary-print')
          window.print()
          document.body.classList.remove('summary-print')
          return false
        })
      })
    }
  }
})()

// Scroll to content module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  moj.Modules.ScrollToContent = {
    // scroll to the #current element on the page (if it exists)
    init: function () {
      const sectionToScrollTo = document.querySelector('#current')

      if (sectionToScrollTo === null) {
        return
      }

      const offsetTop = moj.Helpers.getOffset(sectionToScrollTo).top

      if (offsetTop > 0) {
        setTimeout(function () {
          window.scrollTo(0, offsetTop)
        }, 1)
      }
    }
  }
})()

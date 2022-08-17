;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  // test for html5 storage
  moj.Helpers.hasHtml5Storage = function () {
    try {
      return 'sessionStorage' in window && window.sessionStorage !== null
    } catch (e) {
      return false
    }
  }

  // check if form elements are all empty
  moj.Helpers.hasCleanFields = function () {
    let clean = true
    const selector = 'input:not([type="submit"]), select:not([name*="country"]), textarea'

    document.querySelectorAll(selector).forEach(function (element) {
      const val = element.getAttribute('value')

      if (val !== '' && val !== null) {
        clean = false
      }
    })

    return clean
  }

  // cribbed from jQuery; see
  // https://github.com/jquery/jquery/blob/d0ce00cdfa680f1f0c38460bc51ea14079ae8b07/src/offset.js#L65
  // simplified to use window rather than document.ownerDocument.defaultView
  // as we don't have iframes
  moj.Helpers.getOffset = function (element) {
    try {
      const rect = element.getBoundingClientRect()

      return {
        top: rect.top + window.pageYOffset,
        left: rect.left + window.pageXOffset
      }
    } catch (e) {
      // for browsers which don't support getBoundingClientRect()
      return { top: 0, left: 0 }
    }
  }

  // helper to scroll popup into view;
  // selector is always #popup-content
  moj.Helpers.scrollTo = function (selector) {
    const targetElt = document.querySelector(selector)
    const scrollElt = document.querySelector('#mask')
    const popupElt = document.querySelector('#popup')

    const topPos = moj.Helpers.getOffset(targetElt).top - moj.Helpers.getOffset(popupElt).top

    scrollElt.scrollTop = topPos

    // put focus into the first user input inside the target element
    const inputs = targetElt.querySelectorAll(
      'input:not([type=hidden]), checkbox, radio, select'
    )

    if (inputs.length > 0) {
      inputs[0].focus()
    }
  }

  moj.Helpers.isMobileWidth = function () {
    const w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0)
    if (w > 640) {
      return false
    } else {
      return true
    }
  }
})()

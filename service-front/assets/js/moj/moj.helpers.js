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

  moj.Helpers.isMobileWidth = function () {
    const w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0)
    if (w > 640) {
      return false
    } else {
      return true
    }
  }

  // Convert a string of HTML (with a single parent node)
  // to a Node object; if html contains multiple parent nodes,
  // you'll only get the first one back
  moj.Helpers.strToHtml = function (html) {
    let div = document.createElement('div')
    div.innerHTML = html
    return div.firstChild
  }
})()

;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  // convert response headers on the XmlHttpRequest r
  // into a key=value map;
  // code from https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/getAllResponseHeaders
  const extractHeaders = function (r) {
    // is the response JSON?
    const headers = r.getAllResponseHeaders()

    // Convert the header string into an array
    // of individual headers
    const arr = headers.trim().split(/[\r\n]+/)

    // Create a map of header names to values
    const headerMap = {}
    arr.forEach(function (line) {
      const parts = line.split(': ')
      const header = parts.shift().toLowerCase()
      const value = parts.join(': ')
      headerMap[header] = value
    })

    return headerMap
  }

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

  // Check whether DOM node element would be matched by selector;
  // either uses matches() or its prefixed equivalent for older browsers
  moj.Helpers.matchesSelector = function (element, selector) {
    const docEl = document.documentElement

    const matches = docEl.matches || docEl.webkitMatchesSelector ||
      docEl.mozMatchesSelector || docEl.msMatchesSelector || docEl.oMatchesSelector

    return matches.call(element, selector)
  }

  // synthesise an event on an event; only works for simple events for now
  // eventType: string like 'click' or 'submit'
  // element: DOM node; if null, nothing happens
  moj.Helpers.trigger = function (eventType, element) {
    if (element === null) {
      return false
    }

    const event = document.createEvent('HTMLEvents')
    event.initEvent(eventType, true, true)
    element.dispatchEvent(event)
    return true
  }

  // Convert a string of HTML (with a single parent node)
  // to a Node object; if html contains multiple parent nodes,
  // you'll only get the first one back
  moj.Helpers.strToHtml = function (html) {
    const div = document.createElement('div')
    div.innerHTML = html
    return div.firstChild
  }

  /**
   * Make HTTP requests; NB this will not do cross-domain
   * requests
   * opts.url (REQUIRED): if you need query string parameters,
   *   use the opts.query, don't put them on opts.url
   * opts.success (REQUIRED): handler for successful response
   *   signature: fn(responseBody)
   *   is isJSON is set, responseBody is an object; otherwise, text
   * opts.error (REQUIRED): handler for failure response;
   *   signature: fn(status, Error)
   *   if opts.timeout set and request timed out, status is 'timeout'
   * opts.isJSON: if true, automatically applies JSON.parse()
   *   to the response text
   * opts.query: query string key/value pairs; will be URI-encoded
   *   and sent as a query string on the URL
   * opts.headers: object mapping header names to values, e.g.
   *  {'Content-Type': 'application/json'}
   * opts.method (default 'GET')
   * opts.timeout (default undefined): if set, cb with error
   *  after opts.timeout milliseconds
   * opts.body: POST body (no preprocessing is done, so if sending
   *   JSON, set content-type appropriately)
   * r: http request implementation; if not set, defaults
   *   to new XMLHttpRequest
   *
   * partly based on http://microajax.googlecode.com/svn/trunk/microajax.js
   * (New BSD licence)
   *
   * Example 1: Sending a form in a POST request, where form is a <form> node:
   *
   * moj.Helpers.ajax({
   *   url: url,
   *   method: 'POST',
   *   body: new FormData(form),
   *    success: successCb,
   *    error: errorCb
   * })
   */
  moj.Helpers.ajax = function (opts, r) {
    opts = opts || {}

    r = r || new XMLHttpRequest()

    if (opts.timeout) {
      // set the length of the timeout on the request
      r.timeout = opts.timeout

      // invoke callback if timeout occurs
      r.ontimeout = function () {
        opts.error('timeout', new Error('request timed out after ' + opts.timeout + 'ms'))
      }
    }

    if (opts.query) {
      const qstring = []

      for (const key in opts.query) {
        qstring.push(encodeURIComponent(key) + '=' + encodeURIComponent(opts.query[key]))
      }

      if (qstring.length > 0) {
        opts.url = opts.url + '?' + qstring.join('&')
      }
    }

    // make the request
    r.onreadystatechange = function () {
      if (r.readyState === 4 && r.status < 400) {
        let resp = r.responseText

        const isJSON = opts.isJSON ||
          extractHeaders(r)['content-type'].search(/application\/json/) !== -1

        if (isJSON) {
          resp = JSON.parse(resp)
        }

        opts.success(resp)
      } else if (r.status >= 400) {
        opts.error(r.status, new Error('failed: ' + opts.url + '; status=' + r.status))
      }
    }

    // true => make async request
    r.open(opts.method || 'GET', opts.url, true)

    opts.headers = opts.headers || {}

    // this is essential for our code, which inspects this header and
    // changes the content returned depending on its value
    opts.headers['X-Requested-With'] = 'XMLHttpRequest'

    for (const property in opts.headers) {
      r.setRequestHeader(property, opts.headers[property])
    }

    r.send(opts.body)
  }

  // JavaScript spinner element, converted from old jQuery plugin
  moj.Helpers.spinner = function (element, options) {
    const that = {}

    options = options || {}

    const disabledClass = options.disabledClass || 'disabled'
    const placement = options.placement || 'after'

    let disabled = false
    let spinnerElt = null

    // "private" functions
    const isFormControl = function () {
      return element.tagName === 'SELECT' || element.tagName === 'BUTTON' || element.tagName === 'INPUT'
    }

    const isLink = function () {
      return element.tagName === 'A'
    }

    // public API
    that.on = function () {
      if (disabled) {
        return
      }

      spinnerElt = moj.Helpers.strToHtml(
        '<img src="/assets/v2/images/ajax-loader.gif" alt="Loading spinner" class="spinner" />'
      )

      if (placement === 'after') {
        element.parentNode.insertBefore(spinnerElt, element.nextSibling)
      } else if (placement === 'before') {
        element.parentNode.insertBefore(spinnerElt, element)
      }

      that.disable()
    }

    that.off = function () {
      that.enable()

      if (spinnerElt !== null) {
        // element and spinner are siblings, so have the same parent
        element.parentNode.removeChild(spinnerElt)
      }
    }

    that.disable = function () {
      // Apply disabled class to trigger element
      element.classList.add(disabledClass)

      // If it's a form control disable it
      if (isFormControl()) {
        element.setAttribute('disabled', 'disabled')
      }

      if (isLink()) {
        element.setAttribute('data-href', element.getAttribute('href'))
        element.removeAttribute('href')
      }

      disabled = true
    }

    that.enable = function () {
      element.classList.remove(disabledClass)

      if (isFormControl()) {
        element.removeAttribute('disabled')
      }

      if (isLink()) {
        element.setAttribute('href', element.getAttribute('data-href'))
      }

      disabled = false
    }

    return that
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
})()

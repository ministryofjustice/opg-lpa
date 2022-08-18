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

        if (opts.isJSON) {
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
    for (const property in opts.headers) {
      r.setRequestHeader(property, opts.headers[property])
    }

    r.send(opts.body)
  }
})()

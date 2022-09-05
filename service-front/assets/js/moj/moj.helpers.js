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

  // synthesise an event on an element; only works for simple events for now
  // eventType: string like 'click' or 'submit'
  // element: DOM node; if null, nothing happens
  moj.Helpers.trigger = function (eventType, element, data) {
    if (element === null) {
      return false
    }

    const event = document.createEvent('HTMLEvents')
    event.data = data || {}
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
   *   signature: fn(responseBody, responseStatus)
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

    opts.error = opts.error || console.error

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

        if (isJSON && r.status === 200) {
          resp = JSON.parse(resp)
        }

        opts.success(resp, r.status)
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

  // merge objects together; right-most object in argument list
  // sets the key if multiple passed objects share keys; example:
  //   moj.Helpers.extend({a:1}, {b:2}, {c:3}) => {a:1, b:2, c:3}
  // any functions in the object bind the new object as "this"
  moj.Helpers.extend = function () {
    const newObj = {}

    Array.prototype.slice.call(arguments).forEach(function (obj) {
      for (const key in obj) {
        let value = obj[key]
        if (typeof value === 'function') {
          value = value.bind(newObj)
        }
        newObj[key] = value
      }
    })

    return newObj
  }

  // Fade an element to the opacity set with toOpacity
  //
  // elt: element to fade in or out
  // toOpacity: float with value 0 [fully transparent] to 1 [fully opaque]
  // timeMs: time of the fade animation in ms
  // callback (optional): function to call after animation completes
  moj.Helpers.fade = function (elt, toOpacity, timeMs, callback) {
    if (callback === undefined) {
      callback = function () {}
    }

    const opacity = parseFloat(elt.style.opacity) || 0

    // without this, our popups display as inline blocks
    if (elt.style.display === '') {
      elt.style.display = 'block'
    }

    // nothing to do, early return
    if (opacity === toOpacity) {
      callback()
      return true
    }

    // total change in opacity required
    const opacityDelta = toOpacity - opacity

    // we ignore animation frames which happen before this length of
    // time has elapsed; in effect this gives us a 60fps animation
    // even if the browser refresh rate is higher than that
    const frameLengthMs = 1000 / 60

    let done = false
    let elapsed = 0
    let lastTime = null
    let interval
    let newOpacity

    // function invoked for each step of the animation;
    // timestamp is when the step is invoked, so we can use
    // that to frame limit the animation and measure total elapsed time
    const step = function (timestamp) {
      interval = timestamp - lastTime

      // if interval since last step is very short, don't do anything
      if (interval < frameLengthMs) {
        window.requestAnimationFrame(step)
        return
      }

      // work out elapsed time so we can measure how far through
      // the animation we are
      if (lastTime !== null) {
        elapsed += timestamp - lastTime
      }

      lastTime = timestamp

      // opacity change for this step is a function of elapsed animation time
      // compared with the desired overall length of the animation (timeMs);
      // we are taking a fraction of the overall opacity delta we want to
      // apply based on the percentage of the animation we have executed so far
      newOpacity = opacity + (opacityDelta * (elapsed / timeMs))

      // if we've reached the desired opacity, we can stop; note that we
      // won't reach this until at least timeMs is reached, as we're using
      // that to calculate a percentage of the total opacity delta to apply to the element
      if (
        (opacityDelta > 0 && newOpacity >= toOpacity) ||
        (opacityDelta < 0 && newOpacity <= toOpacity)
      ) {
        done = true
        newOpacity = toOpacity
      }

      elt.style.opacity = newOpacity

      if (done) {
        callback()
      } else {
        // queue up the next step of the animation
        window.requestAnimationFrame(step)
      }
    }

    // start animating
    window.requestAnimationFrame(step)

    return true
  }
})()

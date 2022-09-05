;(function () {
  'use strict'

  const $ = window.jQuery
  const GOVUK = window.GOVUK || {}

  const GOVUKTracker = function (gifUrl) {
    this.gifUrl = gifUrl
    this.dimensions = []
  }

  GOVUKTracker.load = function () {}

  GOVUKTracker.prototype.trackPageview = function (path, title, options) {
    let pageviewObject

    if (typeof path === 'string') {
      pageviewObject = { page: path }
    }

    if (typeof title === 'string') {
      pageviewObject = pageviewObject || {}
      pageviewObject.title = title
    }

    // Set an options object for the pageview (e.g. transport, sessionControl)
    // https://developers.google.com/analytics/devguides/collection/analyticsjs/field-reference#transport
    if (typeof options === 'object') {
      pageviewObject = $.extend(pageviewObject || {}, options)
    }

    if (!$.isEmptyObject(pageviewObject)) {
      this.sendToTracker('pageview', pageviewObject)
    } else {
      this.sendToTracker('pageview')
    }
  }

  // https://developers.google.com/analytics/devguides/collection/analyticsjs/events
  GOVUKTracker.prototype.trackEvent = function (category, action, options) {
    options = options || {}
    const evt = {
      eventCategory: category,
      eventAction: action
    }

    if (options.label) {
      evt.eventLabel = options.label
      delete options.label
    }

    if (options.value) {
      evt.eventValue = options.value.toString()
      delete options.value
    }

    if (typeof options === 'object') {
      $.extend(evt, options)
    }

    this.sendToTracker('event', evt)
  }

  GOVUKTracker.prototype.trackSocial = function (network, action, target, options) {
    const trackingOptions = {
      socialNetwork: network,
      socialAction: action,
      socialTarget: target
    }

    $.extend(trackingOptions, options)

    this.sendToTracker('social', trackingOptions)
  }

  GOVUKTracker.prototype.addLinkedTrackerDomain = function () { /* noop */ }

  GOVUKTracker.prototype.setDimension = function (index, value) {
    this.dimensions['dimension' + index] = value
  }

  GOVUKTracker.prototype.payloadParams = function (type, payload) {
    const data = $.extend({},
      payload,
      this.dimensions,
      {
        eventType: type,
        referrer: global.document.referrer,
        gaClientId: this.gaClientId,
        windowWidth: global.innerWidth,
        windowHeight: global.innerHeight,
        screenWidth: global.screen.width,
        screenHeight: global.screen.height,
        colorDepth: global.screen.colorDepth
      }
    )

    if (global.performance) {
      data.navigationType = global.performance.navigation.type.toString()
      data.redirectCount = global.performance.navigation.redirectCount.toString()

      for (const k in global.performance.timing) {
        const v = global.performance.timing[k]
        if (typeof v === 'string' || typeof v === 'number') {
          data['timing_' + k] = v.toString()
        }
      }
    }

    return data
  }

  GOVUKTracker.prototype.sendData = function (params) {
    const url = this.gifUrl + '?' + $.param(params)
    $.get(url)
  }

  GOVUKTracker.prototype.sendToTracker = function (type, payload) {
    $(global.document).ready(function () {
      if (global.ga) {
        global.ga(function (tracker) {
          this.gaClientId = tracker.get('clientId')
          this.sendData(this.payloadParams(type, payload))
        }.bind(this))
      } else {
        this.sendData(this.payloadParams(type, payload))
      }
    }.bind(this))
  }

  GOVUK.GOVUKTracker = GOVUKTracker
  window.GOVUK = GOVUK
})()

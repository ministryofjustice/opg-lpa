;(function () {
  'use strict'

  const moj = window.moj || {}
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
      pageviewObject = moj.Helpers.extend(pageviewObject || {}, options)
    }

    if (Object.keys(pageviewObject).length === 0) {
      this.sendToTracker('pageview')
    } else {
      this.sendToTracker('pageview', pageviewObject)
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
      moj.Helpers.extend(evt, options)
    }

    this.sendToTracker('event', evt)
  }

  GOVUKTracker.prototype.trackSocial = function (network, action, target, options) {
    const trackingOptions = {
      socialNetwork: network,
      socialAction: action,
      socialTarget: target
    }

    moj.Helpers.extend(trackingOptions, options)

    this.sendToTracker('social', trackingOptions)
  }

  GOVUKTracker.prototype.addLinkedTrackerDomain = function () { /* noop */ }

  GOVUKTracker.prototype.setDimension = function (index, value) {
    this.dimensions['dimension' + index] = value
  }

  GOVUKTracker.prototype.payloadParams = function (type, payload) {
    const data = moj.Helpers.extend(
      {},
      payload,
      this.dimensions,
      {
        eventType: type,
        referrer: window.document.referrer,
        gaClientId: this.gaClientId,
        windowWidth: window.innerWidth,
        windowHeight: window.innerHeight,
        screenWidth: window.screen.width,
        screenHeight: window.screen.height,
        colorDepth: window.screen.colorDepth
      }
    )

    if (window.performance) {
      data.navigationType = window.performance.navigation.type.toString()
      data.redirectCount = window.performance.navigation.redirectCount.toString()

      for (const k in window.performance.timing) {
        const v = window.performance.timing[k]
        if (typeof v === 'string' || typeof v === 'number') {
          data['timing_' + k] = v.toString()
        }
      }
    }

    return data
  }

  GOVUKTracker.prototype.sendData = function (params) {
    moj.Helpers.ajax({
      url: this.gifUrl,
      query: params
    })
  }

  GOVUKTracker.prototype.sendToTracker = function (type, payload) {
    const self = this

    window.addEventListener('DOMContentLoaded', function () {
      if (window.ga) {
        window.ga(function (tracker) {
          self.gaClientId = tracker.get('clientId')
          self.sendData(self.payloadParams(type, payload))
        })
      } else {
        self.sendData(self.payloadParams(type, payload))
      }
    })
  }

  GOVUK.GOVUKTracker = GOVUKTracker
  window.GOVUK = GOVUK
})()

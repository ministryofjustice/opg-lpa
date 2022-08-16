// Stageprompt 2.0.1 (trimmed down for Make an LPA)
//
// See: https://github.com/alphagov/stageprompt
//
// Stageprompt allows user journeys to be described and instrumented
// using data- attributes.
//
// Setup (run this on document ready):
//
//     GOVUK.performance.stageprompt.setupForGoogleAnalytics();
//
// Usage:
//
//     Sending events on click:
//
//         <a class="help-button" href="#" data-journey-click="stage:help:info">See more info...</a>
//
// (we don't send any events on page load, only on clicks)
//
// NOTE: stageprompt is no longer maintained, and has not been updated since c. 2015
// (see https://github.com/alphagov/stageprompt). However, as the code is relatively
// trivial, we are maintaining it ourselves in this codebase rather than rewriting it.
// This should protect us in the event of the repo disappearing.

window.GOVUK = window.GOVUK || {}
const GOVUK = window.GOVUK

GOVUK.performance = GOVUK.performance || {}

GOVUK.performance.stageprompt = (function () {
  const splitAction = function (action) {
    const parts = action.split(':')
    if (parts.length <= 3) return parts
    return [parts.shift(), parts.shift(), parts.join(':')]
  }

  return {
    setupForGoogleAnalytics: function () {
      document.querySelectorAll('[data-journey-click]').forEach(function (journeyHelper) {
        journeyHelper.addEventListener('click', function (event) {
          const action = splitAction(this.getAttribute('data-journey-click'))
          GOVUK.performance.sendGoogleAnalyticsEvent.apply(null, action)
        })
      })
    }
  }
}())

GOVUK.performance.sendGoogleAnalyticsEvent = function (category, event, label) {
  if (window.ga && typeof (window.ga) === 'function') {
    window.ga('send', 'event', category, event, label)
  } else {
    window._gaq.push(['_trackEvent', category, event, label, undefined, true])
  }
}

// Analytics module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  if (typeof(gaConfig) === 'undefined') {
    moj.log('gaConfig not set. skipping Google Analytics tracking.');
    return;
  }

  moj.Modules.Analytics = {

    init: function () {
      this.bindEvents();

      // Check if we have permission to enable tracking
      if (typeof GOVUK.checkConsentCookieCategory === 'function'
          && GOVUK.checkConsentCookieCategory('analytics', 'usage')) {
          this.setup();
      }
    },

    bindEvents: function() {
      moj.Events.on('Analytics.start', this.setup)
    },

    setup: function() {
      GOVUK.Analytics.load();

      // Use document.domain in dev, preview and staging so that tracking works
      // Otherwise remove proceeding www, as only prod will append www to the URL
      var regEx = new RegExp('^www\.')
      var cookieDomain = regEx.test(document.domain) ? document.domain.replace(regEx, '.') : document.domain;

      // Configure profiles and make interface public
      // for custom dimensions, virtual pageviews and events
      GOVUK.analytics = new GOVUK.Analytics({
        universalId: gaConfig.universalId  || '',
        cookieDomain: cookieDomain,
        allowLinker: true,
        allowAnchor: true,

        //TODO are we tracking this within lpa
        stripPostcodePII: true,
        stripDatePII: true
      });

      if (regEx.test(document.domain)) {
        GOVUK.analytics.addLinkedTrackerDomain(gaConfig.govId, 'govuk_shared', ['www.gov.uk', '.payments.service.gov.uk']);
      }

      // Track initial pageview
      if (typeof GOVUK.pageviewOptions !== 'undefined') {
        GOVUK.analytics.trackPageview(null, null, GOVUK.pageviewOptions);
      }
      else {
        GOVUK.analytics.trackPageview();
      }
    }
  };
})();
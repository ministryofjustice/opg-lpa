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
      moj.Events.on('Analytics.end', this.breakDown)
    },

    setup: function() {
      GOVUK.Analytics.load();

      // Use document.domain in dev, preview and staging so that tracking works
      // Otherwise explicitly set the domain as lastingpowerofattorney.service.justice.gov.uk.
      var prodDomain = new RegExp('^(www\.)*lastingpowerofattorney\.service\.gov\.uk$')
      var cookieDomain = prodDomain.test(document.domain) ? '.lastingpowerofattorney.service.gov.uk' : document.domain;

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

      if (prodDomain.test(document.domain)) {
        GOVUK.analytics.addLinkedTrackerDomain(gaConfig.govId, 'govuk_shared', ['www.gov.uk', '.payments.service.gov.uk']);
      }

      // Track initial pageview
      if (typeof GOVUK.pageviewOptions !== 'undefined') {
        GOVUK.analytics.trackPageview(null, null, GOVUK.pageviewOptions);
      }
      else {
        GOVUK.analytics.trackPageview();
      }
    },

    breakDown: function() {
      // Use document.domain in dev, preview and staging so that tracking works
      // Otherwise explicitly set the domain as lastingpowerofattorney.service.justice.gov.uk.
      var prodDomain = new RegExp('^(www\.)*lastingpowerofattorney\.service\.gov\.uk$')
      var cookieDomain = prodDomain.test(document.domain) ? '.lastingpowerofattorney.service.gov.uk' : document.domain;

      document.cookie = '_ga=; path=/; domain='+ cookieDomain +'; expires=' + new Date(0).toUTCString();
      document.cookie = '_gid=; path=/; domain='+ cookieDomain +'; expires=' + new Date(0).toUTCString();
    }
  };
})();
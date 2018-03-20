;GOVUK.analyticsSetup = function(global) {
  "use strict";

  var $ = global.jQuery
  var GOVUK = global.GOVUK || {}
  var gaConfig = global.gaConfig || {}

  // Load Google Analytics libraries
  GOVUK.Analytics.load();

  // Use document.domain in dev, preview and staging so that tracking works
  // Otherwise explicitly set the domain as lastingpowerofattorney.service.justice.gov.uk.
  var cookieDomain = (document.domain === 'lastingpowerofattorney.service.justice.gov.uk') ? '.lastingpowerofattorney.service.justice.gov.uk' : document.domain;

  // Configure profiles and make interface public
  // for custom dimensions, virtual pageviews and events
  GOVUK.analytics = new GOVUK.Analytics({
    universalId: gaConfig.universalId  || '',
    cookieDomain: cookieDomain,
    allowLinker: true,
    allowAnchor: true
  });

  // Activate any event plugins eg. print intent, error tracking
  GOVUK.analyticsPlugins.formErrorTracker();

  // Track initial pageview
  if (typeof GOVUK.pageviewOptions !== 'undefined') {
    GOVUK.analytics.trackPageview(null, null, GOVUK.pageviewOptions);
  }
  else {
    GOVUK.analytics.trackPageview();
  }

};

GOVUK.analyticsSetup(window)
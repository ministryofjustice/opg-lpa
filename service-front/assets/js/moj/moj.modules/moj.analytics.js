// Analytics module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.Analytics = {
    dataLayer: window.dataLayer || [],
    gaId: '1DVC295G9L',

    init: function () {
      // Check if we have permission to enable tracking
      if (
        typeof GOVUK.checkConsentCookieCategory !== 'function' ||
        !GOVUK.checkConsentCookieCategory('analytics', 'usage')
      ) {
        // set session state _ga cookie to expire
        document.cookie = `_ga_${this.gaId}=; expires=Thu, 01 Jan 1970 00:00:00 UTC;`;
        return;
      }

      this.gtag('js', new Date());

      // GA is in debug mode ('dev traffic') if traffic is not from the main website - ie. localhost
      // or dev/test environments - this allows us to use a data filter to filter out dev traffic in GA
      // and keep integration tests that check analytics cookies
      if (
        window.location.hostname.indexOf(
          'lastingpowerofattorney.service.gov.uk',
        ) == 0
      ) {
        this.gtag('config', `G-${this.gaId}`);
      } else {
        this.gtag('config', `G-${this.gaId}`, { debug_mode: true });
      }
    },

    gtag: function () {
      this.dataLayer.push(arguments);
    },
  };
})();

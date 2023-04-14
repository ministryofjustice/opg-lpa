// Analytics module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.Analytics = {
    dataLayer: window.dataLayer || [],

    init: function () {
      // only send analytics data from the main website
      // if (
      //   window.location.hostname.indexOf(
      //     '1469lpal118.development.front.lpa.opg.service.justice.gov.uk',
      //   ) == -1
      // ) {
      //   return;
      // }

      // Check if we have permission to enable tracking
      if (
        typeof GOVUK.checkConsentCookieCategory !== 'function' ||
        !GOVUK.checkConsentCookieCategory('analytics', 'usage')
      ) {
        // delete session state _ga cookie
        document.cookie =
          '_ga_1DVC295G9L=; expires=Thu, 01 Jan 1970 00:00:00 UTC;';
        return;
      }

      this.gtag('js', new Date());
      this.gtag('config', 'G-1DVC295G9L');
    },

    gtag: function () {
      this.dataLayer.push(arguments);
    },
  };
})();

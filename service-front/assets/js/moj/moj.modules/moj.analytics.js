// Analytics module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.Analytics = {
    dataLayer: window.dataLayer || [],

    init: function () {
      // only send analytics data from the main website
      console.log('func init');
      console.log('host name: ' + window.location.hostname);
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
        console.log('analytics rejected - about to return');
        return;
      }

      console.log('about to add tags!');
      this.gtag('js', new Date());
      this.gtag('config', 'G-1DVC295G9L');
    },

    gtag: function () {
      console.log('pushing arguments');
      this.dataLayer.push(arguments);
    },
  };
})();

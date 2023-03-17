// Analytics module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.Analytics = {
    init: function () {
      this.bindEvents();

      // Check if we have permission to enable tracking
      if (
        typeof GOVUK.checkConsentCookieCategory === 'function' &&
        GOVUK.checkConsentCookieCategory('analytics', 'usage')
      ) {
        console.log('ENABLE ANALYTICS HERE');
      }
    },
  };
})();

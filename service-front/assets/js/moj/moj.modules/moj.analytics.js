// Analytics module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  // Ensure window.dataLayer exists so commands queued before the gtag script
  // loads are processed when it does load.
  window.dataLayer = window.dataLayer || [];

  moj.Modules.Analytics = {
    gaId: 'DY4BCL021L',
    scriptLoaded: false,
    cookieDomains: {
      localhost: 'localhost',
      'www.lastingpowerofattorney.service.gov.uk':
        '.lastingpowerofattorney.service.gov.uk',
    },

    init: function () {
      // Listen for consent being granted after page load (e.g. cookie banner accept)
      moj.Events.on('Analytics.start.analytics', this.init.bind(this));

      // Check if we have permission to enable tracking
      if (
        typeof GOVUK.checkConsentCookieCategory !== 'function' ||
        !GOVUK.checkConsentCookieCategory('analytics', 'usage')
      ) {
        const domain =
          this.cookieDomains[document.location.hostname] || '.justice.gov.uk';

        // Remove session state _ga cookie
        document.cookie = `_ga_${this.gaId}=; domain=${domain}; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;`;
        return;
      }

      // Stop listening once we know consent is granted
      moj.Events.off('Analytics.start.analytics');

      const self = this;

      const configure = function () {
        self.gtag('js', new Date());

        // GA is in debug mode ('dev traffic') if traffic is not from the main website - ie. localhost
        // or dev/test environments - this allows us to use a data filter to filter out dev traffic in GA
        // and keep integration tests that check analytics cookies
        if (
          window.location.hostname.indexOf(
            'lastingpowerofattorney.service.gov.uk',
          ) == 0
        ) {
          self.gtag('config', `G-${self.gaId}`);
        } else {
          self.gtag('config', `G-${self.gaId}`, { debug_mode: true });
        }
      };

      if (this.scriptLoaded) {
        configure();
      } else {
        const s = document.createElement('script');
        s.async = true;
        s.src = `https://www.googletagmanager.com/gtag/js?id=G-${this.gaId}`;
        s.onload = function () {
          self.scriptLoaded = true;
          configure();
        };
        document.head.appendChild(s);
      }
    },

    gtag: function () {
      window.dataLayer.push(arguments);
    },
  };
})();

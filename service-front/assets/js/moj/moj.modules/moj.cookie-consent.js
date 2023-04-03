(function () {
  'use strict';

  moj.Modules.CookieConsent = {
    acceptedUsage: null,

    init: function () {
      if (
        !this.isInCookiesPage() &&
        !this.isInIframe() &&
        'true' !== window.GOVUK.cookie('seen_cookie_message')
      ) {
        this.displayPreferencesForm(true);
        this.displayCookieBanner(true);
        window.GOVUK.cookie('cookie_policy') ||
          window.GOVUK.setDefaultConsentCookie();
      }

      var saveCookieConsent = this.saveCookieConsent.bind(this);

      var acceptButton = document.querySelector(
        '.global-cookie-message__button_accept',
      );
      if (acceptButton) {
        acceptButton.addEventListener('click', function () {
          return saveCookieConsent(true);
        });
      }

      var rejectButton = document.querySelector(
        '.global-cookie-message__button_reject',
      );
      if (rejectButton) {
        rejectButton.addEventListener('click', function () {
          return saveCookieConsent(false);
        });
      }

      var hideConfirmationButton = document.querySelector(
        '.global-cookie-message__button_hide',
      );
      if (hideConfirmationButton) {
        hideConfirmationButton.addEventListener(
          'click',
          this.closeSaveConfirmation.bind(this),
        );
      }

      if (this.isInCookiesPage()) {
        var noJsMessage = document.querySelector('#js-warning');
        noJsMessage.setAttribute('hidden', true);

        var submit = document.querySelector('input[type="submit"]');
        submit.addEventListener('click', function () {
          var flashBanner = document.querySelector(
            '#govuk-notification-banner-title',
          );
          var input = document.querySelector('#usageCookies-yes');

          saveCookieConsent(input.checked);
          flashBanner.removeAttribute('hidden');

          return flashBanner.scrollIntoView();
        });
      }
    },

    displayElement: function (elt, show) {
      if (show) {
        elt.hidden = 'false';
        elt.style.display = 'block';
      } else {
        elt.hidden = 'true';
        elt.style.display = 'none';
      }
    },

    displayCookieBanner: function (show) {
      this.displayElement(
        document.getElementById('global-cookie-message'),
        show,
      );
    },

    displayPreferencesForm: function (show) {
      this.displayElement(
        document.getElementById('cookie-preferences-form'),
        show,
      );
    },

    displaySaveConfirmation: function (show) {
      var text = 'rejected';
      if (this.acceptedUsage) {
        text = 'accepted';
      }
      document.getElementById('cookie-preferences-decision').innerHTML = text;

      // set tabindex and role as per https://design-system.service.gov.uk/components/cookie-banner/
      var elt = document.getElementById('cookie-preferences-save-confirm');
      elt.setAttribute('tabindex', '-1');
      elt.setAttribute('role', 'alert');
      this.displayElement(elt, show);
    },

    // usage: true if usage cookies accepted, false otherwise
    saveCookieConsent: function (usage) {
      this.acceptedUsage = usage;

      window.GOVUK.setConsentCookie({ essential: true, usage: usage });
      window.GOVUK.cookie('seen_cookie_message', 'true', { days: 365 });
      this.displayPreferencesForm(false);
      this.displaySaveConfirmation(true, usage);

      if (usage) {
        // enable analytics and fire off a pageview
        moj.Events.trigger('Analytics.start');
      }
    },

    closeSaveConfirmation: function () {
      this.displaySaveConfirmation(false);
      this.displayCookieBanner(false);
    },

    isInCookiesPage: function () {
      return '/cookies' === window.location.pathname;
    },

    isInIframe: function () {
      return window.parent && window.location !== window.parent.location;
    },
  };
})();

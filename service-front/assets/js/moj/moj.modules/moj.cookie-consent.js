(function() {
    'use strict';

    moj.Modules.CookieConsent = {
        acceptedUsage: null,

        init: function () {
            if (!this.isInCookiesPage() && !this.isInIframe() && 'true' !== window.GOVUK.cookie('seen_cookie_message')) {
                this.displayPreferencesForm(true);
                this.displayCookieBanner(true);
                window.GOVUK.cookie('cookie_policy') || window.GOVUK.setDefaultConsentCookie()
            }

            var saveCookieConsent = this.saveCookieConsent.bind(this);

            var acceptButton = document.querySelector('.global-cookie-message__button_accept');
            acceptButton.addEventListener('click', function(evt) {
                return saveCookieConsent(true);
            });

            var rejectButton = document.querySelector('.global-cookie-message__button_reject');
            rejectButton.addEventListener('click', function(evt) {
                return saveCookieConsent(false);
            });

            var hideConfirmationButton = document.querySelector('.global-cookie-message__button_hide');
            hideConfirmationButton.addEventListener('click', this.closeSaveConfirmation.bind(this));
        },

        displayElement: function (elt, show) {
            if (show) {
                elt.style.display = 'block';
            }
            else {
                elt.style.display = 'none';
            }
        },

        displayCookieBanner: function (show) {
            this.displayElement(document.getElementById('global-cookie-message'), show);
        },

        displayPreferencesForm: function (show) {
            this.displayElement(document.getElementById('cookie-preferences-form'), show);
        },

        displaySaveConfirmation: function (show) {
            var text = 'rejected';
            if (this.acceptedUsage) {
                text = 'accepted';
            }
            document.getElementById('cookie-preferences-decision').innerHTML = text;

            this.displayElement(document.getElementById('cookie-preferences-save-confirm'), show);
        },

        // usage: true if usage cookies accepted, false otherwise
        saveCookieConsent: function (usage) {
            this.acceptedUsage = usage;

            window.GOVUK.setConsentCookie({essential: true, usage: usage});
            window.GOVUK.cookie('seen_cookie_message', 'true', { days: 365 });
            this.displayPreferencesForm(false);
            this.displaySaveConfirmation(true, usage);

            if (usage) {
                // enable analytics and fire off a pageview
                moj.Events.trigger('Analytics.start');
            }
        },

        closeSaveConfirmation: function (evt) {
            this.displaySaveConfirmation(false);
            this.displayCookieBanner(false);
        },

        isInCookiesPage: function () {
            return '/cookies' === window.location.pathname
        },

        isInIframe: function () {
            return window.parent && window.location !== window.parent.location
        }
    }
})();
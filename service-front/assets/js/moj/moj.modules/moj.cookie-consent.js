(function() {
    'use strict';

    moj.Modules.CookieConsent = {
        init: function () {
            if (!this.isInCookiesPage() && !this.isInIframe() && 'true' !== window.GOVUK.cookie('seen_cookie_message')) {
                this.displayCookieMessage(true);
                window.GOVUK.cookie('cookie_policy') || window.GOVUK.setDefaultConsentCookie()
            }

            var acceptButton = document.querySelector('.global-cookie-message__button_accept');
            acceptButton.addEventListener('click', this.acceptAdditionalCookies.bind(this));

            var rejectButton = document.querySelector('.global-cookie-message__button_reject');
            rejectButton.addEventListener('click', this.rejectAdditionalCookies.bind(this));
        },

        displayCookieMessage: function(show) {
            var message = document.getElementById('global-cookie-message');

            if (show) {
                message.style.display = 'block';
            } else {
                message.removeAttribute('style');
            }
        },

        acceptAdditionalCookies: function(evt) {
            window.GOVUK.setUsageConsentInCookie(true);
            window.GOVUK.cookie('seen_cookie_message', 'true', { days: 365 });
            this.displayCookieMessage(false);

            // enable analytics and fire off a pageview
            moj.Events.trigger('Analytics.start');
        },

        rejectAdditionalCookies: function(evt) {
            window.GOVUK.setUsageConsentInCookie(false);
            window.GOVUK.cookie('seen_cookie_message', 'true', { days: 365 });
            this.displayCookieMessage(false);
        },

        isInCookiesPage: function() {
            return '/cookies' === window.location.pathname
        },

        isInIframe: function () {
            return window.parent && window.location !== window.parent.location
        }
    }
})();
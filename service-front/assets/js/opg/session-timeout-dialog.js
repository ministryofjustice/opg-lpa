/* jshint unused: false */
/* globals $, window, document */
// SESSION TIMEOUT POPUP LOGIC  
/**
 * @param element
 * @param sessionExpiresMs
 * @param sessionPopupShowAfterMs
 * @param refreshUrl
 */
var SessionTimeoutDialog = function (options) {
    var that = this;
    this.element = options.element;
    this.sessionExpiresMs = options.sessionExpiresMs;
    this.sessionPopupShowAfterMs = options.sessionPopupShowAfterMs;
    this.keepSessionAliveUrl = options.keepSessionAliveUrl;
    this.redirectAfterMs = 3000;

    var continueButton = $('#session-timeout-continue'),
        logoutButton = $('#session-timeout-logout'),
        underlay = $('.session-timeout-underlay');

    // attach click event
    continueButton.click(function (e) {
        e.preventDefault();
        that.hidePopupAndRestartCountdown();

        GOVUK.performance.sendGoogleAnalyticsEvent('timeout warning', 'continue clicked');
    });

    this.startCountdown = function () {
        this.countDownPopup = window.setTimeout(function () {
            that.element.css('visibility', 'visible');
            that.element.show();
            underlay.css(
                {
                    'visibility': 'visible',
                    'height': $(document).height() + 'px'
                });
            underlay.show();
            that.trapNavigation();
            continueButton.focus();

            GOVUK.performance.sendGoogleAnalyticsEvent('timeout warning', 'warning popup');
        }, this.sessionPopupShowAfterMs);

        this.countDownLogout = window.setTimeout(function () {
            window.location.reload();
        }, this.sessionExpiresMs + this.redirectAfterMs);

    };

    this.hidePopupAndRestartCountdown = function () {
        this.element.hide();
        underlay.hide();

        this.keepSessionAlive();

        // restart countdown
        window.clearTimeout(this.countDownLogout);
        this.startCountdown();
        this.releaseNavigation();
    };

    this.keepSessionAlive = function () {
        $.get(this.keepSessionAliveUrl);
    };

    this.trapNavigation = function () {
        continueButton.keydown(function (e) {
            if (e.key === 'Tab' && e.shiftKey) {
                e.preventDefault();
                logoutButton.focus();
            }
        });
        logoutButton.keydown(function (e) {
            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                continueButton.focus();
            }
        });
    };

    this.releaseNavigation = function () {
        continueButton.off('keydown');
        logoutButton.off('keydown');
    };

};

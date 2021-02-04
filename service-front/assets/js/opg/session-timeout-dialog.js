/* globals $, window, document, GOVUK */
/* exported SessionTimeoutDialog */
// SESSION TIMEOUT POPUP LOGIC
/**
 * @param {Object} options
 * @param {Object} options.element The element that is acting as the warning
 * @param {int} options.warningPeriodMs Number of Milliseconds prior to session expiry to show the warning
 * @param {string} [options.remainingTimeUrl] Url to GET the remaining seconds for the current session
 * @param {string} [options.keepSessionAliveUrl] Url to GET if the user wants to continue their session
 * @param {int} [options.initialSessionTimeoutMs] Initial number of Milliseconds until the session expires (just used to
 *        kick off the first countdown to checking if a warning needs to be displayed)
 */
var SessionTimeoutDialog = function (options) {
    'use strict';

    var that = this;

    var debugTimerMs = 0;
    var debug = /debug.dialog.sessionMs=([0-9]+)/.exec(window.location.href);
    if (debug) {
        debugTimerMs = parseInt(debug[1]);
    }

    if (typeof options.element === 'undefined') {
        throw 'Popup element not provided';
    }

    if (typeof options.warningPeriodMs === 'undefined') {
        throw 'Timeout warning in Milliseconds not provided';
    }

    this.element = options.element;
    this.warningPeriodMs = options.warningPeriodMs;
    this.remainingTimeUrl = options.remainingTimeUrl ? options.remainingTimeUrl : '/session-state';
    this.keepSessionAliveUrl = options.keepSessionAliveUrl ? options.keepSessionAliveUrl : '/session-keep-alive';
    var initialSessionTimeoutMs = options.initialSessionTimeoutMs ? options.initialSessionTimeoutMs : 0;

    var continueButton = $('#session-timeout-continue'),
        logoutButton = $('#session-timeout-logout'),
        underlay = $('.session-timeout-underlay');

    // attach click event
    continueButton.click(function (e) {
        e.preventDefault();
        that.hidePopupAndRestartCountdown();

        GOVUK.performance.sendGoogleAnalyticsEvent('timeout warning', 'continue clicked');
    });

    this.showWarning = function () {
        that.element.css('visibility', 'visible');
        that.element.show();
        underlay.css({
            'visibility': 'visible',
            'height': $(document).height() + 'px'
        });
        underlay.show();
        that.trapNavigation([continueButton[0], logoutButton[0]]);
        continueButton.focus();

        GOVUK.performance.sendGoogleAnalyticsEvent('timeout warning', 'warning popup');
    };

    this.hideWarning = function () {
        this.element.hide();
        underlay.hide();
    };

    // Checks how much time is remaining on the session and show/hides the warning as appropriate as well as scheduling
    // another check, refreshes page on timeout
    this.checkSessionState = function () {
        $.ajax({
            url: that.remainingTimeUrl,
            data: {},
            complete: function (data) {
                if (data.status === 204) {
                    // If timed out, refresh page and let the server do the redirect to the timeout page
                    window.location.reload();
                } else if (data.status === 200) {
                    // Check how much time left
                    var remainingMs = data.responseJSON.remainingSeconds * 1000;
                    if (debug) {
                        remainingMs = that.warningPeriodMs - 1000;
                    }

                    if (remainingMs <= that.warningPeriodMs) {
                        // If less time remaining than the warning period then show the warning and check again just
                        // after the session should have expired
                        that.showWarning();
                        that.startExpiryCheckCountdown(remainingMs + 1000);
                    } else {
                        // If more time than the warning period then check again when it we're back in the warning
                        // period
                        that.hideWarning();
                        that.startExpiryCheckCountdown(remainingMs - that.warningPeriodMs);
                    }

                }
            },
            error: function () {
                // Assume it was a temporary error and check again in 1 minute
                that.startExpiryCheckCountdown(60000);
            }
        });
    };

    this.startExpiryCheckCountdown = function (ms) {
        window.clearTimeout(that.checkSessionState);

        this.countDownPopup = window.setTimeout(function () {
            that.checkSessionState();
        }, ms < 0 ? 0 : ms);
    };

    this.hidePopupAndRestartCountdown = function () {
        this.hideWarning();

        // Keep the session alive
        $.get(this.keepSessionAliveUrl)
            .complete(function () {
                // restart countdown to get new session expiry in one minute
                that.checkSessionState();
            });

        this.releaseNavigation();
    };

    this.trapNavigation = function (focusables) {
        var currentElementIndex = 0;
        focusables[currentElementIndex].focus();

        var lastFocusableIndex = focusables.length - 1;

        this.element.keydown(function (e) {
            // capture only tab events on this dialog
            if (e.key !== 'Tab') {
                return true;
            }
            e.preventDefault();

            var direction = 1;
            if (e.shiftKey) {
                direction = -1;
            }

            currentElementIndex = currentElementIndex + direction;

            // cycle to start/end of list if out of bounds
            if (currentElementIndex > lastFocusableIndex) {
                currentElementIndex = 0;
            }
            else if (currentElementIndex < 0) {
                currentElementIndex = lastFocusableIndex;
            }

            focusables[currentElementIndex].focus();
        });
    };

    this.releaseNavigation = function () {
        this.element.off('keydown');
    };

    // Start countdown
    var countdownTimerMs = initialSessionTimeoutMs - this.warningPeriodMs;
    if (debugTimerMs > 0) {
        countdownTimerMs = debugTimerMs;
    }

    this.startExpiryCheckCountdown(countdownTimerMs);
};

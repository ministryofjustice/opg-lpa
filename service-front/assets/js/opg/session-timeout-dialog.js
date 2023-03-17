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
window.SessionTimeoutDialog = function (options) {
  'use strict';

  const that = this;

  if (typeof options.element === 'undefined') {
    throw new Error('Popup element not provided');
  }

  if (typeof options.warningPeriodMs === 'undefined') {
    throw new Error('Timeout warning in Milliseconds not provided');
  }

  this.element = options.element;
  this.warningPeriodMs = options.warningPeriodMs;
  this.remainingTimeUrl = options.remainingTimeUrl
    ? options.remainingTimeUrl
    : '/session-state';
  this.keepSessionAliveUrl = options.keepSessionAliveUrl
    ? options.keepSessionAliveUrl
    : '/session-keep-alive';
  const initialSessionTimeoutMs = options.initialSessionTimeoutMs
    ? options.initialSessionTimeoutMs
    : 0;

  const continueButton = $('#session-timeout-continue');
  const logoutButton = $('#session-timeout-logout');
  const underlay = $('.session-timeout-underlay');

  // attach click event
  continueButton.on('click', function (e) {
    e.preventDefault();
    that.hidePopupAndRestartCountdown();
  });

  this.showWarning = function () {
    that.element.css('visibility', 'visible');
    that.element.show();
    underlay.css({
      visibility: 'visible',
      height: $(document).height() + 'px',
    });
    underlay.show();

    // setTimeout() ensures that the navigation trap happens only after the
    // previous show() functions are called
    setTimeout(function () {
      that.trapNavigation([continueButton[0], logoutButton[0]]);
    }, 1);
  };

  this.hideWarning = function () {
    this.element.hide();
    underlay.hide();
  };

  // status: 200 means user is still logged in; 204 means they have timed out
  // remainingSeconds: number of seconds remaining in user's session
  this.setDialogState = function (status, remainingSeconds) {
    // How long to wait before checking for session timeout
    let nextCheckMs = 0;

    if (status === 204) {
      // If timed out, refresh page and let the server do the redirect
      // to the timeout page
      window.location.reload();
    } else if (status === 200) {
      // Check how much time left
      const remainingMs = remainingSeconds * 1000;

      if (remainingMs <= that.warningPeriodMs) {
        // If less time remaining than the warning period then show the
        // warning and check again just after the session should have expired
        that.showWarning();
        nextCheckMs = remainingMs + 1000;
      } else {
        // If more time than the warning period then check again when
        // we're back in the warning window
        that.hideWarning();
        nextCheckMs = remainingMs - that.warningPeriodMs;
      }

      // Queue up the next check of whether session is about to expire
      that.startExpiryCheckCountdown(nextCheckMs);
    }

    return nextCheckMs;
  };

  // Checks how much time is remaining on the session and show/hides the warning as appropriate as well as scheduling
  // another check, refreshes page on timeout
  this.checkSessionState = function () {
    // Fetch the session data from the API
    $.ajax({
      url: that.remainingTimeUrl,
      headers: {
        'X-SessionReadOnly': 'true',
      },
      data: {},
      complete: function (data) {
        let remainingSeconds = 0;
        if (data.status === 200) {
          remainingSeconds = data.responseJSON.remainingSeconds;
        }
        that.setDialogState(data.status, remainingSeconds);
      },
      error: function () {
        // Assume it was a temporary error and check again in 1 minute
        that.startExpiryCheckCountdown(60000);
      },
    });
  };

  this.startExpiryCheckCountdown = function (ms) {
    window.clearTimeout(that.checkSessionState);

    this.countDownPopup = window.setTimeout(
      function () {
        that.checkSessionState();
      },
      ms < 0 ? 0 : ms,
    );
  };

  this.hidePopupAndRestartCountdown = function () {
    this.hideWarning();

    // Keep the session alive
    $.get(this.keepSessionAliveUrl).complete(function () {
      // restart countdown
      that.checkSessionState();
    });

    this.releaseNavigation();
  };

  this.trapNavigation = function (focusables) {
    let currentElementIndex = 0;
    focusables[currentElementIndex].focus();

    const lastFocusableIndex = focusables.length - 1;

    this.element.on('keydown', function (e) {
      // capture only tab events on this dialog
      if (e.key !== 'Tab' && e.keyCode !== 9) {
        return true;
      }
      e.preventDefault();

      const direction = e.shiftKey ? -1 : 1;

      currentElementIndex = currentElementIndex + direction;

      // cycle to start/end of list if out of bounds
      if (currentElementIndex > lastFocusableIndex) {
        currentElementIndex = 0;
      } else if (currentElementIndex < 0) {
        currentElementIndex = lastFocusableIndex;
      }

      focusables[currentElementIndex].focus();
    });
  };

  this.releaseNavigation = function () {
    this.element.off('keydown');
  };

  // Start countdown
  this.startExpiryCheckCountdown(
    initialSessionTimeoutMs - this.warningPeriodMs,
  );
};

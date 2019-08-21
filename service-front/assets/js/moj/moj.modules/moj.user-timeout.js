// User Timeout module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.UserTimeout = {
    timeoutDuration: 1000 * 60 * 55, // ms * s * m = 55 minutes
    gracePeriod: 1000 * 60 * 5, // ms * s * m = 5 minutes
    timeout: null,

    init: function () {
      _.bindAll(this, 'warning');

      var startTime = this.time();
      // check to see if user is logged in

      if (false) {

        $.ajax({
          url: '/user/is-logged-in',
          dataType: 'json',
          timeout: 10000,
          cache: false,
          context: this,
          success: function (response) {
            if (response.isLoggedIn) {
              // if logged in, start setTimeout to alert them when timeout is imminent
              this.timeout = setTimeout(this.warning, this.timeoutDuration - (this.time() - startTime));
            }
          }
        });

      }
    },

    warning: function () {
      var html = '',
        self = this;

      // TODO: get this HTML out of the JS into a template
      // TODO: get someone to look at this copy, I made it up. Clive.
      html += '<div class="timeout-body">';
      html += '<h1>Session timeout</h1>';
      html += '<p>You have been inactive for 55 minutes and will be automatically logged out in 5 minutes.</p>';
      html += '<p>Click \'OK\' to stay signed in and continue with your LPA.</p>';
      html += '<ul class="actions">';
      html += '<li><a id="sessionRefresh" class="button" href="#">OK</a></li>';
      html += '<li><a class="button-secondary" href="/user/logout">Sign out</a></li>';
      html += '</ul>';
      html += '</div>';

      moj.Modules.Popup.open(html, {
        ident: 'timeout'
      });

      $('#sessionRefresh').on('click', function (e) {
        e.preventDefault();
        window.clearTimeout(self.timeout);
        self.timeout = null;
        moj.Modules.Popup.close();
        self.refreshSession();
      });

      self.timeout = setTimeout(function () {
        // TODO: possibly redirect to a different 'timed out' page rather than standard logout?
        window.location = '/login/timeout';
      }, self.gracePeriod);
    },

    refreshSession: function () {
      var startTime = this.time();

      if (false) {

        $.ajax({
          url: '/user/is-logged-in',
          dataType: 'json',
          timeout: 10000,
          cache: false,
          context: this,
          success: function (response) {
            if (response.isLoggedIn) {
              // if logged in, start another timeout
              this.timeout = setTimeout(this.warning, this.timeoutDuration - (this.time() - startTime));
            } else {
              // redirect tp login if no longer logged in
              window.location = '/login/timeout';
            }
          },
          error: function () {
            // if any errors occur, attempt logout
            window.location = '/login/timeout';
          }
        });

      }
    },

    time: function () {
      return new Date().getTime();
    }
  };

})();
// Disable link after use module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  // Define the class
  var SingleUse = function () {
    this.settings = {
      selector: '.js-single-use',
    };
  };

  SingleUse.prototype = {
    init: function () {
      var useHandler = this.useHandler.bind(this);
      $('body').on('click', this.settings.selector, useHandler);
      $('body').on('submit', this.settings.selector, useHandler);
    },

    noop: function (e) {
      e.preventDefault();
      return false;
    },

    useHandler: function (e) {
      var target = $(e.target);

      // When clicked, we prevent the event firing or percolating from now on
      target.on('click', this.noop);
      target.on('submit', this.noop);

      // Disable the link or form submit button
      var tagName = target.prop('tagName');

      if (tagName === 'A') {
        // Disable link
        target.attr('disabled', 'disabled');
      } else if (tagName === 'FORM') {
        // Disable submit button(s)
        target
          .find('input[type=submit]')
          .attr('disabled', 'disabled')
          .on('click', this.noop);
      }

      return true;
    },
  };

  // Add module to MOJ namespace
  moj.Modules.SingleUse = new SingleUse();
})();

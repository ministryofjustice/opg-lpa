/**
 * JQuery Postcode Lookup plugin for OPG-LPA project
 * Relies on /postcode/lookup route
 *
 * @copyright The Engine Group
 * @author Chris Moreton <chris.moreton@netsensia.com>
 */

(function ($) {
  'use strict';
  $.fn.hasScrollBar = function () {
    return this.get(0).scrollHeight > this.innerHeight();
  };
})(jQuery);
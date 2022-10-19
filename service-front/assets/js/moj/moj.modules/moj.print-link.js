// Print link module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.PrintLink = {
    init: function () {
      this.hookupPrintLinks();
    },

    hookupPrintLinks: function () {
      $('.js-print').on('click', function () {
        $('body').addClass('summary-print');
        window.print();
        $('body').removeClass('summary-print');
        return false;
      });
    },
  };
})();

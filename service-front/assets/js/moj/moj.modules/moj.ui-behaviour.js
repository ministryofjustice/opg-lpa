// UI Behaviour module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.UIBehaviour = {
    init: function () {
      this.sectionScrollTo();
    },

    sectionScrollTo: function () {
      var sectionToScrollTo = $('#current');

      if (sectionToScrollTo.offset() !== undefined) {
        setTimeout(function () {
          window.scrollTo(0, sectionToScrollTo.offset().top);
        }, 1);
      }
    },
  };
})();

// UI Behaviour module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.UIBehaviour = {

    init: function () {
      this.sectionScrollTo();
    },

    sectionScrollTo: function(){
      if ( $('section.current').offset() !== undefined ) {
        setTimeout(function() {
          if (window.location.href.substring(window.location.href.lastIndexOf('/') + 1) !== 'lpa-type') {
            window.scrollTo(0, $('section.current').offset().top - 107);
          }
        }, 200);
      }
    }
  };
})();
// UI Behaviour module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.UIBehaviour = {

    init: function () {
      this.sectionScrollTo();
    },

    sectionScrollTo: function(){
      if ( $('.js-current').offset() !== undefined ) {
        setTimeout(function() {
          window.scrollTo(0, $('.js-current').offset().top - 20);
        }, 200);
      }
    }
  };
})();
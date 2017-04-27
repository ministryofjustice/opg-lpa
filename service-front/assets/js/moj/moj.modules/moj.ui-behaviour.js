// UI Behaviour module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.UIBehaviour = {

    init: function () {
      this.sectionScrollTo();
    },

    sectionScrollTo: function(){
      var sectionToScrollTo = $('.js-current');

      if (sectionToScrollTo.offset() !== undefined ) {
        //  If this section is the applicant section then actually scroll to the section above to reveal the review link
        if (sectionToScrollTo.attr('id') == 'applicant-section') {
          sectionToScrollTo = sectionToScrollTo.prev();
        }

        setTimeout(function() {
          window.scrollTo(0, sectionToScrollTo.offset().top - 20);
        }, 200);
      }
    }
  };
})();
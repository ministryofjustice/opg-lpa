// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.Dashboard = {

    init: function () {
      this.changeMobileActions();
    },

    changeMobileActions: function(){
      // In list view on mobile, disable DETAILS and show all actions
      if (moj.Helpers.isMobileWidth()) {
        $('tr .lpa-actions').each(function(){
          $('.lpa-manage details', this).before($('.lpa-manage details ul', this));
          $('.lpa-manage details').addClass('hidden');
        })
      }
    }
  };
})();

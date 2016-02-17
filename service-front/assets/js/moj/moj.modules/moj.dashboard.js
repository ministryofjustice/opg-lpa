// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.Dashboard = {

    init: function () {
      this.changeMobileActions();
    },

    changeMobileActions: function(){
      if (moj.Helpers.isMobileWidth()) {
        // move the UL from DETAILS and rebind the links
        // Look into the bind & render functions
      }
    }
  };
})();

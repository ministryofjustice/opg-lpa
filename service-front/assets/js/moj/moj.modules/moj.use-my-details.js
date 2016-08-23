// Use My Details module for LPA
// Dependencies: moj, jQuery
// 
// The 'Use My Details' action link can only be used once.
// This module removes the panel and the link if any current actors
// match the signed in user's first and last name.
//
// actors and user objects are dynamically created in the twig template.

(function () {
  'use strict';

  moj.Modules.UseMyDetails = {

    init: function () {
      this.removeIfUsed();
    },

    removeIfUsed: function(){

      // Set variables
      var loop,
        item,
        detailsUsed = false

      // Check for names match (user and actors)
      if ((typeof actors !== 'undefined') && actors.names && actors.names.length) {
        for (loop = 0; loop < actors.names.length; loop++) {
          item = actors.names[loop];
          if (user.firstName.toLocaleLowerCase() === item.firstname.toLocaleLowerCase()) {
            if (user.lastName.toLocaleLowerCase() === item.lastname.toLocaleLowerCase()) {
              detailsUsed = true;
              break;
            }
          }
        }
      }

      // If it's a match then remove the panel with the link
      if (detailsUsed) {
        $('.js-UseMyDetails').remove();
      }

    }
  }

})();

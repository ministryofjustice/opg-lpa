// Confirm module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.Confirm = {

    init: function () {
      this.confirm();
    },

    confirm: function(){
      $('body').on('click', '.js-confirm', function(event){
        moj.log('Delete?');
        event.preventDefault();
        var url = $(this).attr('href');
        var question = $(this).data('confirm-question');
        if(confirm(question)) {
          window.location.href = url;
        }
      });
    }
  };
})();
// Who Are You module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.WhoAreYou = {

    init: function () {
      this.selectionBehaviour();
    },

    selectionBehaviour: function(){
      $('#WhoAreYou input:radio:checked').parents('.set').find('.subquestion:first').show();

      $('#WhoAreYou input:radio').change(function (){
        // Hides all sub-questions...
        $('#WhoAreYou .subquestion').hide();
        // Displays the first sub-questions for the selected radio (if any)...
        $(this).parents('.set').find('.subquestion:first').show();
        // Uncheck all other sub-questions (if any)...
        $('#WhoAreYou .subquestion:hidden input:radio').prop('checked', false);

      });
    }
  };
})();
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.Applicant = {

    init: function () {
      this.selectionBehaviour();
    },

    selectionBehaviour: function(){
      // Only do the following if .js-attorney-list exists
      if ($('.js-attorney-list')[0]) {

        // Toggle all checkboxes under Attorneys
        $('[name="whoIsRegistering"]').change(function(){
          if($(this).val() === 'donor' ){
            $('.attorney-applicant input:checkbox').prop('checked', false);
          } else {
            $('.attorney-applicant input:checkbox').prop('checked', true);
          }
        });

        // Revert to Donor if no Attorneys are checked
        $('.attorney-applicant input').change(function(){
          if($('.attorney-applicant input').is(':checked')){
            $('input[name="whoIsRegistering"][value!="donor"]').prop('checked', true);
          } else {
            $('input[name="whoIsRegistering"][value="donor"]').prop('checked', true);
          }
        });

      }
    }
  };
})();
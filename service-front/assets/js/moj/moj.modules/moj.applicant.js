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
            $('.js-attorney-list input:checkbox').prop('checked', false);
            $('.js-attorney-list label').removeClass('selected');
            $('')
          } else {
            $('.js-attorney-list input:checkbox').prop('checked', true);
            $('.js-attorney-list label').addClass('selected');
          }
        });

        // Revert to Donor if no Attorneys are checked
        $('.js-attorney-list input').change(function(){
          if($('.js-attorney-list input').is(':checked')){
            $('input[name="whoIsRegistering"][value!="donor"]').prop('checked', true).parent().addClass('selected');
            $('input[name="whoIsRegistering"][value="donor"]').parent().removeClass('selected');

          } else {
            $('input[name="whoIsRegistering"][value="donor"]').prop('checked', true).parent().addClass('selected');
            $('input[name="whoIsRegistering"][value!="donor"]').parent().removeClass('selected');
          }
        });

      }
    }
  };
})();
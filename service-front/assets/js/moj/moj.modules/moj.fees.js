// Fees module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.Fees = {

    init: function () {
      this.labelSubtextDisplay();
    },

    labelSubtextDisplay: function(){
      $('input[name=reductionOptions]').change(function(){
        $('.revised-fee').addClass('hidden');
        if ($('#reducedFeeReceivesBenefits').is(':checked')) {
          $('#revised-fee-0').removeClass('hidden');
        } else if ($('#reducedFeeUniversalCredit').is(':checked')) {
          $('#revised-fee-uc').removeClass('hidden');
        }
      });
    }
  };
})();
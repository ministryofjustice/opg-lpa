// Fees module for LPA
// Dependencies: moj, jQuery

(function() {


    moj.Modules.Login = {

        init: function () {
            this.hookupShowPasswordToggles();
        },

        hookupShowPasswordToggles: function(){

            var pwd = $('#password_current');
            var link = $('#js_show_hide_password');

            link.click(function(){
                if (pwd.attr('type') === "password"){
                    pwd.attr('type', 'text');
                    link.html("Hide Password");
                } else {
                    pwd.attr('type', 'password');
                    link.html("Show Password");
                }
                return false;
            });

        };
    };

})();





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

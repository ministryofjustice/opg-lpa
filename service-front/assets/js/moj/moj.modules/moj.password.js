// Fees module for LPA
// Dependencies: moj, jQuery

(function() {
    // Applies to /login /signup and /user/change-password
    // on change password page there are two show / hide links
    moj.Modules.PasswordHide = {

        init: function () {
            this.hookupShowPasswordToggles();
        },

        hookupShowPasswordToggles: function(){
            var link = $('.js-showHidePassword');
            var skipConfirm = $('#js-skipConfirmPassword');
            var pwdConfirmParent = $('#password_confirm').parent();

            link.removeClass('hidden');

            link.click(function(){
                var pwd = $('#' + $(this).attr('data-for'));
                var alsoHideConfirm = $(this).attr('data-alsoHideConfirm');
                if (pwd.attr('type') === "password"){
                    pwd.attr('type', 'text');
                    $(this).html("Hide password");
                    if (alsoHideConfirm) {
                        pwdConfirmParent.addClass('hidden');
                        skipConfirm.val(1);
                    }
                } else {
                    pwd.attr('type', 'password');
                    $(this).html("Show password");
                    if (alsoHideConfirm) {
                        pwdConfirmParent.removeClass('hidden');
                        skipConfirm.val(0);
                    }
                }
                pwd.focus();
                return false;
            });
        }
    };

})();

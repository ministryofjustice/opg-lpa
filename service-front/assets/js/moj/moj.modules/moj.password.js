// Fees module for LPA
// Dependencies: moj, jQuery

(function() {
    // Applies to /login /signup and /user/change-password
    // on change password page there are two show / hide links
    moj.Modules.PasswordHide = {

        init: function () {
            this.hookupShowPasswordToggles();
        },

        hookupShowPasswordToggles: function() {
            var link = $('.js-showHidePassword');
            var skipConfirmPassword = $('#js-skipConfirmPassword');
            var pwdConfirmParent = $('#password_confirm').parent();

            //  The show/hide password links are themselves hidden by default so they're not available for non-JS - show them now
            link.removeClass('hidden');

            //  By default ensure that the confirm password hidden validation skip value is set to false and show the link
            skipConfirmPassword.val(0);

            link.click(function() {
                var pwd = $('#' + $(this).attr('data-for'));
                var alsoHideConfirm = $(this).attr('data-alsoHideConfirm');

                //  Determine if we are showing or hiding the password confirm input
                var isShowing = (pwd.attr('type') === "password");

                if (isShowing) {
                    if (alsoHideConfirm) {
                        pwdConfirmParent.addClass('hidden');
                        skipConfirmPassword.val(1);
                    }
                } else {
                    if (alsoHideConfirm) {
                        pwdConfirmParent.removeClass('hidden');
                        skipConfirmPassword.val(0);
                    }
                }

                //  Change the input values as required
                pwd.attr('type', (isShowing ? 'text' : 'password'));
                $(this).html(isShowing ? 'Hide password' : 'Show password');

                return false;
            });
        }
    };

})();

// Fees module for LPA
// Dependencies: moj, jQuery

(function() {

    moj.Modules.PasswordHide = {

        init: function () {
            this.hookupShowPasswordToggles();
        },

        hookupShowPasswordToggles: function(){
            var current_password = $('#password_current');
            var current_link = $('#js-showCurrentPassword');
            current_link.removeClass('hidden');

            current_link.click(function(){
                if (current_password.attr('type') === "password"){
                    current_password.attr('type', 'text');
                    current_link.html("Hide password");
                } else {
                    current_password.attr('type', 'password');
                    current_link.html("Show password");
                }
                return false;
            });

            var pwd = $('#password');
            var link = $('#js-showHidePassword');
            var skipConfirm = $('#js-skipConfirmPassword');
            var pwdConfirmParent = $('#password_confirm').parent();

            link.removeClass('hidden');

            link.click(function(){
                if (pwd.attr('type') === "password"){
                    pwd.attr('type', 'text');
                    link.html("Hide password");
                    pwdConfirmParent.addClass('hidden');
                    skipConfirm.prop('checked', 'checked');
                } else {
                    pwd.attr('type', 'password');
                    link.html("Show password");
                    pwdConfirmParent.removeClass('hidden');
                    skipConfirm.removeProp('checked');
                }
                return false;
            });
        }
    };

})();

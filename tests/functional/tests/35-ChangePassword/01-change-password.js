
casper.test.begin('Change password', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.passwordChange).then(function () {

            // We should be redirected to the gov.uk landing page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.passwordChange + '$'), 'Page is on the expected URL for changing password.');

        }).then(function() { // Change password validation test

            var formVars = {
                "password_current": password,
                "password": "-",
                "password_confirm": "*"
            };

            casper.fill('#change-password', formVars, true);

        }).waitForText('There is a problem', function(){

            test.assertUrlMatch(new RegExp('^' + basePath + paths.passwordChange + '$'), "Back to Change Password page as expected due to failed validation");

            test.assertTextExists( "Choose a new password that includes at least one digit (0-9)" , "Correct validation message shown for no digits");
            test.assertTextExists( "Choose a new password that includes at least one lower case letter (a-z)", "Correct validation message shown for no lowercase letters");
            test.assertTextExists( "Choose a new password that includes at least one capital letter (A-Z)" , "Correct validation message shown for no uppercase letters");
            test.assertTextExists( "Enter matching passwords" , "Correct validation message shown for password mismatch");

        });

        casper.run(function () { test.done(); });

    } // test

});

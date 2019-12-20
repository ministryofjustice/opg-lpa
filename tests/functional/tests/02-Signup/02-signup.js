
casper.test.begin('Checking for a successful signup', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        //---

        casper.start(basePath + paths.signup).then(function () {

            // We should be redirected to the gov.uk landing page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.signup + '$'), 'Page is on the expected URL.');

        }).then(function(){

            // Correctly complete the form and submit it.

            var form = {
                "email": email,
                "email_confirm": email,
                "password": password,
                "password_confirm": password,
                "terms": 1
            };

            casper.fill('#registration', form, true);

        }).waitForText('Please check your email', function(){

            test.assertTextExists( email , "Page confirms user's email address.");

            test.assertTextExists('Please check your email', "User " + email + " created with password of " + password);

        });

        casper.run(function () { test.done(); });

    } // test

});

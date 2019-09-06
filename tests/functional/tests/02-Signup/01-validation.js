
casper.test.begin('Checking signup form validation', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.signup).then(function () {

            // We should be redirected to the gov.uk landing page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.signup + '$'), 'Page is on the expected URL.');

        }).then(function(){

            // Ensure everything exists

            test.assertExists('[action="'+paths.signup+'"]', 'Registeration form exists');
            test.assertExists('[name="email"]', 'Email field exists');
            test.assertExists('[name="email_confirm"]', 'Email confirm field exists');
            test.assertExists('[name="password"]', 'Password field exists');
            test.assertExists('[name="password_confirm"]', 'Password Confirm field exists');
            test.assertExists('[name="terms"]', 'T & C Field exists');
            test.assertExists('[name="submit"]', 'Register button exists');

            //--------------------------

            var form = {
                "email": null,
                "email_confirm": null,
                "password": null,
                "password_confirm": null,
                "terms": null
            };

            casper.fill('#registration', form, true);

        }).then(function(){

            casper.warn('Post submission validation checks not implemented - waiting for HTML to be confirmed.');

        });

        casper.run(function () { test.done(); });

    } // test

});

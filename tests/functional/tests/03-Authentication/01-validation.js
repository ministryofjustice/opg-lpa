
casper.test.begin('Checking login form validation', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.login).then(function () {

            // We should be redirected to the gov.uk landing page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.login + '\\?cookie=1$'), 'Page is on the expected URL.');

        }).then(function(){

            // Check the expected fields exist...
            test.assertExists('[action="'+paths.login+'"]', 'Form exists');
            test.assertExists('[name="email"]', 'Email field exists');
            test.assertExists('[name="password"]', 'Password field exists');

            //--------------------------

            var form = {
                "email": null,
                "password": null
            };

            casper.fill('#login', form, true);

        }).then(function(){

            casper.warn('Post submission validation checks not implemented - waiting for HTML to be confirmed.');

        });

        casper.run(function () { test.done(); });

    } // test

});

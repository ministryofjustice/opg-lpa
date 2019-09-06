
casper.test.begin('Checking for a successful login', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.login).then(function () {

            // We should be redirected to the gov.uk landing page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.login + '$'), 'Page is on the expected URL.');

        }).then(function(){

            // Correctly complete the form and submit it.

            var form = {
                "email": email,
                "password": password
            };

            casper.fill('#login', form, true);

        }).waitForText('Make a lasting power of attorney', function(){

        	test.info('Current URL: ' + this.getCurrentUrl());

            // We should end up on the Type page (as we don't currently have any LPAs).
            test.assertUrlMatch(new RegExp('^' + basePath + paths.lpa_type_new + '$'), "We're now correctly on the Type page.");

        });

        casper.run(function () { test.done(); });

    } // test

});

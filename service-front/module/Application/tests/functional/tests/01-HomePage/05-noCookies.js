
casper.test.begin('Checking "Cookies Disabled" page', {

    setUp: function(test) {},

    tearDown: function(test) {
        // Ensure we leave with cookies enabled.
        phantom.cookiesEnabled = true;
    },

    test: function(test) {

        casper.start(basePath + paths.login).then(function () {

            // First check we can load the normal login page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.login + '$'), 'Page is on the expected URL.');


        }).then(function(){

            // Then disable cookies and try again.

            phantom.cookiesEnabled = false;

            casper.open( basePath + paths.login ).then(function() {

                test.info('Current URL: ' + this.getCurrentUrl());

                // The expected URL is now the enable cookies page.
                test.assertUrlMatch(new RegExp('^' + basePath + paths.enableCookie + '$'), 'Page is on the expected URL.');

            });

        }).then(function(){

            // Re-enable cookies and ensure we get the login page back.

            phantom.cookiesEnabled = true;

            casper.open( basePath + paths.login ).then(function() {

                test.assertUrlMatch(new RegExp('^' + basePath + paths.login + '$'), 'Page is on the expected URL.');

            });


        });

        casper.run(function () { test.done(); });

    } // test

});

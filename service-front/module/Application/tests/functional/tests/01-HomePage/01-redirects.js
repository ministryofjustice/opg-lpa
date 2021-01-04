
casper.test.begin('Checking root path redirect', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        test.info('Accessing: ' + basePath + '/');

        casper.start(basePath + '/').then(function () {

            // We should be redirected to the gov.uk landing page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + rootRedirectUrl + '$'), 'Page is on the expected URL.');

        });

        casper.run(function () { test.done(); });

    } // test

});

casper.test.begin('Checking http -> https redirect', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        var insecureUrl = 'http://' + baseDomain + paths.home;

        test.info('Accessing: ' + insecureUrl );

        casper.start( insecureUrl ).then(function () {
            // We should be redirected to HTTPS.
            test.info('Current URL: ' + this.getCurrentUrl());
            // The URL should now to be the proper homepage (with https).
            ///all environments should now do this

            test.assertUrlMatch(new RegExp('^' + basePath + paths.home + '$'), 'Page is on the expected URL.');


        });
        casper.run(function () { test.done(); });
    } // test
});

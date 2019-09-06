
casper.test.begin("Checking clicking 'Create a new LPA' from the Dashboard", {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.dashboard).then(function () {

            // This is the first time we're going to create an LPA, so we just get directed to the Type page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.lpa_type_new + '$'), 'Page is on the expected URL.');

        });

        casper.run(function () { test.done(); });

    } // test

});

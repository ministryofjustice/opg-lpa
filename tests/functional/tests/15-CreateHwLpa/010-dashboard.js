
casper.test.begin("Checking clicking 'Create a new LPA' from the Dashboard", {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.dashboard).then(function () {

            // We should be redirected to the gov.uk landing page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.dashboard + '$'), 'Page is on the expected URL.');

            //---

            test.assertVisible( '#create-new-lpa', 'Create new LPA button is visible.');

            test.info('Click [Create a new LPA] button');

        }).thenClick( '#create-new-lpa' ).then(function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.lpa_type_new + '$'), 'Page is on the expected URL.');

        });

        casper.run(function () { test.done(); });

    } // test

});


casper.test.begin('Checking "Privacy Notice" page', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.privacy).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.privacy + '$'), 'Page is on the expected URL.');

        });

        casper.run(function () { test.done(); });
    }
});
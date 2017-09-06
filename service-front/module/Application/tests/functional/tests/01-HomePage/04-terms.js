
casper.test.begin('Checking "Terms and Conditions" page', {

    setUp: function(test) {},

    tearDown: function(test) {},
    
    test: function(test) {

        casper.start(basePath + paths.terms).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.terms + '$'), 'Page is on the expected URL.');

        });
        
        casper.run(function () { test.done(); });
    }
});

casper.test.begin('Checking homepage', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.home).then(function () {

            // General tests

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.home + '$'), 'Page is on the expected URL.');

        }).then(function(){

            // Login link test

            var selector = 'a[href="'+ paths.login +'"]';

            test.assertVisible( selector, paths.login + ' link exists and is visible.');

            // Check clicking the link takes us to the expected location...
            this.thenClick( selector ).then(function() {

                test.assertUrlMatch( new RegExp('^'+basePath + paths.login+'$'), 'Link goes to correct page.');

                casper.back();

            });

        }).then(function(){

            // Signup link test

            var selector = 'a[href="'+ paths.signup +'"]';

            test.assertVisible( selector, paths.signup + ' link exists and is visible.');

            // Check clicking the link takes us to the expected location...
            this.thenClick( selector ).then(function() {

                test.assertUrlMatch( new RegExp('^'+basePath + paths.signup+'$'), 'Link goes to correct page.');

                casper.back();

            });

        }).then(function(){

            // Terms link

            var selector = 'a[href="'+ paths.terms +'"]';

            test.assertVisible( selector, paths.terms + ' link exists and is visible.');

            // The terms links opens in a popup, which Casper refuses to handle well!
            // I therefore extract the href, and then access the URL directly to ensure it exists.

            var link = this.getElementAttribute(selector,'href');

            casper.thenOpen(  basePath + link, function() {

                test.assertUrlMatch( new RegExp('^'+basePath + paths.terms+'$'), 'Page is on the expected URL.');

            });

        });


        casper.run(function () { test.done(); });

    } // test

});

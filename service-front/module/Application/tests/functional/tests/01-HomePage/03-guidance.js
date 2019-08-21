
casper.test.begin('Checking the guidance popup', {

    test: function(test) {

        casper.start(basePath + paths.home).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            var selector = 'a.js-guidance';

            test.assertVisible( selector, 'Guidance link exists and is visible.');

        }).thenClick('a.js-guidance', function() {

            test.info("Clicked guidance button");

        }).waitFor(function check() {

            // waiting for lightbox loading
            // use #help-system rather than #popup to ensure content is also loaded.
            return this.exists('#help-system');

        }, function then() {

            //-------------------------------------------------------
            // Check the popup loads...

            test.pass('Guidance popup appears.');

            //-------------------------------------------------------
            // Initially the popup should show 'What is an LPA?'...

            // By default we should get What is an LPA?...
            test.assertUrlMatch( new RegExp('^'+basePath + paths.firstGuidance+'$'), 'Popup URL is correct.');

            // And so this id should be visible...
            test.assertVisible('#topic-what-is-an-lpa', 'What is an LPA? article is visible.');

            // And most likely the h1 is...
            test.assertSelectorHasText('#topic-what-is-an-lpa h1', 'What is an LPA?', 'Basics article h1 looks sensible.');

            //-------------------------------------------------------
            // Check all of the side links...

            /*
             We don't want to actually test content specific things, so this test works be ensuring the are the expected number of
             help topics (set in Bootstrap), and that each of those topics have their own URL and a unique heading (h1).
             Or in short - the *visible* content should change as each link it clicked.
             */

            test.assertElementCount('#popup .help-topics a.js-guidance', numberOfGuidanceHelpTopics, numberOfGuidanceHelpTopics+' help topics correctly found.');

            var lastUrl = this.getCurrentUrl();
            var lastH1 = this.getElementsInfo('#topic-what-is-an-lpa h1')[0].text;

            // We're going to iterate over all help topics minus the first (because we're already on that one)...
            for (var i = 0; i < (numberOfGuidanceHelpTopics-1); i++) {

                casper.thenClick('#popup .help-topics .active + li a').then(function() {

                    // Check the page's header has changed...

                    // Selects the h1 in the currently visible article...
                    var h1 = this.getElementsInfo('#popup article:not(.hidden) h1')[0].text;

                    test.assertNotEquals( lastH1, h1, "The page's heading has correctly changed: "+h1 );

                    //---

                    // Check the URL has changed...
                    test.assertNotEquals( lastUrl, this.getCurrentUrl(), 'The URL has correctly changed: '+this.getCurrentUrl() );

                    //---

                    lastH1 = h1;
                    lastUrl = this.getCurrentUrl();

                });

            } // for

            //-------------------------------------------------------
            // Check the popup closes as expected...

            casper.thenClick('#popup .js-popup-close').then(function() {

                casper.waitWhileVisible('#popup', function() {

                    test.pass('Popup closes as expected.');

                    // Allow 1 second for the JS animation...
                    this.wait(1000, function() {

                        // Check we're back on the pagepage's url...
                        test.assertUrlMatch( new RegExp('^'+basePath + paths.home+'$'), 'URL correctly changed post-popup.');

                    });

                }, function() {

                    test.fail('Popup failed to close as expected.');

                });

            }); // thenClick

        });


        casper.run(function () { test.done(); });

    } // test

});

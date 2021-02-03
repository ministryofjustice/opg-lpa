
casper.test.begin("Checking user can set life sustaining treatment", {

    setUp: function(test) {
        lifeSustainingPath = paths.life_sustaining.replace('\\d+', lpaId);
        attorneyPath = paths.primary_attorney.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete lifeSustainingPath, attorneyPath;
    },

    test: function(test) {

        casper.start(basePath + lifeSustainingPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + lifeSustainingPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#life-sustaining-section', 'Accordion header is found on the page');

            // check form has correct elements
            test.assertExists('input[type="radio"][name="canSustainLife"][value="1"]', 'Found "Option A: Yes" radio option');
            test.assertExists('input[type="radio"][name="canSustainLife"][value="0"]', 'Found "Option B: No" radio option');

            test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

        }).thenClick('input[name="save"]', function() {

            test.info('Clicked [Save and continue] button to submit the form without user input values');

            // check error handling and response
            test.assertExists('div.error-summary h2#error-heading', 'Error messages are displayed as expected');
            test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');

        }).thenClick('input[type="radio"][name="canSustainLife"][value="1"]', function() {

            test.info('Clicked { canSustainLife } option');

        }).thenClick('input[type=submit]', function() {

            test.info("Clicked [Save and continue] button to submit the form for life sustaining decisions");

            test.assertHttpStatus(200, 'Page returns a 200 when the "life sustaining treatment" form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            // check after form submission, page is landing on primay attorney page.
            test.assertEquals(basePath + attorneyPath, this.getCurrentUrl(), 'Page is on expected URL: lpa/'+lpaId+'/primary_attorney');

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+lifeSustainingPath+'"]', 'Found an accordion bar for page as expected');

        });

        casper.run(function () { test.done(); });

    } // test

});

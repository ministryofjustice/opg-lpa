
casper.test.begin("Checking user can set when lpa starts", {

    setUp: function(test) {
        whenLpaStartsPath = paths.when_lpa_starts.replace('\\d+', lpaId);
        attorneyPath = paths.primary_attorney.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete whenLpaStartsPath, attorneyPath;
    },

    test: function(test) {

        casper.start(basePath + whenLpaStartsPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + whenLpaStartsPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#when-lpa-starts-section', 'Accordion header is found on the page');

            // check form has correct elements
            test.assertExists('input[type="radio"][name="when"][value="now"]', 'Found "as soon as register" radio option');
            test.assertExists('input[type="radio"][name="when"][value="no-capacity"]', 'Found "only if I dont have mental capacity" radio option');

            test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

        }).thenClick('input[name="save"]', function() {

            test.info('Clicked [Save and continue] button to submit the form without user input values');

        }).waitForSelector('.error-summary', function() {

            // check error handling and response
            test.assertExists('div.error-summary h2#error-heading', 'Error messages are displayed as expected');
            test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');

        }).thenClick('input[type="radio"][name="when"][value="now"]', function() {

            test.info("Clicked { as soon as it's registered } radio option");

        }).thenClick('input[type=submit]', function() {

            test.info("Clicked [Save and continue] button to submit the form for when LPA can be used");

            test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            // check after form submission, page is landing on primay attorney page.
            test.assertEquals(basePath + attorneyPath, this.getCurrentUrl(), 'Page is on expected URL: ' + this.getCurrentUrl());

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+whenLpaStartsPath+'"]', 'Found an accordion bar link as expected');

        });

        casper.run(function () { test.done(); });

    } // test

});

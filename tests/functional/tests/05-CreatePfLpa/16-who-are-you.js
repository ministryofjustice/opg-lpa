
casper.test.begin("Checking user can access who are you page", {

    setUp: function(test) {
   		whoAreYouPath = paths.who_are_you.replace('\\d+', lpaId);
   		repeatApplicationPath = paths.repeat_application.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete whoAreYouPath, repeatApplicationPath;
    },

    test: function(test) {

        casper.start(basePath + whoAreYouPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + whoAreYouPath + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#who-are-you-section', 'Accordion header is found on the page');

    		// check form has correct elements
    		test.assertExists('form[name="form-who-are-you"]', 'Found form-who-are-you');
    		test.assertExists('input[type="radio"][name="who"][value="donor"]', 'Found donor option');
    		test.assertExists('input[type="radio"][name="who"][value="friendOrFamily"]', 'Found friendOrFamily option');
    		test.assertExists('input[type="radio"][name="who"][value="financeProfessional"]', 'Found financeProfessional option');
    		test.assertExists('input[type="radio"][name="who"][value="legalProfessional"]', 'Found legalProfessional option');
    		test.assertExists('input[type="radio"][name="who"][value="estatePlanningProfessional"]', 'Found estatePlanningProfessional option');
    		test.assertExists('input[type="radio"][name="who"][value="digitalPartner"]', 'Found digitalPartner option');
    		test.assertExists('input[type="radio"][name="who"][value="charity"]', 'Found charity option');
    		test.assertExists('input[type="radio"][name="who"][value="organisation"]', 'Found organisation option');
    		test.assertExists('input[type="radio"][name="who"][value="other"]', 'Found other option');
    		test.assertExists('input[type="radio"][name="who"][value="notSaid"]', 'Found notSaid option');

    		test.assertExists('input[type="text"][name="other"]', 'Found other text input element');

    		test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

        }).thenClick('input[type="submit"][name="save"]', function() {

            test.info("Clicked 'Save and continue' button on Who Are You page");

        }).waitForSelector('.error-summary', function(){

			// check error handling and response
			test.assertExists('div.error-summary h2#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');

        }).thenClick('input[type="radio"][name="who"][value="donor"]', function() {

        	test.info('Selected the donor');

        }).thenClick('input[type="submit"][name="save"]', function() {

        	test.info('Clicked [Save and continue] button');

        	test.assertHttpStatus(200, 'Page returns a 200 when the "who-are-you" form is submitted');

        	test.info('Current URL: ' + this.getCurrentUrl());

        	// check after form submission, page is expected landing on correspondent page.
        	test.assertUrlMatch(new RegExp('^' + basePath + repeatApplicationPath + '$'), 'Page is on the expected URL.');

			// check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
			test.assertExists('.accordion li.complete a[href="'+whoAreYouPath+'"]', 'Found an accordion bar link as expected');

        }).thenClick('.accordion li.complete a[href="'+whoAreYouPath+'"]', function() {

        	test.info('Clicked the accordion bar for going to who are you page');

        	test.assertUrlMatch(new RegExp('^' + basePath + whoAreYouPath + '$'), 'Page is on the who are you page.');

        	test.assertNotExists('form[name="form-who-are-you"]', 'Page does not have form-who-are-you as expected');

        	test.assertSelectorHasText('a[href="'+repeatApplicationPath+'"]', 'Continue', 'Found the continue button as expected');

        });

        casper.run(function () { test.done(); });

    } // test

});

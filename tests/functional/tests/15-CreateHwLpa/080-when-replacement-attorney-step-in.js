
casper.test.begin("Checking user can set when replacement attorney step in page", {

    setUp: function(test) {
    	whenReplacementStepInPath = paths.when_replacement_attorney_step_in.replace('\\d+', lpaId);
    	howReplacementAttorneysMakeDecisionPath = paths.how_replacement_attorneys_make_decision.replace('\\d+', lpaId);
    	certificateProviderPath = paths.certificate_provider.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete whenReplacementStepInPath, howReplacementAttorneysMakeDecisionPath, certificateProviderPath;
    },

    test: function(test) {

        casper.start(basePath + whenReplacementStepInPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + whenReplacementStepInPath + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#when-replacement-attorney-step-in-section', 'Accordion header is found on the page');

    		test.assertExists('input[type="radio"][name="when"][value="first"]', 'Found radio option "As soon as one of the original attorneys can no longer act"');
    		test.assertExists('input[type="radio"][name="when"][value="last"]', 'Found radio option "Only when none of the original attorneys can act"');
    		test.assertExists('input[type="radio"][name="when"][value="depends"]', 'Found radio option "In some other way"');

    		test.assertExists('textarea[name="whenDetails"]', 'Free text box element exists');
    		test.assertNotVisible('textarea[name="whenDetails"]', 'And the free text box is invisible as expected');
    		test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

        }).thenClick('input[name="save"]', function() {

        	test.info("Clicked [Save and continue] button to submit the form without user input values");

			// check error handling and response
			test.assertExists('div.error-summary h2#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');

        }).wait(3000).then(function() {

        	// select depends
        	test.info('Selected { In some other way } radio option');

        	this.click('input[type="radio"][name="when"][value="depends"]');

        	test.assertVisible('textarea[name="whenDetails"]', 'Now the free text box is visible as expected');

        }).thenClick('input[name="save"]', function() {

        	test.info('Clicked [Save and continue] button without entering details in the free text box');

			// check error handling and response
			test.assertExists('div.error-summary h2#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');
			test.assertExists('div.form-group-error textarea#whenDetails', 'The text area is highlighted as the error.');

        }).then(function() {

        	// Select "As soon as one of the original attorneys can no longer act" radio option
        	this.click('input[type="radio"][name="when"][value="first"]');
        	test.info('Selected { As soon as one of the original attorneys can no longer act } radio option');

        }).thenClick('input[name="save"]', function() {

	        test.info('Clicked [Save and continue] button to save step in decisions');

	        test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

			// check attorneys decision is correctly displayed in an accordion bar
			test.assertExists('.accordion li.complete a[href="'+whenReplacementStepInPath+'"]', 'Found an accordion bar link as expected');

		}).thenClick('.accordion li.complete a[href="'+whenReplacementStepInPath+'"]', function() {

    		// click accordion bar to go to when replacement attorney step in page
    		test.info('Now click when replacement step in on accordion bar to change how replacement attorney to step in');

    		// check it is on lpa/when-replacement-attorney-step-in page
    		test.assertUrlMatch(new RegExp('^' + basePath + whenReplacementStepInPath + '$'), 'Page is on the expected URL: '+whenReplacementStepInPath);

    	}).then(function() {

    		// Select "Only when none of the original attorneys can act" radio option
    		test.info('select { Only when none of the original attorneys can act } radio option');
        	this.click('input[type="radio"][name="when"][value="last"]');

    	}).thenClick('input[type="submit"][name="save"]', function() {

        	test.info('click [Save and continue] button to save step in decisions');

        	test.assertHttpStatus(200, 'Page returns a 200 when attorneys decisions form is submitted');

        	test.info('Current URL: ' + this.getCurrentUrl());

    		// check it is on lpa/how-replacement-attorneys-make-decision page
    		test.assertUrlMatch(new RegExp('^' + basePath + howReplacementAttorneysMakeDecisionPath + '$'), 'Page is landing on the expected URL: '+howReplacementAttorneysMakeDecisionPath);

			// check attorneys decision is correctly displayed in an accordion bar
			test.assertExists('.accordion li.complete a[href="'+whenReplacementStepInPath+'"]', 'Found an accordion bar link as expected');

    	});

        casper.run(function () { test.done(); });

    } // test

});


casper.test.begin("Checking user can access how primary attorney make decision page", {

    setUp: function(test) {
    	primaryAttorneysDecisionPath = paths.how_primary_attorneys_make_decision.replace('\\d+', lpaId);
    	replacementAttorneyPath = paths.replacement_attorney.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete primaryAttorneysDecisionPath, replacementAttorneyPath;
    },

    test: function(test) {
    	
        casper.start(basePath + primaryAttorneysDecisionPath).then(function () {
        	
            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + primaryAttorneysDecisionPath + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#primary-attorney-decision-sections', 'Accordion header is found on the page');
    		
    		test.assertExists('input[type="radio"][name="how"][value="jointly-attorney-severally"]', 'Found radio option "Jointly and severally"');
    		test.assertExists('input[type="radio"][name="how"][value="jointly"]', 'Found radio option "Jointly"');
    		test.assertExists('input[type="radio"][name="how"][value="depends"]', 'Found radio option "Jointly for some decisions, and jointly and severally for other decisions"');
    		
    		test.assertExists('textarea[name="howDetails"]', 'Found textarea box');
    		test.assertNotVisible('textarea[name="howDetails"]', 'And the free text box is invisible as expected');
			test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');
    		
        }).thenClick('input[name="save"]', function() {
        	
        	test.info("Clicked [Save and continue] button to submit the form without user input values");

			// check error handling and response
			test.assertExists('div.error-summary h1#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');
        	
        }).wait(3000).then(function() {
        	
        	// select depends
        	test.info('Selected { Jointly for some decisions, and jointly and severally for other decisions } radio option');
        	
        	this.click('input[type="radio"][name="how"][value="depends"]');
        	
        	test.assertVisible('textarea[name="howDetails"]', 'Now the free text box is visible as expected');

        }).thenClick('input[name="save"]', function() {
        	
        	test.info('Clicked [Save and continue] button without entering details in the free text box');

			// check error handling and response
			test.assertExists('div.error-summary h1#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');
        	
        }).thenClick('input[type="radio"][name="how"][value="jointly-attorney-severally"]', function() {
        	
        	test.info('Clicked jointly and severally radio option');

		}).thenClick('input[name="save"]', function() {
        	
    		test.info("Clicked [Save and continue] button to submit attorneys decisions form");
    		
        	test.assertHttpStatus(200, 'Page returns a 200 when attorneys decisions form is submitted');
        	
        	test.info('Current URL: ' + this.getCurrentUrl());
        	
    		// check it is on lpa/replacement-attorney page
    		test.assertUrlMatch(new RegExp('^' + basePath + replacementAttorneyPath + '$'), 'Page is landing on the expected URL: '+replacementAttorneyPath);

			// check attorneys decision is correctly displayed in an accordion bar
			test.assertExists('.accordion li.complete a[href="'+primaryAttorneysDecisionPath+'"]', 'Found an accordion bar link as expected');
    		
    	});
        
        casper.run(function () { test.done(); });

    } // test

});

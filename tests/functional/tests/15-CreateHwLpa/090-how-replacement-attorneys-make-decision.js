
casper.test.begin("Checking user can access how primary attorney make decision page", {

    setUp: function(test) {
    	howReplacementAttorneysMakeDecisionPath = paths.how_replacement_attorneys_make_decision.replace('\\d+', lpaId);
    	certificateProviderPath = paths.certificate_provider.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete howReplacementAttorneysMakeDecisionPath, certificateProviderPath;
    },

    test: function(test) {
    	
        casper.start(basePath + howReplacementAttorneysMakeDecisionPath).then(function () {
        	
            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + howReplacementAttorneysMakeDecisionPath + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#replacement-attorney-decision-sections', 'Accordion header is found on the page');
    		
    		test.assertVisible('input[type="radio"][name="how"][value="jointly-attorney-severally"]', 'Found radio option "Jointly and severally"');
    		test.assertVisible('input[type="radio"][name="how"][value="jointly"]', 'Found radio option "Jointly"');
    		test.assertVisible('input[type="radio"][name="how"][value="depends"]', 'Found radio option "Jointly for some decisions, and jointly and severally for other decisions"');
    		
    		test.assertExists('textarea[name="howDetails"]', 'Free text box element exists');
    		test.assertNotVisible('textarea[name="howDetails"]', 'And the free text box is invisible as expected');
    		test.assertVisible('input[type="submit"][name="save"]', 'Found "Save and continue" button');
    		
        }).thenClick('input[name="save"]', function() {
        	
        	test.info("Clicked [Save and continue] button to submit the form without user input values");

			// check error handling and response
			test.assertExists('div.error-summary h1#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');
        	
        }).wait(3000).then(function() {
        	
        	// select depends
        	test.info('select "Jointly for some decisions, and jointly and severally for other decisions" radio option');
        	
        	this.click('input[type="radio"][name="how"][value="depends"]');
        	
        	test.assertVisible('textarea[name="howDetails"]', 'Now the free text box is visible as expected');

        }).thenClick('input[name="save"]', function() {
        	
        	test.info('Clicked [Save and continue] button without entering details in the free text box');

			// check error handling and response
			test.assertExists('div.error-summary h1#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');
        	
        }).thenClick('input[type="radio"][name="how"][value="jointly-attorney-severally"]', function() {
        	
        	test.info('Clicked jointly and severally radio option');
        	
        }).thenClick('input[type="submit"][name="save"]', function() {
        	
    		test.info("Clicked [Save and continue] button to submit attorneys decisions form");
    		
        	test.assertHttpStatus(200, 'Page returns a 200 when attorneys decisions form is submitted');
        	
        	test.info('Current URL: ' + this.getCurrentUrl());
        	
    		// check it is on lpa/replacement-attorney page
    		test.assertUrlMatch(new RegExp('^' + basePath + certificateProviderPath + '$'), 'Page is landing on the expected URL: '+certificateProviderPath);

			// check attorneys decision is correctly displayed in an accordion bar
			test.assertExists('.accordion li.complete a[href="'+howReplacementAttorneysMakeDecisionPath+'"]', 'Found an accordion bar link as expected');

    	});
        
        casper.run(function () { test.done(); });

    } // test

});

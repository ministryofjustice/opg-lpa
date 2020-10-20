
casper.test.begin("Checking user can access repeat application page", {

    setUp: function(test) {
   		repeatApplicationPath = paths.repeat_application.replace('\\d+', lpaId);
   		feeReductionPath = paths.fee_reduction.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete repeatApplicationPath, feeReductionPath;
    },

    test: function(test) {

        casper.start(basePath + repeatApplicationPath).then(function () {
        	
            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + repeatApplicationPath + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#repeat-application-section', 'Accordion header is found on the page');
    		
    		// check form has correct elements
    		test.assertExists('form[name="form-repeat-application"]', 'Found form-repeat-application');
    		test.assertExists('input[type="radio"][name="isRepeatApplication"][value="is-repeat"]', 'Found is-repeat option');
    		test.assertExists('input[type="radio"][name="isRepeatApplication"][value="is-new"]', 'Found is-new option');
    		test.assertExists('input[type="tel"][name="repeatCaseNumber"]', 'Found repeat case number text input');
    		test.assertNotVisible('input[type="tel"][name="repeatCaseNumber"]', 'Found repeat case number text input is hidden');
    		
    		test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');
        	
        }).thenClick('input[type="submit"][name="save"]', function() {

			// check error handling and response
			test.assertExists('div.error-summary h1#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');

        	
        }).thenClick('input[type="radio"][name="isRepeatApplication"][value="is-new"]', function() {
        	
        	test.info('Clicked { No }');
        	
        }).thenClick('input[type="submit"][name="save"]', function() {
        	
        	test.info('Clicked [Save and continue] button');
        	
        	test.assertHttpStatus(200, 'Page returns a 200 when the "who-are-you" form is submitted');
        	
        	test.info('Current URL: ' + this.getCurrentUrl());
        	
        	// check after form submission, page is expected landing on correspondent page.
        	test.assertUrlMatch(new RegExp('^' + basePath + feeReductionPath + '$'), 'Page is on the expected URL.');

			// check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
			test.assertExists('.accordion li.complete a[href="'+repeatApplicationPath+'"]', 'Found an accordion bar link as expected');
			
        });
        
        casper.run(function () { test.done(); });

    } // test

});

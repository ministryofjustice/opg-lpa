
casper.test.begin("Checking user can select an applicant", {

    setUp: function(test) {
   		applicantPath = paths.applicant.replace('\\d+', lpaId);
   		correspondentPath = paths.correspondent.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete applicantPath, correspondentPath;
    },

    test: function(test) {

        casper.start(basePath + applicantPath).then(function () {
        	
            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + applicantPath + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#applicant-section', 'Accordion header is found on the page');
    		
    		// check form has correct elements
        	test.assertExists('input[type="radio"][name="whoIsRegistering"][value="donor"]', 'Found donor raido option');
        	test.assertExists('input[type="radio"][name="whoIsRegistering"][value="1,2"]', 'Found attorney radio option');
        	
        	test.assertExists('input[type="checkbox"][name="attorneyList[]"][value="1"]', 'Found the first attorney as a checkbox label');
        	test.assertExists('input[type="checkbox"][name="attorneyList[]"][value="2"]', 'Found the second attorney as a checkbox label');
        	test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');
        	
        }).thenClick('input[type="submit"][name="save"]', function() {
        	
        	test.info("Clicked 'Save and continue' button");

			// check error handling and response
			test.assertExists('div.error-summary h1#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');
        	
        }).thenClick('input[type="checkbox"][id="attorney-2"]', function() {
        	
        	test.info('Selected an attorney as the applicant');
        	
        }).thenClick('input[type="submit"][name="save"]', function check() {
        	
        	test.info('Clicked "Save and continue" button on applicant page');
        	
        	test.assertHttpStatus(200, 'Page returns a 200 when the "applicant" form is submitted');
        	
        	test.info('Current URL: ' + this.getCurrentUrl());
        	
        	// check after form submission, page is expected landing on correspondent page.
        	test.assertUrlMatch(new RegExp('^' + basePath + correspondentPath + '$'), 'Page is on the expected URL.');

			test.assertExists('.accordion li.complete a[href="'+applicantPath+'"]', 'Found an accordion bar link as expected');
        	
    	});
        
        casper.run(function () { test.done(); });

    } // test

});

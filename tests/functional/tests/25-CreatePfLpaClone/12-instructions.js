
casper.test.begin("Checking instructions page", {

    setUp: function(test) {
   		instructionPath = paths.instructions.replace('\\d+', lpaId);
		applicantpath = paths.applicant.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete instructionPath, applicantpath;
    },

    test: function(test) {

        casper.start(basePath + instructionPath).then(function () {
        	
            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + instructionPath + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#preferences-and-instructions-section', 'Accordion header is found on the page');
    		
    		// check form has correct elements
        	test.assertExists('textarea[name="instruction"]', 'Found instructions box');
        	test.assertExists('textarea[name="preference"]', 'Found preferences box');
			test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

			test.assertExists('details summary[aria-expanded="false"]', 'Found Add extra instructions button');

		}).thenClick('form#form-preferences-and-instructions details summary', function() {

			test.info("Clicked [Add extra instructions]");

		}).wait(1500).waitForSelector('form#form-preferences-and-instructions details', function then () {

			test.assertVisible('textarea[name="instruction"]', 'Instructions box is now visible');
			test.assertVisible('textarea[name="preference"]', 'Preferences box is now visible');

		}).then(function() {
        	
        	casper.fill('form[id="form-preferences-and-instructions"]', {
        		'instruction' : 'Lorem Ipsum',
    			'preference' : 'Neque porro quisquam',
    		});
        	
        }).thenClick('input[type="submit"][name="save"]', function() {
        	
        	test.info('Clicked [Save and continue] button to save instructions and preferences');
        	
        	test.assertHttpStatus(200, 'Page returns a 200 when LPA type form is submitted');
        	
        	test.info('Current URL: ' + this.getCurrentUrl());

			// check after form submission, page is landing on created page.
			test.assertEquals(basePath + applicantpath, this.getCurrentUrl(), 'Now page is on expected URL: ' + this.getCurrentUrl());

			// check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
			test.assertExists('.accordion li.complete a[href="'+instructionPath+'"]', 'Found an accordion bar link as expected');

		}).thenClick('.accordion li.complete a[href="'+instructionPath+'"]', function then() {
        	
        	test.assertHttpStatus(200, 'Page returns a 200 when LPA type form is submitted');
        	
        	test.info('Current URL: ' + this.getCurrentUrl());
        	
        	test.assertUrlMatch(new RegExp('^' + basePath + instructionPath + '$'), 'Page is on the expected URL.');
        	
    		test.assertSelectorHasText('textarea[name="instruction"]', 'Lorem Ipsum', 'Instructon was saved correctly');
    		test.assertSelectorHasText('textarea[name="preference"]', 'Neque porro quisquam', 'Preference was saved correctly');
    		
    	});

        casper.run(function () { test.done(); });

    } // test

});

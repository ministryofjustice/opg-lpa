
casper.test.begin("Checking user can add replacement attorney", {

    setUp: function(test) {
    	replacementAttorneyPath = paths.replacement_attorney.replace('\\d+', lpaId);
    	whenReplacementAttorneyStepInPath = paths.when_replacement_attorney_step_in.replace('\\d+', lpaId);
    	certificateProviderPath = paths.certificate_provider.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete replacementAttorneyPath, whenReplacementAttorneyStepInPath, certificateProviderPath;
    },

    test: function(test) {

        casper.start(basePath + replacementAttorneyPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + replacementAttorneyPath + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#replacement-attorney-section', 'Accordion header is found on the page');

    		// check form has correct elements
        	test.assertExists('a[href="'+replacementAttorneyPath+'/add"]', 'Found "Add replacement attorney" button');

        	test.assertVisible('input[type="submit"][name="save"]', '"Save and continue" button is visible as expected');

        }).thenClick('form#blankmainflowform input[type="submit"][name="save"]', function() {

        	test.info('Clicked [Save and continue] without adding replacement attorneys');

        	test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

        	test.assertUrlMatch(new RegExp('^' + basePath + certificateProviderPath + '$'), 'Page is on the expected URL: '+ basePath + certificateProviderPath);

			test.assertExists('.accordion li.complete a[href="'+replacementAttorneyPath+'"]', 'Found an accordion bar link as expected');

    	});

        casper.run(function () { test.done(); });

    } // test

});

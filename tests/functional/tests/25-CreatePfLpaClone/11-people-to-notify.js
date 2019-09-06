
casper.test.begin("Checking user can add people to notify", {

    setUp: function(test) {
   		peopleToNotifyPath = paths.people_to_notify.replace('\\d+', lpaId);
   		instructionPath = paths.instructions.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete peopleToNotifyPath, instructionPath;
    },

    test: function(test) {

        casper.start(basePath + peopleToNotifyPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + peopleToNotifyPath + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#people-to-notify-section', 'Accordion header is found on the page');

    		// check form has correct elements
        	test.assertExists('a[href="'+peopleToNotifyPath+'/add"]', 'Found "Add a person to notify" button');

        	test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

    	}).thenClick('form#blankmainflowform input[type="submit"][name="save"]', function() {

        	test.info("Clicked [Save and continue] button to go to instructions page");

        	test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

        	test.info('Current URL: ' + this.getCurrentUrl());

        	test.assertUrlMatch(new RegExp('^' + basePath + instructionPath + '$'), 'Page is on the expected URL: ' + this.getCurrentUrl());

			// check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
			test.assertExists('.accordion li.complete a[href="'+peopleToNotifyPath+'"]', 'Found an accordion bar link as expected');

    	});

        casper.run(function () { test.done(); });

    } // test

});

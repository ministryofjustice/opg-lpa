
casper.test.begin("Checking user can select an LPA type and create an LPA of the chosen type", {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.lpa_type_new).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.lpa_type_new + '$'), 'Page is on the expected URL.');

			// check accordion bar which shows the heading of current page is displayed.
			test.assertExists('.accordion li#lpa-type-section', 'Accordion header is found on the page');

    		// check form has correct elements
        	test.assertExists('input[type="radio"][name="type"][id="type-property-and-financial"][value="property-and-financial"]', 'Found radio option "Property and financial affairs"');
        	test.assertExists('input[type="radio"][name="type"][id="type-health-and-welfare"][value="health-and-welfare"]', 'Found radio option "Health and welfare"');
        	test.assertExists('input[type="submit"][name="save"][value="Save and continue"]', 'Found submit button on page');

        }).thenClick('input[type="submit"][name="save"]', function() {

        	test.info("Clicked [Save and continue] button to submit the form without user input values");

        	// check error handling and response
			test.assertExists('div.error-summary h1#error-heading', 'Error messages are displayed as expected');
			test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');

        }).thenClick('input[type="radio"][name="type"][value="health-and-welfare"]', function() {

        	test.info("Selected LPA type of Health and Welfare");

        }).thenClick('input[type=submit]', function() {

    		test.info("Clicked [Save and continue] button to create a new LPA");

        	test.assertHttpStatus(200, 'Page returns a 200 when LPA type form is submitted');

        	test.info('Current URL: ' + this.getCurrentUrl());

			//---

			// We should end up on the Donor.
			test.assertUrlMatch(new RegExp('^' + basePath + paths.donor + '$'), "We're now correctly on the Donor page.");

            // check lpa type is correctly displayed in an accordion bar
            test.assertExists('.accordion li.complete', 'Found an accordion bar link as expected');

            // We can now extract the ID from the URL - this will be used in later tests
            lpaId  = this.getCurrentUrl().match(/\/(\d+)\//)[1];
        });

        casper.run(function () { test.done(); });

    } // test

});

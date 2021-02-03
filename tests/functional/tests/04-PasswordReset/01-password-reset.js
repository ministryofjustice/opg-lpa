
casper.test.begin("Check password reset process", {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.logout).then(function () {

            test.info('Current URL (after logout): ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + postLogoutUrl + '$'), "We're now on the post logout page.");

    	}).thenOpen(basePath + paths.passwordReset, function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.passwordReset + '$'), 'Page is on the expected URL.');

            test.assertHttpStatus(200, 'Page returns a 200');

        	test.assertExists('title', 'Page header has a title');

        	test.assertExists('h1', 'Page has a H1 header');

        	test.assertSelectorDoesntHaveText('h1', 'Page not found (404 error)', 'H1 header is not "Page not found"');

        	test.assertExists('input[type="email"][name="email"][id="email"]', 'Found email address input box on page');

        	test.assertExists('input[type="email"][name="email_confirm"][id="email_confirm"]', 'Found email confirmation input box on page');

        	test.assertExists('input[type="submit"]', 'Found submit button on page');

        }).thenClick('input[type=submit]', function() {

            test.info('Clicked [Email me the link] button to submit the form without user input values');

    	}).waitForSelector('.error-summary', function() {

		    test.assertTextExists('There is a problem');

    	}).waitForSelector('input[type="submit"]',  function () {

        	// Using email from bootstrap
        	var formVars = {
        			"email": email,
                	"email_confirm": email,
                };

        	casper.fill('#confirm-email', formVars, false);

        }).thenClick('#form-submit', function() {

             casper.capture('out.png');

             test.assertHttpStatus(200, 'Page returns a 200 when feedback form is submitted');

        }).waitForText('Thank you', function() {

            test.info('Page displays confirmation of email sent');

        	test.info('Current URL: ' + this.getCurrentUrl());

        	test.assertEquals(basePath + paths.passwordReset, this.getCurrentUrl(), 'Request sent page url is correct');

        });

        casper.run(function () { test.done(); });

    } // test

});

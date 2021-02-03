
casper.test.begin("Check feedback page accessible", {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.feedback).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.feedback), 'Page is on the expected URL.');

            test.assertHttpStatus(200, 'Page returns a 200');

        	test.assertExists('title', 'Page header has a title');

        	test.assertExists('h1', 'Page has a H1 header');

        	test.assertSelectorDoesntHaveText('h1', 'Page not found (404 error)', 'H1 header is not "Page not found"');

        	test.assertExists('input[type="radio"][name="rating"][value="very-satisfied"]', 'Found radio option "Very satisfied" on page');

        	test.assertExists('input[type="radio"][name="rating"][value="satisfied"]', 'Found radio option "Satisfied" on page');

        	test.assertExists('input[type="radio"][name="rating"][value="neither-satisfied-or-dissatisfied"]', 'Found radio option "Neither satisfied or dissatisfied" on page');

        	test.assertExists('input[type="radio"][name="rating"][value="dissatisfied"]', 'Found radio option "Dissatisfied" on page');

        	test.assertExists('input[type="radio"][name="rating"][value="very-dissatisfied"]', 'Found radio option "Very dissatisfied" on page');

        	test.assertExists('textarea[name="details"][id="details"]', 'Found feedback text box on page');

        	test.assertExists('input[type="email"][name="email"][id="email"]', 'Found email address input box on page');

        	test.assertExists('input[type="submit"][name="send"][class="button"]', 'Found submit button on page');

        }).thenClick('input[type=submit]', function() {

        	test.assertTextExists('There is a problem');

        }).then(function() {

        	this.click('input[type="radio"][name="rating"][value="satisfied"]');

        	test.assertEquals(this.getFormValues('form').rating, 'satisfied', '"Satisfied" radio button is checked as expected');

        }).thenClick('input[type=submit]', function() {

        	test.assertTextExists('There is a problem');

        }).then(function() {
        	// click a radio button
        	this.click('input[type="radio"][name="rating"][value="neither-satisfied-or-dissatisfied"]');

        	test.assertEquals(this.getFormValues('form').rating, 'neither-satisfied-or-dissatisfied', '"Neither satisfied or dissatisfied" radio button is checked as expected');

        	var email = 'casperjs@opg-lpa-test.net',
        		feedback = 'CasperJs feedback form test';

        	this.fill('form#send-feedback', {
        		'email': email,
        		'details': feedback
			});

        	test.assertEquals(this.getFormValues('form').email, email, 'Email field is populated as expected');

        	test.assertEquals(this.getFormValues('form').details, feedback, 'feedback textarea field is populated as expected');

        }).thenClick('input[type=submit]', function() {

        	test.assertHttpStatus(200, 'Page returns a 200 when feedback form is submitted');

        	test.info('Current URL: ' + this.getCurrentUrl());

        	test.assertEquals(basePath + paths.feedbackThanks, this.getCurrentUrl(), 'Feedback sent page url is correct');

			test.assertExists('.text a[href="/home"]', 'Feedback sent page has a returning url link');

        });

        casper.run(function () { test.done(); });

    } // test

});

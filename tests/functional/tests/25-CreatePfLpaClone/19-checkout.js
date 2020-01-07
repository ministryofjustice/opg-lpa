var fs = require('fs');
var util = require('utils');

casper.test.begin("Checking Checkout page", {

	setUp: function(test) {
		checkoutPath = paths.checkout.replace('\\d+', lpaId);
	},

	tearDown: function(test) {
		delete checkoutPath;
	},

	test: function(test) {

		casper.start(basePath + checkoutPath).then(function () {

			test.info('Current URL: ' + this.getCurrentUrl());

			test.assertUrlMatch(new RegExp('^' + basePath + checkoutPath + '$'), 'Page is on the expected URL.');

		}).then(function() {

			// Test all .group-single rows
			var row = 1;
			test.info('Type');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Type', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Property and finance', 'Content found' );

			//---

			row++;
			test.info('Donor heading');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Donor', 'Title found');

			row++;
			test.info('Donor name');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Name', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Mrs Nancy Garrison', 'Content found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.donor.replace('\\d+', lpaId) +'"]', 'Link found');

			row++;
			test.info('Donor DOB');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Date of birth', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', '22 October 1988', 'Content found' );

			row++;
			test.info('Donor Email');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Email address', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'opglpademo+NancyGarrison@gmail.com', 'Content found' );

			row++;
			test.info('Donor Address');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','Bank End Farm House', 'StreetAddress found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Undercliff Drive', 'AddressLocality found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Ventnor, Isle of Wight', 'AddressRegion found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'PO38 1UL', 'PostalCode found' );

			row++;
			test.info('Can sign?');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'The donor can physically sign or make a mark on the LPA', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Yes', 'Content found' );

			//---

			row++;
			test.info('LPA can be used');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'When LPA starts', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "Only if the donor does not have mental capacity", 'Content found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.when_lpa_starts.replace('\\d+', lpaId) +'"]', 'Link found');

			//---

			row++;
			test.info('Attorney Heading');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Attorney', 'Title found');

			row++;
			test.info('Attorney name');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Name', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Standard Trust', 'Content found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.primary_attorney.replace('\\d+', lpaId) +'"]', 'Link found');

			row++;
			test.info('Attorney Company Number');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Company number', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', '678437685', 'Content found' );

			row++;
			test.info('Attorney Email');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Email address', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'opglpademo+trustcorp@gmail.com', 'Content found' );

			row++;
			test.info('Attorney Address');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','1 Laburnum Place', 'StreetAddress found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Sketty', 'AddressLocality found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Swansea, Abertawe', 'AddressRegion found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'SA2 8HT', 'PostalCode found' );

			//---
      row++;
      test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'No replacement attorneys', 'RAs found' );

			row++;
			test.info('Certificate provider Heading');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Certificate provider', 'Title found');

			row++;
			test.info('Certificate provider name');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Name', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Mr Reece Richards', 'Content found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.certificate_provider.replace('\\d+', lpaId) +'"]', 'Link found');

			row++;
			test.info('Certificate provider Address');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','11 Brookside', 'StreetAddress found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Cholsey', 'AddressLocality found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Wallingford, Oxfordshire', 'AddressRegion found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'OX10 9NN', 'PostalCode found' );

			//---

			row++;
			row++;
			test.info('Preferences');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Preferences', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "Neque porro quisquam", 'Content found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.instructions.replace('\\d+', lpaId) +'"]', 'Link found');

			//---

            row++;
            test.info('Instructions');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Instructions', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "Lorem Ipsum", 'Content found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.instructions.replace('\\d+', lpaId) +'"]', 'Link found');

            //---

            row++;
			test.info('Who is registering the LPA');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Who is registering the LPA', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "Standard Trust", 'Content found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.applicant.replace('\\d+', lpaId) +'"]', 'Link found');

			//---

			row++;
			test.info('Correspondent heading');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Correspondent', 'Title found');

			row++;
			test.info('Correspondent name');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Company name', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Standard Trust', 'Content found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.correspondent.replace('\\d+', lpaId) +'"]', 'Link found');

			row++;
			test.info('Correspondent Email');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Email address', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'opglpademo+trustcorp@gmail.com', 'Content found' );

			row++;
			test.info('Correspondent Address');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','1 Laburnum Place', 'StreetAddress found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Sketty', 'AddressLocality found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Swansea, Abertawe', 'AddressRegion found' );
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'SA2 8HT', 'PostalCode found' );

			//---

			row++;
			test.info('Repeat application');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Repeat application', 'Title found');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "This is not a repeat application", 'Content found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.repeat_application.replace('\\d+', lpaId) +'"]', 'Link found');

			//---

			row++;
			test.info('Application fee');
			test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Application fee', 'Title found');
			test.assertTextExists("Application fee: £0 as the donor is claiming an eligible benefit", 'Fee found' );
			test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.fee_reduction.replace('\\d+', lpaId) +'"]', 'Link found');

		}).then(function() {

			test.info("Checking fee");

			test.assertSelectorHasText('.appstatus', '£0', 'Fee is £0 as expected');

		}).then(function() {

			test.info("Checking for continue button");

			test.assertExists('a[href="'+ paths.checkout_confirm.replace('\\d+', lpaId) +'"]', 'Confirm and finish link found');

		}).thenClick('a[href="'+ paths.checkout_confirm.replace('\\d+', lpaId) +'"]', function() {

			test.info('Current URL: ' + this.getCurrentUrl());

			test.assertUrlMatch(new RegExp('^' + basePath + paths.complete.replace('\\d+', lpaId) + '$'), 'Page is on the expected URL.');

		});

		casper.run(function () { test.done(); });

	} // test

});

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
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'No', 'Content found' );

            //---

            row++;
            test.info('LPA can be used');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'When LPA starts', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "As soon as it's registered (and with the donor's consent)", 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.when_lpa_starts.replace('\\d+', lpaId) +'"]', 'Link found');

            //---

            row++;
            test.info('1st Attorney Heading');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', '1st attorney', 'Title found');

            row++;
            test.info('1st Attorney name');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Name', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Mrs Amy Wheeler', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.primary_attorney.replace('\\d+', lpaId) +'"]', 'Link found');

            row++;
            test.info('1st Attorney DOB');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Date of birth', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', '22 October 1988', 'Content found' );

            row++;
            test.info('1st Attorney Email');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Email address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'opglpademo+AmyWheeler@gmail.com', 'Content found' );

            row++;
            test.info('1st Attorney Address');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','Brickhill Cottage', 'StreetAddress found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Birch Cross', 'AddressLocality found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Marchington, Uttoxeter, Staffordshire', 'AddressRegion found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'ST14 8NX', 'PostalCode found' );

            //---

            row++;
            test.info('2nd Attorney Heading');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', '2nd attorney', 'Title found');

            row++;
            test.info('2nd Attorney name');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Name', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Standard Trust', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.primary_attorney.replace('\\d+', lpaId) +'"]', 'Link found');

            row++;
            test.info('2nd Attorney Company Number');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Company number', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', '678437685', 'Content found' );

            row++;
            test.info('2nd Attorney Email');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Email address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'opglpademo+trustcorp@gmail.com', 'Content found' );

            row++;
            test.info('2nd Attorney Address');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','1 Laburnum Place', 'StreetAddress found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Sketty', 'AddressLocality found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Swansea, Abertawe', 'AddressRegion found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'SA2 8HT', 'PostalCode found' );

            //---

            row++;
            test.info('Attorney decisions Title');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Attorney decisions', 'Title found');

            row++;
            test.info('Attorney decisions Details');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'How decisions are made', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'The attorneys will act jointly and severally', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.how_primary_attorneys_make_decision.replace('\\d+', lpaId) +'"]', 'Link found');

            //---

            row++;
            test.info('1st Replacement Attorney Heading');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', '1st replacement attorney', 'Title found');

            row++;
            test.info('1st Replacement Attorney name');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Name', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Ms Isobel Ward', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.replacement_attorney.replace('\\d+', lpaId) +'"]', 'Link found');

            row++;
            test.info('1st Replacement Attorney DOB');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Date of birth', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', '1 February 1937', 'Content found' );

            row++;
            test.info('1st Replacement Attorney Address');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','2 Westview', 'StreetAddress found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Staplehay', 'AddressLocality found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Trull, Taunton, Somerset', 'AddressRegion found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'TA3 7HF', 'PostalCode found' );

            //---

            row++;
            test.info('2nd Replacement Attorney Heading');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', '2nd replacement attorney', 'Title found');

            row++;
            test.info('2nd Replacement Attorney name');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Name', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Mr Ewan Adams', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.replacement_attorney.replace('\\d+', lpaId) +'"]', 'Link found');

            row++;
            test.info('2nd Replacement Attorney DOB');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Date of birth', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', '12 March 1972', 'Content found' );

            row++;
            test.info('2nd Replacement Attorney Address');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','2 Westview', 'StreetAddress found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Staplehay', 'AddressLocality found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Trull, Taunton, Somerset', 'AddressRegion found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'TA3 7HF', 'PostalCode found' );

            //---

            row++;
            test.info('Replacement attorney decisions Title');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Replacement attorney decisions', 'Title found');

            row++;
            test.info('Replacement attorney decisions - When');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'When they step in', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'The replacement attorneys will only step in when none of the original attorneys can act', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.when_replacement_attorney_step_in.replace('\\d+', lpaId) +'"]', 'Link found');

            row++;
            test.info('Replacement attorney decisions - How');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'How decisions are made', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'The replacement attorneys will act jointly and severally', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.how_replacement_attorneys_make_decision.replace('\\d+', lpaId) +'"]', 'Link found');

            //---

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
            test.info('Person to Notify Heading');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Person to notify', 'Title found');

            row++;
            test.info('Person to Notify name');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Name', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Sir Anthony Webb', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.people_to_notify.replace('\\d+', lpaId) +'"]', 'Link found');

            row++;
            test.info('Person to Notify Address');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','Brickhill Cottage', 'StreetAddress found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Birch Cross', 'AddressLocality found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Marchington, Uttoxeter, Staffordshire', 'AddressRegion found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'BS18 6PL', 'PostalCode found' );

            //---

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
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "Donor", 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.applicant.replace('\\d+', lpaId) +'"]', 'Link found');

            //---

            row++;
            test.info('Correspondent heading');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Correspondent', 'Title found');

            row++;
            test.info('Correspondent name');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Name', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Mrs Nancy Garrison', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.correspondent.replace('\\d+', lpaId) +'"]', 'Link found');

            row++;
            test.info('Correspondent Email');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Email address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'opglpademo+NancyGarrison@gmail.com', 'Content found' );

            row++;
            test.info('Correspondent Address');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','Bank End Farm House', 'StreetAddress found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Undercliff Drive', 'AddressLocality found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Ventnor, Isle of Wight', 'AddressRegion found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'PO38 1UL', 'PostalCode found' );

            //---

            row++;
            test.info('Repeat application');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Repeat application', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "This is a repeat application with case number 12345678", 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.repeat_application.replace('\\d+', lpaId) +'"]', 'Link found');

            //---

            row++;
            test.info('Application fee');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Application fee', 'Title found');
            test.assertTextExists("as the donor has an income of less than", 'Fee found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.fee_reduction.replace('\\d+', lpaId) +'"]', 'Link found');

        }).then(function() {

            test.info("Checking fee");

            test.assertSelectorHasText('.appstatus', '£20.50', 'Fee is £20.50 as expected');

        }).then(function() {

            test.info("Checking payment fields");
            test.assertExists('input[type="submit"][name="submit"]', 'GOV Pay submit button found');
            test.assertExists('a[href="'+ paths.checkout_cheque.replace('\\d+', lpaId) +'"]', 'Pay by cheque link found');

        }).thenClick('input[type="submit"][name="submit"]', function() {

            test.info("Clicked, 'Pay now with GOV Pay'");

        }).waitForText('Enter card details', function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch( govPayUrlRegExp, 'On Gov Pay payment page as expected');

            test.assertTextExists('£20.50', "Correct fee shown");
            test.assertTextExists('Property and financial affairs LPA for Mrs Nancy Garrison', "Correct description found");

            test.assertExists('input[name="email"]', 'Email field found');
            test.assertExists('#submit-card-details', 'Continue button found');
            test.assertExists('input[name="cancel"]', 'Cancel button found');

        // Start testing the cancel card payment flow

        }).thenClick('input[name="cancel"][type="submit"]', function() {

            test.info('Clicked on on [cancel payment] on GOV Pay page');

        }).waitForText('Your payment has been cancelled', function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch( govPayCancelUrlRegExp, 'On the expected cancel page');

            test.assertExists('a[id="return-url"]', "'Go back to the service' link found");

        }).thenClick('a[id="return-url"]', function() {

            test.info("Clicked 'Go back to the service'");

        }).waitForText('Online payment cancelled', function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.checkout_pay_return.replace('\\d+', lpaId) + '$'), 'Page is on the expected URL.');

            test.assertTextExists('Amount due: £20.50', "Correct amount due shown");
            test.assertExists('input[type="submit"][name="submit"]', 'Retry GOV Pay submit button found');

            test.assertExists('a[href="' + paths.checkout_cheque.replace('\\d+', lpaId) + '"]', 'Pay by cheque link instead found');

        }).thenClick('input[name="submit"][type="submit"]', function() {

            test.info("Clicked 'Retry online payment'");

        // End testing the cancel card payment flow
        // Start testing the rejected/failed card payment flow

        }).waitForText('Enter card details', function() {

            // Skipping checking rest of the elements as we've done this in an earlier step

            test.info('Current URL: ' + this.getCurrentUrl());
            test.assertUrlMatch( govPayUrlRegExp, 'On Gov Pay payment page as expected');

            test.info('Enter details for test card that will be declined');

            var form = {
                'cardNo' : '4000000000000002',
                'expiryMonth': '01',
                'expiryYear': '22',
                'cardholderName': 'MR CARD HOLDERSON',
                'cvc': '123',
                'addressCountry': 'GB',
                'addressLine1': '23 Boundary House',
                'addressLine2': 'Boundary Lane',
                'addressCity': 'Welwyn Garden City',
                'addressPostcode': 'AL7 4EH',
                'email': 'opglpademo+AmyWheeler@gmail.com'
            };

            casper.fill('form#card-details', form);

        }).thenClick('input[name="submitCardDetails"][type="submit"]', function() {


        }).waitForText('Your payment has been declined', function() {

            test.info('Current URL: ' + this.getCurrentUrl());
            test.assertUrlMatch( govPayUrlRegExp, 'On the expected declined page');

        }).thenClick('a[id="return-url"]', function() {

            test.info("Clicked 'Go back and try payment again'");

        }).waitForText('Online payment failed', function() {

            test.info('Current URL: ' + this.getCurrentUrl());
            test.assertUrlMatch(new RegExp('^' + basePath + paths.checkout_pay_return.replace('\\d+', lpaId) + '$'), 'On the expected failed page');

        }).thenClick('input[name="submit"][type="submit"]', function() {

            test.info("Clicked 'Retry online payment'");

        // End testing the rejected/failed card payment flow
        // Start testing the successful card payment flow

        }).waitForText('Enter card details', function() {

            // Skipping checking rest of the elements as we've done this in an earlier step
            test.info('Current URL: ' + this.getCurrentUrl());
            test.assertUrlMatch( govPayUrlRegExp, 'On Gov Pay payment page as expected');

            test.info('Enter details for test card that will be accepted');
            casper.fill('form#card-details', {
                'cardNo' : '4444333322221111',
                'expiryMonth': '01',
                'expiryYear': '22',
                'cardholderName': 'MR CARD HOLDERSON',
                'cvc': '123',
                'addressCountry': 'GB',
                'addressLine1': '23 Boundary House',
                'addressLine2': 'Boundary Lane',
                'addressCity': 'Welwyn Garden City',
                'addressPostcode': 'AL7 4EH',
                'email': 'opglpademo+AmyWheeler@gmail.com'
            });

        }).thenClick('input[name="submitCardDetails"][type="submit"]', function() {

            test.info("Clicked 'Submit'");

        }).waitForText('Confirm your payment', function() {

            test.info('Current URL: ' + this.getCurrentUrl());
            test.assertUrlMatch(govPayConfirmUrlRegExp, 'On Gov Pay payment page as expected');

        }).thenClick('button[id="confirm"]', function() {

            test.info("Clicked 'Confirm Payment'");

        }).waitForText('Last steps', function() {

            test.info('Current URL: ' + this.getCurrentUrl());
            test.assertUrlMatch(new RegExp('^' + basePath + paths.complete.replace('\\d+', lpaId) + '$'), 'Page is on the expected URL.');

            test.info("Payment completed and back on LPA site on the Complete page");
        });

        // End testing the successful card payment flow

        casper.run(function () { test.done(); });

    } // test

});

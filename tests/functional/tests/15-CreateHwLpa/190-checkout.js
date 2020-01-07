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
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Health and welfare', 'Content found' );

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
            test.info('Life-sustaining treatment');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Life-sustaining treatment', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "The attorneys can make decisions", 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.life_sustaining.replace('\\d+', lpaId) +'"]', 'Link found');

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
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'Mr David Wheeler', 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.primary_attorney.replace('\\d+', lpaId) +'"]', 'Link found');

            row++;
            test.info('2nd Attorney DOB');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Date of birth', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', '12 March 1972', 'Content found' );

            row++;
            test.info('2nd Attorney Email');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Email address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'opglpademo+DavidWheeler@gmail.com', 'Content found' );

            row++;
            test.info('2nd Attorney Address');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Address', 'Title found');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="streetAddress"]','Brickhill Cottage', 'StreetAddress found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressLocality"]', 'Birch Cross', 'AddressLocality found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="addressRegion"]', 'Marchington, Uttoxeter, Staffordshire', 'AddressRegion found' );
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer div[itemprop="postalCode"]', 'ST14 8NX', 'PostalCode found' );

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
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "Mr David Wheeler", 'Content found' );
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
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', "This is not a repeat application", 'Content found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.repeat_application.replace('\\d+', lpaId) +'"]', 'Link found');

            //---

            row++;
            test.info('Application fee');
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-question', 'Application fee', 'Title found');
            test.assertTextExists("as you are not claiming a reduction", 'Fee found' );
            test.assertExists('.govuk-check-your-answers div:nth-of-type('+row+') .cya-change a[href="'+ paths.fee_reduction.replace('\\d+', lpaId) +'"]', 'Link found');

        }).then(function() {

            test.info("Checking fee");

            test.assertSelectorHasText('.appstatus', '£82', 'Fee is £82 as expected');

        }).then(function() {

            test.info("Checking payment fields");

            test.assertExists('input[type="submit"][name="submit"]', 'GOV Pay submit button found');

            test.assertExists('a[href="'+ paths.checkout_cheque.replace('\\d+', lpaId) +'"]', 'Pay by cheque link found');

        }).thenClick('input[type="submit"][name="submit"]', function() {

            test.info('Click to pay now with GOV Pay')

        }).wait(5000).then(function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch( govPayUrlRegExp, 'On Gov Pay payment page as expected');

            test.assertTextExists('£82.00', "Correct fee shown");
            test.assertTextExists('Health and welfare LPA for Mrs Nancy Garrison', "Correct description found");


            test.assertExists('input[name="email"]', 'Email field found');
            test.assertExists('#submit-card-details', 'Continue button found');
            test.assertExists('input[name="cancel"]', 'Cancel button found');

        }).thenClick('input[name="cancel"][type="submit"]', function() {

            test.info('Click on [cancel payment] on GOV Pay page');

        }).wait(5000).then(function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch( govPayCancelUrlRegExp, 'On the expected cancel page');

            test.assertExists('a[id="return-url"]', "'Go back to the service' link found");

            test.info("Clicking 'Go back to the service'");

        }).thenClick('a[id="return-url"]', function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.checkout_pay_return.replace('\\d+', lpaId) + '$'), 'Page is on the expected URL.');

            test.assertExists('input[type="submit"][name="submit"]', 'Retry GOV Pay submit button found');

            test.assertExists('a[href="'+ paths.checkout_cheque.replace('\\d+', lpaId) +'"]', 'Pay by cheque link instead found');

            //--------------------------------------------------
            // Stop checking GOV Pay here.
            //--------------------------------------------------

        }).thenOpen(basePath + checkoutPath).then(function () {

            // Return to normal checkout page...
            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertExists('a[href="'+ paths.checkout_cheque.replace('\\d+', lpaId) +'"]', 'Recheck: Pay by cheque link found');

        }).thenClick('a[href="'+ paths.checkout_cheque.replace('\\d+', lpaId) +'"]', function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.complete.replace('\\d+', lpaId) + '$'), 'Page is on the expected URL.');

        });

        casper.run(function () { test.done(); });

    } // test

});

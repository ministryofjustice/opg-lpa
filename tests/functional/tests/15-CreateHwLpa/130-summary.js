var fs = require('fs');
var util = require('utils');

casper.test.begin("Checking Summary page", {

    setUp: function(test) {
   		applicantPath = paths.applicant.replace('\\d+', lpaId);
        summaryPath = paths.summaryViaApplicant.replace('\\d+', lpaId);
        lp1DownloadPath = paths.download.replace('\\d+', lpaId) + '/lp1/draft';
        lp1DownloadPdfPath = paths.download.replace('\\d+', lpaId) + '/lp1/Draft-Lasting-Power-of-Attorney-LP1H.pdf';
    },

    tearDown: function(test) {
    	delete applicantPath, summaryPath, lp1DownloadPath;
    },

    test: function(test) {

        casper.start(basePath + applicantPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + applicantPath + '$'), 'Page is on the expected URL.');

            // Check the summary link has appeared in the accordion...
            test.assertExists('.accordion li.complete a[href^="'+summaryPath+'"]', 'Found an accordion bar link as expected');

        }).thenClick('.accordion li.complete a[href^="'+summaryPath+'"]', function() {

            // click accordion bar to go to when replacement attorney step in page
            test.info('Clicked to view Summary page');

            // check it is on lpa/when-replacement-attorney-step-in page
            test.assertUrlMatch(new RegExp('^' + basePath + summaryPath + '\\?return-route=lpa/applicant$'), 'Page is on the expected URL: '+summaryPath);

            casper.capture('summary.png');

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

        }).then(function() {

            test.info("Checking PDF download link");

            test.assertExists('a[href="' + lp1DownloadPath + '"]', 'Found download link');

            casper.checkPdfDownload(test, basePath + lp1DownloadPdfPath, 0);

        }).then(function() {

            test.info("Return to the LPA");

            test.assertExists('a.button[href="'+applicantPath+'"]', 'Found button for returning to LPA');

        }).thenClick('a.button[href="'+applicantPath+'"]', function() {

            test.assertUrlMatch(new RegExp('^' + basePath + applicantPath + '$'), 'We are back on the LPA flow');

        });

        casper.run(function () { test.done(); });

    } // test

});

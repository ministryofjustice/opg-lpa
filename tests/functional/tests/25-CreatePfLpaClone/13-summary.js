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
        delete applicantPath, summaryPath, lp1DownloadPath, lp1DownloadPdfPath;
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
            row++;
            test.assertSelectorHasText('.govuk-check-your-answers div:nth-of-type('+row+') .cya-answer', 'No replacement attorneys', 'RAs found' );

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


        }).then(function() {

            test.info("Checking PDF download link");

            test.assertExists('a[href="'+lp1DownloadPath+'"]', 'Found download link');

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

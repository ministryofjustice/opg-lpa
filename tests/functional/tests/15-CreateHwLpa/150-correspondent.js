
casper.test.begin("Checking user can access correspondent page", {

    setUp: function(test) {
        correspondentPath = paths.correspondent.replace('\\d+', lpaId);
        whoAreYouPath = paths.who_are_you.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete correspondentPath, whoAreYouPath;
    },

    test: function(test) {

        casper.start(basePath + correspondentPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + correspondentPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#correspondent-section', 'Accordion header is found on the page');

            test.assertSelectorHasText('div.person h3', 'Mr David Wheeler', "Found the correspondent is by default the donor as expected");

            test.assertSelectorHasText('a[href="'+correspondentPath+'/edit"]', 'Change correspondent', "Found 'Change correspondent' link as expected");

            test.assertExists('input[type="radio"][name="contactInWelsh"]', "Found 'Welsh' radio buttons");
            test.assertExists('input[type="checkbox"][name="correspondence[contactByPost]"]', "Found 'Post' checkbox");
            test.assertExists('input[type="checkbox"][name="correspondence[contactByPhone]"]', "Found 'Phone' checkbox");
            test.assertExists('input[type="checkbox"][name="correspondence[contactByEmail]"][checked="checked"]', "Found 'Email' checkbox and is checked");
            test.assertSelectorHasText('label[for="contactByEmail"]', 'Email (opglpademo+DavidWheeler@gmail.com)', "Found donor's email address as expected");

            test.assertExists('input[type="tel"][name="correspondence[phone-number]"]', "Found Phone Number field");
            test.assertNotVisible('input[type="tel"][name="correspondence[phone-number]"]', "Found Phone Number is not visible");

            test.assertExists('input[type="text"][name="correspondence[email-address]"]', "Found Email Address field");
            test.assertNotVisible('input[type="text"][name="correspondence[email-address]"]', "Found Email Address is not visible");

            test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

        }).thenClick('a.js-form-popup[href="'+correspondentPath+'/edit"]', function() {

            test.info('Open correspondent form');

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('form#form-reuse-details');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for selecting an actors details to use for a correspondent is loaded and displayed as expected");
            test.assertExists('form#form-reuse-details', "Found reuse details form as expected");
            test.assertElementCount('form#form-reuse-details input[name="reuse-details"]', 8, "Reuse details radio buttons displayed as expected");

        }).then(function() {

            casper.fill('form#form-reuse-details', {
                'reuse-details' : '-1',
            });

        }).thenClick('form#form-reuse-details input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Continue] button to submit the reuse details for None of the Above");

        }).waitFor(function check() {

            // waiting for the form to load
            return this.exists('form#form-correspondent');

        }).then(function() {

            // checking title dropdown
            test.assertExists('form#form-correspondent select option[value="Mr"]', 'Found title element in the lightbox as expected');

            test.assertElementCount('form#form-correspondent select[name="name-title"] option', 8, 'Found title dropdown list has items');

            // checking first names text input box
            test.assertExists('form#form-correspondent input[type="text"][name="name-first"]', 'Found first names text input box in the lightbox as expected');

            // checking last name text input box
            test.assertExists('form#form-correspondent input[type="text"][name="name-last"]', 'Found last name text input box in the lightbox as expected');

            // checking email address text input box
            test.assertExists('form#form-correspondent input[name="company"]', 'Found company namem input box in the lightbox as expected');

            // checking postcode lookup input
            test.assertExists('form#form-correspondent input[type="text"][id="postcode-lookup"]', 'Found postcode lookup input field in the lightbox as expected');

            // checking postcode lookup button
            test.assertExists('a[id="find_uk_address"]', 'Found postcode look button in the lightbox as expected');

            // checking address line 1
            test.assertExists('form#form-correspondent input[type="text"][name="address-address1"]', 'Found address line 1 text input field in the lightbox as expected');

            // checking address line 2
            test.assertExists('form#form-correspondent input[type="text"][name="address-address2"]', 'Found address line 2 text input field in the lightbox as expected');

            // checking address line 3
            test.assertExists('form#form-correspondent input[type="text"][name="address-address3"]', 'Found address line 3 text input field in the lightbox as expected');

            // checking address postcode
            test.assertExists('form#form-correspondent input[type="text"][name="address-postcode"]', 'Found address postcode text input field in the lightbox as expected');

            // checking email address text input box
            test.assertExists('form#form-correspondent input[name="email-address"]', 'Found email address input box in the lightbox as expected');

            // checking daytime phone number text input box
            test.assertExists('form#form-correspondent input[name="phone-number"]', 'Found daytime phone number input box in the lightbox as expected');

            // checking Save details button
            test.assertExists('form#form-correspondent input[type="submit"][name="submit"]', "Found 'Save details' button in the lightbox as expected");

            // checking back button
            test.assertExists('form#form-correspondent a.js-form-popup[href="'+correspondentPath+'/edit"]', "Found 'Back' button in the lightbox as expected");

            // checking cancel button
            test.assertExists('form#form-correspondent a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).thenClick('form#form-correspondent a.js-form-popup[href="'+correspondentPath+'/edit"]', function() {

            test.info("Clicked [Back] button to go back to the radio button options");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('form#form-reuse-details');

        }, function then() {

            // check the radio button options have been displayed as required
            test.assertExists('div#popup', "Lightbox for selecting an actors details to use for a correspondent is loaded and displayed as expected");
            test.assertExists('form#form-reuse-details', "Found reuse details form as expected");
            test.assertElementCount('form#form-reuse-details input[name="reuse-details"]', 8, "Reuse details radio buttons displayed as expected");

        }).then(function() {

            casper.fill('form#form-reuse-details', {
                'reuse-details' : '1',
            });

        }).thenClick('form#form-reuse-details input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Continue] button to submit the reuse details for the donor");

        }).wait(10000).then(function() {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + correspondentPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#correspondent-section', 'Accordion header is found on the page');

            test.assertSelectorHasText('div.person h3', 'Mrs Nancy Garrison', "Found the correspondent is by default the donor as expected");

            test.assertSelectorHasText('a[href="'+correspondentPath+'/edit"]', 'Change correspondent', "Found 'Change correspondent' link as expected");

            test.assertExists('input[type="radio"][name="contactInWelsh"]', "Found 'Welsh' radio buttons");
            test.assertExists('input[type="checkbox"][name="correspondence[contactByPost]"]', "Found 'Post' checkbox");
            test.assertExists('input[type="checkbox"][name="correspondence[contactByPhone]"]', "Found 'Phone' checkbox");
            test.assertExists('input[type="checkbox"][name="correspondence[contactByEmail]"][checked="checked"]', "Found 'Email' checkbox and is checked");
            test.assertSelectorHasText('label[for="contactByEmail"]', 'Email (opglpademo+NancyGarrison@gmail.com)', "Found donor's email address as expected");

            test.assertExists('input[type="tel"][name="correspondence[phone-number]"]', "Found Phone Number field");
            test.assertNotVisible('input[type="tel"][name="correspondence[phone-number]"]', "Found Phone Number is not visible");

            test.assertExists('input[type="text"][name="correspondence[email-address]"]', "Found Email Address field");
            test.assertNotVisible('input[type="text"][name="correspondence[email-address]"]', "Found Email Address is not visible");

            test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

        }).thenClick('input[type="radio"][name="contactInWelsh"][value="0"]', function() {

            test.info("Selected English as the correspondent language");

        }).thenClick('form[id="form-correspondence"] input[type="submit"][name="save"]', function() {

            test.info('Clicked [Save and continue] button');

            test.assertHttpStatus(200, 'Page returns a 200 when the correspondent form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            // check after form submission, page is expected landing on who are you page.
            test.assertUrlMatch(new RegExp('^' + basePath + whoAreYouPath + '$'), 'Page is on the expected URL.');

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+correspondentPath+'"]', 'Found an accordion bar link as expected');

        });

        casper.run(function () { test.done(); });

    } // test

});

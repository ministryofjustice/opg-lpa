
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

            test.assertSelectorHasText('div.person h3', 'Mrs Nancy Garrison', "Found the correspondent is by default the donor as expected");

            test.assertSelectorHasText('a[href="'+correspondentPath+'/edit"]', 'Change correspondent', "Found 'Change correspondent' link as expected");

            test.assertExists('input[type="radio"][name="contactInWelsh"]', "Found 'Welsh' radio buttons");
            test.assertExists('input[type="checkbox"][name="correspondence[contactByPost]"]', "Found 'Post' checkbox");
            test.assertExists('input[type="checkbox"][name="correspondence[contactByPhone]"]', "Found 'Phone' checkbox");
            test.assertExists('input[type="checkbox"][name="correspondence[contactByEmail]"][checked="checked"]', "Found 'Email' checkbox and is checked");
            test.assertSelectorHasText('label[for="contactByEmail"]', 'Email (opglpademo+NancyGarrison@gmail.com)', "Found donor's email address as expected");

            test.assertExists('input[type="text"][name="correspondence[phone-number]"]', "Found Phone Number field");
            test.assertNotVisible('input[type="text"][name="correspondence[phone-number]"]', "Found Phone Number is not visible");

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

        }).then(function() {

            // populate the person to notify form
            casper.fill('form#form-correspondent', {
                'name-first' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'name-last' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'company' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address1':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address2':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address3':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-postcode':'BS18 6PL'
            });

        }).thenClick('form#form-correspondent input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit invalid correspondent details");

        }).waitForText('There was a problem submitting the form', function(){

            test.assertTextExists( "Enter the correspondent's title" , "Correct validation message shown for blank title");
            test.assertTextExists( "Enter a first name that's less than 54 characters long", "Correct validation message shown for too long first name");
            test.assertTextExists( "Enter a last name that's less than 62 characters long", "Correct validation message shown for too long last name");
            test.assertTextExists( "Change address line 1 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 1");
            test.assertTextExists( "Change address line 2 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 2");
            test.assertTextExists( "Change address line 3 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 3");
            
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

        }).waitForText('Where should we send the registered LPA and any correspondence?').then(function() {

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

            test.assertExists('input[type="text"][name="correspondence[phone-number]"]', "Found Phone Number field");
            test.assertNotVisible('input[type="text"][name="correspondence[phone-number]"]', "Found Phone Number is not visible");

            test.assertExists('input[type="text"][name="correspondence[email-address]"]', "Found Email Address field");
            test.assertNotVisible('input[type="text"][name="correspondence[email-address]"]', "Found Email Address is not visible");

            test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

        }).thenClick('input[type="radio"][name="contactInWelsh"][value="0"]', function() {

            test.info("Selected English as the correspondent language");

        }).thenClick('input[type="checkbox"][name="correspondence[contactByEmail]"][checked="checked"]', function() {

            test.info("Unselected Email to check validation");

        }).thenClick('form[id="form-correspondence"] input[type="submit"][name="save"]', function() {

            test.info('Clicked [Save and continue] button with no contact option selected');

        }).waitForText('There was a problem submitting the form', function(){

            test.assertTextExists( "Select at least one option" , "Correct validation message shown when no contact option chosen");
            
        }).thenClick('input[type="checkbox"][name="correspondence[contactByEmail]"]', function() {

            test.info("Reselected Email to pass validation");

        }).thenClick('form[id="form-correspondence"] input[type="submit"][name="save"]', function() {

            test.info('Clicked [Save and continue] button with valid form');

        }).waitForText('Who was using the LPA service?', function() {

            test.assertHttpStatus(200, 'Page returns a 200 when the "correspondence" form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            // check after form submission, page is expected landing on who are you page.
            test.assertUrlMatch(new RegExp('^' + basePath + whoAreYouPath + '$'), 'Page is on the expected URL.');

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+correspondentPath+'"]', 'Found an accordion bar link as expected');

        });

        casper.run(function () { test.done(); });

    } // test

});

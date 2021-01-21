
casper.test.begin("Checking user can add donor", {

    setUp: function(test) {
        donorPath = paths.donor.replace('\\d+', lpaId);
        whenLpaStartsPath = paths.when_lpa_starts.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete donorPath, whenLpaStartsPath;
    },

    test: function(test) {

        casper.start(basePath + donorPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + donorPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#donor-section', 'Accordion header is found on the page');

            // check form has correct elements
            test.assertExists('a.button[href="'+donorPath+'/add"]', 'Found [Add donor details] button');

            // Check teh save button isn't currently there
            test.assertDoesntExist('a.button[href="'+whenLpaStartsPath+'"]', 'Save button currently is not shown');

        }).thenClick('a.js-form-popup[href="'+donorPath+'/add"]', function() {

            test.info("Clicked [Add donor details] button");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing donor's details is loaded and displayed as expected");
            test.assertExists('form#form-donor', "Found donor form as expected");
            test.assertElementCount('form#form-donor select[name="name-title"] option', 8, "Title dropdown is correctly generated");

        }).then(function() {

            // checking donor details popup window are displayed correctly.

            // checking 'use my details' link
            test.assertExists('.use-details-link-panel form input[type="submit"][value="Use my details"]', 'Found "Use my details" link in the lightbox as expected');

            // checking title dropdown
            test.assertExists('form#form-donor select[name="name-title"]', 'Found title element in the lightbox as expected');

            test.assertElementCount('form#form-donor select[name="name-title"] option', 8, 'Found title dropdown list has items');

            // checking first names text input box
            test.assertExists('form#form-donor input[type="text"][name="name-first"]', 'Found first names text input box in the lightbox as expected');

            // checking last name text input box
            test.assertExists('form#form-donor input[type="text"][name="name-last"]', 'Found last name text input box in the lightbox as expected');

            // checking other names input field
            test.assertExists('form#form-donor input[type="text"][name="otherNames"]', 'Found other names text input box in the lightbox as expected');

            // checking dob day text input box
            test.assertExists('form#form-donor input[type="text"][name="dob-date[day]"]', 'Found DOB day text input box in the lightbox as expected');

            // checking dob month text input box
            test.assertExists('form#form-donor input[type="text"][name="dob-date[month]"]', 'Found DOB month text input box in the lightbox as expected');

            // checking dob year text input box
            test.assertExists('form#form-donor input[type="text"][name="dob-date[year]"]', 'Found DOB year text input box in the lightbox as expected');

            // checking email address text input box
            test.assertExists('form#form-donor input[name="email-address"]', 'Found email address input box in the lightbox as expected');

            // checking postcode lookup input
            test.assertExists('form#form-donor input[type="text"][id="postcode-lookup"]', 'Found postcode lookup input field in the lightbox as expected');

            // checking postcode lookup button
            test.assertExists('a[id="find_uk_address"]', 'Found postcode look button in the lightbox as expected');

            // checking address line 1
            test.assertExists('form#form-donor input[type="text"][name="address-address1"]', 'Found address line 1 text input field in the lightbox as expected');

            // checking address line 2
            test.assertExists('form#form-donor input[type="text"][name="address-address2"]', 'Found address line 2 text input field in the lightbox as expected');

            // checking address line 3
            test.assertExists('form#form-donor input[type="text"][name="address-address3"]', 'Found address line 3 text input field in the lightbox as expected');

            // checking address postcode
            test.assertExists('form#form-donor input[type="text"][name="address-postcode"]', 'Found address postcode text input field in the lightbox as expected');

            // checking person can sign checkbox
            test.assertExists('form#form-donor input[type="checkbox"][name="canSign"]', 'Found donor cannot sign checkbox in the lightbox as expected');

            // checking Save details button
            test.assertExists('form#form-donor input[type="submit"][name="submit"]', "Found 'Save details' button in the lightbox as expected");

            // checking cancel button
            test.assertExists('form#form-donor a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).then(function() {

            test.info('Testing postcode lookup service');

            //checking postcode lookup
            this.fillSelectors('form#form-donor', {
                'input#postcode-lookup':'B1 1TF'
            });

            this.click('a#find_uk_address');

        }).waitFor(function check() {

            // waiting for postcode look service returns data
            return this.evaluate(function() {
                return (document.querySelectorAll('select[id="address-search-result"] option').length == 6);
            });

        }, function then() {

            test.info('Postcode lookup service has returned correct address list');

        }).then(function() { // test validation messages

            // populate the donor form

            this.fill('form#form-donor', {
                'name-first' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'name-last' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'dob-date[day]':'22',
                'dob-date[month]':'10',
                'dob-date[year]':'1988',
                'email-address':'opglpademo+NancyGarrison@gmail.com',
                'address-address1':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address2':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address3':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-postcode':'PO38 1UL'
            });

        }).thenClick('form#form-donor input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit invalid donor's details");

        }).waitForText('There was a problem submitting the form', function(){

            test.assertTextExists( "Enter the donor's title" , "Correct validation message shown for blank title");
            test.assertTextExists( "Enter a first name that's less than 54 characters long", "Correct validation message shown for too long first name");
            test.assertTextExists( "Enter a last name that's less than 62 characters long", "Correct validation message shown for too long last name");
            test.assertTextExists( "Change address line 1 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 1");
            test.assertTextExists( "Change address line 2 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 2");
            test.assertTextExists( "Change address line 3 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 3");
            
        }).then(function() {

            // populate the donor form
            this.click('form#form-donor input[type="checkbox"][name="canSign"]');

            this.fill('form#form-donor', {
                'name-title' : 'Mrs',
                'name-first' : 'Nancy',
                'name-last' : 'Garrison',
                'dob-date[day]':'22',
                'dob-date[month]':'10',
                'dob-date[year]':'1988',
                'email-address':'opglpademo+NancyGarrison@gmail.com',
                'address-address1':'Bank End Farm House',
                'address-address2':'Undercliff Drive',
                'address-address3':'Ventnor, Isle of Wight',
                'address-postcode':'PO38 1UL'
            });

        }).thenClick('form#form-donor input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit donor's details");

        }).waitForSelector('.person', function then () {

            // check donor is displayed on the landing page
            test.assertSelectorHasText('div.person h3', 'Mrs Nancy Garrison', "Donor's name is displayed on the donor's landing page as expected");

            test.assertExists('a.js-form-popup[href="'+donorPath+'/edit"]', 'Found the link for editing donor details as expected');

            // Check no Add button
            test.assertDoesntExist('a.button[href="'+donorPath+'/add"]', 'Not found [Add donor details] button');

            // Check there is a continue button
            test.assertExists('a.button[href="'+whenLpaStartsPath+'"]', 'Save button currently is shown');

        }).thenClick('a.js-form-popup[href="'+donorPath+'/edit"]', function() {

            test.info("Clicked << View/edit details >> link to edit donor's details");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing donor's details is loaded and displayed as expected");
            test.assertExists('form#form-donor', "Found donor form as expected");
            test.assertElementCount('form#form-donor select[name="name-title"] option', 8, "Title dropdown is correctly generated");

            test.assertExists('form#form-donor input[name="name-first"][value="Nancy"]', "First names field value is correct");
            test.assertExists('form#form-donor input[name="name-last"][value="Garrison"]', "Last name field value is correct");
            test.assertExists('form#form-donor input[name="dob-date[day]"][value="22"]', "Dob day field value is correct");
            test.assertExists('form#form-donor input[name="dob-date[month]"][value="10"]', "Dob month field value is correct");
            test.assertExists('form#form-donor input[name="dob-date[year]"][value="1988"]', "Dob year field value is correct");
            test.assertExists('form#form-donor input[name="email-address"][value="opglpademo+NancyGarrison@gmail.com"]', "Email field value is correct");
            test.assertExists('form#form-donor input[name="address-address1"][value="Bank End Farm House"]', "Address 1 field value is correct");
            test.assertExists('form#form-donor input[name="address-address2"][value="Undercliff Drive"]', "Address 2 field value is correct");
            test.assertExists('form#form-donor input[name="address-address3"][value="Ventnor, Isle of Wight"]', "Address 3 field value is correct");
            test.assertExists('form#form-donor input[name="address-postcode"][value="PO38 1UL"]', "Postcode field value is correct");

            test.assertExists('form#form-donor input[type="submit"][name="submit"]', "Found [Save details] button on editing donor form");
            test.assertExists('form#form-donor a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).thenClick('form#form-donor a.js-cancel', function() {

            test.info('Clicked [Cancel] button');

        }).waitForText('Who is the donor for this LPA?').thenClick('a[href="'+whenLpaStartsPath+'"]', function () {

            test.info("Clicked [Save and continue] button to go to the next page: when-lpa-starts");

        }).waitForText('When can the LPA be used?', function() {

            test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + whenLpaStartsPath + '$'), 'Page is on the expected URL: ' + this.getCurrentUrl());

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+donorPath+'"]', 'Found an accordion bar link as expected');

        });

        casper.run(function () { test.done(); });

    } // test

});

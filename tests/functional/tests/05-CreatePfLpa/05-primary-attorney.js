
casper.test.begin("Checking user can add primary attorney", {

    setUp: function(test) {
        primaryAttorneyPath = paths.primary_attorney.replace('\\d+', lpaId);
        replacementAttorneyPath = paths.replacement_attorney.replace('\\d+', lpaId);
        primaryAttorneysDecisionPath = paths.how_primary_attorneys_make_decision.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete primaryAttorneyPath, replacementAttorneyPath, primaryAttorneysDecisionPath;
    },

    test: function(test) {

        casper.start(basePath + primaryAttorneyPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + primaryAttorneyPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#primary-attorney-section', 'Accordion header is found on the page');

            // check form has correct elements
            test.assertExists('a.button[href="'+primaryAttorneyPath+'/add"]', 'Found [Add attorney details] button');

            // Check teh save button isn't currently there
            test.assertDoesntExist('a.button[href="'+replacementAttorneyPath+'"]', 'Save button currently is not shown');

        }).thenClick('a.js-form-popup[href="'+primaryAttorneyPath+'/add"]', function() {

            test.info("Clicked [Add an attorney] button to add the first primary attorney");

        }).wait(1500).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing attorney's details is loaded and displayed as expected");
            test.assertExists('form#form-attorney', "Found attorney form as expected");
            test.assertElementCount('form#form-attorney select[name="name-title"] option', 8, "Title dropdown is correctly generated");

        }).then(function() {

            // checking form fields existance.

            // checking 'use my details' link
            test.assertExists('.use-details-link-panel form input[type="submit"][value="Use my details"]', 'Found "Use my details" link in the lightbox as expected');

            // checking 'use a trust corporation' link
            test.assertExists('.use-details-link-panel a[href="'+primaryAttorneyPath+'/add-trust"]', 'Found << Use a trust corporation >> link in the lightbox as expected');

            // checking title dropdown
            test.assertExists('form#form-attorney select[name="name-title"]', 'Found title element in the lightbox as expected');

            test.assertElementCount('form#form-attorney select[name="name-title"] option', 8, 'Found title dropdown list has items');

            // checking first names text input box
            test.assertExists('form#form-attorney input[type="text"][name="name-first"]', 'Found first names text input box in the lightbox as expected');

            // checking last name text input box
            test.assertExists('form#form-attorney input[type="text"][name="name-last"]', 'Found last name text input box in the lightbox as expected');

            // checking dob day text input box
            test.assertExists('form#form-attorney input[type="tel"][name="dob-date[day]"]', 'Found DOB day text input box in the lightbox as expected');

            // checking dob month text input box
            test.assertExists('form#form-attorney input[type="tel"][name="dob-date[month]"]', 'Found DOB month text input box in the lightbox as expected');

            // checking dob year text input box
            test.assertExists('form#form-attorney input[type="tel"][name="dob-date[year]"]', 'Found DOB year text input box in the lightbox as expected');

            // checking email address text input box
            test.assertExists('form#form-attorney input[name="email-address"]', 'Found email address input box in the lightbox as expected');

            // checking postcode lookup input
            test.assertExists('form#form-attorney input[type="text"][id="postcode-lookup"]', 'Found postcode lookup input field in the lightbox as expected');

            // checking postcode lookup button
            test.assertExists('a#find_uk_address', 'Found postcode look button in the lightbox as expected');

            // checking address line 1
            test.assertExists('form#form-attorney input[type="text"][name="address-address1"]', 'Found address line 1 text input field in the lightbox as expected');

            // checking address line 2
            test.assertExists('form#form-attorney input[type="text"][name="address-address2"]', 'Found address line 2 text input field in the lightbox as expected');

            // checking address line 3
            test.assertExists('form#form-attorney input[type="text"][name="address-address3"]', 'Found address line 3 text input field in the lightbox as expected');

            // checking address postcode
            test.assertExists('form#form-attorney input[type="text"][name="address-postcode"]', 'Found address postcode text input field in the lightbox as expected');

            // checking Save details button
            test.assertExists('form#form-attorney input[type="submit"][name="submit"]', "Found 'Save details' button in the lightbox as expected");

            // checking cancel button
            test.assertExists('form#form-attorney a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).then(function() {

            // populate the attorney form
            casper.fill('form#form-attorney', {
                'name-first' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'name-last' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'dob-date[day]':'22',
                'dob-date[month]':'10',
                'dob-date[year]':'1988',
                'email-address':'opglpademo+AmyWheeler@gmail.com',
                'address-address1':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address2':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address3':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-postcode':'ST14 8NX'
            });

        }).thenClick('form#form-attorney input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit invalid attorney details");

        }).waitForText('There was a problem submitting the form', function(){

            test.assertTextExists( "Enter the attorney's title" , "Correct validation message shown for blank title");
            test.assertTextExists( "Enter a first name that's less than 51 characters long", "Correct validation message shown for too long first name");
            test.assertTextExists( "Enter a last name that's less than 51 characters long", "Correct validation message shown for too long last name");
            test.assertTextExists( "Change address line 1 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 1");
            test.assertTextExists( "Change address line 2 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 2");
            test.assertTextExists( "Change address line 3 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 3");
            
        }).then(function() {

            // populate the attorney form
            casper.fill('form#form-attorney', {
                'name-title' : 'Mrs',
                'name-first' : 'Amy',
                'name-last' : 'Wheeler',
                'dob-date[day]':'22',
                'dob-date[month]':'10',
                'dob-date[year]':'1988',
                'email-address':'opglpademo+AmyWheeler@gmail.com',
                'address-address1':'Brickhill Cottage',
                'address-address2':'Birch Cross',
                'address-address3':'Marchington, Uttoxeter, Staffordshire',
                'address-postcode':'ST14 8NX'
            });

        }).thenClick('form#form-attorney input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit attorney Amy Wheeler's details");

        }).wait(1500).waitForSelector('div.person', function then () {

            // check the attorney is displayed on the landing page
            test.assertSelectorHasText('div.person h3', 'Mrs Amy Wheeler', "Attorney's name Amy Wheeler is displayed on the attorney landing page as expected");

            test.assertExists('a[href="'+primaryAttorneyPath+'/edit/0"]', 'Found the link for editing attorney Amy Wheeler as expected');
            test.assertExists('a[href="'+primaryAttorneyPath+'/confirm-delete/0"]', 'Found the link for deleting attorney Amy Wheeler as expected');

            test.assertExist('a.button[href="'+replacementAttorneyPath+'"]', 'Save button currently is now shown');

        }).thenClick('a[href="'+replacementAttorneyPath+'"]', function() {

            test.info("Clicked [Save and continue] button to go to next page: replacement-attorney");

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + replacementAttorneyPath + '$'), 'Page is on the expected URL: ' + this.getCurrentUrl());

        }).thenClick('ul.accordion a.link-edit[href="'+primaryAttorneyPath + '"]', function() {

            test.info("Clicked accordion bar for going back to primary attorney page");

            // check it is on lpa/primary-attorney page
            test.assertUrlMatch(new RegExp('^' + basePath + primaryAttorneyPath + '$'), 'Page is on the expected URL: '+this.getCurrentUrl());

            // add second primary attorney
        }).thenClick('a[href="'+primaryAttorneyPath+'/add"]', function() {

            test.info("Clicked [Add another attorney] button to add second attorney");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing attorney's details is loaded and displayed as expected");
            test.assertExists('form#form-attorney', "Found attorney form as expected");
            test.assertElementCount('form#form-attorney select[name="name-title"] option', 8, "Title dropdown is correctly generated");

        }).then(function() {

            test.info('Test adding same name person');

            // populate the attorney form
            casper.fill('form#form-attorney', {
                'name-title' : 'Mr',
                'name-first' : 'Amy',
                'name-last' : 'Wheeler',
                'dob-date[day]':'22',
                'dob-date[month]':'10',
                'dob-date[year]':'1988',
                'email-address':'opglpademo+AmyWheeler@gmail.com',
                'address-address1':'Brickhill Cottage',
                'address-address2':'Birch Cross',
                'address-address3':'Marchington, Uttoxeter, Staffordshire',
                'address-postcode':'ST14 8NX'
            }, false);

        }).wait(1500).then(function() {

            // check error handling and response
            test.assertVisible('.js-duplication-alert', "Name duplication warning shown");

        }).then(function() {

            // populate the attorney form
            casper.fill('form#form-attorney', {
                'name-title' : 'Mr',
                'name-first' : 'David',
                'name-last' : 'Wheeler',
                'dob-date[day]':'12',
                'dob-date[month]':'03',
                'dob-date[year]':'1972',
                'email-address':'opglpademo+DavidWheeler@gmail.com',
                'address-address1':'Brickhill Cottage',
                'address-address2':'Birch Cross',
                'address-address3':'Marchington, Uttoxeter, Staffordshire',
                'address-postcode':'ST14 8NX'
            });

        }).thenClick('form#form-attorney input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit the second attorney David Wheeler's details");

        }).wait(1500).waitFor(function check() {

            // waiting for second attorney showing on primary attorney page
            return this.evaluate(function() {
                return (document.querySelectorAll('div[class="person"]').length == 2);
            });

        }, function then () {

            test.assertSelectorHasText('div.person h3', 'Mr David Wheeler', "Second attorney's name is displayed on attorney page as expected");

            test.assertExists('a[href="'+primaryAttorneyPath+'/edit/1"]', 'Found the link for editing attorney David Wheeler as expected');
            test.assertExists('a[href="'+primaryAttorneyPath+'/confirm-delete/1"]', 'Found the link for deleting attorney David Wheeler as expected');

            test.assertExist('a.button[href="'+primaryAttorneysDecisionPath+'"]', 'Save button currently is now shown');

        }).thenClick('a[href="'+primaryAttorneyPath+'/confirm-delete/1"]', function() {

            test.info('Test deleting attorney');

        }).wait(1500).waitFor(function check() {

            // waiting for the confirm delete popup
            return this.exists('a[href="'+primaryAttorneyPath+'/delete/1"]');

        }).thenClick('a[href="'+primaryAttorneyPath+'/delete/1"]', function() {

            test.info('Test confirm deleting attorney');

        }).wait(1500).waitFor(function check() {

            // waiting for second attorney showing on primary attorney page
            return this.evaluate(function() {
                return (document.querySelectorAll('div[class="person"]').length == 1);
            });

        }, function then () {

            test.assertSelectorDoesntHaveText('div.person h3', 'Mr David Wheeler', "Second attorney's has been deleted from attorney page as expected");

            test.assertExist('a.button[href="'+replacementAttorneyPath+'"]', 'Save button currently is now shown');

        }).thenClick('a[href="'+primaryAttorneyPath+'/add"]', function() {

            test.info("Clicked [Add another attorney] button to add a trust corportation as the second attorney");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing attorney's details is loaded and displayed as expected");

        }).thenClick('.use-details-link-panel a[href="'+primaryAttorneyPath+'/add-trust"]', function() {

            test.info('Clicked << Use a trust corporation >> link');

        }).wait(5000).then(function() {

            // checking form fields existance.

            // checking 'use my details' link
            test.assertExists('.use-details-link-panel  a[href="'+primaryAttorneyPath+'/add"]', 'Found << Use individual >> link in the lightbox as expected');

            // checking first names text input box
            test.assertExists('form#form-trust-corporation input[type="text"][name="name"]', 'Found first names text input box in the lightbox as expected');

            // checking last name text input box
            test.assertExists('form#form-trust-corporation input[type="tel"][name="number"]', 'Found last name text input box in the lightbox as expected');

            // checking email address text input box
            test.assertExists('form#form-trust-corporation input[name="email-address"]', 'Found email address input box in the lightbox as expected');

            // checking postcode lookup input
            test.assertExists('form#form-trust-corporation input[type="text"][id="postcode-lookup"]', 'Found postcode lookup input field in the lightbox as expected');

            // checking postcode lookup button
            test.assertExists('a#find_uk_address', 'Found postcode look button in the lightbox as expected');

            // checking address line 1
            test.assertExists('form#form-trust-corporation input[type="text"][name="address-address1"]', 'Found address line 1 text input field in the lightbox as expected');

            // checking address line 2
            test.assertExists('form#form-trust-corporation input[type="text"][name="address-address2"]', 'Found address line 2 text input field in the lightbox as expected');

            // checking address line 3
            test.assertExists('form#form-trust-corporation input[type="text"][name="address-address3"]', 'Found address line 3 text input field in the lightbox as expected');

            // checking address postcode
            test.assertExists('form#form-trust-corporation input[type="text"][name="address-postcode"]', 'Found address postcode text input field in the lightbox as expected');

            // checking Save details button
            test.assertExists('form#form-trust-corporation input[type="submit"][name="submit"]', "Found 'Save details' button in the lightbox as expected");

            // checking cancel button
            test.assertExists('form#form-trust-corporation a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).then(function() {

            test.info('Populate trust corporation details');

            // populate the attorney form
            casper.fill('form#form-trust-corporation', {
                'name' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kBPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'number' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kBPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'email-address':'opglpademo+trustcorp@gmail.com',
                'address-address1':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address2':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address3':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-postcode':'SA2 8HT'
            });

        }).thenClick('form#form-trust-corporation input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit invalid trust corporation's details");

        }).waitForText('There was a problem submitting the form', function(){

            test.assertTextExists( "Enter a company name that's less than 76 characters long", "Correct validation message shown for too long company name");
            test.assertTextExists( "Enter a registration number that's less than 76 characters long", "Correct validation message shown for too long reg number");
            test.assertTextExists( "Change address line 1 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 1");
            test.assertTextExists( "Change address line 2 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 2");
            test.assertTextExists( "Change address line 3 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 3");
            
        }).then(function() {

            test.info('Populate trust corporation details');

            // populate the attorney form
            casper.fill('form#form-trust-corporation', {
                'name' : 'Standard Trust',
                'number' : '678437685',
                'email-address':'opglpademo+trustcorp@gmail.com',
                'address-address1':'1 Laburnum Place',
                'address-address2':'Sketty',
                'address-address3':'Swansea, Abertawe',
                'address-postcode':'SA2 8HT'
            });

        }).thenClick('form#form-trust-corporation input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit the trust corporation's details");

        }).wait(1500).waitFor(function check() {

            // waiting for second attorney showing on primary attorney page
            return this.evaluate(function() {
                return (document.querySelectorAll('div[class="person"]').length == 2);
            });

        }, function then () {

            test.assertSelectorHasText('div.person h3', 'Standard Trust', "Trust corportation is displayed on attorney page as expected");

            test.assertExists('a.js-form-popup[href="'+primaryAttorneyPath+'/edit/1"]', 'Found the link for editing trust corporation as expected');
            test.assertExists('a[href="'+primaryAttorneyPath+'/confirm-delete/1"]', 'Found the link for deleting trust corporation as expected');

            test.assertExist('a.button[href="'+primaryAttorneysDecisionPath+'"]', 'Save button currently is now shown');

        }).thenClick('a.js-form-popup[href="'+primaryAttorneyPath+'/edit/0"]', function() {

            test.info("Clicked << View/edit details >> link to edit attorney Amy Wheeler's details");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing attorney Amy Wheller's details is loaded and displayed as expected");
            test.assertExists('form#form-attorney', "Found attorney form as expected");
            test.assertElementCount('form#form-attorney select[name="name-title"] option', 8, "Title dropdown is correctly generated");

            test.assertExists('form#form-attorney input[name="name-first"][value="Amy"]', "First names field value is correct");
            test.assertExists('form#form-attorney input[name="name-last"][value="Wheeler"]', "Last name field value is correct");
            test.assertExists('form#form-attorney input[name="dob-date[day]"][value="22"]', "Dob day field value is correct");
            test.assertExists('form#form-attorney input[name="dob-date[month]"][value="10"]', "Dob month field value is correct");
            test.assertExists('form#form-attorney input[name="dob-date[year]"][value="1988"]', "Dob year field value is correct");
            test.assertExists('form#form-attorney input[name="email-address"][value="opglpademo+AmyWheeler@gmail.com"]', "Email field value is correct");
            test.assertExists('form#form-attorney input[name="address-address1"][value="Brickhill Cottage"]', "Address 1 field value is correct");
            test.assertExists('form#form-attorney input[name="address-address2"][value="Birch Cross"]', "Address 2 field value is correct");
            test.assertExists('form#form-attorney input[name="address-address3"][value="Marchington, Uttoxeter, Staffordshire"]', "Address 3 field value is correct");
            test.assertExists('form#form-attorney input[name="address-postcode"][value="ST14 8NX"]', "Postcode field value is correct");

            test.assertExists('input[type="submit"][name="submit"]', "Found [Save details] button on editing attorney form");
            test.assertExists('form#form-attorney a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).thenClick('form#form-attorney a.js-cancel', function() {

            test.info('Clicked [Cancel] button');

        }).wait(1500).thenClick('a[href="'+primaryAttorneysDecisionPath+'"]', function() {

            test.info("Clicked [Save and continue] button to go to primary attorney decisions page");

            test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            // check it is on lpa/how-primary-attorneys-make-decision page
            test.assertUrlMatch(new RegExp('^' + basePath + primaryAttorneysDecisionPath + '$'), 'Page is on the expected URL: '+ primaryAttorneysDecisionPath + ' as expected');

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+primaryAttorneyPath+'"]', 'Found an accordion bar link as expected');
        })

        casper.run(function () { test.done(); });

    } // test

});

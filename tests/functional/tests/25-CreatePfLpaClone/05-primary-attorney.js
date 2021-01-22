
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

            test.info("Clicked [Add another attorney] button to add a trust corportation as the attorney");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('form#form-reuse-details');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for selecting an actors details to use for a primary attorney is loaded and displayed as expected");
            test.assertExists('form#form-reuse-details', "Found reuse details form as expected");
            test.assertElementCount('form#form-reuse-details input[name="reuse-details"]', 9, "Reuse details radio buttons displayed as expected");

        }).then(function() {

            casper.fill('form#form-reuse-details', {
                'reuse-details' : '-1',
            });

        }).thenClick('form#form-reuse-details input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Continue] button to submit the reuse details for None of the Above");

        }).waitFor(function check() {

            // waiting for the form to load
            return this.exists('form#form-attorney');

        }).then(function() {

            test.assertExists('.use-details-link-panel a[href="'+primaryAttorneyPath+'/add-trust"]', 'Found << Use a trust corporation >> link in the lightbox as expected');

        }).thenClick('.use-details-link-panel a[href="'+primaryAttorneyPath+'/add-trust"]', function() {

            test.info('Clicked << Use a trust corporation >> link');

        }).wait(15000).then(function() {

            // checking form fields existance.

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

        }).wait(15000).waitFor(function check() {

            // waiting for second attorney showing on primary attorney page
            return this.evaluate(function() {
                return (document.querySelectorAll('div[class="person"]').length == 1);
            });

        }, function then () {

            test.assertSelectorHasText('div.person h3', 'Standard Trust', "Trust corportation is displayed on attorney page as expected");

            test.assertExists('a.js-form-popup[href="'+primaryAttorneyPath+'/edit/0"]', 'Found the link for editing trust corporation as expected');
            test.assertExists('a[href="'+primaryAttorneyPath+'/confirm-delete/0"]', 'Found the link for deleting trust corporation as expected');

            test.assertExist('a.button[href="'+replacementAttorneyPath+'"]', 'Save button currently is now shown');

        }).thenClick('a[href="'+replacementAttorneyPath+'"]', function() {

            test.info("Clicked [Save and continue] button to go to replacement attorney page");

            test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            // check it is on lpa/how-primary-attorneys-make-decision page
            test.assertUrlMatch(new RegExp('^' + basePath + replacementAttorneyPath + '$'), 'Page is on the expected URL: '+ replacementAttorneyPath + ' as expected');

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+primaryAttorneyPath+'"]', 'Found an accordion bar link as expected');
        })

        casper.run(function () { test.done(); });

    } // test

});

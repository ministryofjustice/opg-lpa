
casper.test.begin("Checking user can add certificate provider", {

    setUp: function(test) {
        certificateProviderPath = paths.certificate_provider.replace('\\d+', lpaId);
        peopleToNotifyPath = paths.people_to_notify.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete certificateProviderPath, peopleToNotifyPath;
    },

    test: function(test) {

        casper.start(basePath + certificateProviderPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + certificateProviderPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#certificate-provider-section', 'Accordion header is found on the page');

            // check form has correct elements
            test.assertExists('a.button[href="'+certificateProviderPath+'/add"]', 'Found [Add a certificate provider] button');

        }).thenClick('a[href="'+certificateProviderPath+'/add"]', function() {

            test.info("Clicked [Add a certificate provider] button");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing certificate provider's details is loaded and displayed as expected");
            test.assertExists('form#form-certificate-provider', "Found certificate provider form as expected");
            test.assertElementCount('form#form-certificate-provider select[name="name-title"] option', 8, "Title dropdown is correctly generated");

        }).then(function() {

            // checking form fields existance.

            // checking 'use my details' link
            test.assertExists('.use-details-link-panel form input[type="submit"][value="Use my details"]', 'Found "Use my details" link in the lightbox as expected');

            // checking title dropdown
            test.assertExists('form#form-certificate-provider select[name="name-title"]', 'Found title element in the lightbox as expected');

            test.assertElementCount('form#form-certificate-provider select[name="name-title"] option', 8, 'Found title dropdown list has items');

            // checking first names text input box
            test.assertExists('form#form-certificate-provider input[type="text"][name="name-first"]', 'Found first names text input box in the lightbox as expected');

            // checking last name text input box
            test.assertExists('form#form-certificate-provider input[type="text"][name="name-last"]', 'Found last name text input box in the lightbox as expected');

            // checking postcode lookup input
            test.assertExists('form#form-certificate-provider input[type="text"][id="postcode-lookup"]', 'Found postcode lookup input field in the lightbox as expected');

            // checking postcode lookup button
            test.assertExists('a[id="find_uk_address"]', 'Found postcode look button in the lightbox as expected');

            // checking address line 1
            test.assertExists('form#form-certificate-provider input[type="text"][name="address-address1"]', 'Found address line 1 text input field in the lightbox as expected');

            // checking address line 2
            test.assertExists('form#form-certificate-provider input[type="text"][name="address-address2"]', 'Found address line 2 text input field in the lightbox as expected');

            // checking address line 3
            test.assertExists('form#form-certificate-provider input[type="text"][name="address-address3"]', 'Found address line 3 text input field in the lightbox as expected');

            // checking address postcode
            test.assertExists('form#form-certificate-provider input[type="text"][name="address-postcode"]', 'Found address postcode text input field in the lightbox as expected');

            // checking Save details button
            test.assertExists('form#form-certificate-provider input[type="submit"][name="submit"]', "Found 'Save details' button in the lightbox as expected");

            // checking cancel button
            test.assertExists('form#form-certificate-provider a.js-cancel', 'Found cancel button in the lightbox as expected');

            test.info('click "Save details" button to submit empty certificate provider details form');

        }).then(function() {

            // populate the certificate provider form
            casper.fill('form#form-certificate-provider', {
                'name-first' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'name-last' : 'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address1':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address2':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-address3':'qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB',
                'address-postcode':'OX10 9NN'
            });

        }).thenClick('form#form-certificate-provider input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit invalid certificate provider details");

        }).waitForText('There is a problem', function(){

            test.assertTextExists( "Enter the certificate provider's title" , "Correct validation message shown for blank title");
            test.assertTextExists( "Enter a first name that's less than 51 characters long", "Correct validation message shown for too long first name");
            test.assertTextExists( "Enter a last name that's less than 51 characters long", "Correct validation message shown for too long last name");
            test.assertTextExists( "Change address line 1 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 1");
            test.assertTextExists( "Change address line 2 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 2");
            test.assertTextExists( "Change address line 3 so that it has fewer than 51 characters", "Correct validation message shown for too long address line 3");

        }).then(function() {

            // populate the certificate provider form
            casper.fill('form#form-certificate-provider', {
                'name-title' : 'Mr',
                'name-first' : 'Reece',
                'name-last' : 'Richards',
                'address-address1':'11 Brookside',
                'address-address2':'Cholsey',
                'address-address3':'Wallingford, Oxfordshire',
                'address-postcode':'OX10 9NN'
            });

        }).thenClick('form#form-certificate-provider input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit certificate provider details");

        }).waitForSelector('.person', function then () {

            // check certificate provider is displayed on the landing page
            test.assertSelectorHasText('div.person h3', 'Mr Reece Richards', "Certificate provider's name is displayed on landing page");

            // check view/edit link is displayed
            test.assertExists('a.js-form-popup[href="'+certificateProviderPath+'/edit"]', 'Found view/edit link for changing certificate provider details');

            test.assertExists('a.button[href="'+peopleToNotifyPath+'"]', '[Save and continue] button on certificate provider page is visible and pointing to people-to-notify page as expected');

        }).thenClick('a.js-form-popup[href="'+certificateProviderPath+'/edit"]', function() {

            test.info('Click on << View/edit details >> link to edit certificate provider details');

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing certificate provider's details is loaded and displayed as expected");
            test.assertExists('form#form-certificate-provider', "Found certificate provider form as expected");
            test.assertElementCount('form#form-certificate-provider select[name="name-title"] option', 8, "Title dropdown is correctly generated");

            test.assertExists('form#form-certificate-provider input[type="text"][name="name-first"][value="Reece"]', 'First name is correctly loaded');
            test.assertExists('form#form-certificate-provider input[type="text"][name="name-last"][value="Richards"]', 'Last name is correctly loaded');
            test.assertExists('form#form-certificate-provider input[type="text"][name="address-address1"][value="11 Brookside"]', 'Address1 is correctly loaded');
            test.assertExists('form#form-certificate-provider input[type="text"][name="address-address2"][value="Cholsey"]', 'Address2 is correctly loaded');
            test.assertExists('form#form-certificate-provider input[type="text"][name="address-address3"][value="Wallingford, Oxfordshire"]', 'Address3 is correctly loaded');
            test.assertExists('form#form-certificate-provider input[type="text"][name="address-postcode"][value="OX10 9NN"]', 'Postcode is correctly loaded');

            test.assertExists('form#form-certificate-provider input[type="submit"][name="submit"]', "Found [Save details] button on editing certificate provider form");

            // checking cancel button
            test.assertExists('form#form-certificate-provider a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).thenClick('form#form-certificate-provider a.js-cancel', function() {

            test.info('Clicked [Cancel] button');

        }).waitForText('Who is the certificate provider?').thenClick('a[href="'+peopleToNotifyPath+'"]', function() {

            test.info('Clicked [Save and continue] button to go to people to notify page');

        }).waitForText('Who should be notified about the LPA?', function() {

            test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + peopleToNotifyPath + '$'), 'Page is on the expected URL: ' + this.getCurrentUrl());

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+certificateProviderPath+'"]', 'Found an accordion bar link as expected');

        });

        casper.run(function () { test.done(); });

    } // test

});

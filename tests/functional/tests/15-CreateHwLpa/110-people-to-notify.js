
casper.test.begin("Checking user can add people to notify", {

    setUp: function(test) {
        peopleToNotifyPath = paths.people_to_notify.replace('\\d+', lpaId);
        instructionPath = paths.instructions.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete peopleToNotifyPath, instructionPath;
    },

    test: function(test) {

        casper.start(basePath + peopleToNotifyPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + peopleToNotifyPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#people-to-notify-section', 'Accordion header is found on the page');

            // check form has correct elements
            test.assertExists('a[href="'+peopleToNotifyPath+'/add"]', 'Found "Add a person to notify" button');

            test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

        }).thenClick('a[href="'+peopleToNotifyPath+'/add"]', function() {

            test.info("Clicked [Add a person to notify] button");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing people to notify's details is loaded and displayed as expected");
            test.assertExists('form#form-people-to-notify', "Found people to notify form as expected");
            test.assertElementCount('form#form-people-to-notify select[name="name-title"] option', 8, "Title dropdown is correctly generated");

        }).then(function() {

            // checking form fields existance.

            // checking 'use my details' link
            test.assertExists('.use-details-link-panel form input[type="submit"][value="Use my details"]', 'Found "Use my details" link in the lightbox as expected');

            // checking title dropdown
            test.assertExists('form#form-people-to-notify select[name="name-title"]', 'Found title element in the lightbox as expected');

            test.assertElementCount('form#form-people-to-notify select[name="name-title"] option', 8, 'Found title dropdown list has items');

            // checking first names text input box
            test.assertExists('form#form-people-to-notify input[type="text"][name="name-first"]', 'Found first names text input box in the lightbox as expected');

            // checking last name text input box
            test.assertExists('form#form-people-to-notify input[type="text"][name="name-last"]', 'Found last name text input box in the lightbox as expected');

            // checking postcode lookup input
            test.assertExists('form#form-people-to-notify input[type="text"][id="postcode-lookup"]', 'Found postcode lookup input field in the lightbox as expected');

            // checking postcode lookup button
            test.assertExists('a[id="find_uk_address"]', 'Found postcode look button in the lightbox as expected');

            // checking address line 1
            test.assertExists('form#form-people-to-notify input[type="text"][name="address-address1"]', 'Found address line 1 text input field in the lightbox as expected');

            // checking address line 2
            test.assertExists('form#form-people-to-notify input[type="text"][name="address-address2"]', 'Found address line 2 text input field in the lightbox as expected');

            // checking address line 3
            test.assertExists('form#form-people-to-notify input[type="text"][name="address-address3"]', 'Found address line 3 text input field in the lightbox as expected');

            // checking address postcode
            test.assertExists('form#form-people-to-notify input[type="text"][name="address-postcode"]', 'Found address postcode text input field in the lightbox as expected');

            // checking Save details button
            test.assertExists('form#form-people-to-notify input[type="submit"][name="submit"]', "Found 'Save details' button in the lightbox as expected");

            // checking cancel button
            test.assertExists('form#form-people-to-notify a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).then(function() {

            // populate the person to notify form
            casper.fill('form#form-people-to-notify', {
                'name-title' : 'Other',
                'name-first' : 'Anthony',
                'name-last' : 'Webb',
                'address-address1':'Brickhill Cottage',
                'address-address2':'Birch Cross',
                'address-address3':'Marchington, Uttoxeter, Staffordshire',
                'address-postcode':'BS18 6PL'
            });

            //  Then set the custom title string
            casper.fill('form#form-people-to-notify', {
                'name-title' : 'Sir'
            });

        }).thenClick('form#form-people-to-notify input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit person to notify details");

        }).wait(1500).waitForSelector('div.person', function then () {

            // check the person to notify is displayed on the landing page
            test.assertSelectorHasText('div.person h3', 'Sir Anthony Webb', 'Found the notified person is displayed on landing page');

            // check view/edit link is displayed
            test.assertVisible('a[href="'+peopleToNotifyPath+'/edit/0"]', 'Found view/edit link for changing person to notify details');
            test.assertVisible('a[href="'+peopleToNotifyPath+'/confirm-delete/0"]', 'Found delete link for changing person to notify details');

            test.assertExists('input[type="submit"]', '[Save and continue] button on people to notify page is visible');

        }).thenClick('a[href="'+peopleToNotifyPath+'/edit/0"]', function() {

            test.info('Click on << View/edit details >> link to check form data has been saved and loaded back correctly');

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing notified person's details is loaded and displayed as expected");
            test.assertExists('form#form-people-to-notify', "Found notified person form as expected");

            test.assertExists('form#form-people-to-notify input[name="name-title"][value="Sir"]', 'Title is correctly loaded');
            test.assertExists('form#form-people-to-notify input[name="name-first"][value="Anthony"]', 'First name is correctly loaded');
            test.assertExists('form#form-people-to-notify input[name="name-last"][value="Webb"]', 'Last name is correctly loaded');
            test.assertExists('form#form-people-to-notify input[name="address-address1"][value="Brickhill Cottage"]', 'Address1 is correctly loaded');
            test.assertExists('form#form-people-to-notify input[name="address-address2"][value="Birch Cross"]', 'Address2 is correctly loaded');
            test.assertExists('form#form-people-to-notify input[name="address-address3"][value="Marchington, Uttoxeter, Staffordshire"]', 'Address3 is correctly loaded');
            test.assertExists('form#form-people-to-notify input[name="address-postcode"][value="BS18 6PL"]', 'Postcode is correctly loaded');

            test.assertExists('form#form-people-to-notify input[type="submit"][name="submit"]', "Found [Save details] button on editing notified person form");

            // checking cancel button
            test.assertExists('form#form-people-to-notify a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).thenClick('form#form-people-to-notify a.js-cancel', function() {

            test.info("Clicked [Cancel] button");

        }).wait(1500).thenClick('input[type="submit"][name="save"]', function() {

            test.info("Clicked [Save and continue] button to go to instructions page");

            test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + instructionPath + '$'), 'Page is on the expected URL: ' + this.getCurrentUrl());

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+peopleToNotifyPath+'"]', 'Found an accordion bar link as expected');

        });

        casper.run(function () { test.done(); });

    } // test

});

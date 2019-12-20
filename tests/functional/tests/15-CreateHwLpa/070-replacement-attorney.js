
casper.test.begin("Checking user can add replacement attorney", {

    setUp: function(test) {
        replacementAttorneyPath = paths.replacement_attorney.replace('\\d+', lpaId);
        whenReplacementAttorneyStepInPath = paths.when_replacement_attorney_step_in.replace('\\d+', lpaId);
        certificateProviderPath = paths.certificate_provider.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete replacementAttorneyPath, whenReplacementAttorneyStepInPath, certificateProviderPath;
    },

    test: function(test) {

        casper.start(basePath + replacementAttorneyPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + replacementAttorneyPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#replacement-attorney-section', 'Accordion header is found on the page');

            // check form has correct elements
            test.assertExists('a[href="'+replacementAttorneyPath+'/add"]', 'Found "Add replacement attorney" button');

            test.assertVisible('input[type="submit"][name="save"]', '"Save and continue" button is visible as expected');

        }).thenClick('input[type="submit"][name="save"]', function() {

            test.info('Clicked [Save and continue] without adding replacement attorneys');

            test.assertUrlMatch(new RegExp('^' + basePath + certificateProviderPath + '$'), 'Page is on the expected URL: '+ basePath + certificateProviderPath);

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+replacementAttorneyPath+'"]', 'Found an accordion bar link as expected');

        }).thenClick('.accordion li.complete a.link-edit[href="'+replacementAttorneyPath+'"]', function() {

            // click accordion bar to go to replacement attorney page to add second attroney
            test.info('Now go back to replacement attorney landing page');

            // check it is on lpa/replacement-attorney page
            test.assertUrlMatch(new RegExp('^' + basePath + replacementAttorneyPath + '$'), 'Page is on the expected URL: '+replacementAttorneyPath);

        }).thenClick('a[href="'+replacementAttorneyPath+'/add"]', function() {

            test.info("Clicked [Add replacement attorney] button to add the first replacement attorney");

        }).wait(1500).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing replacement attorney's details is loaded and displayed as expected");
            test.assertExists('form#form-attorney', "Found replacement attorney form as expected");
            test.assertElementCount('form#form-attorney select[name="name-title"] option', 8, "Title dropdown is correctly generated");

        }).then(function() {

            // checking form fields existance.

            // checking 'use my details' link
            test.assertExists('.use-details-link-panel form input[type="submit"][value="Use my details"]', 'Found "Use my details" link in the lightbox as expected');

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

            // checking postcode lookup input
            test.assertExists('form#form-attorney input[type="text"][id="postcode-lookup"]', 'Found postcode lookup input field in the lightbox as expected');

            // checking postcode lookup button
            test.assertExists('a[id="find_uk_address"]', 'Found postcode look button in the lightbox as expected');

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
                'name-title' : 'Ms',
                'name-first' : 'Isobel',
                'name-last' : 'Ward',
                'dob-date[day]':'01',
                'dob-date[month]':'02',
                'dob-date[year]':'1937',
                'address-address1':'2 Westview',
                'address-address2':'Staplehay',
                'address-address3':'Trull, Taunton, Somerset',
                'address-postcode':'TA3 7HF'
            });

        }).thenClick('form#form-attorney input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit first replacement attorney Ms Isobel Ward's details");

        }).wait(1500).waitForSelector('div.person', function then () {

            // check the attorney is displayed on the landing page
            test.assertSelectorHasText('div.person h3', 'Ms Isobel Ward', "Replacement attorney's name Isobel Ward is displayed on the replacement attorney landing page as expected");

            test.assertExists('a[href="'+replacementAttorneyPath+'/edit/0"]', 'Found the link for editing replacement attorney Isobel Ward as expected');
            test.assertExists('a[href="'+replacementAttorneyPath+'/confirm-delete/0"]', 'Found the link for deleting replacement attorney Isobel Ward as expected');

        }).thenClick('input[type="submit"][name="save"]', function() {

            test.info("Clicked [Save and continue] button on replacement attorney page");

            test.info('Current URL: ' + this.getCurrentUrl());

            // check it is on lpa/when-replacement-attorney-step-in page
            test.assertUrlMatch(new RegExp('^' + basePath + whenReplacementAttorneyStepInPath + '$'), 'Page is on the expected URL: '+whenReplacementAttorneyStepInPath);

            // click accordion bar to go to replacement attorney page to add second attroney
        }).thenClick('.accordion li.complete a.link-edit[href="'+replacementAttorneyPath+'"]', function() {

            test.info("Clicked accordion bar for going back to replacement attorney page");

            // check it is on lpa/replacement-attorney page
            test.assertUrlMatch(new RegExp('^' + basePath + replacementAttorneyPath + '$'), 'Page is on the expected URL: '+replacementAttorneyPath);

            // add second replacement attorney
        }).thenClick('a[href="'+replacementAttorneyPath+'/add"]', function() {

            test.info("Clicked [Add another replacement attorney] button to add second replacement attorney");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing replacement attorney's details is loaded and displayed as expected");
            test.assertExists('form#form-attorney', "Found replacement attorney form as expected");
            test.assertElementCount('form#form-attorney select[name="name-title"] option', 8, "Title dropdown is correctly generated");

        }).then(function() {

            test.info('Test adding same name person');

            // populate the attorney form
            casper.fill('form#form-attorney', {
                'name-title' : 'Mrs',
                'name-first' : 'Isobel',
                'name-last' : 'Ward',
                'dob-date[day]':'22',
                'dob-date[month]':'10',
                'dob-date[year]':'1988',
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
                'name-first' : 'Ewan',
                'name-last' : 'Adams',
                'dob-date[day]':'12',
                'dob-date[month]':'03',
                'dob-date[year]':'1972',
                'address-address1':'2 Westview',
                'address-address2':'Staplehay',
                'address-address3':'Trull, Taunton, Somerset',
                'address-postcode':'TA3 7HF'
            });

        }).thenClick('form#form-attorney input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit second replacement attorney Ewan Adams's details");

        }).wait(1500).waitFor(function check() {

            // waiting for second attorney showing on primary attorney page
            return this.evaluate(function() {
                return (document.querySelectorAll('div[class="person"]').length == 2);
            });

        }, function then () {

            test.assertSelectorHasText('div.person h3', 'Mr Ewan Adams', "Second replacement attorney's name is displayed on replacement attorney page as expected");

            test.assertExists('a[href="'+replacementAttorneyPath+'/edit/1"]', 'Found the link for editing attorney Ewan Adams as expected');
            test.assertExists('a[href="'+replacementAttorneyPath+'/confirm-delete/1"]', 'Found the link for deleting attorney Ewan Adams as expected');

        }).thenClick('a[href="'+replacementAttorneyPath+'/confirm-delete/1"]', function() {

            test.info('Test deleting replacement attorney');

        }).wait(1500).waitFor(function check() {

            // waiting for the confirm delete popup
            return this.exists('a[href="'+replacementAttorneyPath+'/delete/1"]');

        }).thenClick('a[href="'+replacementAttorneyPath+'/delete/1"]', function() {

            test.info('Test confirm deleting replacement attorney');

        }).wait(1500).waitFor(function check() {

            // waiting for second attorney showing on primary attorney page
            return this.evaluate(function() {
                return (document.querySelectorAll('div[class="person"]').length == 1);
            });

        }, function then () {

            test.assertSelectorDoesntHaveText('div.person h3', 'Mr Ewan Adams', "Second attorney's has been deleted from attorney page as expected");

        }).thenClick('a[href="'+replacementAttorneyPath+'/add"]', function() {

            test.info("Clicked [Add another attorney] button to add second attorney back");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing attorney's details is loaded and displayed as expected");
            test.assertExists('form#form-attorney', "Found attorney form as expected");
            test.assertElementCount('form#form-attorney select[name="name-title"] option', 8, "Title dropdown is correctly generated");

        }).then(function() {

            test.info('Populate second attorney details');

            // populate the attorney form
            casper.fill('form#form-attorney', {
                'name-title' : 'Mr',
                'name-first' : 'Ewan',
                'name-last' : 'Adams',
                'dob-date[day]':'12',
                'dob-date[month]':'03',
                'dob-date[year]':'1972',
                'address-address1':'2 Westview',
                'address-address2':'Staplehay',
                'address-address3':'Trull, Taunton, Somerset',
                'address-postcode':'TA3 7HF'
            });

        }).thenClick('form#form-attorney input[type="submit"][name="submit"]', function() {

            test.info("Clicked [Save details] button to submit the second attorney Ewan Adams's details");

        }).wait(1500).waitFor(function check() {

            // waiting for second attorney showing on primary attorney page
            return this.evaluate(function() {
                return (document.querySelectorAll('div[class="person"]').length == 2);
            });

        }, function then () {

            test.assertSelectorHasText('div.person h3', 'Mr Ewan Adams', "Second replacement attorney's name is displayed on replacement attorney page as expected");

            test.assertExists('a[href="'+replacementAttorneyPath+'/edit/1"]', 'Found the link for editing attorney Ewan Adams as expected');
            test.assertExists('a[href="'+replacementAttorneyPath+'/confirm-delete/1"]', 'Found the link for deleting attorney Ewan Adams as expected');

        }).thenClick('a[href="'+replacementAttorneyPath+'/edit/1"]', function() {

            test.info("Clicked << View/edit details >> link to edit replacement attorney Ewan Adams's details");

        }).waitFor(function check() {

            // waiting for lightbox loading
            return this.exists('div#popup');

        }, function then() {

            // check lightbox has been loaded and displayed
            test.assertExists('div#popup', "Lightbox for editing replacement attorney Ewan Adams's details is loaded and displayed as expected");
            test.assertExists('form#form-attorney', "Found attorney form as expected");
            test.assertElementCount('form#form-attorney select[name="name-title"] option', 8, "Title dropdown is correctly generated");

            test.assertExists('form#form-attorney input[name="name-first"][value="Ewan"]', "First names field value is correct");
            test.assertExists('form#form-attorney input[name="name-last"][value="Adams"]', "Last name field value is correct");
            test.assertExists('form#form-attorney input[name="dob-date[day]"][value="12"]', "Dob day field value is correct");
            test.assertExists('form#form-attorney input[name="dob-date[month]"][value="03"]', "Dob month field value is correct");
            test.assertExists('form#form-attorney input[name="dob-date[year]"][value="1972"]', "Dob year field value is correct");
            test.assertExists('form#form-attorney input[name="address-address1"][value="2 Westview"]', "Address 1 field value is correct");
            test.assertExists('form#form-attorney input[name="address-address2"][value="Staplehay"]', "Address 2 field value is correct");
            test.assertExists('form#form-attorney input[name="address-address3"][value="Trull, Taunton, Somerset"]', "Address 3 field value is correct");
            test.assertExists('form#form-attorney input[name="address-postcode"][value="TA3 7HF"]', "Postcode field value is correct");

            test.assertExists('form#form-attorney input[type="submit"][name="submit"]', "Found [Save details] button on editing attorney form");
            test.assertExists('form#form-attorney a.js-cancel', 'Found cancel button in the lightbox as expected');

        }).thenClick('form#form-attorney a.js-cancel', function() {

            test.info('Clicked [Cancel] button');

        }).wait(1500).thenClick('input[type="submit"][name="save"]', function() {

            test.info("Clicked 'Save and continue button' on replacement attorney page");

            test.assertHttpStatus(200, 'Page returns a 200 when the form is submitted');

            test.info('Current URL: ' + this.getCurrentUrl());

            // check it is on lpa/when-replacement-attorneys-step-in page
            test.assertUrlMatch(new RegExp('^' + basePath + whenReplacementAttorneyStepInPath + '$'), 'Page is on the expected URL: '+ basePath + whenReplacementAttorneyStepInPath + ' as expected');

            // check lpa type is correctly displayed in an accordion bar. i.e. there's a bar with a link back to type.
            test.assertExists('.accordion li.complete a[href="'+replacementAttorneyPath+'"]', 'Found an accordion bar link as expected');

        });

        casper.run(function () { test.done(); });

    } // test

});

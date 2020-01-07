
casper.test.begin('Adding About me details', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        casper.start(basePath + paths.login).then(function () {

            // We should be redirected to the gov.uk landing page.

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.login + '$'), 'Page is on the expected URL.');

        }).then(function(){

            // Correctly complete the form and submit it.

            var form = {
                "email": email,
                "password": password
            };

            casper.fill('#login', form, true);

        }).waitForText('Your details', function(){

            // We should end up on the about you page.
            test.assertUrlMatch(new RegExp('^' + basePath + paths.aboutYouNew + '$'), "We're now correctly on the About You details page for a new account.");

        }).then(function() {

            var formVars = { // Blank title, month and wrong postcode in address + long names
                "name-first": "qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB",
                "name-last": "qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB",
                "dob-date[day]": "1",
                "dob-date[year]": "1982",
                "address-address1": "12 Highway Close",
                "address-postcode": "wrongpostcode",
            };

            casper.fill('#about-you', formVars, true);

        }).waitForText('Your details', function(){

            test.assertUrlMatch(new RegExp('^' + basePath + paths.aboutYouNew + '$'), "Back to About You details as expected due to failed validation.");
            test.assertTextExists( "Select a title or choose ‘Prefer not to say’" , "Correct validation message shown for blank title");
            test.assertTextExists( "Enter a first name that's less than 51 characters long", "Correct validation message shown for too long first name");
            test.assertTextExists( "Enter a last name that's less than 51 characters long", "Correct validation message shown for too long last name");
            test.assertTextExists( "Enter your date of birth and include a day, month and year" , "Correct validation message shown for incomplete DOB");
            test.assertTextExists( "Enter a real postcode. If you live overseas, enter the postcode in the ‘Address line 3’ box, instead of the postcode box" , "Correcty validation message shown for invalid  postcode in manual address Postcode box");
        }).then(function() {

            var formVars = { // Bad DOB month
                "name-title": "Mr",
                "name-first": "Chris",
                "name-last": "Smith",
                "dob-date[day]": "1",
                "dob-date[month]": "xx",
                "dob-date[year]": "1982",
                "address-address1": "12 Highway Close",
                "address-postcode": "PL45 9JA",
            };

            casper.fill('#about-you', formVars, true);

        }).waitForText('Your details', function(){

            test.assertUrlMatch(new RegExp('^' + basePath + paths.aboutYouNew + '$'), "Back to About You details as expected due to failed validation.");
            test.assertTextExists( "Enter a real date of birth", "Correct validation message shown for bad DOB");
        }).then(function() {

            var formVars = { // DOB in the future
                "name-title": "Mr",
                "name-first": "Chris",
                "name-last": "Smith",
                "dob-date[day]": "1",
                "dob-date[month]": "2",
                "dob-date[year]": "5500",
                "address-address1": "12 Highway Close",
                "address-postcode": "PL45 9JA",
            };

            casper.fill('#about-you', formVars, true);

        }).waitForText('Your details', function(){

            test.assertUrlMatch(new RegExp('^' + basePath + paths.aboutYouNew + '$'), "Back to About You details as expected due to failed validation.");
            test.assertTextExists( "Enter a date of birth that’s in the past", "Correct validation message shown for future DOB");
        }).then(function() {

            var formVars = {
                "name-title": "Mr",
                "name-first": "Chris",
                "name-last": "Smith",
                "dob-date[day]": "1",
                "dob-date[month]": "12",
                "dob-date[year]": "1982",
                "address-address1": "12 Highway Close",
                "address-postcode": "PL45 9JA",
            };

            casper.fill('#about-you', formVars, true);

        }).waitForText('Make a lasting power of attorney', function(){

            test.assertUrlMatch(new RegExp('^' + basePath + paths.lpa_type_new + '$'), "We're now correctly on the Type page.");

        }).thenOpen(basePath + paths.logout, function() {

            test.info('Current URL (after logout): ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + postLogoutUrl + '$'), "We're now on the post logout page.");

        });

        casper.run(function () { test.done(); });

    } // test

});

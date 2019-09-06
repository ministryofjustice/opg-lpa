var fs = require('fs');

casper.test.begin('Checking password reset email', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        var link = null;
        var waitTime = 5000;
        var checkCount = 0;

        casper.start();

        function getPasswordResetLink(){

        	var filename = '/mnt/test/activation_emails/' + userNumber + '.passwordreset';

            if( fs.isReadable( filename ) ){

                test.info('Password reset email has arrived!');

                var content = fs.read( filename );
                link = content.substring(content.indexOf(",")+1);

                test.info('Content: ' + content);
                test.info('Link: ' + link);

            } else {

                if( checkCount <= 20 ){

                    test.info('Password reset email has not arrived yet. Waiting...');
                    this.wait(waitTime, getPasswordResetLink);

                    checkCount++;

                }

            } // if

        } // function

        casper.then(function () {

            // Wait for the email to arrive...
            this.wait(waitTime, getPasswordResetLink);

        }).then(function () {

            if( link == null ){

                test.fail('Failed to receive Password reset link.');

            }

            test.info('Opening password reset link: ' + link);

            this.open(link, {
                method: 'get'
            });
        }).then(function () {

        	password = "NewPassword" + userNumber;

        	var form = {
                "password": password,
                "password_confirm": password,
            };

            casper.fill('#set-password', form, true);

            test.info('Password changed to ' + password);
        });

        casper.run(function () { test.done(); });

    } // test

});

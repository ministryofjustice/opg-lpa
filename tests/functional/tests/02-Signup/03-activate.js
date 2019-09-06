var fs = require('fs');

casper.test.begin('Checking account activation', {

    setUp: function(test) {},

    tearDown: function(test) {},

    test: function(test) {

        var link = null;
        var waitTime = 5000;
        var checkCount = 0;

        casper.start();

        function getActivationLink(){

        	var filename = '/mnt/test/activation_emails/' + userNumber + '.activation';
        	
            if( fs.isReadable( filename ) ){

                test.info('Activation email has arrived!');

                var content = fs.read( filename );
                link = content.substring(content.indexOf(",")+1);

                test.info('Content: ' + content);
                test.info('Link: ' + link);

            } else {

                if( checkCount <= 40 ){

                    test.info('Activation email has not arrived yet. Waiting...');
                    this.wait(waitTime, getActivationLink);

                    checkCount++;

                }

            } // if

        } // function

        casper.then(function () {

            // Wait for the email to arrive...
            this.wait(waitTime, getActivationLink);

        }).then(function () {

            if( link == null ){

                test.fail('Failed to receive Activation Link.');

            }

            test.info('Opening activation link: ' + link);
            
            this.open(link, {
                method: 'get'
            });
        });

        casper.run(function () { test.done(); });

    } // test

});


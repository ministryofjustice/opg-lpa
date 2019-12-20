
casper.test.begin("Checking cloning an LPA from the Dashboard", {

    setUp: function(test) {
    	dataCheckPath = paths.date_check.replace('\\d+', lpaId);
    	clonePath = paths.clone.replace('\\d+', lpaId);
        deletePath = paths.delete_lpa.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
    	delete dataCheckPath, clonePath, deletePath;
    },

    test: function(test) {

        casper.start(basePath + paths.dashboard).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + paths.dashboard + '$'), 'Page is on the expected URL.');
            
            test.assertExists('.list-item_secondary_actions a[href="'+dataCheckPath+'"]', 'Found [Check dates] button');
            test.assertExists('.list-item_secondary_actions a[href="'+clonePath+'"]', 'Found [Reuse details] button');
            test.assertExists('.list-item_secondary_actions a[href="'+deletePath+'"]', 'Found [Delete] button');

        }).thenClick( '.list-item_secondary_actions a[href="'+clonePath+'"]' ).then(function() {
        	
        	test.info('Click [Reuse details] button to clone an LPA based on last LPA created');
            test.info('Current URL: ' + this.getCurrentUrl());

            // We should end up on the Type.
            test.assertUrlMatch(new RegExp('^' + basePath + paths.lpa_type + '$'), "We're now correctly on the Type page.");

            // Extracts teh LPA ID from the URL
            lpaId  = this.getCurrentUrl().match(/\/(\d+)\//)[1];

            test.info('New LPA ID: ' + lpaId);

        });

        casper.run(function () { test.done(); });

    } // test

});

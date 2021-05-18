
casper.test.begin("Checking user can access complete page", {

    setUp: function(test) {
        completePath = paths.complete.replace('\\d+', lpaId);
        downloadPath = paths.download.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete downloadPath, completePath;
    },

    test: function(test) {

        casper.start(basePath + completePath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertEquals(this.getCurrentUrl(), basePath + completePath, 'Page is on the expected URL: '+ basePath + completePath);

            test.assertExists('a.iconlinks-link[href="' + downloadPath + '/lp1"]', 'Found LP1 download link');
            test.assertExists('a.iconlinks-link[href="' + downloadPath + '/lp3"]', 'Found LP3 download link');
            test.assertExists('a.iconlinks-link[href="' + downloadPath + '/lpa120"]', 'Found LPA120 download link');

        }).then(function() {

            test.info('Start downloading LP1');
            casper.checkPdfDownload(test, basePath + downloadPath + '/lp1/Lasting-Power-of-Attorney-LP1F.pdf', 0);

        }).then(function() {

            test.info('Start downloading LP3');
            casper.checkPdfDownload(test, basePath + downloadPath + '/lp3/Lasting-Power-of-Attorney-LP3F.pdf', 0);

        }).then(function() {

            test.info('Start downloading LPA120');
            casper.checkPdfDownload(test, basePath + downloadPath + '/lpa120/Lasting-Power-of-Attorney-LP120F.pdf', 0);

        });

        casper.run(function () { test.done(); });

    } // test

});

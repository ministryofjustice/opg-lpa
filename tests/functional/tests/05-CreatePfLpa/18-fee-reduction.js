
casper.test.begin("Checking user can access fee reduction page", {

    setUp: function(test) {
        feeReductionPath = paths.fee_reduction.replace('\\d+', lpaId);
        checkoutPath = paths.checkout.replace('\\d+', lpaId);
    },

    tearDown: function(test) {
        delete feeReductionPath, checkoutPath;
    },

    test: function(test) {

        casper.start(basePath + feeReductionPath).then(function () {

            test.info('Current URL: ' + this.getCurrentUrl());

            test.assertUrlMatch(new RegExp('^' + basePath + feeReductionPath + '$'), 'Page is on the expected URL.');

            // check accordion bar which shows the heading of current page is displayed.
            test.assertExists('.accordion li#fee-reduction-section', 'Accordion header is found on the page');

            // check form has correct elements
            test.assertExists('form[name="form-fee-reduction"]', 'Found form-fee-reduction');
            test.assertExists('input[type="radio"][name="reductionOptions"][value="reducedFeeReceivesBenefits"]', 'Found reducedFeeReceivesBenefits option');
            test.assertExists('input[type="radio"][name="reductionOptions"][value="reducedFeeUniversalCredit"]', 'Found reducedFeeUniversalCredit option');
            test.assertExists('input[type="radio"][name="reductionOptions"][value="reducedFeeLowIncome"]', 'Found reducedFeeLowIncome option');
            test.assertExists('input[type="radio"][name="reductionOptions"][value="notApply"]', 'Found notApply option');

            test.assertExists('input[type="submit"][name="save"]', 'Found "Save and continue" button');

            test.info('Click submit button without selecting a raido option');

        }).thenClick('input[type="submit"][name="save"]', function() {

            // check error handling and response
            test.assertExists('div.error-summary h2#error-heading', 'Error messages are displayed as expected');
            test.assertExists('div.error-summary ul.error-summary-list li', 'There is at least one error displayed.');

        }).then(function() {

            test.assertNotVisible('#receives-benefits', 'Description about fee exemption is hidden as expected');
            test.assertNotVisible('#universal-credit', 'Description about universal credit is hidden as expected');

        }).thenClick('input[type="radio"][name="reductionOptions"][value="reducedFeeReceivesBenefits"]', function() {

            test.info('Click on exemption radio option');

            test.assertVisible('#receives-benefits', 'Description about fee exemption is displayed as expected');

        }).thenClick('input[type="radio"][name="reductionOptions"][value="reducedFeeUniversalCredit"]', function() {

            test.info('Click on universal credit radio option');

            test.assertVisible('#universal-credit', 'Description about universal credit is displayed as expected');
            test.assertNotVisible('#receives-benefits', 'Description about fee exemption is hidden as expected');

        }).thenClick('input[type="radio"][name="reductionOptions"][value="reducedFeeLowIncome"]', function() {

            test.info('Click on low income radio option');

            test.assertNotVisible('#receives-benefits', 'Description about fee exemption is hidden as expected');
            test.assertNotVisible('#universal-credit', 'Description about universal credit is hidden as expected');

        }).thenClick('input[type="radio"][name="reductionOptions"][value="notApply"]', function() {

            test.info('Click on not applying reduced fee radio option');

            test.assertNotVisible('#receives-benefits', 'Description about fee exemption is hidden as expected');
            test.assertNotVisible('#universal-credit', 'Description about universal credit is hidden as expected');

            test.assertExists('input[type="radio"][name="reductionOptions"][value="notApply"]:checked', 'Not Applying is selected');

        }).thenClick('input[type="submit"][name="save"]', function() {

            test.info('Click submit button when low income option is selected');

            test.assertUrlMatch(new RegExp('^' + basePath + checkoutPath + '$'), 'Page is on the expected URL: '+checkoutPath);

            test.assertSelectorHasText('.appstatus', '£41', 'Fee is £41 as expected (because repeat case number)');

        }).thenClick('.cya-change a[href="'+feeReductionPath+'"]', function() {

            test.info('Click on accordion bar to go back to fee reduction page');

        }).thenClick('input[type="radio"][id="reducedFeeLowIncome"]', function() {

            test.info('Click on low income radio option');

        }).thenClick('input[type="submit"][name="save"]', function() {

            test.info('Click submit button');

            test.assertUrlMatch(new RegExp('^' + basePath + checkoutPath + '$'), 'Page is on the expected URL: '+checkoutPath);

            test.assertSelectorHasText('.appstatus', '£20.50', 'Fee is £20.50 as expected (because repeat case number)');

        });

        casper.run(function () { test.done(); });

    } // test

});

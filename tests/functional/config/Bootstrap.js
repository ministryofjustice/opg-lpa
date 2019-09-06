var system = require('system');
var fs = require('fs');

var baseDomain = system.env["BASE_DOMAIN"];

var basePath = 'https://' + baseDomain;

var random = Math.floor(Math.random() * 999999999);

var date = new Date();
var userNumber = date.getTime() + "" + random;

var email = "opgcasper+" + userNumber + "@gmail.com";
var password = "Casper" + userNumber;

var suppressGovUkPayErrors = true;

casper.options.waitTimeout = 20000;

casper.start();

// Password is committed to repo on purpose, this is not a mistake
casper.setHttpAuth('opg', 'monkeytrousers');

casper.options.viewportSize = {width: 960, height: 600};

//----------------------------------

// Once created, stores the lpaId we're currently using for the test.
// This is set in 05-CreatePfLpa\01-dashboard.js
var lpaId;

//----------------------------------

// URL the user should be redirected to after logging out.
var postLogoutUrl = 'https://www.gov.uk/done/lasting-power-of-attorney';

// URL user should be redirected to if they access the root (/) of the site.
var rootRedirectUrl = 'https://www.gov.uk/power-of-attorney/make-lasting-power';

// Number of articles contained in the guidance popup.
var numberOfGuidanceHelpTopics = 22;

// Regular expression to check Gov Pay landing page
var govPayUrlRegExp = new RegExp('^https:\/\/www\.payments\.service\.gov\.uk\/card\_details\/[0-9a-z]{24,}$' );
var govPayCancelUrlRegExp = new RegExp('^https:\/\/www\.payments\.service\.gov\.uk\/card\_details\/[0-9a-z]{24,}\/cancel$' );
var govPayConfirmUrlRegExp = new RegExp('^https:\/\/www\.payments\.service\.gov\.uk\/card\_details\/[0-9a-z]{24,}\/confirm$' );

var paths = {};
paths.home = '/home';
paths.login = '/login';
paths.logout = '/logout';
paths.signup = '/signup';
paths.terms = '/terms';
paths.privacy = '/privacy-notice';
paths.passwordReset = '/forgot-password';
paths.passwordChange = '/user/change-password';
paths.enableCookie = '/enable-cookie';
paths.firstGuidance = '/home#/guide/topic-what-is-an-lpa';
paths.aboutYouNew = '/user/about-you/new';
paths.dashboard = '/user/dashboard';
paths.lpa_type_new = '/lpa/type';
paths.lpa_type = '/lpa/\\d+/type';
paths.signup = '/signup';
paths.feedback = '/send-feedback';
paths.feedbackThanks = '/feedback-thanks';
paths.donor = '/lpa/\\d+/donor';
paths.when_lpa_starts = '/lpa/\\d+/when-lpa-starts';
paths.life_sustaining = '/lpa/\\d+/life-sustaining';
paths.primary_attorney = '/lpa/\\d+/primary-attorney';
paths.how_primary_attorneys_make_decision = '/lpa/\\d+/how-primary-attorneys-make-decision';
paths.replacement_attorney = '/lpa/\\d+/replacement-attorney';
paths.how_replacement_attorneys_make_decision = '/lpa/\\d+/how-replacement-attorneys-make-decision';
paths.when_replacement_attorney_step_in = '/lpa/\\d+/when-replacement-attorney-step-in';
paths.certificate_provider = '/lpa/\\d+/certificate-provider';
paths.people_to_notify = '/lpa/\\d+/people-to-notify';
paths.instructions = '/lpa/\\d+/instructions';
paths.created = '/lpa/\\d+/created';
paths.applicant = '/lpa/\\d+/applicant';
paths.summaryViaApplicant = '/lpa/\\d+/summary';
paths.correspondent = '/lpa/\\d+/correspondent';
paths.who_are_you = '/lpa/\\d+/who-are-you';
paths.repeat_application = '/lpa/\\d+/repeat-application';
paths.fee_reduction = '/lpa/\\d+/fee-reduction';
paths.payment = '/lpa/\\d+/payment';
paths.checkout = '/lpa/\\d+/checkout';
paths.checkout_pay = '/lpa/\\d+/checkout/pay';
paths.checkout_pay_return = '/lpa/\\d+/checkout/pay/response';
paths.checkout_cheque = '/lpa/\\d+/checkout/cheque';
paths.checkout_confirm = '/lpa/\\d+/checkout/confirm';
paths.complete = '/lpa/\\d+/complete';
paths.view_docs = '/lpa/\\d+/view-docs';
paths.download = '/lpa/\\d+/download';
paths.date_check = '/lpa/\\d+/date-check';
paths.clone = '/user/dashboard/create/\\d+';
paths.delete_lpa = '/user/dashboard/confirm-delete-lpa/\\d+';

//uncomment to get Javascript console messages
// casper.on('remote.message', function(message) {
//     this.echo('remote message caught: ' + message);
// });

// shows Javascript console errors
casper.on("page.error", function(msg, trace) {
    if(suppressGovUkPayErrors && casper.getCurrentUrl().indexOf('https://www.payments.service.gov.uk') == 0) {
        return;
    }

    this.echo("JS Console error: " + msg, "ERROR")
});

//show html on test fail
casper.test.on('fail', function doSomething() {
    console.log(casper.getPageContent());
    // This unintentionally enforces a fail-fast behaviour.
    casper.exit(1);
});


casper.checkPdfDownload = function(test, path, iterationCount){
    this.then(function() {
        casper.download( path, 'download.pdf');
    }).wait(5000, function() {
        if( !fs.isReadable( 'download.pdf' ) ){
            test.fail('Error downloading PDF.');
        } else {

            if( fs.size( 'download.pdf' ) < 90000 ){
                if (iterationCount >= 10) {
                    test.info('PDF file size is '+fs.size('download.pdf'));
                    test.fail( 'PDF generation failed' );
                } else {
                    // It's hopefully still generating and we should try again.
                    test.info('PDF is still generating... ('+(iterationCount + 1)+'/10)');
                    casper.checkPdfDownload(test, path, iterationCount + 1);
                }
            } else {
                test.info('PDF file size is '+fs.size('download.pdf'));
                test.pass( 'PDF is a sensible size.' );
            }

            fs.remove('download.pdf');
        }
    });
};

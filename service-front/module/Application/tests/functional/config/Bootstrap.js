var system = require('system');

var baseDomain = system.env["BASE_DOMAIN"];

var basePath = 'https://' + baseDomain;

var random = Math.floor(Math.random() * 999999999);

var date = new Date();
var userNumber = date.getTime() + "" + random;

var email = "opgcasper+" + userNumber + "@gmail.com";
var password = "Casper" + userNumber;

casper.options.waitTimeout = 20000;

casper.start();

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
var numberOfGuidanceHelpTopics = 20;

// Regular expression to check Gov Pay landing page
var govPayUrlRegExp = new RegExp('^https:\/\/www\.payments\.service\.gov\.uk\/card\_details\/[0-9a-z]{24,}$' );
var govPayCancelUrlRegExp = new RegExp('^https:\/\/www\.payments\.service\.gov\.uk\/card\_details\/[0-9a-z]{24,}\/cancel$' );

var paths = {};
paths.home = '/home';
paths.login = '/login';
paths.logout = '/logout';
paths.signup = '/signup';
paths.terms = '/terms';
paths.passwordReset = '/forgot-password';
paths.enableCookie = '/enable-cookie';
paths.firstGuidance = '/home#/guide/topic-what-is-an-lpa';
paths.aboutYouNew = '/user/about-you/new';
paths.dashboard = '/user/dashboard';
paths.lpa_type_new = '/lpa/type';
paths.lpa_type = '/lpa/\\d+/type';
paths.signup = '/signup';
paths.feedback = '/send-feedback';
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
    this.echo("JS Console error: " + msg, "ERROR");
    //console.log(casper.getPageContent());
});

//show html on test fail
casper.test.on('fail', function doSomething() {
    console.log(casper.getPageContent());
});

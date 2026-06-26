<?php

declare(strict_types=1);

use App\Handler\AboutYouHandler;
use App\Handler\AccessibilityHandler;
use App\Handler\ChangeEmailAddressHandler;
use App\Handler\ChangePasswordHandler;
use App\Handler\ContactHandler;
use App\Handler\ConfirmRegistrationHandler;
use App\Handler\CookiesHandler;
use App\Handler\DashboardHandler;
use App\Handler\DeleteAccountConfirmHandler;
use App\Handler\DeleteAccountHandler;
use App\Handler\DeletedAccountHandler;
use App\Handler\EnableCookieHandler;
use App\Handler\FeedbackHandler;
use App\Handler\FeedbackThanksHandler;
use App\Handler\ForgotPasswordHandler;
use App\Handler\GuidanceHandler;
use App\Handler\HomeHandler;
use App\Handler\HomeRedirectHandler;
use App\Handler\LoginHandler;
use App\Handler\LogoutHandler;
use App\Handler\PostcodeHandler;
use App\Handler\RegisterHandler;
use App\Handler\ResendActivationEmailHandler;
use App\Handler\ResetPasswordHandler;
use App\Handler\SessionKeepAliveHandler;
use App\Handler\SessionSetExpiryHandler;
use App\Handler\StatusesHandler;
use App\Handler\TermsChangedHandler;
use App\Handler\VerifyEmailAddressHandler;
use App\Handler\Lpa\ApplicantHandler;
use App\Handler\Lpa\CheckoutChequeHandler;
use App\Handler\Lpa\CheckoutConfirmHandler;
use App\Handler\Lpa\CheckoutIndexHandler;
use App\Handler\Lpa\CheckoutPayHandler;
use App\Handler\Lpa\CheckoutPayResponseHandler;
use App\Handler\Lpa\CreateLpaHandler;
use App\Handler\Lpa\DonorIndexHandler;
use App\Handler\Lpa\IndexHandler;
use App\Handler\LpaTypeHandler;
use App\Handler\PingHandler;
use App\Handler\PingHandlerElb;
use App\Handler\PingHandlerJson;
use App\Handler\PingHandlerPingdom;
use App\Handler\PrivacyHandler;
use App\Handler\SessionExpiryHandler;
use App\Handler\StatsHandler;
use App\Handler\TermsHandler;
use App\Handler\TypeHandler;
use App\Handler\Lpa\CertificateProvider\CertificateProviderAddHandler;
use App\Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler;
use App\Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler;
use App\Handler\Lpa\CertificateProvider\CertificateProviderEditHandler;
use App\Handler\Lpa\CertificateProvider\CertificateProviderHandler;
use App\Handler\Lpa\CompleteIndexHandler;
use App\Handler\Lpa\CompleteViewDocsHandler;
use App\Handler\Lpa\ConfirmDeleteLpaHandler;
use App\Handler\Lpa\DeleteLpaHandler;
use App\Handler\Lpa\CorrespondentEditHandler;
use App\Handler\Lpa\CorrespondentHandler;
use App\Handler\Lpa\DateCheckHandler;
use App\Handler\Lpa\DateCheckValidHandler;
use App\Handler\Lpa\DonorAddHandler;
use App\Handler\Lpa\DonorEditHandler;
use App\Handler\Lpa\Download\DownloadCheckHandler;
use App\Handler\Lpa\Download\DownloadFileHandler;
use App\Handler\Lpa\Download\DownloadHandler;
use App\Handler\Lpa\FeeReductionHandler;
use App\Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler;
use App\Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler;
use App\Handler\Lpa\InstructionsHandler;
use App\Handler\Lpa\LifeSustainingHandler;
use App\Handler\Lpa\MoreInfoRequiredHandler;
use App\Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler;
use App\Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler;
use App\Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler;
use App\Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler;
use App\Handler\Lpa\PeopleToNotify\PeopleToNotifyHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyEditHandler;
use App\Handler\Lpa\PrimaryAttorneyHandler;
use App\Handler\Lpa\RepeatApplicationHandler;
use App\Handler\Lpa\ReplacementAttorneyAddHandler;
use App\Handler\Lpa\ReplacementAttorneyAddTrustHandler;
use App\Handler\Lpa\ReplacementAttorneyConfirmDeleteHandler;
use App\Handler\Lpa\ReplacementAttorneyDeleteHandler;
use App\Handler\Lpa\ReplacementAttorneyEditHandler;
use App\Handler\Lpa\ReplacementAttorneyIndexHandler;
use App\Handler\Lpa\ReuseDetailsHandler;
use App\Handler\Lpa\StatusHandler;
use App\Handler\Lpa\SummaryHandler;
use App\Handler\Lpa\WhenLpaStartsHandler;
use App\Handler\Lpa\WhenReplacementAttorneyStepInHandler;
use App\Handler\Lpa\WhoAreYouHandler;
use App\Middleware\LpaLoaderMiddleware;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/ping', PingHandler::class, 'ping')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/ping/json', PingHandlerJson::class, 'ping/json')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/ping/elb', PingHandlerElb::class, 'ping/elb')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/ping/pingdom', PingHandlerPingdom::class, 'ping/pingdom')
        ->setOptions(['unauthenticated_route' => true]);

    $app->get('/', HomeHandler::class, 'root')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/home', HomeHandler::class, 'application.home')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/home-redirect', HomeRedirectHandler::class, 'application.home-redirect')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/session-state', SessionExpiryHandler::class, 'session-state')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/terms', TermsHandler::class, 'application.terms')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/accessibility', AccessibilityHandler::class, 'application.accessibility')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/privacy-notice', PrivacyHandler::class, 'application.privacy-notice')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/contact', ContactHandler::class, 'application.contact')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/enable-cookie', EnableCookieHandler::class, 'application.enable-cookie')
        ->setOptions(['unauthenticated_route' => true]);
    $app->route('/cookies', CookiesHandler::class, ['GET', 'POST'], 'application.cookies')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/guide[/{section}]', GuidanceHandler::class, 'guidance')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/stats', StatsHandler::class, 'stats')
        ->setOptions(['unauthenticated_route' => true]);

    $app->route('/login[/{state}]', LoginHandler::class, ['GET', 'POST'], 'application.login')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/logout', LogoutHandler::class, 'application.logout')
        ->setOptions(['unauthenticated_route' => true]);
    $app->route('/signup', RegisterHandler::class, ['GET', 'POST'], 'register')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/signup/confirm/{token:[a-zA-Z0-9]+}', ConfirmRegistrationHandler::class, 'register/confirm')
        ->setOptions(['unauthenticated_route' => true]);
    $app->route('/signup/resend-email', ResendActivationEmailHandler::class, ['GET', 'POST'], 'register/resend-email')
        ->setOptions(['unauthenticated_route' => true]);
    $app->route('/forgot-password', ForgotPasswordHandler::class, ['GET', 'POST'], 'forgot-password')
        ->setOptions(['unauthenticated_route' => true]);
    $app->route(
        '/forgot-password/reset/{token:[a-zA-Z0-9]+}',
        ResetPasswordHandler::class,
        ['GET', 'POST'],
        'forgot-password/callback',
    )
        ->setOptions(['unauthenticated_route' => true]);
    $app->route('/send-feedback', FeedbackHandler::class, ['GET', 'POST'], 'send-feedback')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/feedback-thanks', FeedbackThanksHandler::class, 'feedback-thanks')
        ->setOptions(['unauthenticated_route' => true]);

    $app->get('/address-lookup', PostcodeHandler::class, 'postcode')
        ->setOptions(['allowIncompleteUser' => true]);
    $app->get('/statuses/{lpa-ids:[0-9,]+}', StatusesHandler::class, 'statuses');

    $app->get('/deleted', DeletedAccountHandler::class, 'deleted')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/user/change-email-address/verify/{token:[a-zA-Z0-9]+}', VerifyEmailAddressHandler::class, 'user/change-email-address/verify')
        ->setOptions(['unauthenticated_route' => true]);

    // Authenticated routes
    $app->get('/user/dashboard', $factory->pipeline(LpaLoaderMiddleware::class, DashboardHandler::class), 'user/dashboard');
    $app->get('/user/dashboard/page/{page:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, DashboardHandler::class), 'user/dashboard/pagination');
    $app->route('/user/dashboard/create[/{lpa-id:\d+}]', CreateLpaHandler::class, ['GET', 'POST'], 'user/dashboard/create-lpa');
    $app->route('/user/about-you[/{new}]', AboutYouHandler::class, ['GET', 'POST'], 'user/about-you')
        ->setOptions(['allowIncompleteUser' => true]);
    $app->route('/user/change-password', ChangePasswordHandler::class, ['GET', 'POST'], 'user/change-password');
    $app->route('/user/change-email-address', ChangeEmailAddressHandler::class, ['GET', 'POST'], 'user/change-email-address');
    $app->route('/user/delete', DeleteAccountHandler::class, ['GET', 'POST'], 'user/delete');
    $app->route('/user/delete/confirm', DeleteAccountConfirmHandler::class, ['GET', 'POST'], 'user/delete-confirm');
    $app->route('/user/dashboard/new-terms', TermsChangedHandler::class, ['GET', 'POST'], 'user/dashboard/terms-changed');
    $app->get('/user/dashboard/statuses/{lpa-ids:[0-9,]+}', StatusesHandler::class, 'user/dashboard/statuses');

    $app->get('/session-keep-alive', SessionKeepAliveHandler::class, 'session-keep-alive');
    $app->post('/session-set-expiry', SessionSetExpiryHandler::class, 'session-set-expiry');

    $app->route('/lpa/type', LpaTypeHandler::class, ['GET', 'POST'], 'lpa-type-no-id');
    $app->get('/user/dashboard/confirm-delete-lpa/{lpa-id:\d+}', ConfirmDeleteLpaHandler::class, 'user/dashboard/confirm-delete-lpa');
    $app->get('/user/dashboard/delete-lpa/{lpa-id:\d+}', DeleteLpaHandler::class, 'user/dashboard/delete-lpa');
    $app->get('/lpa/{lpa-id:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, IndexHandler::class), 'lpa');

    $app->route('/lpa/{lpa-id:\d+}/type', $factory->pipeline(LpaLoaderMiddleware::class, TypeHandler::class), ['GET', 'POST'], 'lpa/form-type');
    $app->route('/lpa/{lpa-id:\d+}/when-lpa-starts', $factory->pipeline(LpaLoaderMiddleware::class, WhenLpaStartsHandler::class), ['GET', 'POST'], 'lpa/when-lpa-starts');
    $app->route('/lpa/{lpa-id:\d+}/life-sustaining', $factory->pipeline(LpaLoaderMiddleware::class, LifeSustainingHandler::class), ['GET', 'POST'], 'lpa/life-sustaining');
    $app->route('/lpa/{lpa-id:\d+}/instructions', $factory->pipeline(LpaLoaderMiddleware::class, InstructionsHandler::class), ['GET', 'POST'], 'lpa/instructions');
    $app->route('/lpa/{lpa-id:\d+}/reuse-details', $factory->pipeline(LpaLoaderMiddleware::class, ReuseDetailsHandler::class), ['GET', 'POST'], 'lpa/reuse-details');

    $app->get('/lpa/{lpa-id:\d+}/donor', $factory->pipeline(LpaLoaderMiddleware::class, DonorIndexHandler::class), 'lpa/donor');
    $app->route('/lpa/{lpa-id:\d+}/donor/add', $factory->pipeline(LpaLoaderMiddleware::class, DonorAddHandler::class), ['GET', 'POST'], 'lpa/donor/add');
    $app->route('/lpa/{lpa-id:\d+}/donor/edit', $factory->pipeline(LpaLoaderMiddleware::class, DonorEditHandler::class), ['GET', 'POST'], 'lpa/donor/edit');

    $app->get('/lpa/{lpa-id:\d+}/primary-attorney', $factory->pipeline(LpaLoaderMiddleware::class, PrimaryAttorneyHandler::class), 'lpa/primary-attorney');
    $app->route('/lpa/{lpa-id:\d+}/primary-attorney/add', $factory->pipeline(LpaLoaderMiddleware::class, PrimaryAttorneyAddHandler::class), ['GET', 'POST'], 'lpa/primary-attorney/add');
    $app->route('/lpa/{lpa-id:\d+}/primary-attorney/add-trust', $factory->pipeline(LpaLoaderMiddleware::class, PrimaryAttorneyAddTrustHandler::class), ['GET', 'POST'], 'lpa/primary-attorney/add-trust');
    $app->route('/lpa/{lpa-id:\d+}/primary-attorney/edit/{idx:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, PrimaryAttorneyEditHandler::class), ['GET', 'POST'], 'lpa/primary-attorney/edit');
    $app->get('/lpa/{lpa-id:\d+}/primary-attorney/confirm-delete/{idx:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, PrimaryAttorneyConfirmDeleteHandler::class), 'lpa/primary-attorney/confirm-delete');
    $app->get('/lpa/{lpa-id:\d+}/primary-attorney/delete/{idx:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, PrimaryAttorneyDeleteHandler::class), 'lpa/primary-attorney/delete');
    $app->route('/lpa/{lpa-id:\d+}/how-primary-attorneys-make-decision', $factory->pipeline(LpaLoaderMiddleware::class, HowPrimaryAttorneysMakeDecisionHandler::class), ['GET', 'POST'], 'lpa/how-primary-attorneys-make-decision');

    $app->route('/lpa/{lpa-id:\d+}/replacement-attorney', $factory->pipeline(LpaLoaderMiddleware::class, ReplacementAttorneyIndexHandler::class), ['GET', 'POST'], 'lpa/replacement-attorney');
    $app->route('/lpa/{lpa-id:\d+}/replacement-attorney/add', $factory->pipeline(LpaLoaderMiddleware::class, ReplacementAttorneyAddHandler::class), ['GET', 'POST'], 'lpa/replacement-attorney/add');
    $app->route('/lpa/{lpa-id:\d+}/replacement-attorney/add-trust', $factory->pipeline(LpaLoaderMiddleware::class, ReplacementAttorneyAddTrustHandler::class), ['GET', 'POST'], 'lpa/replacement-attorney/add-trust');
    $app->route('/lpa/{lpa-id:\d+}/replacement-attorney/edit/{idx:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, ReplacementAttorneyEditHandler::class), ['GET', 'POST'], 'lpa/replacement-attorney/edit');
    $app->get('/lpa/{lpa-id:\d+}/replacement-attorney/confirm-delete/{idx:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, ReplacementAttorneyConfirmDeleteHandler::class), 'lpa/replacement-attorney/confirm-delete');
    $app->get('/lpa/{lpa-id:\d+}/replacement-attorney/delete/{idx:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, ReplacementAttorneyDeleteHandler::class), 'lpa/replacement-attorney/delete');
    $app->route('/lpa/{lpa-id:\d+}/how-replacement-attorneys-make-decision', $factory->pipeline(LpaLoaderMiddleware::class, HowReplacementAttorneysMakeDecisionHandler::class), ['GET', 'POST'], 'lpa/how-replacement-attorneys-make-decision');
    $app->route('/lpa/{lpa-id:\d+}/when-replacement-attorney-step-in', $factory->pipeline(LpaLoaderMiddleware::class, WhenReplacementAttorneyStepInHandler::class), ['GET', 'POST'], 'lpa/when-replacement-attorney-step-in');

    $app->route('/lpa/{lpa-id:\d+}/certificate-provider', $factory->pipeline(LpaLoaderMiddleware::class, CertificateProviderHandler::class), ['GET', 'POST'], 'lpa/certificate-provider');
    $app->route('/lpa/{lpa-id:\d+}/certificate-provider/add', $factory->pipeline(LpaLoaderMiddleware::class, CertificateProviderAddHandler::class), ['GET', 'POST'], 'lpa/certificate-provider/add');
    $app->route('/lpa/{lpa-id:\d+}/certificate-provider/edit', $factory->pipeline(LpaLoaderMiddleware::class, CertificateProviderEditHandler::class), ['GET', 'POST'], 'lpa/certificate-provider/edit');
    $app->get('/lpa/{lpa-id:\d+}/certificate-provider/confirm-delete', $factory->pipeline(LpaLoaderMiddleware::class, CertificateProviderConfirmDeleteHandler::class), 'lpa/certificate-provider/confirm-delete');
    $app->get('/lpa/{lpa-id:\d+}/certificate-provider/delete', $factory->pipeline(LpaLoaderMiddleware::class, CertificateProviderDeleteHandler::class), 'lpa/certificate-provider/delete');

    $app->route('/lpa/{lpa-id:\d+}/people-to-notify', $factory->pipeline(LpaLoaderMiddleware::class, PeopleToNotifyHandler::class), ['GET', 'POST'], 'lpa/people-to-notify');
    $app->route('/lpa/{lpa-id:\d+}/people-to-notify/add', $factory->pipeline(LpaLoaderMiddleware::class, PeopleToNotifyAddHandler::class), ['GET', 'POST'], 'lpa/people-to-notify/add');
    $app->route('/lpa/{lpa-id:\d+}/people-to-notify/edit/{idx:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, PeopleToNotifyEditHandler::class), ['GET', 'POST'], 'lpa/people-to-notify/edit');
    $app->get('/lpa/{lpa-id:\d+}/people-to-notify/confirm-delete/{idx:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, PeopleToNotifyConfirmDeleteHandler::class), 'lpa/people-to-notify/confirm-delete');
    $app->get('/lpa/{lpa-id:\d+}/people-to-notify/delete/{idx:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, PeopleToNotifyDeleteHandler::class), 'lpa/people-to-notify/delete');

    $app->route('/lpa/{lpa-id:\d+}/correspondent', $factory->pipeline(LpaLoaderMiddleware::class, CorrespondentHandler::class), ['GET', 'POST'], 'lpa/correspondent');
    $app->route('/lpa/{lpa-id:\d+}/correspondent/edit', $factory->pipeline(LpaLoaderMiddleware::class, CorrespondentEditHandler::class), ['GET', 'POST'], 'lpa/correspondent/edit');

    $app->route('/lpa/{lpa-id:\d+}/fee-reduction', $factory->pipeline(LpaLoaderMiddleware::class, FeeReductionHandler::class), ['GET', 'POST'], 'lpa/fee-reduction');
    $app->route('/lpa/{lpa-id:\d+}/repeat-application', $factory->pipeline(LpaLoaderMiddleware::class, RepeatApplicationHandler::class), ['GET', 'POST'], 'lpa/repeat-application');
    $app->route('/lpa/{lpa-id:\d+}/who-are-you', $factory->pipeline(LpaLoaderMiddleware::class, WhoAreYouHandler::class), ['GET', 'POST'], 'lpa/who-are-you');

    $app->route('/lpa/{lpa-id:\d+}/applicant', $factory->pipeline(LpaLoaderMiddleware::class, ApplicantHandler::class), ['GET', 'POST'], 'lpa/applicant');

    $app->route('/lpa/{lpa-id:\d+}/checkout', $factory->pipeline(LpaLoaderMiddleware::class, CheckoutIndexHandler::class), ['GET', 'POST'], 'lpa/checkout');
    $app->route('/lpa/{lpa-id:\d+}/checkout/cheque', $factory->pipeline(LpaLoaderMiddleware::class, CheckoutChequeHandler::class), ['GET', 'POST'], 'lpa/checkout/cheque');
    $app->route('/lpa/{lpa-id:\d+}/checkout/pay', $factory->pipeline(LpaLoaderMiddleware::class, CheckoutPayHandler::class), ['GET', 'POST'], 'lpa/checkout/pay');
    $app->route('/lpa/{lpa-id:\d+}/checkout/pay/response', $factory->pipeline(LpaLoaderMiddleware::class, CheckoutPayResponseHandler::class), ['GET', 'POST'], 'lpa/checkout/pay/response');
    $app->route('/lpa/{lpa-id:\d+}/checkout/confirm', $factory->pipeline(LpaLoaderMiddleware::class, CheckoutConfirmHandler::class), ['GET', 'POST'], 'lpa/checkout/confirm');

    $app->route('/lpa/{lpa-id:\d+}/date-check', $factory->pipeline(LpaLoaderMiddleware::class, DateCheckHandler::class), ['GET', 'POST'], 'lpa/date-check');
    $app->route('/lpa/{lpa-id:\d+}/date-check/complete', $factory->pipeline(LpaLoaderMiddleware::class, DateCheckHandler::class), ['GET', 'POST'], 'lpa/date-check/complete');
    $app->get('/lpa/{lpa-id:\d+}/date-check/valid', $factory->pipeline(LpaLoaderMiddleware::class, DateCheckValidHandler::class), 'lpa/date-check/valid');

    $app->get('/lpa/{lpa-id:\d+}/summary', $factory->pipeline(LpaLoaderMiddleware::class, SummaryHandler::class), 'lpa/summary');
    $app->get('/lpa/{lpa-id:\d+}/status', $factory->pipeline(LpaLoaderMiddleware::class, StatusHandler::class), 'lpa/status');
    $app->get('/lpa/{lpa-id:\d+}/more-info-required', $factory->pipeline(LpaLoaderMiddleware::class, MoreInfoRequiredHandler::class), 'lpa/more-info-required');
    $app->get('/lpa/{lpa-id:\d+}/complete', $factory->pipeline(LpaLoaderMiddleware::class, CompleteIndexHandler::class), 'lpa/complete');
    $app->get('/lpa/{lpa-id:\d+}/view-docs', $factory->pipeline(LpaLoaderMiddleware::class, CompleteViewDocsHandler::class), 'lpa/view-docs');

    $app->get('/lpa/{lpa-id:\d+}/download/{pdf-type:lp1|lp3|lpa120}', $factory->pipeline(LpaLoaderMiddleware::class, DownloadHandler::class), 'lpa/download');
    $app->get('/lpa/{lpa-id:\d+}/download/{pdf-type:lp1|lp3|lpa120}/draft', $factory->pipeline(LpaLoaderMiddleware::class, DownloadHandler::class), 'lpa/download/draft');
    $app->get('/lpa/{lpa-id:\d+}/download/{pdf-type:lp1|lp3|lpa120}/{pdf-filename:[a-zA-Z0-9-]+\.pdf}', $factory->pipeline(LpaLoaderMiddleware::class, DownloadFileHandler::class), 'lpa/download/file');
    $app->get('/lpa/{lpa-id:\d+}/download/{pdf-type:lp1|lp3|lpa120}/check', $factory->pipeline(LpaLoaderMiddleware::class, DownloadCheckHandler::class), 'lpa/download/check');
};

<?php

declare(strict_types=1);

use App\Handler\AccessibilityHandler;
use App\Handler\ChangeEmailAddressHandler;
use App\Handler\ContactHandler;
use App\Handler\CookiesHandler;
use App\Handler\AboutYouHandler;
use App\Handler\ChangePasswordHandler;
use App\Handler\DashboardHandler;
use App\Handler\DeleteAccountConfirmHandler;
use App\Handler\DeleteAccountHandler;
use App\Handler\DeletedAccountHandler;
use App\Handler\EnableCookieHandler;
use App\Handler\FeedbackHandler;
use App\Handler\FeedbackThanksHandler;
use App\Handler\GuidanceHandler;
use App\Handler\HomeHandler;
use App\Handler\HomeRedirectHandler;
use App\Handler\LoginHandler;
use App\Handler\LogoutHandler;
use App\Handler\ConfirmRegistrationHandler;
use App\Handler\ForgotPasswordHandler;
use App\Handler\PostcodeHandler;
use App\Handler\RegisterHandler;
use App\Handler\ResendActivationEmailHandler;
use App\Handler\ResetPasswordHandler;
use App\Handler\SessionKeepAliveHandler;
use App\Handler\SessionSetExpiryHandler;
use App\Handler\StatusesHandler;
use App\Handler\TermsChangedHandler;
use App\Handler\VerifyEmailAddressHandler;
use App\Handler\Lpa\CreateLpaHandler;
use App\Handler\Lpa\DonorIndexHandler;
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
use App\Middleware\LpaLoaderMiddleware;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', HomeHandler::class, 'root')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/home', HomeHandler::class, 'application.home')
        ->setOptions(['unauthenticated_route' => true]);
    $app->route('/login[/{state}]', LoginHandler::class, ['GET', 'POST'], 'application.login')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/logout', LogoutHandler::class, 'application.logout')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/session-state', SessionExpiryHandler::class, 'session-state')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/home-redirect', HomeRedirectHandler::class, 'application.home-redirect')
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
    )->setOptions(['unauthenticated_route' => true]);

    // Feedback
    $app->route('/send-feedback', FeedbackHandler::class, ['GET', 'POST'], 'send-feedback')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/feedback-thanks', FeedbackThanksHandler::class, 'feedback-thanks')
        ->setOptions(['unauthenticated_route' => true]);

    // Guidance
    $app->get('/guide[/{section}]', GuidanceHandler::class, 'guidance')
        ->setOptions(['unauthenticated_route' => true]);

    // Stats
    $app->get('/stats', StatsHandler::class, 'stats')
        ->setOptions(['unauthenticated_route' => true]);

    // Ping / health checks
    $app->get('/ping', PingHandler::class, 'ping')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/ping/json', PingHandlerJson::class, 'ping/json')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/ping/elb', PingHandlerElb::class, 'ping/elb')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/ping/pingdom', PingHandlerPingdom::class, 'ping/pingdom')
        ->setOptions(['unauthenticated_route' => true]);

    // Unauthenticated routes
    $app->get('/deleted', DeletedAccountHandler::class, 'deleted')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/user/change-email-address/verify/{token:[a-zA-Z0-9]+}', VerifyEmailAddressHandler::class, 'user/change-email-address/verify')
        ->setOptions(['unauthenticated_route' => true]);

    // Authenticated routes
    $app->get('/user/dashboard', $factory->pipeline(LpaLoaderMiddleware::class, DashboardHandler::class), 'user/dashboard');
    $app->get('/user/dashboard/page/{page:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, DashboardHandler::class), 'user/dashboard/pagination');
    $app->route('/user/dashboard/create[/{lpa-id:\d+}]', $factory->pipeline(LpaLoaderMiddleware::class, CreateLpaHandler::class), ['GET', 'POST'], 'user/dashboard/create-lpa');
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
    $app->get('/address-lookup', PostcodeHandler::class, 'postcode');

    $app->route('/lpa/type', LpaTypeHandler::class, ['GET', 'POST'], 'lpa-type-no-id');
    $app->route('/lpa/{lpa-id:\d+}/type', $factory->pipeline(LpaLoaderMiddleware::class, TypeHandler::class), ['GET', 'POST'], 'lpa/form-type');
    $app->get('/lpa/{lpa-id:\d+}/donor', $factory->pipeline(LpaLoaderMiddleware::class, DonorIndexHandler::class), 'lpa/donor');
};

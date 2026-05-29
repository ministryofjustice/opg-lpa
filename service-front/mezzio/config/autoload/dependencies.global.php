<?php

declare(strict_types=1);

use App\Authentication\AuthenticationService;
use App\Handler;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\IdentityTokenRefreshMiddleware;
use App\Middleware\IdentityTokenRefreshMiddlewareFactory;
use App\Middleware\LpaLoaderMiddleware;
use App\Middleware\PersistentSessionDetailsMiddleware;
use App\Middleware\UserDetailsMiddleware;
use App\Middleware\UserDetailsMiddlewareFactory;
use App\Model\UserDetailsHolder;
use App\Model\Service\Session\PersistentSessionDetails;
use App\Service\Date\DateService;
use App\Service\Feedback\FeedbackService;
use App\Service\Guidance\GuidanceService;
use App\Service\LpaApplicationServiceFactory;
use App\Service\Stats\StatsService;
use App\Service\System\StatusService;
use App\Storage\MezzioSessionStorage;
use App\View;
use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\CsrfMiddlewareFactory;
use Mezzio\Csrf\SessionCsrfGuardFactory;
use Mezzio\Helper\UrlHelper;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    'dependencies' => [
        'aliases' => [],
        'invokables' => [],
        'factories' => [
            // Storage — registered as a factory (not invokable) to guarantee
            // the container returns the same shared instance to both
            // LpaApplicationServiceFactory and IdentityTokenRefreshMiddlewareFactory,
            // so setSession() on the storage is visible to getIdentity().
            MezzioSessionStorage::class => static fn() => new MezzioSessionStorage(),
            // Shared ApiClient — single instance so IdentityTokenRefreshMiddleware's
            // updateToken() call is visible to LpaApplicationService on every request.
            ApiClient::class => static function (ContainerInterface $c): ApiClient {
                $config = $c->get('config');
                $apiUri = $config['api_client']['api_uri'] ?? '';
                $client = new ApiClient(new GuzzleClient(), (string) $apiUri);
                $client->setLogger($c->get(LoggerInterface::class));
                return $client;
            },
            // PersistentSessionDetails — shared instance; refreshed per-request by PersistentSessionDetailsMiddleware
            PersistentSessionDetails::class => static fn() => new PersistentSessionDetails(),
            // UserDetailsHolder — shared instance; populated per-request by UserDetailsMiddleware
            UserDetailsHolder::class => static fn() => new UserDetailsHolder(),

            // Handlers — simple template renderers
            Handler\HomeRedirectHandler::class => static fn(ContainerInterface $c) => new Handler\HomeRedirectHandler(
                $c->get('config'),
            ),
            Handler\TermsHandler::class => static fn(ContainerInterface $c) => new Handler\TermsHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\AccessibilityHandler::class => static fn(ContainerInterface $c) => new Handler\AccessibilityHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\PrivacyHandler::class => static fn(ContainerInterface $c) => new Handler\PrivacyHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\ContactHandler::class => static fn(ContainerInterface $c) => new Handler\ContactHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\EnableCookieHandler::class => static fn(ContainerInterface $c) => new Handler\EnableCookieHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\CookiesHandler::class => static fn(ContainerInterface $c) => new Handler\CookiesHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
            ),

            // Handlers
            Handler\HomeHandler::class => static fn(ContainerInterface $c) => new Handler\HomeHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get('config'),
            ),
            Handler\LoginHandler::class => static fn(ContainerInterface $c) => new Handler\LoginHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(AuthenticationService::class),
            ),
            Handler\LogoutHandler::class => static fn(ContainerInterface $c) => new Handler\LogoutHandler(
                $c->get('config'),
            ),
            Handler\DashboardHandler::class => static fn(ContainerInterface $c) => new Handler\DashboardHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
            ),
            Handler\Lpa\CreateLpaHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CreateLpaHandler(
                $c->get(LpaApplicationService::class),
            ),
            Handler\LpaTypeHandler::class => static fn(ContainerInterface $c) => new Handler\LpaTypeHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\TypeHandler::class => static fn(ContainerInterface $c) => new Handler\TypeHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\SessionExpiryHandler::class => static fn(ContainerInterface $c) => new Handler\SessionExpiryHandler(
                new LpaAuthAdapter($c->get(ApiClient::class)),
                $c->get(MezzioSessionStorage::class),
            ),
            Handler\Lpa\DonorIndexHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DonorIndexHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            Handler\FeedbackHandler::class => static fn(ContainerInterface $c) => new Handler\FeedbackHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(FeedbackService::class),
                $c->get(LoggerInterface::class),
                $c->get(DateService::class),
            ),
            Handler\FeedbackThanksHandler::class => static fn(ContainerInterface $c) => new Handler\FeedbackThanksHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\GuidanceHandler::class => static fn(ContainerInterface $c) => new Handler\GuidanceHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(GuidanceService::class),
            ),
            Handler\StatsHandler::class => static fn(ContainerInterface $c) => new Handler\StatsHandler(
                $c->get(StatsService::class),
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\PingHandler::class => static fn(ContainerInterface $c) => new Handler\PingHandler(
                $c->get(StatusService::class),
            ),
            Handler\PingHandlerJson::class => static fn(ContainerInterface $c) => new Handler\PingHandlerJson(
                $c->get('config'),
                $c->get(StatusService::class),
            ),
            Handler\PingHandlerPingdom::class => static fn(ContainerInterface $c) => new Handler\PingHandlerPingdom(
                $c->get(StatusService::class),
                $c->get(DateService::class),
            ),
            Handler\PingHandlerElb::class => static fn() => new Handler\PingHandlerElb(),

            // Services
            LpaApplicationService::class => LpaApplicationServiceFactory::class,
            DateService::class => static fn() => new DateService(),
            FeedbackService::class => static fn(ContainerInterface $c) => new FeedbackService(
                $c->get(ApiClient::class),
                $c->get(LoggerInterface::class),
            ),
            GuidanceService::class => static fn() => new GuidanceService(),
            StatsService::class => static fn(ContainerInterface $c) => new StatsService(
                $c->get(ApiClient::class),
            ),
            StatusService::class => static fn(ContainerInterface $c) => new StatusService(
                $c->get(ApiClient::class),
            ),

            // Middleware
            AuthenticationMiddleware::class => static function (ContainerInterface $c): AuthenticationMiddleware {
                $authService = $c->get(LpaApplicationService::class)->getAuthenticationService();
                return new AuthenticationMiddleware($authService, $c->get(UrlHelper::class));
            },
            IdentityTokenRefreshMiddleware::class  => IdentityTokenRefreshMiddlewareFactory::class,
            LpaLoaderMiddleware::class => static fn(ContainerInterface $c) => new LpaLoaderMiddleware(
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            UserDetailsMiddleware::class           => UserDetailsMiddlewareFactory::class,
            CsrfValidationMiddleware::class        => static fn() => new CsrfValidationMiddleware(),
            PersistentSessionDetailsMiddleware::class => static fn(ContainerInterface $c) => new PersistentSessionDetailsMiddleware(
                $c->get(PersistentSessionDetails::class),
            ),
            // CSRF — session-backed guard, wired via CsrfMiddleware in the pipeline
            CsrfMiddleware::class              => CsrfMiddlewareFactory::class,
            CsrfGuardFactoryInterface::class   => static fn() => new SessionCsrfGuardFactory(),

            // View extensions
            View\Twig\LegacyCompatExtension::class => View\Twig\LegacyCompatExtensionFactory::class,

            // Mezzio-native authentication service
            AuthenticationService::class => static function (ContainerInterface $c): AuthenticationService {
                $config    = $c->get('config');
                $apiUri    = $config['api_client']['api_uri'] ?? null;
                $apiClient = new ApiClient(new GuzzleClient(), (string) $apiUri);
                $apiClient->setLogger($c->get(LoggerInterface::class));
                return new AuthenticationService(new LpaAuthAdapter($apiClient));
            },

            // Logger — writes to stderr so output appears in `make mezzio-dc-logs`
            LoggerInterface::class => static function (): LoggerInterface {
                $logger = new Logger('mezzio');
                $logger->pushHandler(new StreamHandler('php://stderr'));
                return $logger;
            },
        ],
    ],

    'api_client' => [
        'api_uri' => getenv('OPG_LPA_ENDPOINTS_API') ?: null,
    ],

    'processing-status' => [
        'track-from-date' => getenv('OPG_LPA_FRONT_TRACK_FROM_DATE') ?: '2019-04-01',
    ],

    'email' => [
        'notify' => [
            'key'                   => getenv('OPG_LPA_FRONT_EMAIL_NOTIFY_API_KEY') ?: null,
            'smokeTestEmailAddress' => 'simulate-delivered@notifications.service.gov.uk',
        ],
    ],

    'redirects' => [
        'logout' => 'https://www.gov.uk/done/lasting-power-of-attorney',
    ],
];

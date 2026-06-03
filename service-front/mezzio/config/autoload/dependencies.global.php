<?php

declare(strict_types=1);

use App\Authentication\AuthenticationService;
use App\Handler;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\FlashMessagesHolderMiddleware;
use App\Middleware\IdentityTokenRefreshMiddleware;
use App\Middleware\IdentityTokenRefreshMiddlewareFactory;
use App\Middleware\LpaLoaderMiddleware;
use App\Middleware\PersistentSessionDetailsMiddleware;
use App\Middleware\UserDetailsMiddleware;
use App\Middleware\UserDetailsMiddlewareFactory;
use App\Model\UserDetailsHolder;
use App\Model\FlashMessagesHolder;
use App\Model\Service\Session\PersistentSessionDetails;
use App\Service\Date\DateService;
use App\Service\Feedback\FeedbackService;
use App\Service\Guidance\GuidanceService;
use App\Service\LpaApplicationServiceFactory;
use App\Service\Stats\StatsService;
use App\Service\System\StatusService;
use App\Service\UserDetails;
use App\Service\UserDetailsFactory;
use App\Storage\MezzioSessionStorage;
use App\View;
use App\Authentication\Adapter\LpaAuthAdapter;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\Lpa\Application as LpaApplicationService;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use MakeShared\Logging\LoggerFactory;
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\CsrfMiddlewareFactory;
use Mezzio\Csrf\SessionCsrfGuardFactory;
use Mezzio\Helper\UrlHelper;
use Monolog\Level;
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
            },            // PersistentSessionDetails — shared instance; refreshed per-request by PersistentSessionDetailsMiddleware
            PersistentSessionDetails::class => static fn() => new PersistentSessionDetails(),
            // UserDetailsHolder — shared instance; populated per-request by UserDetailsMiddleware
            UserDetailsHolder::class => static fn() => new UserDetailsHolder(),
            // FlashMessagesHolder — shared instance; populated per-request by FlashMessagesHolderMiddleware
            FlashMessagesHolder::class => static fn() => new FlashMessagesHolder(),

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
            FeedbackService::class => static function (ContainerInterface $c): FeedbackService {
                $config = $c->get('config');
                return new FeedbackService(
                    $c->get(ApiClient::class),
                    $c->get(LoggerInterface::class),
                    $c->has(\Application\Model\Service\Mail\Transport\MailTransportInterface::class) ? $c->get(\Application\Model\Service\Mail\Transport\MailTransportInterface::class) : null,
                    $config['email']['sendFeedbackEmailTo'] ?? (getenv('OPG_LPA_FRONT_EMAIL_SENDTO') ?: ''),
                );
            },
            GuidanceService::class => static fn() => new GuidanceService(),
            StatsService::class => static fn(ContainerInterface $c) => new StatsService(
                $c->get(ApiClient::class),
            ),
            StatusService::class => static function (ContainerInterface $c): StatusService {
                $config = $c->get('config');
                return new StatusService(
                    $c->get(ApiClient::class),
                    $c->has(\Aws\DynamoDb\DynamoDbClient::class) ? $c->get(\Aws\DynamoDb\DynamoDbClient::class) : null,
                    $c->has(\Laminas\Session\SaveHandler\SaveHandlerInterface::class) ? $c->get(\Laminas\Session\SaveHandler\SaveHandlerInterface::class) : null,
                    $c->has(\Application\Model\Service\Mail\Transport\MailTransportInterface::class) ? $c->get(\Application\Model\Service\Mail\Transport\MailTransportInterface::class) : null,
                    $c->has(\Application\Model\Service\AddressLookup\OrdnanceSurvey::class) ? $c->get(\Application\Model\Service\AddressLookup\OrdnanceSurvey::class) : null,
                    $c->has(\Application\Model\Service\Redis\RedisClient::class) ? $c->get(\Application\Model\Service\Redis\RedisClient::class) : null,
                    $config,
                );
            },

            // Infrastructure services for StatusService health checks
            \Aws\DynamoDb\DynamoDbClient::class => static function (ContainerInterface $c): \Aws\DynamoDb\DynamoDbClient {
                $config = $c->get('config');
                return new \Aws\DynamoDb\DynamoDbClient($config['admin']['dynamodb']['client'] ?? [
                    'region' => 'eu-west-1',
                    'version' => '2012-08-10',
                    'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
                ]);
            },
            \Application\Model\Service\Redis\RedisClient::class => static function (ContainerInterface $c): \Application\Model\Service\Redis\RedisClient {
                $config = $c->get('config');
                $redisUrl = $config['redis']['url'] ?? (getenv('OPG_LPA_COMMON_REDIS_CACHE_URL') ?: '');
                $ttlMs = $config['redis']['ttlMs'] ?? (int)(getenv('OPG_LPA_COMMON_REDIS_CACHE_TTL_MS') ?: 604800000);
                return new \Application\Model\Service\Redis\RedisClient($redisUrl, $ttlMs, new \Redis());
            },
            \Laminas\Session\SaveHandler\SaveHandlerInterface::class => static function (ContainerInterface $c): \Laminas\Session\SaveHandler\SaveHandlerInterface {
                $redisClient = $c->get(\Application\Model\Service\Redis\RedisClient::class);
                return new \Application\Model\Service\Session\FilteringSaveHandler($redisClient, [
                    static fn() => empty($_SERVER['HTTP_X_SESSIONREADONLY']),
                ]);
            },
            \Application\Model\Service\Mail\Transport\MailTransportInterface::class => static function (ContainerInterface $c): \Application\Model\Service\Mail\Transport\MailTransportInterface {
                $config = $c->get('config');
                $notifyKey = $config['email']['notify']['key'] ?? (getenv('OPG_LPA_FRONT_EMAIL_NOTIFY_API_KEY') ?: '');
                $notifyClient = new \Alphagov\Notifications\Client([
                    'apiKey' => $notifyKey,
                    'httpClient' => new \Http\Adapter\Guzzle7\Client(),
                ]);
                return new \Application\Model\Service\Mail\Transport\NotifyMailTransport($notifyClient);
            },
            Handler\AboutYouHandler::class => static fn(ContainerInterface $c) => new Handler\AboutYouHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(UserDetails::class),
            ),
            Handler\ChangePasswordHandler::class => static fn(ContainerInterface $c) => new Handler\ChangePasswordHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(AuthenticationService::class),
                $c->get(UserDetails::class),
            ),
            Handler\RegisterHandler::class => static fn(ContainerInterface $c) => new Handler\RegisterHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(UserDetails::class),
                $c->get(LoggerInterface::class),
            ),
            Handler\ConfirmRegistrationHandler::class => static fn(ContainerInterface $c) => new Handler\ConfirmRegistrationHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(UserDetails::class),
            ),
            Handler\ResendActivationEmailHandler::class => static fn(ContainerInterface $c) => new Handler\ResendActivationEmailHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(UserDetails::class),
            ),
            Handler\ForgotPasswordHandler::class => static fn(ContainerInterface $c) => new Handler\ForgotPasswordHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(UserDetails::class),
            ),
            Handler\ResetPasswordHandler::class => static fn(ContainerInterface $c) => new Handler\ResetPasswordHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(UserDetails::class),
            ),

            // Services
            LpaApplicationService::class => LpaApplicationServiceFactory::class,
            UserDetails::class => UserDetailsFactory::class,

            // Middleware
            AuthenticationMiddleware::class => static function (ContainerInterface $c): AuthenticationMiddleware {
                return new AuthenticationMiddleware($c->get(AuthenticationService::class), $c->get(UrlHelper::class));
            },
            IdentityTokenRefreshMiddleware::class  => IdentityTokenRefreshMiddlewareFactory::class,
            LpaLoaderMiddleware::class => static fn(ContainerInterface $c) => new LpaLoaderMiddleware(
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            UserDetailsMiddleware::class           => UserDetailsMiddlewareFactory::class,
            CsrfValidationMiddleware::class        => static fn() => new CsrfValidationMiddleware(),
            FlashMessagesHolderMiddleware::class    => static fn(ContainerInterface $c) => new FlashMessagesHolderMiddleware(
                $c->get(FlashMessagesHolder::class),
            ),
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
                $service = new AuthenticationService(new LpaAuthAdapter($c->get(ApiClient::class)));
                $service->setStorage($c->get(MezzioSessionStorage::class));
                return $service;
            },

            // Logger — uses shared LoggerFactory for consistent JSON output, trace-id injection,
            // and header scrubbing across all OPG services.
            LoggerInterface::class => LoggerFactory::class,
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

    'admin' => [
        'dynamodb' => [
            'client' => [
                'region' => 'eu-west-1',
                'version' => '2012-08-10',
                'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
            ],
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE') ?: 'lpa-properties-shared',
            ],
            'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
        ],
    ],

    'redis' => [
        'url' => getenv('OPG_LPA_COMMON_REDIS_CACHE_URL') ?: null,
        'ttlMs' => (int)(getenv('OPG_LPA_COMMON_REDIS_CACHE_TTL_MS') ?: 604800000),
        'ordnance_survey' => [
            'max_call_per_min' => 6,
        ],
        'logging' => [
        'serviceName' => 'opg-lpa/front',
        'minLevel'    => Level::fromName('DEBUG'),
        ],
    ];

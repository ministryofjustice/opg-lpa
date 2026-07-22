<?php

declare(strict_types=1);

use App\Adapter\DynamoDbKeyValueStore;
use App\Authentication\AuthenticationService;
use App\Authentication\AuthenticationServiceFactory;
use App\Form;
use App\Handler;
use App\Handler\Lpa\StatusHandler;
use App\Middleware\RegisterSessionSaveHandlerMiddleware;
use App\Middleware\RouteNameMiddleware;
use App\Middleware\TermsAndConditionsMiddleware;
use App\Model\FlashMessagesHolder;
use App\Model\Service\Session\PersistentSessionDetails;
use App\Model\UserDetailsHolder;
use App\Service\AddressLookup\OrdnanceSurvey as OrdnanceSurveyService;
use App\Service\AddressLookup\OrdnanceSurveyFactory;
use App\Service\ApiClient\ApiClientFactory;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\DynamoDbClientFactory;
use App\Service\Feedback\FeedbackService;
use App\Service\Feedback\FeedbackServiceFactory;
use App\Service\Guidance\GuidanceService;
use App\Service\Lpa\Applicant as ApplicantService;
use App\Service\Lpa\ApplicantFactory;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication as CommunicationService;
use App\Service\Lpa\CommunicationFactory;
use App\Service\Lpa\ReplacementAttorneyCleanup as ReplacementAttorneyCleanupService;
use App\Service\Lpa\ReplacementAttorneyCleanupFactory;
use App\Service\LpaApplicationServiceFactory;
use App\Service\Mail\Transport\MailTransportFactory;
use App\Service\Mail\Transport\MailTransportInterface as AppMailTransportInterface;
use App\Service\Payment\AlphagovPayClientFactory;
use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Service\Redis\RedisClient;
use App\Service\Redis\RedisClientFactory;
use App\Service\Session\FilteringSaveHandler;
use App\Service\Session\SaveHandlerFactory;
use App\Service\System\StatusService;
use App\Service\System\StatusServiceFactory;
use App\Service\SystemMessage;
use App\Service\UserDetails;
use App\Service\UserDetailsFactory;
use App\Storage\MezzioSessionStorage;
use App\View;
use Aws\DynamoDb\DynamoDbClient;
use Laminas\EventManager\EventManager;
use Laminas\Stratigility\Middleware\ErrorHandler;
use MakeShared\Logging\LoggerFactory;
use MakeShared\Logging\RequestLoggingMiddleware;
use MakeShared\Logging\RequestLoggingMiddlewareFactory;
use Mezzio\Container\ErrorHandlerFactory;
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\CsrfMiddlewareFactory;
use Mezzio\Csrf\SessionCsrfGuardFactory;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Monolog\Level;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

return [
    'form_elements'     => [
        'invokables' => [
            Form\Lpa\AbstractLpaForm::class                   => Form\Lpa\AbstractLpaForm::class,
            Form\Lpa\AbstractActorForm::class                 => Form\Lpa\AbstractActorForm::class,
            Form\Lpa\AbstractMainFlowForm::class              => Form\Lpa\AbstractMainFlowForm::class,
            Form\Lpa\BlankMainFlowForm::class                 => Form\Lpa\BlankMainFlowForm::class,
            Form\Lpa\AttorneyForm::class                      => Form\Lpa\AttorneyForm::class,
            Form\Lpa\TrustCorporationForm::class              => Form\Lpa\TrustCorporationForm::class,
            Form\Lpa\DonorForm::class                         => Form\Lpa\DonorForm::class,
            Form\Lpa\CertificateProviderForm::class           => Form\Lpa\CertificateProviderForm::class,
            Form\Lpa\PeopleToNotifyForm::class                => Form\Lpa\PeopleToNotifyForm::class,
            Form\Lpa\CorrespondentForm::class                 => Form\Lpa\CorrespondentForm::class,
            Form\Lpa\CorrespondenceForm::class                => Form\Lpa\CorrespondenceForm::class,
            Form\Lpa\TypeForm::class                          => Form\Lpa\TypeForm::class,
            Form\Lpa\WhoAreYouForm::class                     => Form\Lpa\WhoAreYouForm::class,
            Form\Lpa\HowAttorneysMakeDecisionForm::class      => Form\Lpa\HowAttorneysMakeDecisionForm::class,
            Form\Lpa\WhenLpaStartsForm::class                 => Form\Lpa\WhenLpaStartsForm::class,
            Form\Lpa\FeeReductionForm::class                  => Form\Lpa\FeeReductionForm::class,
            Form\Lpa\RepeatApplicationForm::class             => Form\Lpa\RepeatApplicationForm::class,
            Form\Lpa\ReuseDetailsForm::class                  => Form\Lpa\ReuseDetailsForm::class,
            Form\Lpa\InstructionsAndPreferencesForm::class    => Form\Lpa\InstructionsAndPreferencesForm::class,
            Form\Lpa\LifeSustainingForm::class                => Form\Lpa\LifeSustainingForm::class,
            Form\Lpa\WhenReplacementAttorneyStepInForm::class => Form\Lpa\WhenReplacementAttorneyStepInForm::class,
            Form\Lpa\ApplicantForm::class                     => Form\Lpa\ApplicantForm::class,
            Form\Lpa\DateCheckForm::class                     => Form\Lpa\DateCheckForm::class,
            Form\General\CookieConsentForm::class             => Form\General\CookieConsentForm::class,
            Form\General\FeedbackForm::class                  => Form\General\FeedbackForm::class,
            Form\User\AboutYou::class                         => Form\User\AboutYou::class,
            Form\Fieldset\Dob::class                          => Form\Fieldset\Dob::class,
            Form\Fieldset\Correspondence::class               => Form\Fieldset\Correspondence::class,
        ],
    ],
    'dependencies'      => [
        'aliases'    => [
            'Communication'                   => CommunicationService::class,
            'GovPayClient'                    => GovPayClient::class,
            'EventManager'                    => EventManager::class,
            CsrfGuardFactoryInterface::class  => SessionCsrfGuardFactory::class,
        ],
        'invokables' => [
            EventManager::class             => EventManager::class,
            RouteNameMiddleware::class      => RouteNameMiddleware::class,
            MezzioSessionStorage::class     => MezzioSessionStorage::class,
            PersistentSessionDetails::class => PersistentSessionDetails::class,
            UserDetailsHolder::class        => UserDetailsHolder::class,
            FlashMessagesHolder::class      => FlashMessagesHolder::class,
            SessionCsrfGuardFactory::class  => SessionCsrfGuardFactory::class,
        ],
        'factories'  => [
            // Override the default ErrorHandler factory to attach a logging listener.
            // Laminas\Stratigility\Middleware\ErrorHandler catches all unhandled Throwables
            // but does not log them by default — this listener ensures every 500 is logged
            // with full exception context (message, class, file, line, trace).
            ErrorHandler::class                           => static function (ContainerInterface $c): ErrorHandler {
                /** @var ErrorHandler $handler */
                $handler = (new ErrorHandlerFactory())($c);
                $logger  = $c->get(LoggerInterface::class);

                $handler->attachListener(
                    static function (
                        Throwable $error,
                        ServerRequestInterface $request,
                        ResponseInterface $response,
                    ) use ($logger): void {
                        $logger->error('Unhandled exception — 500 response', [
                            'exception'      => [
                                'class'   => $error::class,
                                'message' => $error->getMessage(),
                                'code'    => $error->getCode(),
                                'file'    => $error->getFile() . ':' . $error->getLine(),
                                'trace'   => array_slice(
                                    array_map(
                                        static fn(array $f) => ($f['file'] ?? '') . ':' . ($f['line'] ?? ''),
                                        $error->getTrace()
                                    ),
                                    0,
                                    15
                                ),
                            ],
                            'request_path'   => $request->getUri()->getPath(),
                            'request_method' => $request->getMethod(),
                        ]);
                    }
                );

                return $handler;
            },
            ApiClient::class                              => ApiClientFactory::class,
            Handler\HomeRedirectHandler::class            => static fn(ContainerInterface $c) => new Handler\HomeRedirectHandler(
                $c->get('config'),
            ),
            Handler\HomeHandler::class                    => static fn(ContainerInterface $c) => new Handler\HomeHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get('config'),
            ),
            Handler\LogoutHandler::class                  => static fn(ContainerInterface $c) => new Handler\LogoutHandler(
                $c->get('config'),
            ),
            Handler\LoginHandler::class                   => static fn(ContainerInterface $c) => new Handler\LoginHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(\App\Authentication\AuthenticationService::class),
                App\Feature::OneLogin->isEnabled(),
            ),
            Handler\OneLoginSignInHandler::class          => static fn(ContainerInterface $c) => new Handler\OneLoginSignInHandler(
                $c->get(\App\Service\OneLogin\OneLoginService::class),
                $c->get('config')['onelogin']['redirect_base_url'] ?? null,
            ),
            StatusHandler::class                          => static fn(ContainerInterface $c) => new StatusHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get('config'),
            ),
            Handler\PingHandlerJson::class                => static fn(ContainerInterface $c) => new Handler\PingHandlerJson(
                $c->get('config'),
                $c->get(StatusService::class),
            ),
            LpaApplicationService::class                  => LpaApplicationServiceFactory::class,
            CommunicationService::class                   => CommunicationFactory::class,
            GovPayClient::class                           => AlphagovPayClientFactory::class,
            ApplicantService::class                       => ApplicantFactory::class,
            ReplacementAttorneyCleanupService::class      => ReplacementAttorneyCleanupFactory::class,
            UserDetails::class                            => UserDetailsFactory::class,
            FeedbackService::class                        => FeedbackServiceFactory::class,
            GuidanceService::class                        => static fn() => new GuidanceService(
                getcwd() . '/content/guidance',
            ),
            OrdnanceSurveyService::class                  => OrdnanceSurveyFactory::class,
            StatusService::class                          => StatusServiceFactory::class,
            DynamoDbClient::class                         => DynamoDbClientFactory::class,
            SystemMessage::class                          => static function (ContainerInterface $c): SystemMessage {
                $config                    = $c->get('config');
                $dynamoConfig              = $config['admin']['dynamodb'];
                $dynamoConfig['keyPrefix'] = getenv('OPG_LPA_STACK_NAME') ?: 'local';
                $store                     = new DynamoDbKeyValueStore($dynamoConfig);
                $store->setDynamoDbClient($c->get(DynamoDbClient::class));
                return new SystemMessage($store);
            },
            RedisClient::class                            => RedisClientFactory::class,
            FilteringSaveHandler::class                   => SaveHandlerFactory::class,
            RegisterSessionSaveHandlerMiddleware::class   => static fn(ContainerInterface $c) => new RegisterSessionSaveHandlerMiddleware(
                $c->get(FilteringSaveHandler::class),
                $c->get('config')['session']['native_settings'] ?? [],
            ),
            AppMailTransportInterface::class              => MailTransportFactory::class,
            TermsAndConditionsMiddleware::class           => static fn(ContainerInterface $c) => new TermsAndConditionsMiddleware(
                $c->get('config'),
                $c->get(AuthenticationService::class),
                $c->get(UrlHelper::class),
            ),
            CsrfMiddleware::class                         => CsrfMiddlewareFactory::class,
            View\Twig\LegacyCompatExtension::class        => View\Twig\LegacyCompatExtensionFactory::class,
            AuthenticationService::class                  => AuthenticationServiceFactory::class,
            LoggerInterface::class                        => LoggerFactory::class,
            RequestLoggingMiddleware::class               => RequestLoggingMiddlewareFactory::class,
        ],
    ],
    'api_client'        => [
        'api_uri' => getenv('OPG_LPA_ENDPOINTS_API') ?: null,
    ],
    'onelogin'          => [
        'redirect_base_url' => getenv('ONELOGIN_REDIRECT_BASE_URL') ?: null,
    ],
    'alphagov'          => [
        'pay' => [
            'key' => getenv('OPG_LPA_FRONT_GOV_PAY_KEY') ?: null,
            'url' => getenv('OPG_LPA_FRONT_GOV_PAY_URL') ?: null,
        ],
    ],
    'processing-status' => [
        'track-from-date'                      => getenv('OPG_LPA_FRONT_TRACK_FROM_DATE') ?: '2019-04-01',
        'expected-working-days-before-receipt' => 15,
    ],
    'email'             => [
        'notify'              => [
            'key'                   => getenv('OPG_LPA_FRONT_EMAIL_NOTIFY_API_KEY') ?: null,
            'smokeTestEmailAddress' => 'simulate-delivered@notifications.service.gov.uk',
        ],
        'sendFeedbackEmailTo' => 'LPADigitalFeedback@PublicGuardian.gov.uk',
    ],
    'admin'             => [
        'dynamodb' => [
            'client'      => [
                'region'   => getenv('AWS_REGION') ?: 'eu-west-1',
                'version'  => '2012-08-10',
                'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
            ],
            'settings'    => [
                'table_name' => getenv('OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE') ?: 'lpa-properties-shared',
            ],
            'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
        ],
    ],
    'address'           => [
        'ordnancesurvey' => [
            'key'      => getenv('OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY') ?: null,
            'endpoint' => getenv('OPG_LPA_OS_PLACES_HUB_ENDPOINT') ?: 'https://api.os.uk/search/places/v1/postcode',
        ],
    ],
    'redis'             => [
        'url'             => getenv('OPG_LPA_COMMON_REDIS_CACHE_URL') ?: null,
        'ttlMs'           => (int) (getenv('OPG_LPA_COMMON_REDIS_CACHE_TTL_MS') ?: 10800000), // 3 hours, matching legacy app
        'ordnance_survey' => [
            'max_call_per_min' => 6,
        ],
    ],
    'logging'           => [
        'serviceName' => 'opg-lpa/front',
        'minLevel'    => Level::fromName('DEBUG'),
    ],
];

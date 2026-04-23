<?php

declare(strict_types=1);

namespace Application;

use Application\Form\AbstractCsrfForm;
use Application\Form\Element\CsrfBuilder;
use Application\Form\Factory\CsrfBuilderFactory;
use Application\Form\Error\FormLinkedErrors;
use Application\Handler\AboutYouHandler;
use Application\Handler\AccessibilityHandler;
use Application\Handler\ChangeEmailAddressHandler;
use Application\Handler\ChangePasswordHandler;
use Application\Handler\ConfirmRegistrationHandler;
use Application\Handler\ContactHandler;
use Application\Handler\CookiesHandler;
use Application\Handler\DashboardHandler;
use Application\Handler\DeleteAccountConfirmHandler;
use Application\Handler\DeleteAccountHandler;
use Application\Handler\DeletedAccountHandler;
use Application\Handler\EnableCookieHandler;
use Application\Handler\Factory\AboutYouHandlerFactory;
use Application\Handler\Factory\AccessibilityHandlerFactory;
use Application\Handler\Factory\ChangeEmailAddressHandlerFactory;
use Application\Handler\Factory\ChangePasswordHandlerFactory;
use Application\Handler\Factory\ConfirmRegistrationHandlerFactory;
use Application\Handler\Factory\ContactHandlerFactory;
use Application\Handler\Factory\CookiesHandlerFactory;
use Application\Handler\Factory\DashboardHandlerFactory;
use Application\Handler\Factory\DeleteAccountConfirmHandlerFactory;
use Application\Handler\Factory\DeleteAccountHandlerFactory;
use Application\Handler\Factory\DeletedAccountHandlerFactory;
use Application\Handler\Factory\EnableCookieHandlerFactory;
use Application\Handler\Factory\FeedbackHandlerFactory;
use Application\Handler\Factory\FeedbackThanksHandlerFactory;
use Application\Handler\Factory\ForgotPasswordHandlerFactory;
use Application\Handler\Factory\GuidanceHandlerFactory;
use Application\Handler\Factory\HomeHandlerFactory;
use Application\Handler\Factory\HomeRedirectHandlerFactory;
use Application\Handler\Factory\LoginHandlerFactory;
use Application\Handler\Factory\LogoutHandlerFactory;
use Application\Handler\Factory\Lpa\ApplicantHandlerFactory;
use Application\Handler\Factory\Lpa\CertificateProvider\CertificateProviderAddHandlerFactory;
use Application\Handler\Factory\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\CertificateProvider\CertificateProviderDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\CertificateProvider\CertificateProviderEditHandlerFactory;
use Application\Handler\Factory\Lpa\CertificateProvider\CertificateProviderHandlerFactory;
use Application\Handler\Factory\Lpa\CheckoutChequeHandlerFactory;
use Application\Handler\Factory\Lpa\CheckoutConfirmHandlerFactory;
use Application\Handler\Factory\Lpa\CheckoutIndexHandlerFactory;
use Application\Handler\Factory\Lpa\CheckoutPayHandlerFactory;
use Application\Handler\Factory\Lpa\CheckoutPayResponseHandlerFactory;
use Application\Handler\Factory\Lpa\CompleteIndexHandlerFactory;
use Application\Handler\Factory\Lpa\CompleteViewDocsHandlerFactory;
use Application\Handler\Factory\Lpa\ConfirmDeleteLpaHandlerFactory;
use Application\Handler\Factory\Lpa\CorrespondentEditHandlerFactory;
use Application\Handler\Factory\Lpa\CorrespondentHandlerFactory;
use Application\Handler\Factory\Lpa\CreateLpaHandlerFactory;
use Application\Handler\Factory\Lpa\DateCheckHandlerFactory;
use Application\Handler\Factory\Lpa\DateCheckValidHandlerFactory;
use Application\Handler\Factory\Lpa\DeleteLpaHandlerFactory;
use Application\Handler\Factory\Lpa\DonorAddHandlerFactory;
use Application\Handler\Factory\Lpa\DonorEditHandlerFactory;
use Application\Handler\Factory\Lpa\DonorIndexHandlerFactory;
use Application\Handler\Factory\Lpa\Download\DownloadCheckHandlerFactory;
use Application\Handler\Factory\Lpa\Download\DownloadFileHandlerFactory;
use Application\Handler\Factory\Lpa\Download\DownloadHandlerFactory;
use Application\Handler\Factory\Lpa\FeeReductionHandlerFactory;
use Application\Handler\Factory\Lpa\HowPrimaryAttorneysMakeDecisionHandlerFactory;
use Application\Handler\Factory\Lpa\HowReplacementAttorneysMakeDecisionHandlerFactory;
use Application\Handler\Factory\Lpa\IndexHandlerFactory;
use Application\Handler\Factory\Lpa\InstructionsHandlerFactory;
use Application\Handler\Factory\Lpa\LifeSustainingHandlerFactory;
use Application\Handler\Factory\Lpa\MoreInfoRequiredHandlerFactory;
use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyAddHandlerFactory;
use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyEditHandlerFactory;
use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyEditHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorneyHandlerFactory;
use Application\Handler\Factory\Lpa\RepeatApplicationHandlerFactory;
use Application\Handler\Factory\Lpa\ReplacementAttorneyAddHandlerFactory;
use Application\Handler\Factory\Lpa\ReplacementAttorneyAddTrustHandlerFactory;
use Application\Handler\Factory\Lpa\ReplacementAttorneyConfirmDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\ReplacementAttorneyDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\ReplacementAttorneyEditHandlerFactory;
use Application\Handler\Factory\Lpa\ReplacementAttorneyIndexHandlerFactory;
use Application\Handler\Factory\Lpa\ReuseDetailsHandlerFactory;
use Application\Handler\Factory\Lpa\StatusHandlerFactory;
use Application\Handler\Factory\Lpa\SummaryHandlerFactory;
use Application\Handler\Factory\Lpa\WhenLpaStartsHandlerFactory;
use Application\Handler\Factory\Lpa\WhenReplacementAttorneyStepInHandlerFactory;
use Application\Handler\Factory\Lpa\WhoAreYouHandlerFactory;
use Application\Handler\Factory\LpaTypeHandlerFactory;
use Application\Handler\Factory\PingHandlerFactory;
use Application\Handler\Factory\PingHandlerJsonFactory;
use Application\Handler\Factory\PingHandlerPingdomFactory;
use Application\Handler\Factory\PostcodeHandlerFactory;
use Application\Handler\Factory\PrivacyHandlerFactory;
use Application\Handler\Factory\RegisterHandlerFactory;
use Application\Handler\Factory\ResendActivationEmailHandlerFactory;
use Application\Handler\Factory\ResetPasswordHandlerFactory;
use Application\Handler\Factory\SessionExpiryHandlerFactory;
use Application\Handler\ResetPasswordHandler;
use Application\Handler\Factory\SessionKeepAliveHandlerFactory;
use Application\Handler\Factory\SessionSetExpiryHandlerFactory;
use Application\Handler\Factory\StatsHandlerFactory;
use Application\Handler\Factory\StatusesHandlerFactory;
use Application\Handler\Factory\TermsChangedHandlerFactory;
use Application\Handler\Factory\TermsHandlerFactory;
use Application\Handler\Factory\TypeHandlerFactory;
use Application\Handler\FeedbackHandler;
use Application\Handler\FeedbackThanksHandler;
use Application\Handler\ForgotPasswordHandler;
use Application\Handler\GuidanceHandler;
use Application\Handler\HomeHandler;
use Application\Handler\HomeRedirectHandler;
use Application\Handler\LoginHandler;
use Application\Handler\LogoutHandler;
use Application\Handler\Lpa\ApplicantHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderAddHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderEditHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderHandler;
use Application\Handler\Lpa\CheckoutChequeHandler;
use Application\Handler\Lpa\CheckoutConfirmHandler;
use Application\Handler\Lpa\CheckoutIndexHandler;
use Application\Handler\Lpa\CheckoutPayHandler;
use Application\Handler\Lpa\CheckoutPayResponseHandler;
use Application\Handler\Lpa\CompleteIndexHandler;
use Application\Handler\Lpa\CompleteViewDocsHandler;
use Application\Handler\Lpa\ConfirmDeleteLpaHandler;
use Application\Handler\Lpa\CorrespondentEditHandler;
use Application\Handler\Lpa\CorrespondentHandler;
use Application\Handler\Lpa\CreateLpaHandler;
use Application\Handler\Lpa\DateCheckHandler;
use Application\Handler\Lpa\DateCheckValidHandler;
use Application\Handler\Lpa\DeleteLpaHandler;
use Application\Handler\Lpa\DonorAddHandler;
use Application\Handler\Lpa\DonorEditHandler;
use Application\Handler\Lpa\DonorIndexHandler;
use Application\Handler\Lpa\Download\DownloadCheckHandler;
use Application\Handler\Lpa\Download\DownloadFileHandler;
use Application\Handler\Lpa\Download\DownloadHandler;
use Application\Handler\Lpa\FeeReductionHandler;
use Application\Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler;
use Application\Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler;
use Application\Handler\Lpa\IndexHandler;
use Application\Handler\Lpa\InstructionsHandler;
use Application\Handler\Lpa\LifeSustainingHandler;
use Application\Handler\Lpa\MoreInfoRequiredHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyHandler as PeopleToNotifyIndexHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyEditHandler;
use Application\Handler\Lpa\PrimaryAttorneyHandler;
use Application\Handler\Lpa\RepeatApplicationHandler;
use Application\Handler\Lpa\ReplacementAttorneyAddHandler;
use Application\Handler\Lpa\ReplacementAttorneyAddTrustHandler;
use Application\Handler\Lpa\ReplacementAttorneyConfirmDeleteHandler;
use Application\Handler\Lpa\ReplacementAttorneyDeleteHandler;
use Application\Handler\Lpa\ReplacementAttorneyEditHandler;
use Application\Handler\Lpa\ReplacementAttorneyIndexHandler;
use Application\Handler\Lpa\ReuseDetailsHandler;
use Application\Handler\Lpa\StatusHandler;
use Application\Handler\Lpa\SummaryHandler;
use Application\Handler\Lpa\WhenLpaStartsHandler;
use Application\Handler\Lpa\WhenReplacementAttorneyStepInHandler;
use Application\Handler\Lpa\WhoAreYouHandler;
use Application\Handler\LpaTypeHandler;
use Application\Handler\PingHandler;
use Application\Handler\PingHandlerJson;
use Application\Handler\PingHandlerPingdom;
use Application\Handler\PostcodeHandler;
use Application\Handler\PrivacyHandler;
use Application\Handler\RegisterHandler;
use Application\Handler\ResendActivationEmailHandler;
use Application\Handler\SessionExpiryHandler;
use Application\Handler\SessionKeepAliveHandler;
use Application\Handler\SessionSetExpiryHandler;
use Application\Handler\StatsHandler;
use Application\Handler\StatusesHandler;
use Application\Handler\TermsChangedHandler;
use Application\Handler\TermsHandler;
use Application\Handler\TypeHandler;
use Application\Handler\VerifyEmailAddressHandler;
use Application\Middleware\AuthenticationMiddleware;
use Application\Middleware\Factory\AuthenticationMiddlewareFactory;
use Application\Middleware\Factory\IdentityTokenRefreshMiddlewareFactory;
use Application\Middleware\Factory\LpaLoaderMiddlewareFactory;
use Application\Middleware\Factory\RequestLoggingMiddlewareFactory;
use Application\Middleware\Factory\SessionBootstrapMiddlewareFactory;
use Application\Middleware\Factory\TermsAndConditionsMiddlewareFactory;
use Application\Middleware\Factory\UserDetailsMiddlewareFactory;
use Application\Middleware\IdentityTokenRefreshMiddleware;
use Application\Middleware\LpaLoaderMiddleware;
use Application\Middleware\RequestLoggingMiddleware;
use Application\Middleware\RouteMatchMiddleware;
use Application\Middleware\SessionBootstrapMiddleware;
use Application\Middleware\TermsAndConditionsMiddleware;
use Application\Middleware\TrailingSlashMiddleware;
use Application\Middleware\UserDetailsMiddleware;
use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Model\Service\AddressLookup\OrdnanceSurveyFactory;
use Application\Model\Service\ApiClient\ClientFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\AuthenticationServiceFactory;
use Application\Model\Service\Date\DateService;
use Application\Model\Service\Date\IDateService;
use Application\Model\Service\Lpa\ContinuationSheets;
use Application\Model\Service\Mail\Transport\MailTransportFactory;
use Application\Model\Service\Redis\RedisClient;
use Application\Model\Service\Session\NativeSessionConfig;
use Application\Model\Service\Session\SessionFactory;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\Session\WritePolicy;
use Application\Service\AccordionService;
use Application\Service\CompleteViewParamsHelper;
use Application\Service\DateCheckViewModelHelper;
use Application\Service\Factory\AccordionServiceFactory;
use Application\Service\Factory\ActorReuseDetailsServiceFactory;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Service\Factory\AppFiltersExtensionFactory;
use Application\Service\Factory\AppFunctionsExtensionFactory;
use Application\Service\Factory\CompleteViewParamsHelperFactory;
use Application\Service\Factory\DynamoDbSystemMessageCacheFactory;
use Application\Service\Factory\DynamoDbSystemMessageClientFactory;
use Application\Service\Factory\FormLinkedErrorsFactory;
use Application\Service\Factory\GovPayClientFactory;
use Application\Service\Factory\HttpClientFactory;
use Application\Service\Factory\LpaAuthAdapterFactory;
use Application\Service\Factory\NavigationViewModelHelperFactory;
use Application\Service\Factory\NativeSessionConfigFactory;
use Application\Service\Factory\RedisClientFactory;
use Application\Service\Factory\SaveHandlerFactory;
use Application\Service\Factory\SessionManagerSupportFactory;
use Application\Service\Factory\SessionMiddlewareFactory;
use Application\Service\Factory\SystemMessageFactory;
use Application\Service\Factory\TelemetryTracerFactory;
use Application\Service\Factory\TwigViewRendererFactory;
use Application\Service\Factory\WritePolicyFactory;
use Application\Service\NavigationViewModelHelper;
use Application\Service\SystemMessage;
use Application\View\Twig\AppFiltersExtension;
use Application\View\Twig\AppFunctionsExtension;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Session\SessionManager;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\Logging\LoggerFactory;
use MakeShared\Telemetry\Exporter\ExporterFactory;
use MakeShared\Telemetry\Tracer;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Twig\TwigRenderer;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Mezzio ConfigProvider for the Application module.
 *
 * Consolidates all service, middleware, and handler registrations that were
 * previously split across Module::getServiceConfig(), Module::getFormElementConfig(),
 * and the service_manager section of module.config.php.
 *
 * Register this provider in mezzio/config/config.php via the ConfigAggregator.
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'  => $this->getDependencies(),
            'form_elements' => $this->getFormElementConfig(),
            'templates'     => $this->getTemplates(),
        ];
    }

    /**
     * Container dependency configuration.
     *
     * Sourced from Module::getServiceConfig() (primary) and the
     * service_manager key in module.config.php (secondary).
     */
    public function getDependencies(): array
    {
        return [
            // HttpClient must not be shared so each consumer gets a fresh instance.
            'shared' => [
                'HttpClient' => false,
            ],

            'aliases' => [
                OrdnanceSurvey::class        => 'OrdnanceSurvey',
                AuthenticationService::class => 'AuthenticationService',
                IDateService::class          => DateService::class,
                // Bind Mezzio's TemplateRendererInterface to the Twig implementation.
                TemplateRendererInterface::class => TwigRenderer::class,
                // Ensure requests for the FQCN key resolve to our custom SessionFactory
                // rather than Laminas\Session\Module's built-in SessionManagerFactory,
                // which requires a 'session_config' key we don't provide.
                SessionManager::class        => 'SessionManager',
                // String aliases from module.config.php service_manager — allow code
                // that still uses short string keys to resolve the correct FQCN service.
                'AdminService'               => 'Application\Model\Service\Admin\Admin',
                'ApplicantService'           => 'Application\Model\Service\Lpa\Applicant',
                'Communication'              => 'Application\Model\Service\Lpa\Communication',
                'Feedback'                   => 'Application\Model\Service\Feedback\Feedback',
                'Guidance'                   => 'Application\Model\Service\Guidance\Guidance',
                'LpaApplicationService'      => 'Application\Model\Service\Lpa\Application',
                'Metadata'                   => 'Application\Model\Service\Lpa\Metadata',
                'ReplacementAttorneyCleanup' => 'Application\Model\Service\Lpa\ReplacementAttorneyCleanup',
                'SiteStatus'                 => 'Application\Model\Service\System\Status',
                'StatsService'               => 'Application\Model\Service\Stats\Stats',
                'UserService'                => 'Application\Model\Service\User\Details',
            ],

            'abstract_factories' => [
                // Resolves Application\Model\Service\* services generically.
                \Application\Model\Service\ServiceAbstractFactory::class,
                // Provides Laminas\Cache storage adapters.
                \Laminas\Cache\Service\StorageCacheAbstractServiceFactory::class,
                // Auto-wires any service whose constructor dependencies are all
                // resolvable from the container (fallback for unregistered services).
                ReflectionBasedAbstractFactory::class,
            ],

            'factories' => [
                // ----------------------------------------------------------------
                // Infrastructure / third-party clients
                // ----------------------------------------------------------------
                'HttpClient'                  => HttpClientFactory::class,
                'GovPayClient'                => GovPayClientFactory::class,
                'DynamoDbSystemMessageClient' => DynamoDbSystemMessageClientFactory::class,
                'DynamoDbSystemMessageCache'  => DynamoDbSystemMessageCacheFactory::class,
                RedisClient::class            => RedisClientFactory::class,

                // ----------------------------------------------------------------
                // Telemetry
                // ----------------------------------------------------------------
                ExporterFactory::class => ReflectionBasedAbstractFactory::class,
                'TelemetryTracer'      => TelemetryTracerFactory::class,
                Tracer::class          => TelemetryTracerFactory::class,

                // ----------------------------------------------------------------
                // Logging
                // ----------------------------------------------------------------
                'Logger'               => LoggerFactory::class,
                LoggerInterface::class => LoggerFactory::class,

                // ----------------------------------------------------------------
                // API / authentication
                // ----------------------------------------------------------------
                'ApiClient'             => ClientFactory::class,
                'AuthenticationService' => AuthenticationServiceFactory::class,
                'LpaAuthAdapter'        => LpaAuthAdapterFactory::class,
                'MailTransport'         => MailTransportFactory::class,
                'OrdnanceSurvey'        => OrdnanceSurveyFactory::class,

                // ----------------------------------------------------------------
                // Session
                // ----------------------------------------------------------------
                'SessionManager'           => SessionFactory::class,
                SessionUtility::class      => InvokableFactory::class,
                SessionManagerSupport::class => SessionManagerSupportFactory::class,
                SessionMiddleware::class   => SessionMiddlewareFactory::class,
                WritePolicy::class         => WritePolicyFactory::class,
                'SaveHandler'              => SaveHandlerFactory::class,
                NativeSessionConfig::class => NativeSessionConfigFactory::class,

                // ----------------------------------------------------------------
                // Application services
                // ----------------------------------------------------------------
                CsrfBuilder::class               => CsrfBuilderFactory::class,
                DateService::class               => InvokableFactory::class,
                ContinuationSheets::class        => InvokableFactory::class,
                'Calculator'                     => InvokableFactory::class,
                Calculator::class                => InvokableFactory::class,
                AccordionService::class          => AccordionServiceFactory::class,
                CompleteViewParamsHelper::class  => CompleteViewParamsHelperFactory::class,
                // No dedicated factory; constructor dependencies are all auto-wireable.
                DateCheckViewModelHelper::class  => ReflectionBasedAbstractFactory::class,
                NavigationViewModelHelper::class => NavigationViewModelHelperFactory::class,
                SystemMessage::class             => SystemMessageFactory::class,
                ActorReuseDetailsService::class  => ActorReuseDetailsServiceFactory::class,
                FormLinkedErrors::class          => FormLinkedErrorsFactory::class,

                // ----------------------------------------------------------------
                // Twig extensions
                // ----------------------------------------------------------------
                'TwigViewRenderer'           => TwigViewRendererFactory::class,
                AppFiltersExtension::class   => AppFiltersExtensionFactory::class,
                AppFunctionsExtension::class => AppFunctionsExtensionFactory::class,

                // ----------------------------------------------------------------
                // Middleware
                // ----------------------------------------------------------------
                TrailingSlashMiddleware::class              => InvokableFactory::class,
                RequestLoggingMiddleware::class             => RequestLoggingMiddlewareFactory::class,
                SessionBootstrapMiddleware::class           => SessionBootstrapMiddlewareFactory::class,
                IdentityTokenRefreshMiddleware::class       => IdentityTokenRefreshMiddlewareFactory::class,
                AuthenticationMiddleware::class             => AuthenticationMiddlewareFactory::class,
                UserDetailsMiddleware::class                => UserDetailsMiddlewareFactory::class,
                TermsAndConditionsMiddleware::class         => TermsAndConditionsMiddlewareFactory::class,
                // TODO(mezzio): LpaLoaderMiddlewareFactory currently injects MvcUrlHelper
                // which wraps Laminas MVC's RouteStackInterface. Before enabling this in
                // production Mezzio, update LpaLoaderMiddleware to accept
                // Mezzio\Helper\UrlHelper and update LpaLoaderMiddlewareFactory accordingly.
                LpaLoaderMiddleware::class          => LpaLoaderMiddlewareFactory::class,
                RouteMatchMiddleware::class         => InvokableFactory::class,

                // ----------------------------------------------------------------
                // Handlers — general / public
                // ----------------------------------------------------------------
                PingHandler::class         => PingHandlerFactory::class,
                PingHandlerJson::class     => PingHandlerJsonFactory::class,
                PingHandlerPingdom::class  => PingHandlerPingdomFactory::class,
                PostcodeHandler::class     => PostcodeHandlerFactory::class,
                HomeHandler::class         => HomeHandlerFactory::class,
                HomeRedirectHandler::class => HomeRedirectHandlerFactory::class,
                AccessibilityHandler::class => AccessibilityHandlerFactory::class,
                CookiesHandler::class      => CookiesHandlerFactory::class,
                EnableCookieHandler::class => EnableCookieHandlerFactory::class,
                ContactHandler::class      => ContactHandlerFactory::class,
                FeedbackHandler::class     => FeedbackHandlerFactory::class,
                FeedbackThanksHandler::class => FeedbackThanksHandlerFactory::class,
                GuidanceHandler::class     => GuidanceHandlerFactory::class,
                PrivacyHandler::class      => PrivacyHandlerFactory::class,
                TermsHandler::class        => TermsHandlerFactory::class,
                StatsHandler::class        => StatsHandlerFactory::class,
                StatusesHandler::class     => StatusesHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — authentication
                // ----------------------------------------------------------------
                LoginHandler::class                  => LoginHandlerFactory::class,
                LogoutHandler::class                 => LogoutHandlerFactory::class,
                RegisterHandler::class               => RegisterHandlerFactory::class,
                ForgotPasswordHandler::class         => ForgotPasswordHandlerFactory::class,
                ResendActivationEmailHandler::class  => ResendActivationEmailHandlerFactory::class,
                ConfirmRegistrationHandler::class    => ConfirmRegistrationHandlerFactory::class,
                ResetPasswordHandler::class          => ResetPasswordHandlerFactory::class,
                // No dedicated factory exists; ReflectionBasedAbstractFactory auto-wires this.
                VerifyEmailAddressHandler::class     => ReflectionBasedAbstractFactory::class,

                // ----------------------------------------------------------------
                // Handlers — account management
                // ----------------------------------------------------------------
                AboutYouHandler::class             => AboutYouHandlerFactory::class,
                ChangeEmailAddressHandler::class   => ChangeEmailAddressHandlerFactory::class,
                ChangePasswordHandler::class       => ChangePasswordHandlerFactory::class,
                DeleteAccountHandler::class        => DeleteAccountHandlerFactory::class,
                DeleteAccountConfirmHandler::class => DeleteAccountConfirmHandlerFactory::class,
                DeletedAccountHandler::class       => DeletedAccountHandlerFactory::class,
                TermsChangedHandler::class         => TermsChangedHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — session
                // ----------------------------------------------------------------
                SessionExpiryHandler::class    => SessionExpiryHandlerFactory::class,
                SessionKeepAliveHandler::class => SessionKeepAliveHandlerFactory::class,
                SessionSetExpiryHandler::class => SessionSetExpiryHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA top-level
                // ----------------------------------------------------------------
                LpaTypeHandler::class          => LpaTypeHandlerFactory::class,
                TypeHandler::class             => TypeHandlerFactory::class,
                DashboardHandler::class        => DashboardHandlerFactory::class,
                IndexHandler::class            => IndexHandlerFactory::class,
                CreateLpaHandler::class        => CreateLpaHandlerFactory::class,
                DeleteLpaHandler::class        => DeleteLpaHandlerFactory::class,
                ConfirmDeleteLpaHandler::class => ConfirmDeleteLpaHandlerFactory::class,
                ApplicantHandler::class        => ApplicantHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA donor
                // ----------------------------------------------------------------
                DonorIndexHandler::class => DonorIndexHandlerFactory::class,
                DonorAddHandler::class   => DonorAddHandlerFactory::class,
                DonorEditHandler::class  => DonorEditHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA primary attorneys
                // ----------------------------------------------------------------
                PrimaryAttorneyHandler::class              => PrimaryAttorneyHandlerFactory::class,
                PrimaryAttorneyAddHandler::class           => PrimaryAttorneyAddHandlerFactory::class,
                PrimaryAttorneyAddTrustHandler::class      => PrimaryAttorneyAddTrustHandlerFactory::class,
                PrimaryAttorneyEditHandler::class          => PrimaryAttorneyEditHandlerFactory::class,
                PrimaryAttorneyConfirmDeleteHandler::class => PrimaryAttorneyConfirmDeleteHandlerFactory::class,
                PrimaryAttorneyDeleteHandler::class        => PrimaryAttorneyDeleteHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA replacement attorneys
                // ----------------------------------------------------------------
                ReplacementAttorneyIndexHandler::class        => ReplacementAttorneyIndexHandlerFactory::class,
                ReplacementAttorneyAddHandler::class          => ReplacementAttorneyAddHandlerFactory::class,
                ReplacementAttorneyAddTrustHandler::class     => ReplacementAttorneyAddTrustHandlerFactory::class,
                ReplacementAttorneyEditHandler::class         => ReplacementAttorneyEditHandlerFactory::class,
                ReplacementAttorneyConfirmDeleteHandler::class => ReplacementAttorneyConfirmDeleteHandlerFactory::class,
                ReplacementAttorneyDeleteHandler::class       => ReplacementAttorneyDeleteHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA certificate provider
                // ----------------------------------------------------------------
                CertificateProviderHandler::class              => CertificateProviderHandlerFactory::class,
                CertificateProviderAddHandler::class           => CertificateProviderAddHandlerFactory::class,
                CertificateProviderEditHandler::class          => CertificateProviderEditHandlerFactory::class,
                CertificateProviderConfirmDeleteHandler::class => CertificateProviderConfirmDeleteHandlerFactory::class,
                CertificateProviderDeleteHandler::class        => CertificateProviderDeleteHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA people to notify
                // ----------------------------------------------------------------
                PeopleToNotifyIndexHandler::class        => PeopleToNotifyHandlerFactory::class,
                PeopleToNotifyAddHandler::class          => PeopleToNotifyAddHandlerFactory::class,
                PeopleToNotifyEditHandler::class         => PeopleToNotifyEditHandlerFactory::class,
                PeopleToNotifyConfirmDeleteHandler::class => PeopleToNotifyConfirmDeleteHandlerFactory::class,
                PeopleToNotifyDeleteHandler::class       => PeopleToNotifyDeleteHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA decisions / preferences
                // ----------------------------------------------------------------
                LifeSustainingHandler::class                      => LifeSustainingHandlerFactory::class,
                HowPrimaryAttorneysMakeDecisionHandler::class     => HowPrimaryAttorneysMakeDecisionHandlerFactory::class,
                HowReplacementAttorneysMakeDecisionHandler::class => HowReplacementAttorneysMakeDecisionHandlerFactory::class,
                WhenReplacementAttorneyStepInHandler::class       => WhenReplacementAttorneyStepInHandlerFactory::class,
                WhenLpaStartsHandler::class                       => WhenLpaStartsHandlerFactory::class,
                MoreInfoRequiredHandler::class                    => MoreInfoRequiredHandlerFactory::class,
                InstructionsHandler::class                        => InstructionsHandlerFactory::class,
                FeeReductionHandler::class                        => FeeReductionHandlerFactory::class,
                RepeatApplicationHandler::class                   => RepeatApplicationHandlerFactory::class,
                ReuseDetailsHandler::class                        => ReuseDetailsHandlerFactory::class,
                WhoAreYouHandler::class                           => WhoAreYouHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA date check / summary / completion
                // ----------------------------------------------------------------
                DateCheckHandler::class      => DateCheckHandlerFactory::class,
                DateCheckValidHandler::class => DateCheckValidHandlerFactory::class,
                SummaryHandler::class        => SummaryHandlerFactory::class,
                StatusHandler::class         => StatusHandlerFactory::class,
                CompleteIndexHandler::class  => CompleteIndexHandlerFactory::class,
                CompleteViewDocsHandler::class => CompleteViewDocsHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA correspondent
                // ----------------------------------------------------------------
                CorrespondentHandler::class     => CorrespondentHandlerFactory::class,
                CorrespondentEditHandler::class => CorrespondentEditHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA checkout
                // ----------------------------------------------------------------
                CheckoutIndexHandler::class       => CheckoutIndexHandlerFactory::class,
                CheckoutChequeHandler::class      => CheckoutChequeHandlerFactory::class,
                CheckoutConfirmHandler::class     => CheckoutConfirmHandlerFactory::class,
                CheckoutPayHandler::class         => CheckoutPayHandlerFactory::class,
                CheckoutPayResponseHandler::class => CheckoutPayResponseHandlerFactory::class,

                // ----------------------------------------------------------------
                // Handlers — LPA download
                // ----------------------------------------------------------------
                DownloadHandler::class      => DownloadHandlerFactory::class,
                DownloadCheckHandler::class => DownloadCheckHandlerFactory::class,
                DownloadFileHandler::class  => DownloadFileHandlerFactory::class,
            ],

            // Injects a logger into any service implementing LoggerAwareInterface.
            // Consider removing this in favour of explicit constructor injection
            // as handlers are migrated fully to Mezzio.
            'initializers' => [
                function (ContainerInterface $container, object $instance): void {
                    if ($instance instanceof LoggerAwareInterface) {
                        $instance->setLogger($container->get(LoggerInterface::class));
                    }
                },
            ],
        ];
    }

    /**
     * FormElementManager configuration.
     *
     * Injects CsrfBuilder into any AbstractCsrfForm at construction time.
     * Sourced from Module::getFormElementConfig().
     */
    public function getFormElementConfig(): array
    {
        return [
            'initializers' => [
                'InitCsrfForm' => static function (\Laminas\ServiceManager\ServiceManager $serviceManager, object $form): void {
                    if ($form instanceof AbstractCsrfForm) {
                        $form->setCsrf($serviceManager->get(CsrfBuilder::class));
                    }
                },
            ],
        ];
    }

    /**
     * Twig template path configuration.
     *
     * Sourced from the templates key in module.config.php.
     * The Mezzio ConfigAggregator merges this with paths registered by
     * other providers (e.g. App\ConfigProvider).
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'application' => [__DIR__ . '/../view/application'],
            ],
        ];
    }
}

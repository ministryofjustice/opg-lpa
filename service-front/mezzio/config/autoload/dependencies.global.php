<?php

declare(strict_types=1);

use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Authentication\Adapter\LpaAuthAdapter;
use App\Authentication\AuthenticationService;
use App\Authentication\AuthenticationServiceFactory;
use App\Service\AddressLookup\OrdnanceSurveyFactory;
use App\Service\ApiClient\ApiClientFactory;
use App\Adapter\DynamoDbKeyValueStore;
use App\Service\DynamoDbClientFactory;
use App\Service\Feedback\FeedbackServiceFactory;
use App\Service\SystemMessage;
use App\Service\Lpa\ActorReuseDetailsService;
use App\Service\Lpa\ApplicantFactory;
use App\Service\Lpa\CommunicationFactory;
use App\Service\Lpa\MetadataFactory;
use App\Service\Lpa\ReplacementAttorneyCleanupFactory;
use App\Service\Payment\AlphagovPayClientFactory;
use App\Service\Redis\RedisClientFactory;
use App\Service\Session\FilteringSaveHandler;
use App\Service\Session\SaveHandlerFactory;
use App\Service\System\StatusServiceFactory;
use App\Service\Lpa\Communication as CommunicationService;
use App\Service\Lpa\Metadata as LpaMetadata;
use App\Service\Mail\Transport\MailTransportInterface as AppMailTransportInterface;
use App\Handler;
use App\Handler\Lpa\CompleteViewDocsHandler;
use App\Handler\Lpa\WhoAreYouHandler;
use App\Handler\Lpa\WhenLpaStartsHandler;
use App\Handler\Lpa\WhenReplacementAttorneyStepInHandler;
use App\Handler\Lpa\ReuseDetailsHandler;
use App\Handler\Lpa\StatusHandler;
use App\Handler\Lpa\Download\DownloadHandler;
use App\Handler\Lpa\Download\DownloadFileHandler;
use App\Handler\Lpa\Download\DownloadCheckHandler;
use App\Handler\Lpa\FeeReductionHandler;
use App\Handler\Lpa\RepeatApplicationHandler;
use App\Handler\Lpa\ReplacementAttorneyAddHandler;
use App\Handler\Lpa\ReplacementAttorneyAddTrustHandler;
use App\Handler\Lpa\ReplacementAttorneyConfirmDeleteHandler;
use App\Handler\Lpa\ReplacementAttorneyDeleteHandler;
use App\Handler\Lpa\ReplacementAttorneyEditHandler;
use App\Handler\Lpa\ReplacementAttorneyIndexHandler;
use App\Handler\Lpa\PrimaryAttorneyHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandler;
use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyEditHandler;
use App\Form;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\AuthenticationMiddlewareFactory;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\FlashMessagesHolderMiddleware;
use App\Middleware\IdentityTokenRefreshMiddleware;
use App\Middleware\IdentityTokenRefreshMiddlewareFactory;
use App\Middleware\LpaLoaderMiddleware;
use App\Middleware\PersistentSessionDetailsMiddleware;
use App\Middleware\RegisterSessionSaveHandlerMiddleware;
use App\Middleware\RouteNameMiddleware;
use App\Middleware\TermsAndConditionsMiddleware;
use App\Middleware\UserDetailsMiddleware;
use App\Middleware\UserDetailsMiddlewareFactory;
use App\Model\UserDetailsHolder;
use App\Model\FlashMessagesHolder;
use App\Model\Service\Session\PersistentSessionDetails;
use App\Service\AddressLookup\OrdnanceSurvey as OrdnanceSurveyService;
use App\Service\Date\DateService;
use App\Service\Feedback\FeedbackService;
use App\Service\Guidance\GuidanceService;
use App\Service\CompleteViewParamsHelper;
use App\Service\LpaApplicationServiceFactory;
use App\Service\Lpa\ContinuationSheets as LpaContinuationSheets;
use App\Service\Mail\Transport\MailTransportFactory;
use App\Service\Redis\RedisClient;
use App\Service\Stats\StatsService;
use App\Service\System\StatusService;
use App\Service\UserDetails;
use App\Service\UserDetailsFactory;
use App\Storage\MezzioSessionStorage;
use App\View;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\Lpa\Applicant as ApplicantService;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup as ReplacementAttorneyCleanupService;
use Aws\DynamoDb\DynamoDbClient;
use Laminas\EventManager\EventManager;
use Laminas\Form\FormElementManager;
use MakeShared\Logging\LoggerFactory;
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\CsrfMiddlewareFactory;
use Mezzio\Csrf\SessionCsrfGuardFactory;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Monolog\Level;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    'form_elements' => [
        'invokables' => [
            Form\Lpa\AbstractLpaForm::class              => Form\Lpa\AbstractLpaForm::class,
            Form\Lpa\AbstractActorForm::class            => Form\Lpa\AbstractActorForm::class,
            Form\Lpa\AbstractMainFlowForm::class         => Form\Lpa\AbstractMainFlowForm::class,
            Form\Lpa\BlankMainFlowForm::class            => Form\Lpa\BlankMainFlowForm::class,
            Form\Lpa\AttorneyForm::class                 => Form\Lpa\AttorneyForm::class,
            Form\Lpa\TrustCorporationForm::class         => Form\Lpa\TrustCorporationForm::class,
            Form\Lpa\DonorForm::class                    => Form\Lpa\DonorForm::class,
            Form\Lpa\CertificateProviderForm::class      => Form\Lpa\CertificateProviderForm::class,
            Form\Lpa\PeopleToNotifyForm::class           => Form\Lpa\PeopleToNotifyForm::class,
            Form\Lpa\CorrespondentForm::class            => Form\Lpa\CorrespondentForm::class,
            Form\Lpa\CorrespondenceForm::class           => Form\Lpa\CorrespondenceForm::class,
            Form\Lpa\TypeForm::class                     => Form\Lpa\TypeForm::class,
            Form\Lpa\WhoAreYouForm::class                => Form\Lpa\WhoAreYouForm::class,
            Form\Lpa\HowAttorneysMakeDecisionForm::class => Form\Lpa\HowAttorneysMakeDecisionForm::class,
            Form\Lpa\WhenLpaStartsForm::class            => Form\Lpa\WhenLpaStartsForm::class,
            Form\Lpa\FeeReductionForm::class             => Form\Lpa\FeeReductionForm::class,
            Form\Lpa\RepeatApplicationForm::class        => Form\Lpa\RepeatApplicationForm::class,
            Form\Lpa\ReuseDetailsForm::class             => Form\Lpa\ReuseDetailsForm::class,
            Form\Lpa\InstructionsAndPreferencesForm::class => Form\Lpa\InstructionsAndPreferencesForm::class,
            Form\Lpa\LifeSustainingForm::class           => Form\Lpa\LifeSustainingForm::class,
            Form\Lpa\WhenReplacementAttorneyStepInForm::class => Form\Lpa\WhenReplacementAttorneyStepInForm::class,
            Form\Lpa\ApplicantForm::class                => Form\Lpa\ApplicantForm::class,
            Form\Lpa\DateCheckForm::class                => Form\Lpa\DateCheckForm::class,
            Form\General\CookieConsentForm::class        => Form\General\CookieConsentForm::class,
            Form\General\FeedbackForm::class             => Form\General\FeedbackForm::class,
            Form\User\AboutYou::class                    => Form\User\AboutYou::class,
            Form\Fieldset\Dob::class                     => Form\Fieldset\Dob::class,
            Form\Fieldset\Correspondence::class          => Form\Fieldset\Correspondence::class,
        ],
    ],

    'dependencies' => [
        'aliases' => [
            'Communication' => CommunicationService::class,
            'GovPayClient'  => GovPayClient::class,
            'EventManager'  => EventManager::class,
        ],
        'invokables' => [
            EventManager::class       => EventManager::class,
            RouteNameMiddleware::class => RouteNameMiddleware::class,
        ],
        'factories' => [
            MezzioSessionStorage::class => static fn() => new MezzioSessionStorage(),
            ApiClient::class            => ApiClientFactory::class,
            PersistentSessionDetails::class => static fn() => new PersistentSessionDetails(),
            UserDetailsHolder::class => static fn() => new UserDetailsHolder(),
            FlashMessagesHolder::class => static fn() => new FlashMessagesHolder(),

            Handler\HomeRedirectHandler::class => static fn(ContainerInterface $c) => new Handler\HomeRedirectHandler(
                $c->get('config'),
            ),
            Handler\TermsHandler::class => static fn(ContainerInterface $c) => new Handler\TermsHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\AccessibilityHandler::class => static fn(ContainerInterface $c) => new Handler\AccessibilityHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\PrivacyHandler::class => static fn(ContainerInterface $c) => new Handler\PrivacyHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\ContactHandler::class => static fn(ContainerInterface $c) => new Handler\ContactHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\EnableCookieHandler::class => static fn(ContainerInterface $c) => new Handler\EnableCookieHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\CookiesHandler::class => static fn(ContainerInterface $c) => new Handler\CookiesHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
            ),

            Handler\HomeHandler::class => static fn(ContainerInterface $c) => new Handler\HomeHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get('config'),
            ),
            Handler\LoginHandler::class => static fn(ContainerInterface $c) => new Handler\LoginHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(AuthenticationService::class),
            ),
            Handler\LogoutHandler::class => static fn(ContainerInterface $c) => new Handler\LogoutHandler(
                $c->get('config'),
            ),
            Handler\DashboardHandler::class => static fn(ContainerInterface $c) => new Handler\DashboardHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
            ),
            Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ApplicantService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\ApplicantHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\ApplicantHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\InstructionsHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\InstructionsHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(LpaMetadata::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\LifeSustainingHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\LifeSustainingHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CompleteIndexHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CompleteIndexHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get(CompleteViewParamsHelper::class),
            ),
            CompleteViewDocsHandler::class => static fn(ContainerInterface $c) => new CompleteViewDocsHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get(CompleteViewParamsHelper::class),
            ),
            WhoAreYouHandler::class => static fn(ContainerInterface $c) => new WhoAreYouHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            WhenLpaStartsHandler::class => static fn(ContainerInterface $c) => new WhenLpaStartsHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            WhenReplacementAttorneyStepInHandler::class => static fn(ContainerInterface $c) => new WhenReplacementAttorneyStepInHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
                $c->get(UrlHelper::class),
            ),
            ReuseDetailsHandler::class => static fn(ContainerInterface $c) => new ReuseDetailsHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            StatusHandler::class => static fn(ContainerInterface $c) => new StatusHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get('config'),
            ),
            Handler\Lpa\CheckoutIndexHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CheckoutIndexHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(CommunicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CheckoutChequeHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CheckoutChequeHandler(
                $c->get(LpaApplicationService::class),
                $c->get(CommunicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CheckoutPayHandler::class => static function (ContainerInterface $c) {
                $handler = new Handler\Lpa\CheckoutPayHandler(
                    $c->get(FormElementManager::class),
                    $c->get(LpaApplicationService::class),
                    $c->get(CommunicationService::class),
                    $c->get(GovPayClient::class),
                    $c->get(UrlHelper::class),
                );
                $handler->setLogger($c->get(LoggerInterface::class));
                return $handler;
            },
            Handler\Lpa\CheckoutPayResponseHandler::class => static function (ContainerInterface $c) {
                $handler = new Handler\Lpa\CheckoutPayResponseHandler(
                    $c->get(FormElementManager::class),
                    $c->get(LpaApplicationService::class),
                    $c->get(CommunicationService::class),
                    $c->get(GovPayClient::class),
                    $c->get(UrlHelper::class),
                    $c->get(TemplateRendererInterface::class),
                );
                $handler->setLogger($c->get(LoggerInterface::class));
                return $handler;
            },
            Handler\Lpa\CheckoutConfirmHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CheckoutConfirmHandler(
                $c->get(LpaApplicationService::class),
                $c->get(CommunicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\MoreInfoRequiredHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\MoreInfoRequiredHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            FeeReductionHandler::class => static fn(ContainerInterface $c) => new FeeReductionHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            RepeatApplicationHandler::class => static fn(ContainerInterface $c) => new RepeatApplicationHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
            ),
            ReplacementAttorneyIndexHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyIndexHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
            ),
            ReplacementAttorneyAddHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyAddHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
                $c->get(LpaMetadata::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            ReplacementAttorneyEditHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyEditHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            ReplacementAttorneyConfirmDeleteHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyConfirmDeleteHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            ReplacementAttorneyDeleteHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyDeleteHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            ReplacementAttorneyAddTrustHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyAddTrustHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
                $c->get(LpaMetadata::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            Handler\Lpa\CorrespondentHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CorrespondentHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CorrespondentEditHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CorrespondentEditHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            Handler\Lpa\DateCheckHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DateCheckHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\DateCheckValidHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DateCheckValidHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\Lpa\CreateLpaHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CreateLpaHandler(
                $c->get(LpaApplicationService::class),
            ),
            Handler\LpaTypeHandler::class => static fn(ContainerInterface $c) => new Handler\LpaTypeHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\TypeHandler::class => static fn(ContainerInterface $c) => new Handler\TypeHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\SessionExpiryHandler::class => static fn(ContainerInterface $c) => new Handler\SessionExpiryHandler(
                new LpaAuthAdapter($c->get(ApiClient::class)),
                $c->get(MezzioSessionStorage::class),
            ),
            Handler\Lpa\IndexHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\IndexHandler(
                $c->get(LpaMetadata::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\DonorIndexHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DonorIndexHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\SummaryHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\SummaryHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\Lpa\DonorAddHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DonorAddHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            Handler\Lpa\DonorEditHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DonorEditHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            DownloadHandler::class => static fn(ContainerInterface $c) => new DownloadHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LoggerInterface::class),
            ),
            DownloadFileHandler::class => static fn(ContainerInterface $c) => new DownloadFileHandler(
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LoggerInterface::class),
            ),
            DownloadCheckHandler::class => static fn(ContainerInterface $c) => new DownloadCheckHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LoggerInterface::class),
            ),
            PrimaryAttorneyHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            PrimaryAttorneyAddHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyAddHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ApplicantService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            PrimaryAttorneyEditHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyEditHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            PrimaryAttorneyConfirmDeleteHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyConfirmDeleteHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            PrimaryAttorneyDeleteHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyDeleteHandler(
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ApplicantService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            PrimaryAttorneyAddTrustHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyAddTrustHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ApplicantService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler(
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderAddHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderAddHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderEditHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderEditHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler(
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\FeedbackHandler::class => static fn(ContainerInterface $c) => new Handler\FeedbackHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(FeedbackService::class),
                $c->get(LoggerInterface::class),
                $c->get(DateService::class),
            ),
            Handler\FeedbackThanksHandler::class => static fn(ContainerInterface $c) => new Handler\FeedbackThanksHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\Lpa\ConfirmDeleteLpaHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\ConfirmDeleteLpaHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
            ),
            Handler\Lpa\DeleteLpaHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DeleteLpaHandler(
                $c->get(LpaApplicationService::class),
            ),
            Handler\GuidanceHandler::class => static fn(ContainerInterface $c) => new Handler\GuidanceHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(GuidanceService::class),
            ),
            Handler\StatsHandler::class => static fn(ContainerInterface $c) => new Handler\StatsHandler(
                $c->get(StatsService::class),
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\PingHandler::class => static fn(ContainerInterface $c) => new Handler\PingHandler(
                $c->get(TemplateRendererInterface::class),
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
            Handler\DeletedAccountHandler::class => static fn(ContainerInterface $c) => new Handler\DeletedAccountHandler(
                $c->get(TemplateRendererInterface::class),
            ),
            Handler\VerifyEmailAddressHandler::class => static fn(ContainerInterface $c) => new Handler\VerifyEmailAddressHandler(
                $c->get(UserDetails::class),
            ),
            Handler\SessionKeepAliveHandler::class => static fn() => new Handler\SessionKeepAliveHandler(),
            Handler\SessionSetExpiryHandler::class => static fn(ContainerInterface $c) => new Handler\SessionSetExpiryHandler(
                $c->get(AuthenticationService::class),
            ),
            Handler\ChangeEmailAddressHandler::class => static fn(ContainerInterface $c) => new Handler\ChangeEmailAddressHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(AuthenticationService::class),
                $c->get(UserDetails::class),
            ),
            Handler\DeleteAccountHandler::class => static fn(ContainerInterface $c) => new Handler\DeleteAccountHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(AuthenticationService::class),
            ),
            Handler\DeleteAccountConfirmHandler::class => static fn(ContainerInterface $c) => new Handler\DeleteAccountConfirmHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(AuthenticationService::class),
                $c->get(UserDetails::class),
            ),
            Handler\PostcodeHandler::class => static fn(ContainerInterface $c) => new Handler\PostcodeHandler(
                $c->get(OrdnanceSurveyService::class),
                $c->get(LoggerInterface::class),
            ),
            Handler\StatusesHandler::class => static fn(ContainerInterface $c) => new Handler\StatusesHandler(
                $c->get(LpaApplicationService::class),
            ),
            Handler\TermsChangedHandler::class => static fn(ContainerInterface $c) => new Handler\TermsChangedHandler(
                $c->get(TemplateRendererInterface::class),
            ),

            LpaApplicationService::class => LpaApplicationServiceFactory::class,
            CommunicationService::class  => CommunicationFactory::class,
            GovPayClient::class          => AlphagovPayClientFactory::class,
            ApplicantService::class      => ApplicantFactory::class,
            ReplacementAttorneyCleanupService::class => ReplacementAttorneyCleanupFactory::class,
            CompleteViewParamsHelper::class => static fn(ContainerInterface $c) => new CompleteViewParamsHelper(
                $c->get(UrlHelper::class),
                new LpaContinuationSheets(),
            ),
            UserDetails::class => UserDetailsFactory::class,
            LpaMetadata::class => MetadataFactory::class,
            ActorReuseDetailsService::class => static fn(ContainerInterface $c) => new ActorReuseDetailsService(
                $c->get(LpaApplicationService::class),
            ),
            DateService::class    => static fn() => new DateService(),
            FeedbackService::class => FeedbackServiceFactory::class,
            GuidanceService::class    => static fn() => new GuidanceService(
                dirname(__DIR__, 2) . '/content/guidance',
            ),
            OrdnanceSurveyService::class => OrdnanceSurveyFactory::class,
            StatsService::class  => static fn(ContainerInterface $c) => new StatsService(
                $c->get(ApiClient::class),
            ),
            StatusService::class => StatusServiceFactory::class,

            DynamoDbClient::class      => DynamoDbClientFactory::class,
            SystemMessage::class => static function (ContainerInterface $c): SystemMessage {
                $config = $c->get('config');
                $dynamoConfig = $config['admin']['dynamodb'];
                $dynamoConfig['keyPrefix'] = getenv('OPG_LPA_STACK_NAME') ?: 'local';
                $store = new DynamoDbKeyValueStore($dynamoConfig);
                $store->setDynamoDbClient($c->get(DynamoDbClient::class));
                return new SystemMessage($store);
            },
            RedisClient::class          => RedisClientFactory::class,
            FilteringSaveHandler::class => SaveHandlerFactory::class,
            RegisterSessionSaveHandlerMiddleware::class => static fn(ContainerInterface $c) => new RegisterSessionSaveHandlerMiddleware(
                $c->get(FilteringSaveHandler::class),
                $c->get('config')['session']['native_settings'] ?? [],
            ),
            AppMailTransportInterface::class => MailTransportFactory::class,
            Handler\AboutYouHandler::class => static fn(ContainerInterface $c) => new Handler\AboutYouHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(UserDetails::class),
            ),
            Handler\ChangePasswordHandler::class => static fn(ContainerInterface $c) => new Handler\ChangePasswordHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(AuthenticationService::class),
                $c->get(UserDetails::class),
            ),
            Handler\RegisterHandler::class => static fn(ContainerInterface $c) => new Handler\RegisterHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(UserDetails::class),
                $c->get(LoggerInterface::class),
            ),
            Handler\ConfirmRegistrationHandler::class => static fn(ContainerInterface $c) => new Handler\ConfirmRegistrationHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(UserDetails::class),
            ),
            Handler\ResendActivationEmailHandler::class => static fn(ContainerInterface $c) => new Handler\ResendActivationEmailHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(UserDetails::class),
            ),
            Handler\ForgotPasswordHandler::class => static fn(ContainerInterface $c) => new Handler\ForgotPasswordHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(UserDetails::class),
            ),
            Handler\ResetPasswordHandler::class => static fn(ContainerInterface $c) => new Handler\ResetPasswordHandler(
                $c->get(TemplateRendererInterface::class),
                $c->get(FormElementManager::class),
                $c->get(UserDetails::class),
            ),

            AuthenticationMiddleware::class => AuthenticationMiddlewareFactory::class,
            IdentityTokenRefreshMiddleware::class  => IdentityTokenRefreshMiddlewareFactory::class,
            LpaLoaderMiddleware::class => static fn(ContainerInterface $c) => new LpaLoaderMiddleware(
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            UserDetailsMiddleware::class           => UserDetailsMiddlewareFactory::class,
            TermsAndConditionsMiddleware::class    => static fn(ContainerInterface $c) => new TermsAndConditionsMiddleware(
                $c->get('config'),
                $c->get(AuthenticationService::class),
                $c->get(UrlHelper::class),
            ),
            CsrfValidationMiddleware::class        => static fn() => new CsrfValidationMiddleware(),
            FlashMessagesHolderMiddleware::class    => static fn(ContainerInterface $c) => new FlashMessagesHolderMiddleware(
                $c->get(FlashMessagesHolder::class),
            ),
            PersistentSessionDetailsMiddleware::class => static fn(ContainerInterface $c) => new PersistentSessionDetailsMiddleware(
                $c->get(PersistentSessionDetails::class),
            ),

            CsrfMiddleware::class              => CsrfMiddlewareFactory::class,
            CsrfGuardFactoryInterface::class   => static fn() => new SessionCsrfGuardFactory(),

            View\Twig\LegacyCompatExtension::class => View\Twig\LegacyCompatExtensionFactory::class,

            AuthenticationService::class => AuthenticationServiceFactory::class,

            LoggerInterface::class => LoggerFactory::class,
        ],
    ],

    'api_client' => [
        'api_uri' => getenv('OPG_LPA_ENDPOINTS_API') ?: null,
    ],

    'alphagov' => [
        'pay' => [
            'key' => getenv('OPG_LPA_FRONT_GOV_PAY_KEY') ?: null,
            'url' => getenv('OPG_LPA_FRONT_GOV_PAY_URL') ?: null,
        ],
    ],

    'processing-status' => [
        'track-from-date' => getenv('OPG_LPA_FRONT_TRACK_FROM_DATE') ?: '2019-04-01',
        'expected-working-days-before-receipt' => 15,
    ],

    'email' => [
        'notify' => [
            'key'                   => getenv('OPG_LPA_FRONT_EMAIL_NOTIFY_API_KEY') ?: null,
            'smokeTestEmailAddress' => 'simulate-delivered@notifications.service.gov.uk',
        ],
        'sendFeedbackEmailTo' => 'LPADigitalFeedback@PublicGuardian.gov.uk',
    ],

    'admin' => [
        'dynamodb' => [
            'client' => [
                'region' => getenv('AWS_REGION') ?: 'eu-west-1',
                'version' => '2012-08-10',
                'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
            ],
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE') ?: 'lpa-properties-shared',
            ],
            'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
        ],
    ],

    'address' => [
        'ordnancesurvey' => [
            'key'      => getenv('OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY') ?: null,
            'endpoint' => getenv('OPG_LPA_OS_PLACES_HUB_ENDPOINT') ?: 'https://api.os.uk/search/places/v1/postcode',
        ],
    ],

    'redis' => [
        'url' => getenv('OPG_LPA_COMMON_REDIS_CACHE_URL') ?: null,
        'ttlMs' => (int)(getenv('OPG_LPA_COMMON_REDIS_CACHE_TTL_MS') ?: 10800000), // 3 hours, matching legacy app
        'ordnance_survey' => [
            'max_call_per_min' => 6,
        ],
    ],

    'logging' => [
        'serviceName' => 'opg-lpa/front',
        'minLevel'    => Level::fromName('DEBUG'),
    ],
];

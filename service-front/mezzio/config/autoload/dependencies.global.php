<?php

declare(strict_types=1);

use App\Authentication\AuthenticationService;
use App\Service\Lpa\ActorReuseDetailsService;
use App\Service\Lpa\Communication as CommunicationService;
use App\Service\Lpa\Metadata as LpaMetadata;
use App\Service\Mail\MailParameters as AppMailParameters;
use App\Service\Mail\Transport\MailTransportInterface as AppMailTransportInterface;
use App\Service\Mail\Transport\NotifyMailTransport as AppNotifyMailTransport;
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
use App\Service\AddressLookup\OrdnanceSurvey as OrdnanceSurveyService;
use App\Service\Date\DateService;
use App\Service\Feedback\FeedbackService;
use App\Service\Guidance\GuidanceService;
use App\Service\CompleteViewParamsHelper;
use App\Service\LpaApplicationServiceFactory;
use App\Service\Lpa\ContinuationSheets as LpaContinuationSheets;
use App\Service\Stats\StatsService;
use App\Service\System\StatusService;
use App\Service\UserDetails;
use App\Service\UserDetailsFactory;
use App\Storage\MezzioSessionStorage;
use App\View;
use App\Authentication\Adapter\LpaAuthAdapter;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\Lpa\Applicant as ApplicantService;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup as ReplacementAttorneyCleanupService;
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
        'aliases' => [
            'Communication' => CommunicationService::class,
            'GovPayClient'  => \Alphagov\Pay\Client::class,
        ],
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
            Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ApplicantService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\InstructionsHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\InstructionsHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(LpaMetadata::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\LifeSustainingHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\LifeSustainingHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CompleteIndexHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CompleteIndexHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get(CompleteViewParamsHelper::class),
            ),
            CompleteViewDocsHandler::class => static fn(ContainerInterface $c) => new CompleteViewDocsHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get(CompleteViewParamsHelper::class),
            ),
            WhoAreYouHandler::class => static fn(ContainerInterface $c) => new WhoAreYouHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            WhenLpaStartsHandler::class => static fn(ContainerInterface $c) => new WhenLpaStartsHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            WhenReplacementAttorneyStepInHandler::class => static fn(ContainerInterface $c) => new WhenReplacementAttorneyStepInHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
                $c->get(UrlHelper::class),
            ),
            ReuseDetailsHandler::class => static fn(ContainerInterface $c) => new ReuseDetailsHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            StatusHandler::class => static fn(ContainerInterface $c) => new StatusHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get('config'),
            ),
            Handler\Lpa\CheckoutIndexHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CheckoutIndexHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(CommunicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CheckoutChequeHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CheckoutChequeHandler(
                $c->get(LpaApplicationService::class),
                $c->get(CommunicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CheckoutPayHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CheckoutPayHandler(
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(CommunicationService::class),
                $c->get(\Alphagov\Pay\Client::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CheckoutPayResponseHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CheckoutPayResponseHandler(
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(CommunicationService::class),
                $c->get(\Alphagov\Pay\Client::class),
                $c->get(UrlHelper::class),
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\Lpa\CheckoutConfirmHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CheckoutConfirmHandler(
                $c->get(LpaApplicationService::class),
                $c->get(CommunicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\MoreInfoRequiredHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\MoreInfoRequiredHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            FeeReductionHandler::class => static fn(ContainerInterface $c) => new FeeReductionHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            RepeatApplicationHandler::class => static fn(ContainerInterface $c) => new RepeatApplicationHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
            ),
            ReplacementAttorneyIndexHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyIndexHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
            ),
            ReplacementAttorneyAddHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyAddHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
                $c->get(LpaMetadata::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            ReplacementAttorneyEditHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyEditHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            ReplacementAttorneyConfirmDeleteHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyConfirmDeleteHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            ReplacementAttorneyDeleteHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyDeleteHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            ReplacementAttorneyAddTrustHandler::class => static fn(ContainerInterface $c) => new ReplacementAttorneyAddTrustHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
                $c->get(LpaMetadata::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            Handler\Lpa\CorrespondentHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CorrespondentHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CorrespondentEditHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CorrespondentEditHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            Handler\Lpa\DateCheckHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DateCheckHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\DateCheckValidHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DateCheckValidHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
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
            Handler\Lpa\SummaryHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\SummaryHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\Lpa\DonorAddHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DonorAddHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            Handler\Lpa\DonorEditHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\DonorEditHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            DownloadHandler::class => static fn(ContainerInterface $c) => new DownloadHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
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
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LoggerInterface::class),
            ),
            PrimaryAttorneyHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            PrimaryAttorneyAddHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyAddHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ApplicantService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            PrimaryAttorneyEditHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyEditHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            PrimaryAttorneyConfirmDeleteHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyConfirmDeleteHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            PrimaryAttorneyDeleteHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyDeleteHandler(
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ApplicantService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            PrimaryAttorneyAddTrustHandler::class => static fn(ContainerInterface $c) => new PrimaryAttorneyAddTrustHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(ApplicantService::class),
                $c->get(ReplacementAttorneyCleanupService::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler(
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderAddHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderAddHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
                $c->get(LpaMetadata::class),
                $c->get(ActorReuseDetailsService::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderEditHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderEditHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(LpaApplicationService::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(UrlHelper::class),
            ),
            Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler(
                $c->get(LpaApplicationService::class),
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
            Handler\PostcodeHandler::class => static function (ContainerInterface $c): Handler\PostcodeHandler {
                return new Handler\PostcodeHandler(
                    $c->get(OrdnanceSurveyService::class),
                    $c->get(LoggerInterface::class),
                );
            },
            Handler\StatusesHandler::class => static fn(ContainerInterface $c) => new Handler\StatusesHandler(
                $c->get(LpaApplicationService::class),
            ),
            Handler\Lpa\ConfirmDeleteLpaHandler::class => static fn(ContainerInterface $c) => new Handler\Lpa\ConfirmDeleteLpaHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(LpaApplicationService::class),
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
            Handler\DeletedAccountHandler::class => static fn(ContainerInterface $c) => new Handler\DeletedAccountHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),
            Handler\VerifyEmailAddressHandler::class => static fn(ContainerInterface $c) => new Handler\VerifyEmailAddressHandler(
                $c->get(UserDetails::class),
            ),
            Handler\SessionKeepAliveHandler::class => static fn() => new Handler\SessionKeepAliveHandler(),
            Handler\SessionSetExpiryHandler::class => static fn(ContainerInterface $c) => new Handler\SessionSetExpiryHandler(
                $c->get(AuthenticationService::class),
            ),
            Handler\ChangeEmailAddressHandler::class => static fn(ContainerInterface $c) => new Handler\ChangeEmailAddressHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(\Laminas\Form\FormElementManager::class),
                $c->get(AuthenticationService::class),
                $c->get(UserDetails::class),
            ),
            Handler\DeleteAccountHandler::class => static fn(ContainerInterface $c) => new Handler\DeleteAccountHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(AuthenticationService::class),
            ),
            Handler\DeleteAccountConfirmHandler::class => static fn(ContainerInterface $c) => new Handler\DeleteAccountConfirmHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
                $c->get(AuthenticationService::class),
                $c->get(UserDetails::class),
            ),
            Handler\PostcodeHandler::class => static fn(ContainerInterface $c) => new Handler\PostcodeHandler(
                $c->get(\Application\Model\Service\AddressLookup\OrdnanceSurvey::class),
                $c->get(LoggerInterface::class),
            ),
            Handler\StatusesHandler::class => static fn(ContainerInterface $c) => new Handler\StatusesHandler(
                $c->get(LpaApplicationService::class),
            ),
            Handler\TermsChangedHandler::class => static fn(ContainerInterface $c) => new Handler\TermsChangedHandler(
                $c->get(\Mezzio\Template\TemplateRendererInterface::class),
            ),

            // Services
            LpaApplicationService::class => LpaApplicationServiceFactory::class,
            CommunicationService::class => static function (ContainerInterface $c): CommunicationService {
                $config = $c->get('config');
                $emailConfig = $config['email'] ?? [];
                $notifyKey = $emailConfig['notify']['key'] ?? null;
                $smokeTestEmail = $emailConfig['notify']['smokeTestEmailAddress'] ?? null;

                if ($notifyKey) {
                    $mailTransport = new AppNotifyMailTransport(
                        new \Alphagov\Notifications\Client([
                            'apiKey'     => $notifyKey,
                            'httpClient' => new \Http\Adapter\Guzzle7\Client(),
                        ]),
                        $smokeTestEmail,
                    );
                } else {
                    $mailTransport = new class implements AppMailTransportInterface {
                        public function send(AppMailParameters $mailParameters): void
                        {
                        }
                        public function healthcheck(): array
                        {
                            return ['ok' => true, 'status' => 'ok'];
                        }
                    };
                }

                $service = new CommunicationService($mailTransport);
                $service->setUrlHelper($c->get(UrlHelper::class));
                return $service;
            },
            \Alphagov\Pay\Client::class => static function (ContainerInterface $c): \Alphagov\Pay\Client {
                $config = $c->get('config');
                $payConfig = $config['alphagov']['pay'] ?? [];
                return new \Alphagov\Pay\Client([
                    'apiKey'     => $payConfig['key'] ?? '',
                    'httpClient' => new \Http\Adapter\Guzzle7\Client(),
                    'baseUrl'    => $payConfig['url'] ?? null,
                ]);
            },
            ApplicantService::class => static function (ContainerInterface $c): ApplicantService {
                $service = new ApplicantService();
                $service->setLpaApplicationService($c->get(LpaApplicationService::class));
                return $service;
            },
            ReplacementAttorneyCleanupService::class => static function (ContainerInterface $c): ReplacementAttorneyCleanupService {
                $service = new ReplacementAttorneyCleanupService();
                $service->setLpaApplicationService($c->get(LpaApplicationService::class));
                return $service;
            },
            CompleteViewParamsHelper::class => static fn(ContainerInterface $c) => new CompleteViewParamsHelper(
                $c->get(UrlHelper::class),
                new LpaContinuationSheets(),
            ),
            UserDetails::class => UserDetailsFactory::class,
            LpaMetadata::class => static function (ContainerInterface $c): LpaMetadata {
                $service = new LpaMetadata();
                $service->setLpaApplicationService($c->get(LpaApplicationService::class));
                $service->setLogger($c->get(LoggerInterface::class));
                return $service;
            },
            ActorReuseDetailsService::class => static fn(ContainerInterface $c) => new ActorReuseDetailsService(
                $c->get(LpaApplicationService::class),
            ),
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
            OrdnanceSurveyService::class => static function (ContainerInterface $c): OrdnanceSurveyService {
                $config = $c->get('config');
                return new OrdnanceSurveyService(
                    new GuzzleClient(),
                    $config['address']['ordnancesurvey']['key'] ?? '',
                    $config['address']['ordnancesurvey']['endpoint'] ?? '',
                );
            },
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

                if (!$notifyKey) {
                    return new class implements \Application\Model\Service\Mail\Transport\MailTransportInterface {
                        public function send(\Application\Model\Service\Mail\MailParameters $mailParameters): void
                        {
                        }
                        public function healthcheck(): array
                        {
                            return ['ok' => true, 'status' => 'ok'];
                        }
                    };
                }

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

    'alphagov' => [
        'pay' => [
            'key' => getenv('OPG_LPA_FRONT_GOV_PAY_KEY') ?: null,
            'url' => getenv('OPG_LPA_FRONT_GOV_PAY_URL') ?: null,
        ],
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
    ],

    'logging' => [
        'serviceName' => 'opg-lpa/front',
        'minLevel'    => Level::fromName('DEBUG'),
    ],
];

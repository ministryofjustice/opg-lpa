<?php

namespace Application\ControllerFactory;

use Application\Controller\AbstractAuthenticatedController;
use Application\Controller\AbstractBaseController;
use Application\Controller\AbstractLpaController;
use Application\Controller\Authenticated\AboutYouController;
use Application\Controller\Authenticated\AdminController;
use Application\Controller\Authenticated\Lpa\CheckoutController;
use Application\Controller\Authenticated\Lpa\DownloadController;
use Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController;
use Application\Controller\Authenticated\Lpa\PrimaryAttorneyController;
use Application\Controller\Authenticated\Lpa\ReuseDetailsController;
use Application\Controller\Authenticated\PostcodeController;
use Application\Controller\General\AuthController;
use Application\Controller\General\FeedbackController;
use Application\Controller\General\ForgotPasswordController;
use Application\Controller\General\GuidanceController;
use Application\Controller\General\PingController;
use Application\Controller\General\RegisterController;
use Application\Controller\General\SendgridController;
use Application\Controller\General\StatsController;
use Application\Controller\General\VerifyEmailAddressController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\Stdlib\DispatchableInterface as Dispatchable;
use Exception;
use RuntimeException;

/**
 * Creates a controller based on those requested without a specific entry in the controller service locator.
 *
 * Class ControllerAbstractFactory
 * @package Application\ControllerFactory
 */
class ControllerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Any additional services to be injected into the requested service using the setter method specified
     *
     * @var array
     */
    private $additionalServices = [
        AboutYouController::class => [
            'setUserDetailsSession' => 'UserDetailsSession',
        ],
        AdminController::class => [
            'setAdminService' => 'AdminService'
        ],
        AuthController::class => [
            'setLpaApplicationService' => 'LpaApplicationService'
        ],
        CheckoutController::class => [
            'setCommunicationService' => 'Communication',
            'setPaymentClient'        => 'GovPayClient'
        ],
        DownloadController::class => [
            'setAnalyticsService' => 'AnalyticsService'
        ],
        FeedbackController::class => [
            'setFeedbackService' => 'Feedback'
        ],
        ForgotPasswordController::class => [
            'setUserService' => 'UserService'
        ],
        GuidanceController::class => [
            'setGuidanceService' => 'Guidance'
        ],
        HowPrimaryAttorneysMakeDecisionController::class => [
            'setApplicantService' => 'ApplicantService',
        ],
        PingController::class => [
            'setStatusService' => 'SiteStatus'
        ],
        PostcodeController::class => [
            'setAddressLookup' => 'AddressLookup'
        ],
        PrimaryAttorneyController::class => [
            'setApplicantService' => 'ApplicantService',
        ],
        RegisterController::class => [
            'setUserService' => 'UserService'
        ],
        ReuseDetailsController::class => [
            'setRouter' => 'Router'
        ],
        SendgridController::class => [
            'setMailTransport' => 'MailTransport'
        ],
        StatsController::class => [
            'setStatsService' => 'StatsService',
        ],
        VerifyEmailAddressController::class => [
            'setUserService' => 'UserService'
        ],
    ];

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $controllerName = $this->getControllerName($requestedName);

        return (class_exists($controllerName) && is_subclass_of($controllerName, AbstractBaseController::class));
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws Exception if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!$this->canCreate($container, $requestedName)) {
            throw new ServiceNotFoundException(sprintf(
                'Abstract factory %s can not create the requested service %s',
                get_class($this),
                $requestedName
            ));
        }

        $controllerName = $this->getControllerName($requestedName);

        $formElementManager = $container->get('FormElementManager');
        $sessionManager = $container->get('SessionManager');
        $authenticationService = $container->get('AuthenticationService');
        $config = $container->get('Config');
        $cache = $container->get('Cache');

        if (is_subclass_of($controllerName, AbstractAuthenticatedController::class)) {
            $userDetailsSession = $container->get('UserDetailsSession');
            $lpaApplicationService = $container->get('LpaApplicationService');
            $userService = $container->get('UserService');

            if (is_subclass_of($controllerName, AbstractLpaController::class)) {
                //  Get the LPA ID from the route params
                $lpaId = $container->get('Application')->getMvcEvent()->getRouteMatch()->getParam('lpa-id');

                if (!is_numeric($lpaId)) {
                    throw new RuntimeException('Invalid LPA ID passed');
                }

                $controller = new $controllerName(
                    $lpaId,
                    $formElementManager,
                    $sessionManager,
                    $authenticationService,
                    $config,
                    $cache,
                    $userDetailsSession,
                    $lpaApplicationService,
                    $userService,
                    $container->get('ReplacementAttorneyCleanup'),
                    $container->get('Metadata')
                );
            } else {
                $controller = new $controllerName(
                    $formElementManager,
                    $sessionManager,
                    $authenticationService,
                    $config,
                    $cache,
                    $userDetailsSession,
                    $lpaApplicationService,
                    $userService
                );
            }
        } else {
            $controller = new $controllerName(
                $formElementManager,
                $sessionManager,
                $authenticationService,
                $config,
                $cache
            );
        }

        // Ensure it's Dispatchable...
        if (($controller instanceof Dispatchable) === false) {
            throw new RuntimeException('Requested controller class is not Dispatchable');
        }

        //  If required load any additional services into the resource
        if (array_key_exists($controllerName, $this->additionalServices)
            && is_array($this->additionalServices[$controllerName])) {
            foreach ($this->additionalServices[$controllerName] as $setterMethod => $additionalService) {
                if (!method_exists($controller, $setterMethod)) {
                    throw new Exception(sprintf(
                        'The setter method %s does not exist on the requested resource %s',
                        $setterMethod,
                        $controllerName
                    ));
                }

                $controller->$setterMethod($container->get($additionalService));
            }
        }

        return $controller;
    }

    /**
     * Prepends the namespace to the requested controller.
     *
     * @param $requestedName
     * @return string
     */
    private function getControllerName($requestedName)
    {
        return 'Application\Controller\\' . $requestedName;
    }
}

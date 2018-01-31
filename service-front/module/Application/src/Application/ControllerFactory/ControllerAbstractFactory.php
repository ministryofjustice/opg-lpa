<?php

namespace Application\ControllerFactory;

use Application\Controller\AbstractAuthenticatedController;
use Application\Controller\AbstractBaseController;
use Application\Controller\AbstractLpaController;
use Application\Controller\Authenticated\AdminController;
use Application\Controller\Authenticated\DashboardController;
use Application\Controller\Authenticated\DeleteController;
use Application\Controller\Authenticated\Lpa\CheckoutController;
use Application\Controller\Authenticated\Lpa\ReuseDetailsController;
use Application\Controller\Authenticated\PostcodeController;
use Application\Controller\General\AuthController;
use Application\Controller\General\FeedbackController;
use Application\Controller\General\ForgotPasswordController;
use Application\Controller\General\GuidanceController;
use Application\Controller\General\NotificationsController;
use Application\Controller\General\PingController;
use Application\Controller\General\RegisterController;
use Application\Controller\General\SendgridController;
use Application\Controller\General\VerifyEmailAddressController;
use Exception;
use RuntimeException;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\DispatchableInterface as Dispatchable;

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
        AdminController::class => [
            'setAdminService' => 'AdminService'
        ],
        DashboardController::class => [
            'setApplicationList' => 'ApplicationList'
        ],
        DeleteController::class => [
            'setDeleteUser' => 'DeleteUser'
        ],
        PostcodeController::class => [
            'setAddressLookup' => 'AddressLookupMoj'
        ],
        CheckoutController::class => [
            'setCommunicationService' => 'Communication',
            'setPaymentClient'        => 'GovPayClient',
            'setPaymentService'       => 'Payment'
        ],
        ReuseDetailsController::class => [
            'setRouter' => 'Router'
        ],
        AuthController::class => [
            'setAuthenticationAdapter' => 'AuthenticationAdapter'
        ],
        FeedbackController::class => [
            'setFeedbackService' => 'Feedback'
        ],
        ForgotPasswordController::class => [
            'setPasswordResetService' => 'PasswordReset'
        ],
        GuidanceController::class => [
            'setGuidanceService' => 'Guidance'
        ],
        NotificationsController::class => [
            'setTwigEmailRenderer' => 'TwigEmailRenderer',
            'setMailTransport'     => 'MailTransport'
        ],
        PingController::class => [
            'setStatusService' => 'SiteStatus'
        ],
        RegisterController::class => [
            'setRegisterService' => 'Register'
        ],
        SendgridController::class => [
            'setTwigEmailRenderer' => 'TwigEmailRenderer'
        ],
        VerifyEmailAddressController::class => [
            'setAboutYouDetails' => 'AboutYouDetails'
        ],
    ];

    /**
     * Checks whether this abstract factory can create the requested controller
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $controllerName = $this->getControllerName($requestedName);

        return (class_exists($controllerName) && is_subclass_of($controllerName, AbstractBaseController::class));
    }

    /**
     * Creates the requested controller
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     * @throws Exception
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (!$this->canCreateServiceWithName($serviceLocator, $name, $requestedName)) {
            throw new ServiceNotFoundException(sprintf(
                'Abstract factory %s can not create the requested service %s',
                get_class($this),
                $requestedName
            ));
        }

        $serviceLocator = $serviceLocator->getServiceLocator();

        $controllerName = $this->getControllerName($requestedName);

        $formElementManager = $serviceLocator->get('FormElementManager');
        $sessionManager = $serviceLocator->get('SessionManager');
        $authenticationService = $serviceLocator->get('AuthenticationService');
        $config = $serviceLocator->get('Config');
        $cache = $serviceLocator->get('Cache');

        if (is_subclass_of($controllerName, AbstractAuthenticatedController::class)) {
            $userDetailsSession = $serviceLocator->get('UserDetailsSession');
            $lpaApplicationService = $serviceLocator->get('LpaApplicationService');
            $aboutYouDetails = $serviceLocator->get('AboutYouDetails');
            $authenticationAdapter = $serviceLocator->get('AuthenticationAdapter');

            if (is_subclass_of($controllerName, AbstractLpaController::class)) {
                $controller = new $controllerName(
                    $formElementManager,
                    $sessionManager,
                    $authenticationService,
                    $config,
                    $cache,
                    $userDetailsSession,
                    $lpaApplicationService,
                    $aboutYouDetails,
                    $authenticationAdapter,
                    $serviceLocator->get('ApplicantCleanup'),
                    $serviceLocator->get('ReplacementAttorneyCleanup'),
                    $serviceLocator->get('Metadata')
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
                    $aboutYouDetails,
                    $authenticationAdapter
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

                $controller->$setterMethod($serviceLocator->get($additionalService));
            }
        }

        return $controller;
    }

    /**
     * Appends the namespace to the requested controller.
     *
     * @param $requestedName
     * @return string
     */
    private function getControllerName($requestedName)
    {
        return'Application\Controller\\' . $requestedName;
    }
}

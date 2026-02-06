<?php

namespace Application\ControllerFactory;

use Application\Controller\AbstractAuthenticatedController;
use Application\Controller\AbstractBaseController;
use Application\Controller\Authenticated\Lpa\CheckoutController;
use Application\Controller\Authenticated\Lpa\DateCheckController;
use Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController;
use Application\Controller\Authenticated\Lpa\PrimaryAttorneyController;
use Application\Controller\Authenticated\Lpa\ReuseDetailsController;
use Application\Controller\Authenticated\PostcodeController;
use Application\Controller\General\AuthController;
use Application\Controller\General\RegisterController;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Service\DateCheckViewModelHelper;
use Application\Model\Service\Session\SessionUtility;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\Stdlib\DispatchableInterface as Dispatchable;
use Exception;
use MakeShared\Logging\LoggerTrait;
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
     */
    /** @var array */
    private $additionalServices = [
        AuthController::class => [
            'setLpaApplicationService' => 'LpaApplicationService'
        ],
        CheckoutController::class => [
            'setCommunicationService' => 'Communication',
            'setPaymentClient'        => 'GovPayClient'
        ],
        HowPrimaryAttorneysMakeDecisionController::class => [
            'setApplicantService' => 'ApplicantService',
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
        DateCheckController::class => [
            'setDateCheckViewModelHelper' => DateCheckViewModelHelper::class,
        ]
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
        return (
            class_exists($controllerName) &&
            is_subclass_of($controllerName, AbstractBaseController::class)
        );
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
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
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
        // Container is just initiated, but this is required to populate twig helper
        // function RouteName within templates.
        $container->get('PersistentSessionDetails');
        $sessionManagerSupport = $container->get(SessionManagerSupport::class);
        $authenticationService = $container->get('AuthenticationService');
        $config = $container->get('Config');
        $sessionUtility = $container->get(SessionUtility::class);

        if (is_subclass_of($controllerName, AbstractAuthenticatedController::class)) {
            $lpaApplicationService = $container->get('LpaApplicationService');
            $userService = $container->get('UserService');

            $controller = new $controllerName(
                $formElementManager,
                $sessionManagerSupport,
                $authenticationService,
                $config,
                $lpaApplicationService,
                $userService,
                $sessionUtility
            );
        } else {
            $controller = new $controllerName(
                $formElementManager,
                $sessionManagerSupport,
                $authenticationService,
                $config,
                $sessionUtility
            );
        }

        // Ensure it's Dispatchable...
        if (($controller instanceof Dispatchable) === false) {
            throw new RuntimeException('Requested controller class is not Dispatchable');
        }

        //  If required load any additional services into the resource
        if (
            array_key_exists($controllerName, $this->additionalServices)
            && is_array($this->additionalServices[$controllerName])
        ) {
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

        $traitsUsed = class_uses($controller);

        if (in_array(LoggerTrait::class, $traitsUsed)) {
            /**
             * psalm thinks controller could be a DispatchableInterface which lacks a setLogger
             * but in practice this will always be a subclass of AbstractLpaController or AbstractAuthenticatedController
             * @psalm-suppress UndefinedInterfaceMethod
             */
            $controller->setLogger($container->get('Logger'));
        }

        if (method_exists($controller, 'setReplacementAttorneyCleanup')) {
            $controller->setReplacementAttorneyCleanup($container->get(ReplacementAttorneyCleanup::class));
        }

        if (method_exists($controller, 'setMetadata')) {
            $controller->setMetadata($container->get('Metadata'));
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

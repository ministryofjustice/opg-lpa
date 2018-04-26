<?php

namespace Application\Controller;

use Application\Controller\Version2;
use Application\Model\Service;
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
     * @var array
     */
    private $serviceMappings = [
        Version2\ApplicationController::class                   => Service\Applications\Service::class,
        Version2\CertificateProviderController::class           => Service\CertificateProvider\Service::class,
        Version2\CorrespondentController::class                 => Service\Correspondent\Service::class,
        Version2\DonorController::class                         => Service\Donor\Service::class,
        Version2\InstructionController::class                   => Service\Instruction\Service::class,
        Version2\LockController::class                          => Service\Lock\Service::class,
        Version2\NotifiedPeopleController::class                => Service\NotifiedPeople\Service::class,
        Version2\PaymentController::class                       => Service\Payment\Service::class,
        Version2\PdfController::class                           => Service\Pdfs\Service::class,
        Version2\PreferenceController::class                    => Service\Preference\Service::class,
        Version2\PrimaryAttorneyController::class               => Service\AttorneysPrimary\Service::class,
        Version2\PrimaryAttorneyDecisionsController::class      => Service\AttorneyDecisionsPrimary\Service::class,
        Version2\RepeatCaseNumberController::class              => Service\RepeatCaseNumber\Service::class,
        Version2\ReplacementAttorneyController::class           => Service\AttorneysReplacement\Service::class,
        Version2\ReplacementAttorneyDecisionsController::class  => Service\AttorneyDecisionsReplacement\Service::class,
        Version2\SeedController::class                          => Service\Seed\Service::class,
        Version2\StatsController::class                         => Service\Stats\Service::class,
        Version2\TypeController::class                          => Service\Type\Service::class,
        Version2\UserController::class                          => Service\Users\Service::class,
        Version2\WhoAreYouController::class                     => Service\WhoAreYou\Service::class,
        Version2\WhoIsRegisteringController::class              => Service\WhoIsRegistering\Service::class,
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
        return (class_exists($requestedName) && is_subclass_of($requestedName, Version2\AbstractController::class));
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

        //  Create the controller injecting the appropriate service
        $service = $container->get($this->serviceMappings[$requestedName]);

        $controller = new $requestedName($service);

        // Ensure it's Dispatchable...
        if (($controller instanceof Dispatchable) === false) {
            throw new RuntimeException('Requested controller class is not Dispatchable');
        }

        return $controller;
    }
}

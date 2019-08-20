<?php

namespace Application\ControllerFactory;

use Application\Controller\Version2\Lpa as LpaControllers;
use Application\Model\Service;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class LpaControllerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * @var array
     */
    private $serviceMappings = [
        LpaControllers\ApplicationController::class                   => Service\Applications\Service::class,
        LpaControllers\CertificateProviderController::class           => Service\CertificateProvider\Service::class,
        LpaControllers\CorrespondentController::class                 => Service\Correspondent\Service::class,
        LpaControllers\DonorController::class                         => Service\Donor\Service::class,
        LpaControllers\InstructionController::class                   => Service\Instruction\Service::class,
        LpaControllers\LockController::class                          => Service\Lock\Service::class,
        LpaControllers\NotifiedPeopleController::class                => Service\NotifiedPeople\Service::class,
        LpaControllers\PaymentController::class                       => Service\Payment\Service::class,
        LpaControllers\PdfController::class                           => Service\Pdfs\Service::class,
        LpaControllers\PreferenceController::class                    => Service\Preference\Service::class,
        LpaControllers\PrimaryAttorneyController::class               => Service\AttorneysPrimary\Service::class,
        LpaControllers\PrimaryAttorneyDecisionsController::class      => Service\AttorneyDecisionsPrimary\Service::class,
        LpaControllers\RepeatCaseNumberController::class              => Service\RepeatCaseNumber\Service::class,
        LpaControllers\ReplacementAttorneyController::class           => Service\AttorneysReplacement\Service::class,
        LpaControllers\ReplacementAttorneyDecisionsController::class  => Service\AttorneyDecisionsReplacement\Service::class,
        LpaControllers\SeedController::class                          => Service\Seed\Service::class,
        LpaControllers\TypeController::class                          => Service\Type\Service::class,
        LpaControllers\UserController::class                          => Service\Users\Service::class,
        LpaControllers\WhoAreYouController::class                     => Service\WhoAreYou\Service::class,
        LpaControllers\WhoIsRegisteringController::class              => Service\WhoIsRegistering\Service::class,
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
        return (class_exists($requestedName) && is_subclass_of($requestedName, LpaControllers\AbstractLpaController::class));
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed
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

        //  Create the controller injecting the appropriate services
        $authorizationService = $container->get('ZfcRbac\Service\AuthorizationService');
        $service = $container->get($this->serviceMappings[$requestedName]);

        return new $requestedName($authorizationService, $service);
    }
}

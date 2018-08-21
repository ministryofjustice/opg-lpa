<?php

namespace Application\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollectionTrait;
use Application\Model\DataAccess\Repository\Application as ApplicationRepository;
use Application\Model\DataAccess\Repository\User as UserRepository;
use Application\Model\DataAccess\Repository\Stats as StatsRepository;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\UserManagement\Service as UserManagementService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Exception;

/**
 * Creates a service and injects the required dependencies
 *
 * Class ServiceAbstractFactory
 * @package Application\Model\Service
 */
class ServiceAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Any additional services to be injected into the requested service using the setter method specified
     *
     * @var array
     */
    private $additionalServices = [
        AccountCleanup\Service::class => [
            'setConfig'                 => 'config',
            'setGuzzleClient'           => 'GuzzleClient',
            'setSnsClient'              => 'SnsClient',
            'setUserManagementService'  => UserManagementService::class,
        ],
        Password\Service::class => [
            'setAuthenticationService' => AuthenticationService::class,
        ],
        Pdfs\Service::class => [
            'setPdfConfig'         => 'config',
            'setDynamoQueueClient' => 'DynamoQueueClient',
            'setS3Client'          => 'S3Client',
        ],
        Seed\Service::class => [
            'setApplicationsService' => ApplicationsService::class,
        ],
        Users\Service::class => [
            'setApplicationsService'   => ApplicationsService::class,
            'setUserManagementService' => UserManagementService::class,
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
        return (class_exists($requestedName)
            && is_subclass_of($requestedName, AbstractService::class));
    }

    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed
     * @throws ApiProblemException
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!$this->canCreate($container, $requestedName)) {
            throw new Exception(sprintf('Abstract factory %s can not create the requested service %s', get_class($this), $requestedName));
        }

        $service = new $requestedName();

        $traitsUsed = class_uses($service);

        //  Inject the required Mongo collections
        if (is_array($traitsUsed)) {
            if (in_array(ApiLpaCollectionTrait::class, $traitsUsed)) {
                $service->setApiLpaCollection($container->get(ApiLpaCollection::class));
            }

            if (in_array(UserRepository\LogRepositoryTrait::class, $traitsUsed)) {
                $service->setLogRepository($container->get(UserRepository\LogRepositoryInterface::class));
            }

            if (in_array(UserRepository\UserRepositoryTrait::class, $traitsUsed)) {
                $service->setUserRepository($container->get(UserRepository\UserRepositoryInterface::class));
            }

            if (in_array(ApplicationRepository\WhoRepositoryTrait::class, $traitsUsed)) {
                $service->setWhoRepository($container->get(ApplicationRepository\WhoRepositoryInterface::class));
            }

            if (in_array(StatsRepository\StatsRepositoryTrait::class, $traitsUsed)) {
                $service->setStatsRepository($container->get(StatsRepository\StatsRepositoryInterface::class));
            }
        }

        //  If required load any additional services into the service
        if (array_key_exists($requestedName, $this->additionalServices) && is_array($this->additionalServices[$requestedName])) {
            foreach ($this->additionalServices[$requestedName] as $setterMethod => $additionalService) {
                if (!method_exists($service, $setterMethod)) {
                    throw new Exception(sprintf('The setter method %s does not exist on the requested service %s', $setterMethod, $requestedName));
                }

                $service->$setterMethod($container->get($additionalService));
            }
        }

        return $service;
    }
}

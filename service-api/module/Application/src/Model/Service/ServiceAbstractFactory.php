<?php

namespace Application\Model\Service;

use Application\Model\DataAccess\Mongo\CollectionFactory;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Auth\Model\Service\UserManagementService;
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
        Pdfs\Service::class => [
            'setPdfConfig'         => 'config',
            'setDynamoQueueClient' => 'DynamoQueueClient',
            'setS3Client'          => 'S3Client',
        ],
        Seed\Service::class => [
            'setApplicationsService' => ApplicationsService::class,
        ],
        Users\Service::class => [
            'setApiUserCollection'     => CollectionFactory::class . '-api-user',
            'setApplicationsService'   => ApplicationsService::class,
            'setUserManagementService' => UserManagementService::class,
        ],
        WhoAreYou\Service::class => [
            'setApiStatsWhoCollection' => CollectionFactory::class . '-api-stats-who',
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

        $lpaCollection = $container->get(CollectionFactory::class . '-api-lpa');

        $service = new $requestedName($lpaCollection);

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

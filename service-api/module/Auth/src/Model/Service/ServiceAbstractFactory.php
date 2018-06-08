<?php

namespace Auth\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\AuthLogCollection;
use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use Application\Model\DataAccess\Mongo\CollectionFactory;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Exception;

class ServiceAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Any additional services to be injected into the requested service using the setter method specified
     *
     * @var array
     */
    private $additionalServices = [
        AccountCleanupService::class => [
            'setUserManagementService' => UserManagementService::class,
            'setSnsClient'             => 'SnsClient',
            'setGuzzleClient'          => 'GuzzleClient',
            'setConfig'                => 'config',
            'setApiLpaCollection'      => CollectionFactory::class . '-api-lpa',
            'setApiUserCollection'     => CollectionFactory::class . '-api-user',
        ],
        PasswordChangeService::class => [
            'setAuthenticationService' => AuthenticationService::class,
        ],
        UserManagementService::class => [
            'setAuthLogCollection' => AuthLogCollection::class,
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
        return class_exists($requestedName)
            && is_subclass_of($requestedName, AbstractService::class);
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return AccountCleanupService|PasswordChangeService
     * @throws Exception
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

        //  Create the service with the common mongo user collection
        $authUserCollection = $container->get(AuthUserCollection::class);

        $service = new $requestedName($authUserCollection);

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

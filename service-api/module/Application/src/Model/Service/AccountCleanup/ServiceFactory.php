<?php

namespace Application\Model\Service\AccountCleanup;

use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use Application\Model\DataAccess\Mongo\CollectionFactory;
use Auth\Model\Service\UserManagementService;
use Aws\Sns\SnsClient;
use GuzzleHttp\Client as GuzzleClient;
use Interop\Container\ContainerInterface;
use MongoDB\Collection;
use Zend\ServiceManager\Factory\FactoryInterface;

class ServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Service
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var UserManagementService $userManagementService */
        $userManagementService = $container->get(UserManagementService::class);
        /** @var SnsClient $snsClient */
        $snsClient = $container->get('SnsClient');
        /** @var GuzzleClient $guzzleClient */
        $guzzleClient = $container->get('GuzzleClient');
        /** @var array $config */
        $config = $container->get('config');
        /** @var Collection $apiLpaCollection */
        $apiLpaCollection = $container->get(CollectionFactory::class . '-api-lpa');
        /** @var Collection $apiUserCollection */
        $apiUserCollection = $container->get(CollectionFactory::class . '-api-user');
        /** @var AuthUserCollection $authUserCollection */
        $authUserCollection = $container->get(AuthUserCollection::class);

        return new Service($userManagementService, $snsClient, $guzzleClient, $config, $apiLpaCollection, $apiUserCollection, $authUserCollection);
    }
}

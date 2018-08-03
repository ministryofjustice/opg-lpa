<?php

namespace Application\Model\Service\Stats;

use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollection;
use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use Interop\Container\ContainerInterface;
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
        /** @var ApiStatsLpasCollection $apiStatsLpasCollection */
        $apiStatsLpasCollection = $container->get(ApiStatsLpasCollection::class);
        /** @var AuthUserCollection $authUserCollection */
        $authUserCollection = $container->get(AuthUserCollection::class);

        return new Service($apiStatsLpasCollection, $authUserCollection);
    }
}

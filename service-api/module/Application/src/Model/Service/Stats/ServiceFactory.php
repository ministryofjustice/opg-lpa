<?php

namespace Application\Model\Service\Stats;

use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use Application\Model\DataAccess\Mongo\CollectionFactory;
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
        /** @var Collection $lpaCollection */
        $collection = $container->get(CollectionFactory::class . '-api-stats-lpas');
        /** @var AuthUserCollection $authUserCollection */
        $authUserCollection = $container->get(AuthUserCollection::class);

        return new Service($collection, $authUserCollection);
    }
}

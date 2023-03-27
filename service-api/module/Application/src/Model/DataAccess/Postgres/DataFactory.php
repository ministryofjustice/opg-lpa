<?php

namespace Application\Model\DataAccess\Postgres;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Application\Model\DataAccess\Postgres\AbstractBase;
use Application\Model\DataAccess\Postgres\DbWrapper;

/**
 * Used to instantiate any class that extents Application\Model\DataAccess\Postgres\AbstractBase
 *
 * Injects a configured DbWrapper
 */
class DataFactory implements FactoryInterface
{
    /**
     * @return AbstractBase
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Ensure that the requested class exists
        if (!class_exists($requestedName)) {
            throw new \RuntimeException("Class {$requestedName} does not exist");
        };

        if (is_subclass_of($requestedName, AbstractBase::class)) {
            $adapter = $container->get('ZendDbAdapter');
            $config = $container->get('Config');
            $dbWrapper = new DbWrapper($adapter);
            return new $requestedName($dbWrapper, $config);
        }

        throw new \RuntimeException("Class {$requestedName} cannot be created with this factory");
    }
}

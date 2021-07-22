<?php
namespace Application\Model\DataAccess\Postgres;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Used to instantiate any class that extents Application\Model\DataAccess\Postgres\AbstractBase
 *
 * Injects a configured Zend DB Adapter.
 *
 * Class DataFactory
 * @package Application\Model\DataAccess\Postgres
 */
class DataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Ensure that the request class exists
        if (!class_exists($requestedName)) {
            throw new \RuntimeException("Class {$requestedName} does not exist");
        };

        // Special treatment for ApplicationData as it is in the process of being refactored
        if ($requestedName === ApplicationData::class) {
            $dbWrapper = new AbstractBase(
                $container->get('ZendDbAdapter'),
                $container->get('Config')
            );

            return new ApplicationData($dbWrapper);
        }

        // Create subclasses of AbstractBase
        if (!is_subclass_of($requestedName, AbstractBase::class)) {
            throw new \RuntimeException("Class {$requestedName} cannot be created with this factory");
        }

        return new $requestedName(
            $container->get('ZendDbAdapter'),
            $container->get('Config')
        );
    }
}

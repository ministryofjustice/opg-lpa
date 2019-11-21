<?php
namespace Application\Model\DataAccess\Postgres;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

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
        if (!(class_exists($requestedName) && is_subclass_of($requestedName, AbstractBase::class))) {
            throw new \RuntimeException("Class {$requestedName} cannot be created with this factory");
        };

        //---

        $test =  new $requestedName(
            $container->get('ZendDbAdapter'),
            $container->get('Config')
        );
        return $test;
    }
}

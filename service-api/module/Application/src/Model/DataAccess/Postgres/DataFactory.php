<?php
namespace Application\Model\DataAccess\Postgres;

use PDO;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\Db\Adapter\Adapter as ZendDbAdapter;

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

        $config = $container->get('config');

        if (!isset($config['db']['postgres']['default'])) {
            throw new \RuntimeException("Missing Postgres configuration");
        }

        //---

        $dbconf = $config['db']['postgres']['default'];

        $dsn = "{$dbconf['adapter']}:host={$dbconf['host']};port={$dbconf['port']};dbname={$dbconf['dbname']}";

        $adapter = new ZendDbAdapter([
            'dsn' => $dsn,
            'driver' => 'pdo',
            'username' => $dbconf['username'],
            'password' => $dbconf['password'],
            'driver_options' => [
                // RDS doesn't play well with persistent connections
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ],
        ]);

        return new $requestedName($adapter);
    }
}

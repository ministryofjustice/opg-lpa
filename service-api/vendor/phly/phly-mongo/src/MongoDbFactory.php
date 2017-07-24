<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongo;

use MongoDB;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MongoDbFactory implements FactoryInterface
{
    protected $dbName;
    protected $connectionService;

    public function __construct($dbName, $connectionService)
    {
        $this->dbName            = $dbName;
        $this->connectionService = $connectionService;
    }

    /**
     * @param ServiceLocatorInterface $services
     * @return MongoDB\Database
     */
    public function createService(ServiceLocatorInterface $services)
    {
        /** @var MongoDB\Driver\Manager $connection */
        $connection = $services->get($this->connectionService);
        return new MongoDB\Database($connection, $this->dbName);
    }
}

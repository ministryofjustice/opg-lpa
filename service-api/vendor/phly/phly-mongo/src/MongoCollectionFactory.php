<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace PhlyMongo;

use MongoDB\Database;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MongoCollectionFactory implements FactoryInterface
{
    protected $collectionName;
    protected $dbService;

    public function __construct($collectionName, $dbService)
    {
        $this->collectionName    = $collectionName;
        $this->dbService         = $dbService;
    }

    /**
     * @param ServiceLocatorInterface $services
     * @return \MongoDB\Collection
     */
    public function createService(ServiceLocatorInterface $services)
    {
        /** @var Database $db */
        $db = $services->get($this->dbService);
        $collection = $db->selectCollection($this->collectionName, ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]);
        return $collection;
    }
}

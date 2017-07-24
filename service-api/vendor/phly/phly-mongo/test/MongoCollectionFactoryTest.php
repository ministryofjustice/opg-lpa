<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongoTest;

use MongoDB\Collection;
use PhlyMongo\MongoCollectionFactory;
use PhlyMongo\MongoConnectionFactory;
use PhlyMongo\MongoDbFactory;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;

class MongoCollectionFactoryTest extends TestCase
{
    public function setUp()
    {
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('The mongodb extension is required to run the unit tests');
        }
        $this->services = new ServiceManager();
        $this->services->setFactory('PhlyMongoTest\Mongo', new MongoConnectionFactory());
        $this->services->setFactory('PhlyMongoTest\MongoDB', new MongoDbFactory('test', 'PhlyMongoTest\Mongo'));
    }

    public function testFactoryCreatesAMongoCollectionInstance()
    {
        $factory    = new MongoCollectionFactory('test', 'PhlyMongoTest\MongoDB');
        $collection = $factory->createService($this->services);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals('test', $collection->getCollectionName());
    }
}

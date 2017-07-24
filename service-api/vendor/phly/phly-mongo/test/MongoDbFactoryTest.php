<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongoTest;

use MongoDB\Database;
use PhlyMongo\MongoConnectionFactory;
use PhlyMongo\MongoDbFactory;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;

class MongoDbFactoryTest extends TestCase
{
    public function setUp()
    {
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('The mongodb extension is required to run the unit tests');
        }
        $this->services = new ServiceManager();
        $this->services->setFactory('PhlyMongoTest\Mongo', new MongoConnectionFactory());
    }

    public function testFactoryCreatesAMongoDBInstance()
    {
        $factory = new MongoDbFactory('test', 'PhlyMongoTest\Mongo');
        $db      = $factory->createService($this->services);
        $this->assertInstanceOf(Database::class, $db);
        $this->assertEquals('test', (string) $db);
    }
}

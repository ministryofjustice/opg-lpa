<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongoTest;

use MongoDB;
use PHPUnit_Framework_TestCase as TestCase;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @var MongoDB\Driver\Manager
     */
    protected $manager;

    /**
     * @var MongoDB\Database
     */
    protected $database;

    /**
     * @var MongoDB\Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $authors;

    public function setUp()
    {
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('MongoDB extension is required to run tests');
        }

        $services   = Bootstrap::getServiceManager();
        $config     = $services->get('ApplicationConfig');
        $config     = $config['mongo'];
        $manager    = new MongoDB\Driver\Manager($config['server'], $config['server_options']);
        $database   = new MongoDB\Database($manager, $config['db']);
        $collection = new MongoDB\Collection($manager, $config['db'], $config['collection']);

        $this->manager    = $manager;
        $this->database   = $database;
        $this->collection = $collection;

        $this->seedCollection();
    }

    protected function seedCollection()
    {
        $this->collection->drop();
        $this->authors = $authors = [
            'Matthew',
            'Mark',
            'Luke',
            'John',
        ];
        $this->items = [];
        for ($i = 0; $i < 100; $i += 1) {
            $authorIndex = array_rand($authors);
            $title       = uniqid();
            $data = [
                'title'   => $title,
                'author'  => $authors[$authorIndex],
                'content' => str_repeat($title, $i + 1),
            ];
            $result = $this->collection->insertOne($data);
            $data['_id'] = $result->getInsertedId();
            $this->items[] = $data;
        }
    }
}

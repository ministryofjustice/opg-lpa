<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongoTest;

use MongoDB\Driver\Cursor;
use PhlyMongo\PaginatorAdapter;

class PaginatorAdapterTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->cursor  = $this->collection->find();
        $this->count  = $this->collection->count();
        $this->adapter = new PaginatorAdapter($this->manager, $this->collection, []);
    }

    public function testCountReturnsTotalNumberOfItems()
    {
        $this->assertEquals($this->count, $this->adapter->count());
        $this->assertGreaterThan(1, $this->adapter->count());
    }

    public function testGetItemsReturnsCursor()
    {
        $test    = $this->adapter->getItems(5, 5);
        $this->assertInstanceOf(Cursor::class, $test);
    }

    public function testIteratingReturnedItemsReturnsProperOffsetAndCount()
    {
        $items    = $this->adapter->getItems(5, 5);
        $expected = array_slice($this->items, 5, 5);
        $test     = [];
        foreach ($items as $item) {
            $test[] = $item;
        }
        $this->assertEquals($expected, $test);
    }
}

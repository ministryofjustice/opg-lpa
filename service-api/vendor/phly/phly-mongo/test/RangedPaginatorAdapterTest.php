<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongoTest;

use MongoDB\Driver\Cursor;
use PhlyMongo\RangedPaginatorAdapter;

class RangedPaginatorAdapterTest extends AbstractTestCase
{
    public function testCountReturnsTotalNumberOfItems()
    {
        $adapter = new RangedPaginatorAdapter('', $this->manager, $this->collection);

        $this->assertEquals(count($this->items), $adapter->count());
        $this->assertGreaterThan(1, $adapter->count());
    }

    public function testGetItemsReturnsCursor()
    {
        $adapter = new RangedPaginatorAdapter(5, $this->manager, $this->collection);
        $test    = $adapter->getItems(5, 5);
        $this->assertInstanceOf(Cursor::class, $test);
    }

    public function testIteratingReturnedItemsReturnsProperOffsetAndCount()
    {
        $expected = array_slice($this->items, 5, 5);
        $adapter  = new RangedPaginatorAdapter($expected[0]['_id'], $this->manager, $this->collection);
        $items    = $adapter->getItems(5, 5);
        $test     = [];
        foreach ($items as $item) {
            $test[] = $item;
        }
        $this->assertEquals($expected, $test);
    }
}

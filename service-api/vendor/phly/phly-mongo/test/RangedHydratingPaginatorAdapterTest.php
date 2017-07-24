<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongoTest;

use PhlyMongo\HydratingMongoCursor;
use PhlyMongo\HydratingPaginatorAdapter;
use Zend\Stdlib\Hydrator\ObjectProperty;

class RangedHydratingPaginatorAdapterTest extends AbstractTestCase
{
    /**
     * @var TestAsset\Foo
     */
    protected $prototype;

    /**
     * @var ObjectProperty
     */
    protected $hydrator;

    /**
     * @var HydratingMongoCursor
     */
    protected $cursor;

    public function setUp()
    {
        parent::setUp();
        $this->prototype  = new TestAsset\Foo;
        $this->hydrator   = new ObjectProperty;
        $this->cursor     = new HydratingMongoCursor($this->hydrator, $this->prototype, $this->manager, $this->collection);
    }

    public function testCountReturnsTotalNumberOfItems()
    {
        $adapter    = new HydratingPaginatorAdapter($this->cursor, '');
        $this->assertEquals(count($this->items), $adapter->count());
        $this->assertGreaterThan(1, $adapter->count());
    }

    public function testGetItemsReturnsCursor()
    {
        $adapter    = new HydratingPaginatorAdapter($this->cursor, '');
        $test = $adapter->getItems(5, 5);
        $this->assertSame($this->cursor, $test);
    }

    public function testIteratingReturnedItemsReturnsProperOffsetAndCountOfObjectsOfCorrectPrototype()
    {
        $expected = array_slice($this->items, 5, 5);
        $adapter  = new HydratingPaginatorAdapter($this->cursor, $expected[0]['_id']);
        $items    = $adapter->getItems(5, 5);
        foreach ($expected as $index => $item) {
            $expected[(string) $item['_id']] = $item;
            unset($expected[$index]);
        }
        foreach ($items as $key => $item) {
            $this->assertInstanceOf('PhlyMongoTest\TestAsset\Foo', $item);
            $this->assertEquals($expected[$key], (array) $item);
        }
    }
}

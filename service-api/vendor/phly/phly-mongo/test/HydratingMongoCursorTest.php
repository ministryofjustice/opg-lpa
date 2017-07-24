<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongoTest;

use MongoDB\BSON\ObjectID;
use PhlyMongo\HydratingMongoCursor;
use Zend\Stdlib\Hydrator\ObjectProperty;

class HydratingMongoCursorTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->hydrator   = new ObjectProperty();
        $this->prototype  = new TestAsset\Foo;
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
        for ($i = 0; $i < 100; $i += 1) {
            $authorIndex = array_rand($authors);
            $title       = uniqid();
            $data = [
                'title'   => $title,
                'author'  => $authors[$authorIndex],
                'content' => str_repeat($title, $i + 1),
            ];
            $this->collection->insertOne($data);
        }
    }

    public function testConstructorRaisesExceptionOnInvalidPrototype()
    {
        $this->setExpectedException('InvalidArgumentException');
        $cursor = new HydratingMongoCursor($this->hydrator, [], $this->manager, $this->collection);
    }

    public function tetHydratorIsAccessibleAfterInstantiation()
    {
        $cursor = new HydratingMongoCursor($this->hydrator, $this->prototype, $this->manager, $this->collection);
        $this->assertSame($this->hydrator, $cursor->getHydrator());
    }

    public function tetPrototypeIsAccessibleAfterInstantiation()
    {
        $cursor = new HydratingMongoCursor($this->hydrator, $this->prototype, $this->manager, $this->collection);
        $this->assertSame($this->prototype, $cursor->getPrototype());
    }

    public function testCursorIsCountable()
    {
        $cursor     = new HydratingMongoCursor($this->hydrator, $this->prototype, $this->manager, $this->collection);

        $rootCount = $this->collection->count();
        $testCount = count($cursor);
        $this->assertEquals($rootCount, $testCount, "Expected $rootCount did not match test $testCount");
        $this->assertGreaterThan(0, $testCount);
    }

    public function testIterationReturnsClonesOfPrototype()
    {
        $cursor = new HydratingMongoCursor($this->hydrator, $this->prototype, $this->manager, $this->collection);
        foreach ($cursor as $item) {
            $this->assertInstanceOf('PhlyMongoTest\TestAsset\Foo', $item);
            $this->assertInstanceOf(ObjectID::class, $item->_id);
            $this->assertFalse(empty($item->title));
            $this->assertContains($item->author, $this->authors);
            $this->assertContains($item->title, $item->content);
        }
    }
}

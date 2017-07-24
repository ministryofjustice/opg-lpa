<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongo;

use Countable;
use InvalidArgumentException;
use Iterator;
use MongoDB\Collection;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use Zend\Hydrator\HydratorInterface;
use Zend\Hydrator\Iterator\HydratingIteratorInterface;

class HydratingMongoCursor implements Countable, Iterator, HydratingIteratorInterface
{
    /**
     * @var HydratorInterface
     */
    protected $hydrator;
    protected $prototype;
    /**
     * @var Manager
     */
    protected $manager;
    /**
     * @var Collection
     */
    protected $collection;
    /**
     * @var
     */
    protected $filter;
    /**
     * @var array
     */
    protected $queryOptions;

    private $iterator;

    /**
     * HydratingMongoCursor constructor.
     * @param HydratorInterface $hydrator
     * @param $prototype
     * @param Manager $manager
     * @param Collection $collection
     * @param $filter
     * @param array $queryOptions
     */
    public function __construct(HydratorInterface $hydrator, $prototype, Manager $manager, Collection $collection, $filter = [], array $queryOptions = [])
    {
        $this->setHydrator($hydrator);
        $this->setPrototype($prototype);
        $this->manager = $manager;
        $this->collection = $collection;
        $this->filter = $filter;
        $this->queryOptions = $queryOptions;
    }

    private function getCursor()
    {
        $query = new Query($this->filter, $this->queryOptions);
        $cursor = $this->manager->executeQuery($this->collection->getNamespace(), $query);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
        return $cursor;
    }

    public function getPrototype()
    {
        return $this->prototype;
    }

    /**
     * This sets the prototype to hydrate.
     *
     * This prototype can be the name of the class or the object itself;
     * iteration will clone the object.
     *
     * @param string|object $prototype
     */
    public function setPrototype($prototype)
    {
        if (!is_object($prototype)) {
            throw new InvalidArgumentException(sprintf(
                'Prototype must be an object; received "%s"',
                gettype($prototype)
            ));
        }
        $this->prototype = $prototype;
    }

    public function getHydrator()
    {
        return $this->hydrator;
    }

    /**
     * Sets the hydrator to use during iteration.
     *
     * @param HydratorInterface $hydrator
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    public function count()
    {
        return $this->collection->count($this->filter);
    }

    public function current()
    {
        $result = $this->getIterator()->current();
        if (!is_array($result)) {
            return $result;
        }

        return $this->hydrator->hydrate($result, clone $this->prototype);
    }

    public function key()
    {
        $current = $this->getIterator()->current();
        return (string) $current['_id'];
    }

    public function next()
    {
        $this->getIterator()->next();
    }

    public function rewind()
    {
        //Re run the query to rewind
        $cursor = $this->getCursor();
        $this->iterator = new \IteratorIterator($cursor);
        $this->iterator->rewind();
    }

    public function valid()
    {
        return $this->getIterator()->valid();
    }

    public function skip($num)
    {
        $this->queryOptions['skip'] = $num;
        $this->iterator = null;
    }

    public function limit($num)
    {
        $this->queryOptions['limit'] = $num;
        $this->iterator = null;
    }

    public function addOption($key, $value)
    {
        $this->queryOptions[$key] = $value;
        $this->iterator = null;
    }

    private function getIterator()
    {
        if ($this->iterator === null) {
            $this->rewind();
        }
        return $this->iterator;
    }
}

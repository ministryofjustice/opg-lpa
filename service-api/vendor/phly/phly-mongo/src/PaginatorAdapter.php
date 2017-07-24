<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongo;

use MongoDB\Collection;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use Zend\Paginator\Adapter\AdapterInterface;

class PaginatorAdapter implements AdapterInterface
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array|object
     */
    protected $filter;

    /**
     * @var array
     */
    protected $queryOptions;

    /**
     * PaginatorAdapter constructor.
     * @param Manager $manager
     * @param Collection $collection
     * @param array|object $filter The search filter.
     */
    public function __construct(Manager $manager, Collection $collection, $filter = [], array $queryOptions = [])
    {
        $this->manager = $manager;
        $this->collection = $collection;
        $this->filter = $filter;
        $this->queryOptions = $queryOptions;
    }

    public function count()
    {
        return $this->collection->count($this->filter);
    }

    public function getItems($offset, $itemCountPerPage)
    {
        $queryOptions = array_merge($this->queryOptions, ['skip' => $offset, 'limit' => $itemCountPerPage]);
        return $this->getCursor($queryOptions);
    }

    /**
     * @param $queryOptions
     * @return \MongoDB\Driver\Cursor
     */
    protected function getCursor($queryOptions)
    {
        $query = new Query($this->filter, $queryOptions);
        $cursor = $this->manager->executeQuery($this->collection->getNamespace(), $query);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
        return $cursor;
    }
}

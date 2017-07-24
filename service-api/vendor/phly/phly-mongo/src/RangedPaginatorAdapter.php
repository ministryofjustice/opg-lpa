<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongo;

use MongoCursor;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use MongoDB\Driver\Manager;
use Zend\Paginator\Adapter\AdapterInterface;

class RangedPaginatorAdapter extends PaginatorAdapter
{
    /**
     * @var mixed|ObjectID
     */
    protected $currentId;

    public function __construct($currentId, Manager $manager, Collection $collection, $filter = [], array $queryOptions = [])
    {
        parent::__construct($manager, $collection, $filter, $queryOptions);

        $this->currentId = $currentId;
    }

    public function getItems($offset, $itemCountPerPage)
    {
        //offset is never used in range based
        //kept here to satisfy interface
        $queryOptions = array_merge($this->queryOptions, ['min' => ['_id' => $this->currentId], 'limit' => $itemCountPerPage]);
        return $this->getCursor($queryOptions);
    }
}

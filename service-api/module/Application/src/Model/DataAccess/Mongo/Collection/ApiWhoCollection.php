<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use MongoDB\Collection as MongoCollection;

class ApiWhoCollection
{
    /**
     * @var MongoCollection
     */
    protected $collection;

    /**
     * @param MongoCollection $collection
     */
    public function __construct(MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Proxy requests to collection
     * TODO - To be removed....
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (is_callable([$this->collection, $name ])) {
            return call_user_func_array([$this->collection, $name], $arguments);
        }

        throw new \InvalidArgumentException("Unknown method $name called");
    }
}

<?php

namespace ApplicationTest\Model\Rest\Stats;

use Iterator;
use Opg\Lpa\DataModel\Lpa\Lpa;

class DummyStatMongoCursor implements Iterator
{
    private $stats;
    private $statIndex;

    /**
     * DummyMongoCursor constructor.
     * @param [] $lpas
     */
    function __construct($stats)
    {
        $this->stats = $stats;
        $this->statIndex = 0;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->stats[$this->statIndex];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->statIndex++;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->statIndex;
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->statIndex < count($this->stats);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->statIndex = 0;
    }

    public function count()
    {
        return count($this->stats);
    }

    public function sort()
    {
        return $this;
    }

    public function skip()
    {
        return $this;
    }

    public function limit()
    {
        return $this;
    }
}
<?php

namespace ApplicationTest\Model\Service\Applications;

use Application\Model\DataAccess\Mongo\DateCallback;
use Iterator;
use Opg\Lpa\DataModel\Lpa\Lpa;

class DummyLpaMongoCursor implements Iterator
{
    private $lpas;
    private $lpaIndex;

    /**
     * DummyMongoCursor constructor.
     * @param Lpa[] $lpas
     */
    public function __construct($lpas)
    {
        $this->lpas = [];
        foreach ($lpas as $lpa) {
            array_push($this->lpas, $lpa->toArray(new DateCallback()));
        }
        $this->lpaIndex = 0;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->lpas[$this->lpaIndex];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->lpaIndex++;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->lpaIndex;
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
        return $this->lpaIndex < count($this->lpas);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->lpaIndex = 0;
    }

    public function toArray()
    {
        return $this->lpas;
    }
}
<?php

namespace Application\Model\Rest\Stats;

use Application\Model\Rest\EntityInterface;

class Entity implements EntityInterface
{
    private $stats;

    public function __construct(array $stats)
    {
        $this->stats = $stats;
    }

    public function userId()
    {
        return null;
    }

    public function lpaId()
    {
        return null;
    }

    public function resourceId()
    {
        return null;
    }

    public function toArray()
    {
        return $this->stats;
    }
}

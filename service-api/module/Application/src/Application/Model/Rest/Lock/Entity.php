<?php

namespace Application\Model\Rest\Lock;

use Application\Model\Rest\EntityInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $lpa;
    protected $locked;

    public function __construct($locked, Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->locked = $locked;
    }

    public function userId()
    {
        return $this->lpa->user;
    }

    public function lpaId()
    {
        return $this->lpa->id;
    }

    public function resourceId()
    {
        return null;
    }

    public function toArray()
    {
        if (is_bool($this->locked)) {
            return [
                'locked' => $this->locked,
                'lockedAt' => ($this->lpa->lockedAt instanceof \DateTime ? $this->lpa->lockedAt->format('Y-m-d\TH:i:s.uO') : null),
            ];
        }

        return [];
    }
}

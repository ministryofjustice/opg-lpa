<?php

namespace Application\Model\Service\Lock;

use Application\Model\Service\EntityInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $lpa;

    public function __construct(Lpa $lpa)
    {
        $this->lpa = $lpa;
    }

    public function toArray()
    {
        if (is_bool($this->lpa->locked)) {
            return [
                'locked'   => $this->lpa->locked,
                'lockedAt' => ($this->lpa->lockedAt instanceof \DateTime ? $this->lpa->lockedAt->format('Y-m-d\TH:i:s.uO') : null),
            ];
        }

        return [];
    }
}

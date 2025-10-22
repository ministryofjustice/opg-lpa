<?php

namespace Application\Model\Service\Lock;

use Application\Model\Service\EntityInterface;
use MakeShared\DataModel\Lpa\Lpa;
use DateTime;

class Entity implements EntityInterface
{
    protected $lpa;

    public function __construct(Lpa $lpa)
    {
        $this->lpa = $lpa;
    }

    /**
     * @return (bool|null|string)[]
     *
     * @psalm-return array{locked?: bool, lockedAt?: null|string}
     */
    public function toArray()
    {
        if (is_bool($this->lpa->locked)) {
            return [
                'locked'   => $this->lpa->locked,
                'lockedAt' => ($this->lpa->lockedAt instanceof DateTime ? $this->lpa->lockedAt->format('Y-m-d\TH:i:s.uO') : null),
            ];
        }

        return [];
    }
}

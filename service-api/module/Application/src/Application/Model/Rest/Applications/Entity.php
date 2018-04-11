<?php

namespace Application\Model\Rest\Applications;

use Application\Model\Rest\EntityInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $lpa;

    public function __construct(Lpa $lpa)
    {
        $this->lpa = $lpa;
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

    public function getLpa()
    {
        return $this->lpa;
    }

    public function toArray()
    {
        return $this->lpa->toArray();
    }

    public function equals($comparisonEntity)
    {
        return $this->lpa !== null && $this->lpa->equals($comparisonEntity->lpa);
    }

    public function equalsIgnoreMetadata($comparisonEntity)
    {
        return $this->lpa !== null && $this->lpa->equalsIgnoreMetadata($comparisonEntity->lpa);
    }
}

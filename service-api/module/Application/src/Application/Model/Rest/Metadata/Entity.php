<?php

namespace Application\Model\Rest\Metadata;

use Application\Model\Rest\EntityInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $lpa;
    protected $metadata;

    public function __construct(array $metadata, Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->metadata = $metadata;
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
        return $this->metadata;
    }
}

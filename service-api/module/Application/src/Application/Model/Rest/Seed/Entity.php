<?php

namespace Application\Model\Rest\Seed;

use Application\Model\Rest\EntityInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $lpa;
    protected $seed;

    public function __construct(Lpa $seed = null, Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->seed = $seed;
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
        if (is_null($this->seed)) {
            return [];
        }

        $result = [
            'seed' => $this->seed->id,
        ];

        if ($this->seed->document == null) {
            return $result;
        }

        $document = $this->seed->document->toArray();

        // Extract the following fields to return from the seed document.
        $result = $result + array_intersect_key($document, array_flip([
            'donor',
            'correspondent',
            'certificateProvider',
            'primaryAttorneys',
            'replacementAttorneys',
            'peopleToNotify'
        ]));

        // Strip out null values and empty arrays...
        $result = array_filter($result, function ($v) {
            return !empty($v);
        });

        return $result;
    }
}

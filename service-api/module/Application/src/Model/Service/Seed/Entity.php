<?php

namespace Application\Model\Service\Seed;

use Application\Model\Service\EntityInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $seed;

    public function __construct(Lpa $seed)
    {
        $this->seed = $seed;
    }

    public function toArray()
    {
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

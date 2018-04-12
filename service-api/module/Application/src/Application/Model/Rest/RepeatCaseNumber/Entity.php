<?php

namespace Application\Model\Rest\RepeatCaseNumber;

use Application\Model\Rest\EntityInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $lpa;
    protected $repeatCaseNumber;

    public function __construct($repeatCaseNumber, Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->repeatCaseNumber = $repeatCaseNumber;
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
        if (!is_null($this->repeatCaseNumber)) {
            return [
                'repeatCaseNumber' => $this->repeatCaseNumber
            ];
        }

        return [];
    }
}

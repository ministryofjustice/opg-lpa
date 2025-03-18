<?php

namespace Application\Model\Service\RepeatCaseNumber;

use Application\Model\Service\EntityInterface;

class Entity implements EntityInterface
{
    protected $repeatCaseNumber;

    public function __construct($repeatCaseNumber)
    {
        $this->repeatCaseNumber = $repeatCaseNumber;
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

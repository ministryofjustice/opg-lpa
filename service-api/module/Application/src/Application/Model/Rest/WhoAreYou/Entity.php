<?php

namespace Application\Model\Rest\WhoAreYou;

use Application\Model\Rest\EntityInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $lpa;
    protected $answered;

    public function __construct($answered, Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->answered = $answered;
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
        if (is_bool($this->answered)) {
            return [
                'whoAreYouAnswered' => $this->answered,
            ];
        }

        return [];
    }
}

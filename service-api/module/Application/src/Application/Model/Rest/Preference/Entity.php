<?php

namespace Application\Model\Rest\Preference;

use Application\Model\Rest\EntityInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $lpa;
    protected $preference;

    public function __construct($preference, Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->preference = $preference;
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
        if (is_string($this->preference) || $this->preference === false) {
            return [ 'preference' => $this->preference ];
        }

        return [];
    }
}

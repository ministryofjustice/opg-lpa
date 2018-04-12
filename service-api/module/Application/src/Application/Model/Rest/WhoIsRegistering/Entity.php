<?php

namespace Application\Model\Rest\WhoIsRegistering;

use Application\Model\Rest\EntityInterface;
use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface
{
    protected $lpa;
    protected $who;

    public function __construct($who = null, Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->who = $who;
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
        $who = [];

        if (is_string($this->who)) {
            $who = [
                'who' => $this->who,
            ];
        } elseif (is_array($this->who)) {
            $who = [
                'who' => array_map(function ($v) {
                    if ($v instanceof LpaAccessorInterface) {
                        return $v->toArray();
                    } else {
                        return $v;
                    }
                }, $this->who)
            ];
        }

        return $who;
    }
}

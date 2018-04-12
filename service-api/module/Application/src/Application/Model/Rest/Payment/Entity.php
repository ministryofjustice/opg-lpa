<?php

namespace Application\Model\Rest\Payment;

use Application\Model\Rest\EntityInterface;
use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class Entity implements EntityInterface
{
    protected $lpa;
    protected $payment;

    public function __construct(Payment $payment = null, Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->payment = $payment;
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
        if ($this->payment instanceof LpaAccessorInterface) {
            return $this->payment->toArray();
        }

        return [];
    }
}

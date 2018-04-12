<?php

namespace Application\Model\Rest\CertificateProvider;

use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\Model\Rest\EntityInterface;

class Entity implements EntityInterface
{
    protected $lpa;
    protected $provider;

    public function __construct(CertificateProvider $provider = null, Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->provider = $provider;
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
        if ($this->provider instanceof LpaAccessorInterface) {
            return $this->provider->toArray();
        }

        return [];
    }
}
